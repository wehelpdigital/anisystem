<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

/**
 * What to Plant — the sister of When to Plant, asked the other way round.
 *
 * The farmer answers a wizard about the ground itself (place, soil, water,
 * troubles, when they want to start, what the harvest is for) and the model
 * is asked WHICH crops suit that ground best — ranked across the families a
 * Philippine farm actually weighs: grains, vegetables, root crops, legumes,
 * fruit trees. Same shelf, same job walk, same honest ledger as its sister:
 * the price is said before anything is spent and charged exactly as said.
 */
class WhatToPlantController extends Controller
{
    /** The house's flat price for one analysis, in credits. */
    public const PRICE = AiPrices::DEFAULTS['what'];

    public const SOILS = [
        'clay' => 'Heavy clay — sticky wet, cracks dry',
        'loam' => 'Loam — dark, crumbly, holds water well',
        'sandy' => 'Sandy — light, drains fast',
        'silty' => 'Silty — fine, smooth, floodplain soil',
        'rocky' => 'Rocky / shallow soil',
        'unsure' => 'Not sure — it grows weeds fine',
    ];

    public const WATERS = [
        'irrigated' => 'Reliable irrigation all season',
        'limited' => 'Some irrigation, but limited',
        'rainfed' => 'Rain only',
    ];

    public const AIMS = [
        'sell' => 'To sell at the market',
        'family' => 'To feed the family',
        'both' => 'Both — eat some, sell the rest',
    ];

    /**
     * The extra signals (2026-09-17): each optional, each a fact a farmer
     * can read off the field without a laboratory, each one that moves the
     * ranking. "Not sure" is always the first answer and costs nothing.
     */
    public const PH_LEVELS = [
        'unsure' => 'Not sure / never tested',
        'acidic' => 'Acidic — below 6 (moss, ferns, poor legumes)',
        'neutral' => 'Around neutral — 6 to 7.5',
        'alkaline' => 'Alkaline — above 7.5 (white crust, yellowing young leaves)',
    ];

    public const WATER_LOOKS = [
        'unsure' => 'Not sure / no irrigation water',
        'clear' => 'Clear and clean',
        'muddy' => 'Muddy or brown — carries silt',
        'milky' => 'Cloudy white or milky',
        'green' => 'Greenish — algae in it',
        'salty' => 'Tastes salty or leaves a white crust',
        'smelly' => 'Smells bad, oily film or foam',
    ];

    public const LAYS = [
        'flat' => 'Flat',
        'gentle' => 'Gently sloping',
        'steep' => 'Steep — water runs off fast',
        'low' => 'Low-lying — water collects',
    ];

    public const ELEVATIONS = [
        'lowland' => 'Lowland — near sea level, warm',
        'upland' => 'Upland / hilly — rolling ground',
        'highland' => 'Highland — cool, cloudy mornings',
    ];

    public const SUNS = [
        'full' => 'Full sun all day',
        'part' => 'Shaded part of the day (trees, buildings)',
        'shade' => 'Mostly shaded',
    ];

    public const LABORS = [
        'hand' => 'Mostly by hand, family labor',
        'some' => 'Some hired help or a small machine',
        'mech' => 'Machinery and a crew when needed',
    ];

    public const BUDGETS = [
        'tight' => 'Tight — seeds and little else',
        'moderate' => 'Moderate — the usual inputs',
        'invest' => 'Can invest — irrigation, good seed, protection',
    ];

    public const MARKETS = [
        'farm' => 'Buyers come to the farm',
        'town' => 'The nearby town market',
        'city' => 'A city market or trader, hours away',
        'contract' => 'A contract or cooperative buyer',
    ];

