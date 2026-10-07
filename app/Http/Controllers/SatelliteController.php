<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\FieldHealth;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\EnsoOutlook;
use App\Support\FieldContext;
use App\Support\SoilConditions;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Satellite Analysis (2026-10-07): a field drawn on the map, read from space.
 *
 * The farmer answers a short wizard (the place, the crop and its age, how it
 * was planted, the soil, the water, what worries them), draws the field as a
 * polygon, and one run:
 *
 *   1. asks the field health service (services/field-health, Google Earth
 *      Engine) for the latest clear Sentinel-2 look of the last 5 to 10 days
 *      (NDVI, clouds masked, the field cut in nine zones) and the latest
 *      Sentinel-1 radar pass (VV and VH, which see through typhoon cloud),
 *      with tile URLs for both heatmaps and 90 days of history;
 *   2. gathers the place's context: the last 30 and next 16 days of weather,
 *      the same weeks in the last five years, ENSO, the modelled soil;
 *   3. has Anee read the web for soil studies and hazards of the area, then
 *      write the analysis: health, stand and spacing, disease risk, threats,
 *      soil, and what to do.
 *
 * Flat priced (AiPrices 'satellite'), charged only when the report lands.
 * Rows live on the analyses shelf (as_plant_analyses, kind 'satellite').
 */
class SatelliteController extends Controller
{
    public const KIND = 'satellite';

    /** What the farmer is worried about. Keys are stable. */
    public const CONCERNS = [
        'uneven' => 'Uneven growth across the field',
        'yellowing' => 'Yellowing leaves',
        'stunting' => 'Stunted plants',
        'gaps' => 'Missing hills or gaps',
        'lodging' => 'Plants falling over (lodging)',
        'pests' => 'Insect damage',
        'disease' => 'Leaf spots or disease',
        'weeds' => 'Heavy weeds',
        'drought' => 'Dry, cracked soil or wilting',
        'flood' => 'Standing water or flooding',
        'salt' => 'Salt or white crust on the soil',
        'none' => 'Nothing yet, a routine check',
    ];

    public const WATER = [
        'irrigated' => 'Irrigated (NIA, pump or canal)',
        'partial' => 'Partly irrigated',
        'rainfed' => 'Rainfed only',
    ];

    public const METHODS = [
        'transplanted' => 'Transplanted',
        'direct' => 'Direct seeded',
        'planted' => 'Planted by seed in rows or hills',
        'ratoon' => 'Ratoon or regrowth',
        'perennial' => 'Trees or perennials already standing',
    ];

    public function __construct(private AiCreditService $credits, private AiClient $ai, private FieldHealth $field)
    {
    }