    /** The ground's troubles — the sister module's list, minus the two the
     *  soil question already answers. */
    public const PROBLEMS = [
        'floods' => 'Floods / standing water after rain',
        'water_source' => 'Limited irrigation water source',
        'river' => 'Beside a river (overflow reaches the field)',
        'sea' => 'Near the sea (salt spray / brackish water)',
        'wind' => 'Strong winds pass through (typhoon corridor)',
        'pests' => 'Pests have been heavy in past seasons',
        'weeds' => 'Weed pressure is heavy',
        'drainage' => 'Poor drainage',
        'shade' => 'Part of the field is shaded',
        'slope' => 'Sloping / hilly ground',
    ];

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    /** The tier wall: analyses ride the Solo Farmer plan and up. */
    private function guardTier(): void
    {
        if (! \App\Support\Tier::farmCan('reportsAll')) {
            \App\Support\Tier::deny('The What to Plant analysis comes with the Solo Farmer plan.');
        }
    }

    public function page()
    {
        $this->guardTier();
        return view('what-to-plant.index');
    }

    /** Everything the wizard needs, plus the standing price. */
    public function options()
    {
        $farmerCountry = \App\Support\Region::code();
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable();

        // The next twelve months, so "when do you want to start" is a tap.
        $months = [];
        $cursor = now('Asia/Manila')->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $months[] = ['key' => $cursor->format('Y-m'), 'label' => $cursor->format('F Y')];
            $cursor = $cursor->addMonth();
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'soils' => self::SOILS,
            'phLevels' => self::PH_LEVELS,
            'waterLooks' => self::WATER_LOOKS,
            'lays' => self::LAYS,
            'elevations' => self::ELEVATIONS,
            'suns' => self::SUNS,
            'labors' => self::LABORS,
            'budgets' => self::BUDGETS,
            'markets' => self::MARKETS,
            'waters' => self::WATERS,
            'aims' => self::AIMS,
            'problems' => self::PROBLEMS,
            'months' => $months,
            'country' => $farmerCountry,
            'quote' => $canUse ? (float) AiPrices::of('what') : null,
            'aneeFace' => $settings->faceUrl(),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null
                : ($settings->isUsable()
                    ? 'This analysis runs on the AI Technician, which needs a Boss or Lifetime plan'
                        . ((int) $payer->id === (int) Auth::id() ? '.' : ' on the farm owner\'s account.')
                    : 'The AI Technician is not switched on yet. Please check back soon.'),
        ]]);
    }

    /** Run the analysis — the sister's job walk, question swapped. */
    public function generate(Request $request)
    {
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, 'The analysis needs the AI Technician (Boss or Lifetime plan).', [], 403);
        }

        $v = Validator::make($request->all(), [
            'location' => 'required|string|max:160',
            'country' => 'nullable|string|size:2',
            'startMonth' => 'required|date_format:Y-m',
            'soil' => 'required|in:' . implode(',', array_keys(self::SOILS)),
            'water' => 'required|in:' . implode(',', array_keys(self::WATERS)),
            'aim' => 'required|in:' . implode(',', array_keys(self::AIMS)),
            'area' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:400',
            'ph' => 'nullable|in:' . implode(',', array_keys(self::PH_LEVELS)),
            'phValue' => 'nullable|numeric|min:3|max:10',
            'waterLook' => 'nullable|in:' . implode(',', array_keys(self::WATER_LOOKS)),
            'lay' => 'nullable|in:' . implode(',', array_keys(self::LAYS)),
            'elevation' => 'nullable|in:' . implode(',', array_keys(self::ELEVATIONS)),
            'sun' => 'nullable|in:' . implode(',', array_keys(self::SUNS)),
            'prevCrop' => 'nullable|string|max:120',
            'grewWell' => 'nullable|string|max:160',
            'labor' => 'nullable|in:' . implode(',', array_keys(self::LABORS)),
            'budget' => 'nullable|in:' . implode(',', array_keys(self::BUDGETS)),
            'market' => 'nullable|in:' . implode(',', array_keys(self::MARKETS)),
            'problems' => 'nullable|array',
            'problems.*' => 'string|in:' . implode(',', array_keys(self::PROBLEMS)),
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }

        $balance = $this->credits->balance($payer->id);
        if ($balance < AiPrices::of('what') && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . AiPrices::of('what') . ' credits for this analysis and have '
                . number_format((int) floor($balance)) . '.',
                ['outOfCredits' => true], 402);
        }

        // One in flight at a time — a double press must not buy two.
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'what')
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(10))
            ->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }

        $p = $this->params($request);
        // A field in another country than the farmer's: Anee is told so in
        // her own instructions, or she declines for being "set up" at home.
        $settings = $settings->forField($p['country']);
        $title = 'What to plant · ' . \Illuminate\Support\Carbon::parse($p['startMonth'] . '-01')->format('M Y')
            . ' · ' . $p['location'] . ($p['country'] !== \App\Support\Region::code() ? ', ' . \App\Support\Region::name($p['country']) : '');
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(),
            'kind' => 'what',
            'title' => mb_substr($title, 0, 190),
            'params' => json_encode($p),
            'report' => json_encode(new \stdClass),
            'credits' => 0,
            'status' => 'pending',
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $prompt = $this->prompt($p);

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $id,
            ]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $prompt);
            exit;
        }

        @set_time_limit(300);
        $this->runJob($id, (int) $payer->id, $settings, $prompt);

        return $this->jobState($id);
    }

    /** The model call and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt): void
    {
        try {
            $result = $this->ai->askForJson($settings, $prompt, 4000, fn (string $t) => $this->parseReport($t));
            $report = $result['data'];
            if ($report === null) {
                \Illuminate\Support\Facades\Log::warning('what-to-plant: unparsable answer', [
                    'head' => mb_substr((string) $result['text'], 0, 400),
                ]);
                throw new \RuntimeException($result['error'] ?? 'The analysis came back unreadable. Nothing was charged — please try again.');
            }

            $charged = (float) AiPrices::of('what');
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $p = json_decode((string) ($row->params ?? '[]'), true) ?: [];
            $note = AiUsage::record('what', (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('What-to-plant analysis — ' . ($p['location'] ?? '') . $note, 0, 250));

            DB::table('as_plant_analyses')->where('id', $id)->update([
                'report' => json_encode($report),
                'credits' => round($charged, 2),
                'status' => 'ready',
                'error' => null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
                'updated_at' => now(),
            ]);
        }
    }

    /** Where a job stands — polled by the page until ready or failed. */
    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('id', $id)->where('kind', 'what')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending']);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The analysis failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Analysis ready.', [
            'status' => 'ready',
            'savedId' => (int) $r->id,
            'report' => json_decode($r->report, true),
            'params' => json_decode($r->params, true),
            'charged' => (float) $r->credits,
            'balance' => round($this->credits->balance($this->payer()->id), 2),
        ]);
    }

    public function list(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per = 20;
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'what')
            ->where('deleteStatus', 1)->where('status', 'ready')->orderByDesc('id')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')))
            ->skip(($page - 1) * $per)->take($per + 1)
            ->get(['id', 'title', 'description', 'credits', 'created_at']);
        $hasMore = $rows->count() > $per;
        $rows = $rows->take($per);

        return $this->json(true, 'ok', ['page' => $page, 'hasMore' => $hasMore, 'q' => $q, 'rows' => $rows->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'description' => $r->description,
            'credits' => (float) $r->credits,
            'at' => \Illuminate\Support\Carbon::parse($r->created_at)->format('M j, Y'),
        ])->values()]);
    }

    public function one(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('id', $id)->where('kind', 'what')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return $this->json(false, 'That analysis is gone.', [], 404);
        }

        return $this->json(true, 'ok', [
            'id' => $r->id,
            'title' => $r->title,
            'params' => json_decode($r->params, true),
            'report' => json_decode($r->report, true),
            'credits' => (float) $r->credits,
        ]);
    }

    /** What Anee reads when a what-to-plant report is attached. */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = DB::table('as_plant_analyses')->where('userId', $userId)
            ->where('id', $id)->where('kind', 'what')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return null;
        }
        $report = json_decode($r->report, true) ?: [];
        $params = json_decode($r->params, true) ?: [];
        $problems = collect($params['problems'] ?? [])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ');
        $top = $report['topPick'] ?? [];
        $text = "\n\n--- ATTACHED: What-to-plant analysis (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Ground: soil ' . (self::SOILS[$params['soil'] ?? ''] ?? '') . '; water ' . (self::WATERS[$params['water'] ?? ''] ?? '')
            . (($params['ph'] ?? 'unsure') !== 'unsure' ? '; pH ' . (self::PH_LEVELS[$params['ph']] ?? '') : '') . (($params['waterLook'] ?? 'unsure') !== 'unsure' ? '; water looks ' . (self::WATER_LOOKS[$params['waterLook']] ?? '') : '')
            . '; aim ' . (self::AIMS[$params['aim'] ?? ''] ?? '') . ($params['area'] ?? null ? '; area ' . $params['area'] : '') . "\n"
            . 'Troubles considered: ' . ($problems ?: 'none') . "\n"
            . 'Top pick: ' . ($top['crop'] ?? '') . ' (' . ($top['category'] ?? '') . ') — ' . ($top['why'] ?? '') . "\n"
            . 'Ranked: ' . collect($report['recommendations'] ?? [])->map(fn ($x) => ($x['rank'] ?? '') . '. ' . ($x['crop'] ?? '') . ' [' . ($x['category'] ?? '') . '] score ' . ($x['score'] ?? ''))->implode(' | ') . "\n"
            . 'Better avoided: ' . collect($report['avoid'] ?? [])->map(fn ($x) => ($x['crop'] ?? '') . ' — ' . ($x['why'] ?? ''))->implode(' | ') . "\n"
            . 'Summary: ' . ($report['summary'] ?? '') . "\n"
            . 'Stated confidence: ' . ($report['confidence'] ?? '') . '; data gaps: ' . collect($report['dataGaps'] ?? [])->implode('; ')
            . "\n--- END OF ATTACHED ANALYSIS ---\n";

        return ['title' => $r->title, 'text' => $text];
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)
            ->where('kind', 'what')
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Analysis removed.');
    }

    /** Rename a saved analysis and describe it in your own words. */
    public function meta(\Illuminate\Http\Request $request)
    {
        $title = trim((string) $request->input('title'));
        if ($title === '' || mb_strlen($title) > 191) {
            return $this->json(false, 'Give it a name up to 191 characters.', [], 422);
        }
        $description = trim((string) $request->input('description'));
        $updated = DB::table('as_plant_analyses')
            ->where('userId', Auth::id())
            ->where('kind', 'what')
            ->where('id', (int) $request->input('id'))
            ->where('deleteStatus', 1)
            ->update([
                'title' => $title,
                'description' => $description !== '' ? mb_substr($description, 0, 2000) : null,
                'updated_at' => now(),
            ]);
        if (! $updated) {
            return $this->json(false, 'That analysis is not on your shelf.', [], 404);
        }

        return $this->json(true, 'Analysis updated.', ['title' => $title, 'description' => $description ?: null]);
    }

    /* ------------------------------------------------------------ helpers */

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    private function params(Request $request): array
    {
        return [
            'location' => trim((string) $request->input('location')),
            'country' => \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code(),
            'startMonth' => (string) $request->input('startMonth'),
            'soil' => (string) $request->input('soil'),
            'water' => (string) $request->input('water'),
            'aim' => (string) $request->input('aim'),
            'area' => trim((string) $request->input('area', '')),
            'notes' => trim((string) $request->input('notes', '')),
            'problems' => array_values((array) $request->input('problems', [])),
            // The extra signals, each optional; an unknown reads as "not sure".
            'ph' => array_key_exists((string) $request->input('ph'), self::PH_LEVELS) ? (string) $request->input('ph') : 'unsure',
            'phValue' => $request->filled('phValue') ? round((float) $request->input('phValue'), 1) : null,
            'waterLook' => array_key_exists((string) $request->input('waterLook'), self::WATER_LOOKS) ? (string) $request->input('waterLook') : 'unsure',
            'lay' => array_key_exists((string) $request->input('lay'), self::LAYS) ? (string) $request->input('lay') : null,
            'elevation' => array_key_exists((string) $request->input('elevation'), self::ELEVATIONS) ? (string) $request->input('elevation') : null,
            'sun' => array_key_exists((string) $request->input('sun'), self::SUNS) ? (string) $request->input('sun') : null,
            'prevCrop' => trim((string) $request->input('prevCrop', '')),
            'grewWell' => trim((string) $request->input('grewWell', '')),
            'labor' => array_key_exists((string) $request->input('labor'), self::LABORS) ? (string) $request->input('labor') : null,
            'budget' => array_key_exists((string) $request->input('budget'), self::BUDGETS) ? (string) $request->input('budget') : null,
            'market' => array_key_exists((string) $request->input('market'), self::MARKETS) ? (string) $request->input('market') : null,
        ];
    }

    /** The question, spelled out so the answer is bounded and honest. */
    private function prompt(array $p): string
    {
        $start = \Illuminate\Support\Carbon::parse($p['startMonth'] . '-01')->format('F Y');
        $soil = self::SOILS[$p['soil']] ?? $p['soil'];
        $water = self::WATERS[$p['water']] ?? $p['water'];
        $aim = self::AIMS[$p['aim']] ?? $p['aim'];
        $problems = collect($p['problems'])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none reported';
        $area = $p['area'] !== '' ? $p['area'] : 'not stated';
        $notes = $p['notes'] !== '' ? $p['notes'] : 'none';
        // The extra signals, in words; "not stated" where the farmer skipped one.
        $said = fn (array $table, ?string $k) => ($k && $k !== 'unsure' && isset($table[$k])) ? $table[$k] : 'not stated';
        $ph = $said(self::PH_LEVELS, $p['ph'] ?? null) . (($p['phValue'] ?? null) ? ' (tested: pH ' . $p['phValue'] . ')' : '');
        $waterLook = $said(self::WATER_LOOKS, $p['waterLook'] ?? null);
        $lay = $said(self::LAYS, $p['lay'] ?? null);
        $elevation = $said(self::ELEVATIONS, $p['elevation'] ?? null);
        $sun = $said(self::SUNS, $p['sun'] ?? null);
        $prevCrop = ($p['prevCrop'] ?? '') !== '' ? $p['prevCrop'] : 'not stated';
        $grewWell = ($p['grewWell'] ?? '') !== '' ? $p['grewWell'] : 'not stated';
        $labor = $said(self::LABORS, $p['labor'] ?? null);
        $budget = $said(self::BUDGETS, $p['budget'] ?? null);
        $market = $said(self::MARKETS, $p['market'] ?? null);
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ' . $enso . "\n" : '';

        // The FIELD's country, which may not be the farmer's: its name, its
        // weather authority, its crops; the language stays the farmer's.
        $fc = $p['country'] ?? \App\Support\Region::code();
        $fieldPH = $fc === \App\Support\Region::HOME;
        $countryName = \App\Support\Region::name($fc);
        $regionBlock = \App\Support\Region::promptBlock($fc, \App\Support\Region::code());
        $met = \App\Support\Region::as($fc, fn () => \App\Support\Region::agency('met'));
        $climateRule = $fieldPH
            ? 'PAGASA climatological normals for the region named (wet/dry timing, typhoon seasonality)'
            : 'the climatological normals for the region named as published by ' . $met . ' (frost dates and growing-season length where they apply, rainfall and temperature timing, the severe-weather season)';

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$countryName}. The farmer asks WHAT to plant on the ground described below, starting around {$start}. Recommend the best-suited crops, ranked, drawn from across the families a farm in {$countryName} weighs: grains, vegetables, root crops, legumes, and fruit/tree crops — only crops actually grown and sold in {$countryName}.

FACTS GIVEN
- {$regionBlock}
- Location as the farmer wrote it: {$p['location']}
- Planned start: {$start}
- Soil, as the farmer describes it: {$soil}
- Water: {$water}
- What the harvest is for: {$aim}
- Land area: {$area}
- Soil pH, as far as the farmer knows: {$ph}
- The irrigation water, as it looks: {$waterLook}
- The lay of the land: {$lay}
- Elevation: {$elevation}
- Sunlight: {$sun}
- What grew there last: {$prevCrop}
- What has grown well there before: {$grewWell}
- Labor and machinery: {$labor}
- Budget for inputs: {$budget}
- Where the harvest would be sold: {$market}
- Field troubles the farmer reports: {$problems}
- The farmer's own notes: {$notes}
{$ensoBlock}
GROUND RULES
- Reason only from established knowledge: {$climateRule}, the soil-water behaviour implied by the described soil and troubles, each candidate crop's real agronomic needs and calendar, and typical {$countryName} market/home-use patterns for the stated aim. No invented prices, no yield promises.
- Read the extra signals as an agronomist would, and say in each "why" which ones moved the pick: soil pH sets which crops tolerate the ground (acid-tolerant vs lime-loving); the LOOK of the irrigation water is a clue — muddy/brown carries silt (fine for paddy, clogs drip), cloudy white or milky suggests suspended lime/minerals or fine clay (check salinity and hardness before drip or sensitive vegetables), greenish means algae and nutrient load (watch clogging and disease), salty taste or a white crust means salinity (favor salt-tolerant crops), a bad smell or oily film means contamination (avoid leafy vegetables eaten raw); the lay and elevation set drainage, cold and cloud; sunlight rules out sun-loving crops in shade; the previous crop sets rotation (do not repeat a family that shares its pests and diseases); labor, budget and the market decide whether a labor-heavy, input-heavy or perishable crop is realistic. A skipped signal is "not stated" — do not guess it; name it in dataGaps if it would have changed the ranking.
- Cover the families honestly: at least one strong root crop and one perennial/tree option must be CONSIDERED — recommended if they fit, or placed in avoid with the reason if they do not.
- Where the given facts cannot answer something (soil test values, exact microclimate, market access), name it in dataGaps instead of guessing.
- Be scientific and neutral: no seed brands, no product recommendations, no marketing tone.
- Write every "why" in plain words a farmer reads easily. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"topPick":{"crop":"","category":"","why":"","window":""},"recommendations":[{"rank":1,"crop":"","category":"","score":0,"window":"","why":"","watch":""}],"avoid":[{"crop":"","why":""}],"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- recommendations: SIX to EIGHT crops, ranked best-first, each with: category (one of "Grain", "Vegetable", "Root crop", "Legume", "Fruit / tree"), score 0-100 (fit for THIS ground and start month), window (when to plant it, specific — e.g. "mid May – early June"), why (≤ 35 words, grounded in the facts), watch (the one thing to watch for on this ground, ≤ 15 words).
- topPick repeats the rank-1 crop with a fuller why (≤ 60 words) and its window.
- avoid: two to four crops a farmer in this region might otherwise try, each with the plain reason this ground argues against it.
- confidence "low"/"moderate"/"high"; dataGaps at most three; summary ≤ 90 words, plain and warm but factual.
PROMPT;
    }

    /** The model's JSON, taken carefully. Null when it cannot be trusted. */
    private function parseReport(string $text): ?array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text);
        $from = strpos($text, '{');
        $to = strrpos($text, '}');
        if ($from === false || $to === false || $to <= $from) {
            return null;
        }
        $json = json_decode(substr($text, $from, $to - $from + 1), true);
        if (! is_array($json) || ! isset($json['topPick'], $json['recommendations'], $json['summary'])) {
            return null;
        }
        if (! is_array($json['recommendations']) || count($json['recommendations']) < 3) {
            return null;
        }

        // The persona's shortcodes have no renderer here.
        $sweep = fn ($v) => is_string($v) ? trim(preg_replace('/:[a-z0-9_-]+:/i', '', $v)) : $v;
        array_walk_recursive($json, function (&$v) use ($sweep) {
            $v = $sweep($v);
        });

        return $json;
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