    private function guardTier(): void
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'Satellite Analysis comes with {plan}, and with every plan above it.');
        }
    }

    public function page()
    {
        $this->guardTier();

        return view('satellite.index');
    }

    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable();

        return $this->json(true, 'ok', [
            'crops' => collect(CropCatalog::CROPS)->filter(fn ($c) => empty($c['intl']))->map(fn ($c, $key) => [
                'key' => $key, 'label' => CropCatalog::label($key), 'icon' => $c['icon'] ?? '🌱', 'group' => $c['group'] ?? 'Other',
                'maturity' => $c['maturity'] ?? null, 'perennial' => CropCatalog::isPerennial($key),
            ])->values(),
            'concerns' => self::CONCERNS,
            'water' => self::WATER,
            'methods' => self::METHODS,
            'soilConditions' => SoilConditions::OPTIONS,
            'quote' => $canUse ? AiPrices::of(self::KIND) : null,
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'connected' => $this->field->configured(),
            'whyNot' => $canUse ? null : ($settings->isUsable() ? Tier::aneeNeeds($payer) : 'Anee is not switched on yet. Please check back soon.'),
            'mapsKey' => (string) config('services.google_maps.key'),
            'today' => now('Asia/Manila')->toDateString(),
        ]);
    }

    /** Find a place by its words (OpenStreetMap through our server). */
    public function places(Request $request)
    {
        return $this->json(true, 'ok', ['places' => \App\Support\PlaceSearch::find(mb_substr(trim((string) $request->query('q', '')), 0, 120))]);
    }

    public function generate(Request $request)
    {
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'Satellite Analysis'), [], 403);
        }
        if (! $this->field->configured()) {
            return $this->json(false, 'The satellite connection is being set up. Nothing was charged. Please check back soon.', ['notConnected' => true], 503);
        }

        $v = Validator::make($request->all(), [
            'location' => 'required|string|max:160',
            'crop' => 'required|string',
            'variety' => 'nullable|string|max:80',
            'plantedOn' => 'nullable|date|before_or_equal:today',
            'method' => 'required|string|in:' . implode(',', array_keys(self::METHODS)),
            'density' => 'nullable|numeric|min:1|max:10000000',
            'densityUnit' => 'nullable|string|in:seeds_ha,plants_ha,kg_ha,hills_m2',
            'water' => 'required|string|in:' . implode(',', array_keys(self::WATER)),
            'soilConditions' => 'nullable|array|max:7',
            'soilConditions.*' => 'string|in:' . implode(',', array_keys(SoilConditions::OPTIONS)),
            'phValue' => 'nullable|numeric|min:2|max:12',
            'concerns' => 'nullable|array|max:12',
            'concerns.*' => 'string|in:' . implode(',', array_keys(self::CONCERNS)),
            'notes' => 'nullable|string|max:800',
            'polygon' => 'required|array',
            'polygon.type' => 'required|in:Polygon',
            'polygon.coordinates' => 'required|array|min:1',
            'polygon.coordinates.0' => 'required|array|min:4|max:300',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), ['errors' => $v->errors()], 422);
        }
        if (! isset(CropCatalog::CROPS[$request->input('crop')])) {
            return $this->json(false, 'Pick a crop from the list.', [], 422);
        }
        $ring = $this->cleanRing((array) $request->input('polygon.coordinates.0'));
        if (! $ring) {
            return $this->json(false, 'Draw the field with at least three corners inside the Philippines.', [], 422);
        }

        $price = (float) AiPrices::of(self::KIND);
        if ($this->credits->balance($payer->id) < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . ceil($price) . ' credits for this analysis and have '
                . number_format((int) floor($this->credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)
            ->where('status', 'pending')->where('deleteStatus', 1)->where('created_at', '>', now()->subMinutes(15))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }

        $p = [
            'location' => trim((string) $request->input('location')),
            'crop' => (string) $request->input('crop'),
            'variety' => trim((string) $request->input('variety', '')),
            'plantedOn' => $request->input('plantedOn') ?: null,
            'method' => (string) $request->input('method'),
            'density' => $request->input('density') !== null ? (float) $request->input('density') : null,
            'densityUnit' => (string) $request->input('densityUnit', 'seeds_ha'),
            'water' => (string) $request->input('water'),
            'soilConditions' => SoilConditions::normalize($request->input('soilConditions', [])),
            'phValue' => SoilConditions::phValue($request->input('phValue')),
            'concerns' => array_values(array_intersect((array) $request->input('concerns', []), array_keys(self::CONCERNS))),
            'notes' => trim((string) $request->input('notes', '')),
            'polygon' => ['type' => 'Polygon', 'coordinates' => [$ring]],
        ];
        $title = CropCatalog::label($p['crop']) . ' · ' . $p['location'] . ' · ' . now('Asia/Manila')->format('M j, Y');
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr($title, 0, 190),
            'params' => json_encode($p), 'report' => json_encode(new \stdClass), 'credits' => 0,
            'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $id]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $p);
            exit;
        }
        @set_time_limit(900);
        $this->runJob($id, (int) $payer->id, $settings, $p);

        return $this->jobState($id);
    }

    /** The ring as [lng, lat] pairs, closed, inside a box around the Philippines. */
    private function cleanRing(array $ring): ?array
    {
        $out = [];
        foreach ($ring as $pt) {
            if (! is_array($pt) || count($pt) < 2 || ! is_numeric($pt[0]) || ! is_numeric($pt[1])) {
                return null;
            }
            [$lng, $lat] = [round((float) $pt[0], 7), round((float) $pt[1], 7)];
            if ($lng < 114 || $lng > 128 || $lat < 3.5 || $lat > 22) {
                return null;
            }
            $out[] = [$lng, $lat];
        }
        if (count($out) < 3) {
            return null;
        }
        if ($out[0] !== end($out)) {
            $out[] = $out[0];
        }

        return count($out) >= 4 ? $out : null;
    }

    private function runJob(int $id, int $payerId, AiSetting $settings, array $p): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')
                ->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('satellite');
            $sat = $this->field->fieldHealth($p['polygon'], 10);
            if (! $sat['ok']) {
                throw new \RuntimeException(($sat['error'] === 'not-configured' ? 'The satellite connection is being set up.' : $sat['error']) . ' Nothing was charged.');
            }
            $s = $sat['data'];
            if (($s['mode'] ?? 'none') === 'none') {
                throw new \RuntimeException('Neither satellite has passed over this field in the last few weeks, so there is nothing to read yet. Nothing was charged.');
            }

            $beat('context');
            $lat = (float) ($s['centroid']['lat'] ?? $p['polygon']['coordinates'][0][0][1]);
            $lng = (float) ($s['centroid']['lng'] ?? $p['polygon']['coordinates'][0][0][0]);
            $ctx = [
                'weather' => FieldContext::weather($lat, $lng),
                'climate' => FieldContext::climate($lat, $lng),
                'soil' => FieldContext::soil($lat, $lng),
                'elevation' => FieldContext::elevation($lat, $lng),
                'enso' => EnsoOutlook::forPrompt(),
            ];

            $settings = $settings->forField('PH');
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p, $lat, $lng), $this->prompt($p, $s, $ctx), 9000,
                fn (string $t) => $this->parse($t), $beat);
            $anee = $result['data'];
            if ($anee === null) {
                Log::warning('satellite: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The analysis could not be read. Nothing was charged. Please try again.');
            }

            $report = [
                'format' => 1,
                'satellite' => $s,
                'context' => [
                    'weather' => $ctx['weather'], 'climate' => $ctx['climate'], 'soil' => $ctx['soil'],
                    'elevation' => $ctx['elevation'], 'enso' => $ctx['enso'], 'lat' => $lat, 'lng' => $lng,
                ],
                'anee' => $anee,
                'webSources' => (array) ($result['sources'] ?? []),
                'searched' => (bool) ($result['searched'] ?? false),
            ];
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $charged = (float) AiPrices::of(self::KIND);
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('Satellite Analysis: ' . CropCatalog::label($p['crop']) . ', ' . $p['location'] . $note, 0, 250));
            DB::table('as_plant_analyses')->where('id', $id)->update([
                'report' => json_encode($report), 'credits' => round($charged, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update([
                'status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now(),
            ]);
        }
    }

    private function dead(object $r): bool
    {
        $beatAt = \Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at);

        return $beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 120))
            || \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(25));
    }

    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)
            ->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            if ($this->dead($r)) {
                $why = 'The analysis was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')
                    ->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', [
                'pending' => true, 'id' => (int) $r->id, 'status' => 'pending',
                'phase' => (string) ($beat['phase'] ?? 'start'), 'try' => (int) ($beat['try'] ?? 1),
                'beatAgo' => (int) max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at), true)),
            ]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The analysis failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Analysis ready.', [
            'status' => 'ready', 'savedId' => (int) $r->id, 'title' => $r->title,
            'report' => json_decode($r->report, true), 'params' => json_decode($r->params, true),
            'charged' => (float) $r->credits, 'at' => \Illuminate\Support\Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'balance' => round($this->credits->balance($this->payer()->id), 2),
        ]);
    }

    public function list(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)
            ->where('deleteStatus', 1)->where('status', 'ready')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')))
            ->orderByDesc('id')->skip(($page - 1) * $per)->take($per + 1)
            ->get(['id', 'title', 'description', 'credits', 'created_at', 'report']);
        $more = $rows->count() > $per;

        return $this->json(true, 'ok', ['page' => $page, 'hasMore' => $more, 'rows' => $rows->take($per)->map(function ($r) {
            $rep = json_decode((string) $r->report, true) ?: [];

            return [
                'id' => $r->id, 'title' => $r->title, 'description' => $r->description, 'credits' => (float) $r->credits,
                'at' => \Illuminate\Support\Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y'),
                'score' => $rep['anee']['healthScore'] ?? null, 'word' => $rep['anee']['healthWord'] ?? null,
                'ndvi' => $rep['satellite']['sentinel2']['ndvi']['mean'] ?? null, 'mode' => $rep['satellite']['mode'] ?? null,
            ];
        })->values()]);
    }

    public function one(int $id)
    {
        return $this->jobState($id);
    }

    /** Fresh tile URLs for a saved report (Earth Engine map ids expire). */
    public function tiles(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)
            ->where('id', $id)->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }
        $rep = json_decode((string) $r->report, true) ?: [];
        $par = json_decode((string) $r->params, true) ?: [];
        $res = $this->field->tiles((array) ($par['polygon'] ?? []), $rep['satellite']['sentinel2']['imageId'] ?? null, $rep['satellite']['sentinel1']['imageId'] ?? null);
        if (! $res['ok']) {
            return $this->json(false, 'The map layers could not be refreshed right now.', [], 502);
        }

        return $this->json(true, 'ok', ['tiles' => $res['data']['tiles'] ?? []]);
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Analysis deleted.');
    }

    public function meta(Request $request)
    {
        $title = trim((string) $request->input('title'));
        if ($title === '' || mb_strlen($title) > 190) {
            return $this->json(false, 'Give it a name up to 190 characters.', [], 422);
        }
        $ok = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)
            ->where('id', (int) $request->input('id'))->where('deleteStatus', 1)
            ->update(['title' => $title, 'description' => trim((string) $request->input('description')) ?: null, 'updated_at' => now()]);

        return $ok ? $this->json(true, 'Saved.', ['title' => $title]) : $this->json(false, 'That analysis is not in your list.', [], 404);
    }

    /** The analysis as chat context, when it is attached to a question for Anee. */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = DB::table('as_plant_analyses')->where('userId', $userId)->where('id', $id)->where('kind', self::KIND)
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return null;
        }
        $rep = json_decode((string) $r->report, true) ?: [];
        $a = $rep['anee'] ?? [];
        $s = $rep['satellite'] ?? [];
        $text = "\n\n--- ATTACHED: Satellite analysis of a field (treat as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Health: ' . ($a['healthWord'] ?? '') . ' (' . ($a['healthScore'] ?? '') . '/100). ' . ($a['summary'] ?? '') . "\n"
            . 'NDVI mean ' . ($s['sentinel2']['ndvi']['mean'] ?? 'n/a') . ' on ' . ($s['sentinel2']['time']['ph'] ?? 'n/a')
            . '; radar VV ' . ($s['sentinel1']['vvDb'] ?? 'n/a') . ' dB, VH ' . ($s['sentinel1']['vhDb'] ?? 'n/a') . ' dB on ' . ($s['sentinel1']['time']['ph'] ?? 'n/a') . "\n"
            . 'Actions: ' . collect($a['actions'] ?? [])->map(fn ($x) => ($x['what'] ?? '') . ' (' . ($x['when'] ?? '') . ')')->implode('; ') . "\n"
            . "--- END OF ATTACHED ANALYSIS ---\n";

        return ['title' => $r->title, 'text' => $text];
    }

    /* ------------------------------------------------------------ prompts */

    private function facts(array $p): string
    {
        $crop = CropCatalog::label($p['crop']);
        $age = $p['plantedOn'] ? (int) \Illuminate\Support\Carbon::parse($p['plantedOn'])->diffInDays(now('Asia/Manila')->startOfDay()) : null;
        $units = ['seeds_ha' => 'seeds per hectare', 'plants_ha' => 'plants per hectare', 'kg_ha' => 'kg of seed per hectare', 'hills_m2' => 'hills per square meter'];

        return 'Crop: ' . $crop . ($p['variety'] ? ' (variety ' . $p['variety'] . ')' : '') . "\n"
            . 'Place: ' . $p['location'] . ", Philippines\n"
            . 'Planted: ' . ($p['plantedOn'] ? $p['plantedOn'] . ' (' . $age . ' days ago)' : 'not stated') . '; way in: ' . (self::METHODS[$p['method']] ?? $p['method']) . "\n"
            . 'Seeding rate or density: ' . ($p['density'] ? rtrim(rtrim(number_format($p['density'], 2, '.', ''), '0'), '.') . ' ' . ($units[$p['densityUnit']] ?? '') : 'not stated') . "\n"
            . 'Water: ' . (self::WATER[$p['water']] ?? $p['water']) . "\n"
            . 'Soil as the farmer knows it: ' . (SoilConditions::words($p['soilConditions'], $p['phValue']) ?: 'not stated') . "\n"
            . 'What worries the farmer: ' . (collect($p['concerns'])->map(fn ($k) => self::CONCERNS[$k] ?? $k)->implode('; ') ?: 'nothing named') . "\n"
            . ($p['notes'] ? 'Farmer\'s notes: ' . $p['notes'] . "\n" : '');
    }

    private function researchPrompt(array $p, float $lat, float $lng): string
    {
        return "You are researching for a satellite field analysis in the Philippines. Use web search. Write plain research notes (not JSON).\n\n"
            . $this->facts($p)
            . 'Field center: ' . round($lat, 4) . ', ' . round($lng, 4) . "\n\n"
            . "Find and summarize, citing the sources you read:\n"
            . "1. SOIL OF THIS AREA: any soil survey, BSWM soil series, or published study of the soils in or near this town or province (pH, sodic, alkaline, saline, acid sulfate, texture, drainage, known deficiencies). Name the study and what it found. If none exists for this exact place, say so and give the province's general soil picture.\n"
            . "2. HAZARDS OF THIS PLACE: the area's record of typhoons (which months, how often), floods, drought and heat in the last 20 years; what PAGASA or the DA say about this season.\n"
            . "3. THE CROP: for this crop (and variety if named) at about this age, the typical Sentinel-2 NDVI range of a healthy field in the tropics, typical Sentinel-1 VH backscatter behaviour as the canopy grows, and the main pests and diseases that strike at this stage in the Philippines and the weather that favours them.\n"
            . ($p['crop'] === 'corn' || str_contains(strtolower(CropCatalog::label($p['crop'])), 'corn') ? "4. HIGH DENSITY HYBRID CORN: how 90,000 or more seeds per hectare behaves in NDVI and radar, lodging and nutrient risks at that density, and how sodic or alkaline soil limits it.\n" : '')
            . "\nBe factual. Keep numbers with their source.";
    }

    private function prompt(array $p, array $s, array $ctx): string
    {
        $s2 = $s['sentinel2'] ?? [];
        $s1 = $s['sentinel1'] ?? [];
        $w = $ctx['weather'] ?? [];
        $nextDays = collect($w['days'] ?? [])->where('past', false)->take(10)
            ->map(fn ($d) => $d['date'] . ': ' . ($d['tmin'] ?? '?') . '-' . ($d['tmax'] ?? '?') . 'C, rain ' . ($d['rain'] ?? '?') . 'mm (' . ($d['pop'] ?? '?') . '%), gusts ' . ($d['gust'] ?? '?') . ' km/h')->implode("\n");
        $sat = [
            'mode' => $s['mode'] ?? null,
            'areaHa' => $s['areaHa'] ?? null,
            'sentinel2' => $s2['available'] ?? false ? [
                'imagePH' => $s2['time']['ph'] ?? null, 'ageDays' => $s2['time']['ageDays'] ?? null, 'fieldClearShare' => $s2['fieldClear'] ?? null,
                'ndvi' => $s2['ndvi'] ?? null, 'zones' => $s2['zones'] ?? [],
            ] : ['available' => false, 'blockedByCloud' => $s2['blockedByCloud'] ?? null, 'lastClear' => $s2['lastClear']['ph'] ?? null],
            'sentinel1' => $s1['available'] ?? false ? [
                'imagePH' => $s1['time']['ph'] ?? null, 'ageDays' => $s1['time']['ageDays'] ?? null, 'pass' => $s1['pass'] ?? null,
                'vvDb' => $s1['vvDb'] ?? null, 'vhDb' => $s1['vhDb'] ?? null, 'vvStdDb' => $s1['vvStdDb'] ?? null, 'vhStdDb' => $s1['vhStdDb'] ?? null,
                'vvMinusVhDb' => $s1['vvMinusVhDb'] ?? null, 'vhVvRatio' => $s1['vhVvRatio'] ?? null, 'zonesVH' => $s1['zones'] ?? [],
            ] : ['available' => false],
            'history90Days' => $s['series'] ?? [],
        ];

        return "You are Anee, a smart farm technician for Filipino farmers. Write a satellite field analysis as ONE JSON object, nothing else.\n\n"
            . "THE FIELD\n" . $this->facts($p)
            . 'Elevation: ' . ($ctx['elevation'] ?? 'unknown') . " m\n\n"
            . "WHAT THE SATELLITES SAW (Google Earth Engine; times are Philippine time; NDVI from 10 m Sentinel-2 with clouds masked; radar in dB from Sentinel-1, VV and VH):\n"
            . json_encode($sat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "WEATHER: last 30 days rain " . ($w['past30Rain'] ?? '?') . " mm, evapotranspiration " . ($w['past30Et0'] ?? '?') . " mm. Next 10 days:\n" . $nextDays . "\n"
            . 'Next 16 days rain total ' . ($w['next16Rain'] ?? '?') . " mm.\n\n"
            . "THE SAME WEEKS IN EARLIER YEARS (this month and next):\n" . json_encode($ctx['climate'] ?? []) . "\n\n"
            . 'ENSO: ' . ($ctx['enso'] ?: 'not available') . "\n\n"
            . "MODELLED SOIL AT THE SPOT (ISRIC SoilGrids 250 m, top 30 cm; a map estimate, not a test of this field):\n" . json_encode($ctx['soil'] ?? []) . "\n\n"
            . "HOW TO READ IT\n"
            . "- NDVI: bare or flooded ground is near 0 to 0.2; young crop 0.2 to 0.4; a full green canopy 0.6 to 0.9. Judge against what this crop should show at this age. A wide stdDev, a high lowShare or one weak zone means uneven growth: say which part of the field (use the zone names), and the likely causes (water, nutrients, pests, disease, gaps, waterlogging, salt), and what to check on foot.\n"
            . "- Radar: VH rises as leaves and stems fill in; VV minus VH narrows as the canopy thickens; very low VV and VH (below about -20 dB) over a paddy can mean standing water. Radar sees through cloud.\n"
            . "- If Sentinel-2 is not available (typhoon cloud), say so plainly and base the crop reading on the radar and its history alone.\n"
            . "- Spacing: at 10 m pixels a satellite cannot count plants. Judge stand uniformity and gaps from the zone spread and the history, and compare the stated density with what the crop needs, saying the limit honestly.\n"
            . "- High density hybrid corn (90,000 or more seeds per hectare) on sodic or alkaline soil: weigh lodging, zinc and iron shortage, sodium crusting and poor emergence.\n"
            . "- Use the research notes below for the soil studies and hazards of this place. Never invent a number. Say what you cannot know.\n\n"
            . "Return exactly this shape:\n"
            . '{"headline":"one line","healthScore":0-100,"healthWord":"Strong|Good|Fair|Stressed|Poor","summary":"3 to 4 plain sentences",'
            . '"stage":{"guess":"growth stage now","basis":"why"},'
            . '"health":{"reading":"what NDVI and radar say together","ndviMeaning":"the mean NDVI against what this crop should show now","uniformity":"even or patchy, and where","weakZones":[{"where":"zone name","what":"what is seen","likelyCause":"","check":"what to check on foot"}],"radar":"what VV and VH say about canopy and biomass","trend":"what the 90 day history shows"},'
            . '"spacing":{"reading":"stand and density judgement","density":"stated density against what the crop needs","note":"the 10 m limit, honestly"},'
            . '"disease":{"risk":"low|medium|high","reading":"why","watchFor":[{"problem":"","why":"","sign":"","when":""}]},'
            . '"threats":[{"threat":"","when":"","likelihood":"low|medium|high","severity":"low|medium|high","why":"","action":""}],'
            . '"weather":{"outlook":"the next 10 days for this crop","rain":"","heat":"","wind":""},'
            . '"climate":{"enso":"what ENSO means here now","history":"what earlier years did in these weeks"},'
            . '"soil":{"reading":"the soil picture","properties":[{"name":"","value":"","meaning":""}],"studies":[{"title":"","finding":""}],"fit":"how well this soil suits the crop and what to watch"},'
            . '"actions":[{"priority":"now|this week|this month","what":"","why":""}],'
            . '"confidence":"low|medium|high","dataGaps":["what would make this surer"]}';
    }

    private function parse(string $text): ?array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?|```$/m', '', $t) ?? $t;
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a === false || $b === false) {
            return null;
        }
        $d = json_decode(substr($t, $a, $b - $a + 1), true);

        return is_array($d) && isset($d['summary']) ? $d : null;
    }

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
