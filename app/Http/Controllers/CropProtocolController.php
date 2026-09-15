<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\WeatherService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Crop Protocol Analysis -- the fourth of the Quick Tools.
 *
 * When to Plant names the window, What to Plant the crop, Variety
 * Research the variety; this one writes the SEASON: a semi-complete
 * protocol for one field -- how many bags of what fertilizer and at which
 * growth stage, the sprays and foliars to have ready and when to reach
 * for them, how to run the water, which pests, diseases and weeds to
 * watch for, and what the sky is likely to do -- worked out from the
 * farmer's answers (crop, variety, month, cropping method, priority,
 * target yield, field size, soil, water, troubles), the place, the ENSO
 * state and forecast, the coming weather, and the variety's real
 * published traits, which Anee reads up on before she writes.
 *
 * Everything is hung on the GROWTH STAGE, never on a day count: the guide
 * says what to do when the crop reaches a stage, and the farmer's eyes
 * say when that is. That is precision agriculture as this app means it,
 * and the report says so out loud.
 *
 * Same shelf (as_plant_analyses, kind 'protocol'), same job walk, same
 * honest ledger as its sisters.
 */
class CropProtocolController extends Controller
{
    /** The house's flat price for one protocol, in credits. */
    public const PRICE = AiPrices::DEFAULTS['protocol'];

    public const SOILS = WhatToPlantController::SOILS;

    public const WATERS = WhatToPlantController::WATERS;

    public const PROBLEMS = VarietyAnalysisController::PROBLEMS;

    /** How the crop is put in the ground. Rice has three of its own. */
    public const METHODS = [
        'transplanted' => ['label' => 'Transplanted', 'sub' => 'Seedlings raised in a seedbed, then set out', 'icon' => '🌱', 'rice' => true],
        'direct_wet' => ['label' => 'Direct-seeded, wet', 'sub' => 'Pre-germinated seed broadcast on puddled soil', 'icon' => '💧', 'rice' => true],
        'direct_dry' => ['label' => 'Direct-seeded, dry', 'sub' => 'Dry seed sown into dry or moist soil', 'icon' => '🌤️', 'rice' => true],
        'direct' => ['label' => 'Direct-seeded', 'sub' => 'Seed sown straight into the field', 'icon' => '🌱', 'rice' => false],
        'nursery' => ['label' => 'From a nursery', 'sub' => 'Seedlings or cuttings raised first, then planted out', 'icon' => '🪴', 'rice' => false],
    ];

    /** What the farmer is chasing this season. */
    public const PRIORITIES = [
        'yield' => ['label' => 'Highest yield', 'sub' => 'Spend what it takes for the biggest harvest', 'icon' => '🌾'],
        'balanced' => ['label' => 'In the middle', 'sub' => 'A good harvest without overspending', 'icon' => '⚖️'],
        'cost' => ['label' => 'Lower cost', 'sub' => 'The leanest inputs that still make a decent crop', 'icon' => '💰'],
    ];

    public const YIELD_UNITS = ['cavans' => 'cavans per hectare', 'tons' => 'tons per hectare'];

    public function __construct(private AiCreditService $credits, private AiClient $ai)
    {
    }

    /** The tier wall: analyses ride the Solo Farmer plan and up. */
    private function guardTier(): void
    {
        if (! \App\Support\Tier::farmCan('reportsAll')) {
            \App\Support\Tier::deny('The Crop Protocol Analysis comes with the Solo Farmer plan.');
        }
    }

    public function page()
    {
        $this->guardTier();

        return view('crop-protocol.index');
    }

    /** Everything the wizard needs, plus the standing price. */
    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable();

        // The next eighteen months, so "when will you plant" is a tap.
        $months = [];
        $cursor = now('Asia/Manila')->startOfMonth();
        for ($i = 0; $i < 18; $i++) {
            $months[] = ['key' => $cursor->format('Y-m'), 'label' => $cursor->format('F Y'), 'short' => $cursor->format('M Y')];
            $cursor = $cursor->addMonth();
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'crops' => collect(CropCatalog::CROPS)->map(fn ($c, $key) => [
                'key' => $key,
                'label' => $c['label'],
                'icon' => $c['icon'],
                'group' => $c['group'],
                'maturity' => $c['maturity'] ?? null,
                'perennial' => CropCatalog::isPerennial($key),
                'isRice' => str_starts_with($key, 'rice'),
            ])->values(),
            'months' => $months,
            'methods' => self::METHODS,
            'priorities' => self::PRIORITIES,
            'yieldUnits' => self::YIELD_UNITS,
            'soils' => self::SOILS,
            'waters' => self::WATERS,
            'problems' => self::PROBLEMS,
            'quote' => $canUse ? (float) AiPrices::of('protocol') : null,
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

    /** Run the analysis — the sisters' job walk, the question swapped. */
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
            'crop' => 'required|in:' . implode(',', array_keys(CropCatalog::CROPS)),
            'variety' => 'nullable|string|max:80',
            'month' => 'required|date_format:Y-m',
            'method' => 'required|in:' . implode(',', array_keys(self::METHODS)),
            'priority' => 'required|in:' . implode(',', array_keys(self::PRIORITIES)),
            'targetYield' => 'nullable|numeric|min:0|max:100000',
            'yieldUnit' => 'nullable|in:' . implode(',', array_keys(self::YIELD_UNITS)),
            'area' => 'required|numeric|min:0.01|max:10000',
            'soil' => 'required|in:' . implode(',', array_keys(self::SOILS)),
            'water' => 'required|in:' . implode(',', array_keys(self::WATERS)),
            'problems' => 'nullable|array',
            'problems.*' => 'string|in:' . implode(',', array_keys(self::PROBLEMS)),
            'notes' => 'nullable|string|max:400',
        ]);
        if ($v->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $v->errors()], 422);
        }

        $price = AiPrices::of('protocol');
        $balance = $this->credits->balance($payer->id);
        if ($balance < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . $price . ' credits for this protocol and have '
                . number_format((int) floor($balance)) . '.',
                ['outOfCredits' => true], 402);
        }

        // One in flight at a time — a double press must not buy two.
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'protocol')
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(10))
            ->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }

        $p = $this->params($request);
        $crop = CropCatalog::CROPS[$p['crop']];
        $when = \Illuminate\Support\Carbon::parse($p['month'] . '-01')->format('M Y');
        $title = 'Protocol · ' . $crop['label'] . ($p['variety'] !== '' ? ' (' . $p['variety'] . ')' : '') . ' · ' . $when . ' · ' . $p['location'];
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(),
            'kind' => 'protocol',
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

        /* Should PHP itself be cut off mid-run, the row must not stay
         * "pending" -- that blocks every next run for ten minutes. */
        register_shutdown_function(function () use ($id) {
            try {
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed',
                    'error' => 'The protocol took too long and was stopped. Nothing was charged — please try again.',
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Nothing more to do at shutdown.
            }
        });

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $id,
            ]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $prompt, $p);
            exit;
        }

        @set_time_limit(900);
        $this->runJob($id, (int) $payer->id, $settings, $prompt, $p);

        return $this->jobState($id);
    }

    /** The research, the document, and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, string $prompt, array $p): void
    {
        try {
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p), $prompt, 7000, fn (string $t) => $this->parseReport($t));
            $report = $result['data'];
            if ($report === null) {
                \Illuminate\Support\Facades\Log::warning('crop-protocol: unparsable answer', [
                    'head' => mb_substr((string) $result['text'], 0, 400),
                ]);
                throw new \RuntimeException($result['error'] ?? 'The protocol came back unreadable. Nothing was charged — please try again.');
            }
            $report = $this->tidy($report, $p);
            $report['webSources'] = $result['sources'];
            $report['searched'] = (bool) $result['searched'];

            $charged = (float) AiPrices::of('protocol');
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $crop = CropCatalog::CROPS[$p['crop']] ?? ['label' => 'Crop'];
            $note = AiUsage::record('protocol', (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('Crop Protocol Analysis — ' . $crop['label'] . ', ' . $p['location'] . $note, 0, 250));

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

    /**
     * Numbers the page can chart, worked out here so a chart never lies
     * about a total: every fertilizer line gets a total for the field
     * from its per-hectare figure, and the program's totals per product
     * are added up from the lines rather than trusted from the model.
     */
    private function tidy(array $report, array $p): array
    {
        $area = max(0.01, (float) $p['area']);
        $totals = [];
        $program = [];
        foreach ((array) ($report['fertilizer']['program'] ?? []) as $step) {
            $products = [];
            foreach ((array) ($step['products'] ?? []) as $pr) {
                $perHa = max(0, (float) ($pr['bagsPerHa'] ?? 0));
                $total = round($perHa * $area, 1);
                $pr['bagsPerHa'] = $perHa;
                $pr['totalBags'] = $total;
                $products[] = $pr;
                $name = trim((string) ($pr['name'] ?? 'Fertilizer'));
                $totals[$name] = ($totals[$name] ?? 0) + $total;
            }
            $step['products'] = $products;
            $step['bags'] = round(array_sum(array_map(fn ($x) => (float) $x['totalBags'], $products)), 1);
            $program[] = $step;
        }
        $report['fertilizer']['program'] = $program;
        $report['fertilizer']['totals'] = collect($totals)->map(fn ($bags, $name) => ['name' => $name, 'bags' => round($bags, 1)])->values()->all();
        $report['fertilizer']['totalBags'] = round(array_sum($totals), 1);
        $report['area'] = $area;

        /* The shopping list's costs come back as anything from 7200 to
           "≈ ₱7,200" to "not published"; a number becomes pesos, words
           without a digit become nothing, and the whole list is added up. */
        $sum = 0.0;
        $known = 0;
        $items = [];
        foreach ((array) ($report['prepare'] ?? []) as $it) {
            $raw = trim((string) ($it['estCost'] ?? ''));
            $num = preg_match('/\d[\d,]*(\.\d+)?/', $raw, $m) ? (float) str_replace(',', '', $m[0]) : null;
            if ($num !== null && $num > 0) {
                $it['estCost'] = '≈ ₱'.number_format($num);
                $sum += $num;
                $known++;
            } else {
                $it['estCost'] = '';
            }
            $items[] = $it;
        }
        $report['prepare'] = $items;
        $report['prepareCost'] = $known ? ['sum' => '≈ ₱'.number_format($sum), 'known' => $known, 'of' => count($items)] : null;

        return $report;
    }

    /** Where a job stands — polled by the page until ready or failed. */
    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('id', $id)->where('kind', 'protocol')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That protocol is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            if (\Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(15))) {
                DB::table('as_plant_analyses')->where('id', $id)->update([
                    'status' => 'failed', 'deleteStatus' => 0,
                    'error' => 'The protocol took too long and was stopped. Nothing was charged — please try again.',
                    'updated_at' => now(),
                ]);

                return $this->json(false, 'The protocol took too long and was stopped. Nothing was charged — please try again.', ['status' => 'failed'], 502);
            }

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending']);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The protocol failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Protocol ready.', [
            'status' => 'ready',
            'savedId' => (int) $r->id,
            'report' => json_decode($r->report, true),
            'params' => json_decode($r->params, true),
            'charged' => (float) $r->credits,
            'balance' => round($this->credits->balance($this->payer()->id), 2),
        ]);
    }

    public function list()
    {
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'protocol')
            ->where('deleteStatus', 1)->where('status', 'ready')->orderByDesc('id')
            ->get(['id', 'title', 'description', 'credits', 'created_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($r) => [
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
            ->where('id', $id)->where('kind', 'protocol')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return $this->json(false, 'That protocol is gone.', [], 404);
        }

        return $this->json(true, 'ok', [
            'id' => $r->id,
            'title' => $r->title,
            'params' => json_decode($r->params, true),
            'report' => json_decode($r->report, true),
            'credits' => (float) $r->credits,
        ]);
    }

    /** What Anee reads when a protocol is attached to a chat. */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = DB::table('as_plant_analyses')->where('userId', $userId)
            ->where('id', $id)->where('kind', 'protocol')
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
        if (! $r) {
            return null;
        }
        $report = json_decode($r->report, true) ?: [];
        $params = json_decode($r->params, true) ?: [];
        $fert = collect($report['fertilizer']['program'] ?? [])->map(fn ($s) => ($s['stage'] ?? '') . ': '
            . collect($s['products'] ?? [])->map(fn ($x) => ($x['totalBags'] ?? '') . ' bags ' . ($x['name'] ?? ''))->implode(', '))->implode(' | ');
        $text = "\n\n--- ATTACHED: Crop Protocol Analysis (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Field: ' . ($params['area'] ?? '') . ' ha; method ' . (self::METHODS[$params['method'] ?? '']['label'] ?? '') . '; priority ' . (self::PRIORITIES[$params['priority'] ?? '']['label'] ?? '')
            . '; target ' . (($params['targetYield'] ?? null) ? $params['targetYield'] . ' ' . (self::YIELD_UNITS[$params['yieldUnit'] ?? 'cavans'] ?? '') : 'not set') . "\n"
            . 'Headline: ' . ($report['headline'] ?? '') . "\n"
            . 'Fertilizer by stage: ' . $fert . "\n"
            . 'Irrigation: ' . collect($report['irrigation'] ?? [])->map(fn ($x) => ($x['stage'] ?? '') . ' — ' . ($x['need'] ?? ''))->implode(' | ') . "\n"
            . 'Pests to watch: ' . collect($report['protection']['insects'] ?? [])->pluck('pest')->implode(', ') . "\n"
            . 'Diseases: ' . collect($report['protection']['diseases'] ?? [])->pluck('disease')->implode(', ') . "\n"
            . 'Weeds: ' . collect($report['protection']['weeds'] ?? [])->pluck('weed')->implode(', ') . "\n"
            . 'Summary: ' . ($report['summary'] ?? '') . "\n"
            . 'Stated confidence: ' . ($report['confidence'] ?? '') . '; data gaps: ' . collect($report['dataGaps'] ?? [])->implode('; ')
            . "\n--- END OF ATTACHED PROTOCOL ---\n";

        return ['title' => $r->title, 'text' => $text];
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)
            ->where('kind', 'protocol')
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Protocol removed.');
    }

    /** Rename a saved protocol and describe it in your own words. */
    public function meta(Request $request)
    {
        $title = trim((string) $request->input('title'));
        if ($title === '' || mb_strlen($title) > 191) {
            return $this->json(false, 'Give it a name up to 191 characters.', [], 422);
        }
        $description = trim((string) $request->input('description'));
        $updated = DB::table('as_plant_analyses')
            ->where('userId', Auth::id())
            ->where('kind', 'protocol')
            ->where('id', (int) $request->input('id'))
            ->where('deleteStatus', 1)
            ->update([
                'title' => $title,
                'description' => $description !== '' ? mb_substr($description, 0, 2000) : null,
                'updated_at' => now(),
            ]);
        if (! $updated) {
            return $this->json(false, 'That protocol is not on your shelf.', [], 404);
        }

        return $this->json(true, 'Protocol updated.', ['title' => $title, 'description' => $description ?: null]);
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
            'crop' => (string) $request->input('crop'),
            'variety' => trim((string) $request->input('variety', '')),
            'month' => (string) $request->input('month'),
            'method' => (string) $request->input('method'),
            'priority' => (string) $request->input('priority'),
            'targetYield' => $request->filled('targetYield') ? (float) $request->input('targetYield') : null,
            'yieldUnit' => in_array($request->input('yieldUnit'), array_keys(self::YIELD_UNITS), true) ? $request->input('yieldUnit') : 'cavans',
            'area' => (float) $request->input('area'),
            'soil' => (string) $request->input('soil'),
            'water' => (string) $request->input('water'),
            'problems' => array_values((array) $request->input('problems', [])),
            'notes' => trim((string) $request->input('notes', '')),
        ];
    }

    /** The coming days at the field, read from the forecast, in a line or two. */
    private function forecastLines(string $place): string
    {
        try {
            $fc = app(WeatherService::class)->forecastForPlace($place, 7);
        } catch (\Throwable $e) {
            $fc = null;
        }
        if (! $fc || empty($fc['days'])) {
            return 'not available — rely on the seasonal outlook for the region';
        }
        $days = collect($fc['days'])->map(fn ($d) => $d['label'] . ': ' . $d['text']
            . (isset($d['max'], $d['min']) ? ', ' . $d['min'] . '–' . $d['max'] . '°C' : '')
            . (isset($d['pop']) ? ', rain ' . $d['pop'] . '%' : ''))->implode('; ');

        return 'the next 7 days at ' . $fc['place'] . ' (Open-Meteo): ' . $days;
    }

    private function facts(array $p): array
    {
        $crop = CropCatalog::CROPS[$p['crop']];
        $when = \Illuminate\Support\Carbon::parse($p['month'] . '-01')->format('F Y');
        $target = $p['targetYield'] !== null
            ? rtrim(rtrim(number_format((float) $p['targetYield'], 2), '0'), '.') . ' ' . (self::YIELD_UNITS[$p['yieldUnit']] ?? 'per hectare')
            : 'not set — aim for what the variety realistically gives here';

        return [
            'crop' => $crop,
            'when' => $when,
            'target' => $target,
            'method' => self::METHODS[$p['method']]['label'] ?? $p['method'],
            'priority' => (self::PRIORITIES[$p['priority']]['label'] ?? $p['priority']) . ' — ' . (self::PRIORITIES[$p['priority']]['sub'] ?? ''),
            'soil' => self::SOILS[$p['soil']] ?? $p['soil'],
            'water' => self::WATERS[$p['water']] ?? $p['water'],
            'problems' => collect($p['problems'])->map(fn ($k) => self::PROBLEMS[$k] ?? $k)->implode('; ') ?: 'none reported',
            'variety' => $p['variety'] !== '' ? $p['variety'] : 'not named — assume a widely grown, well-documented variety for this crop, season and region, and SAY which you assumed',
            'area' => rtrim(rtrim(number_format((float) $p['area'], 2), '0'), '.'),
            'notes' => $p['notes'] !== '' ? $p['notes'] : 'none',
        ];
    }

    /**
     * The research brief: what to look up, with the web open, before the
     * protocol is written. Prose, not JSON -- asked for JSON the model
     * does not search (see AiClient::researchThenJson).
     */
    private function researchPrompt(array $p): string
    {
        $f = $this->facts($p);
        $year = now('Asia/Manila')->year;

        return <<<PROMPT
You are a research assistant for Philippine agronomy with web search. SEARCH THE WEB NOW and write research notes for a season protocol. Do not answer from memory alone; every finding must come from a page you read, with the source name and year beside it.

THE CASE
- Crop: {$f['crop']['label']}; variety: {$f['variety']}
- Planting: {$f['when']}, {$f['method']}, at {$p['location']}
- Field: {$f['area']} ha; soil {$f['soil']}; water: {$f['water']}; troubles: {$f['problems']}
- The farmer's aim: {$f['priority']}; target yield: {$f['target']}

FIND, IN THIS ORDER
1. The variety's published traits: days to maturity, yield potential and typical farm yields, recommended planting method and season, fertilizer response and any published fertilizer recommendation for it, resistance and tolerance ratings, known weaknesses (lodging, shattering, disease).
2. The official Philippine recommendations for this crop's nutrient management by growth stage in this region and season (DA / PhilRice PalayCheck, LCC and MOET guidance for rice; DA-BAR, UPLB, ATI, the regional DA office for other crops): kg N-P-K per hectare, split timing by stage, common products (urea 46-0-0, 14-14-14, 16-20-0, 0-0-60, muriate of potash, organic/biofertilizers), and the soil-specific adjustments for {$f['soil']} and for these troubles.
3. Integrated pest management for this crop in the region: the insects, diseases and weeds that matter by growth stage, their economic thresholds, the recommended controls (cultural, biological, chemical with active ingredients), and any current outbreak advisories.
4. Irrigation and water management by growth stage for this crop and method (e.g. alternate wetting and drying for rice), and what to change under drought or flooding.
5. PAGASA's seasonal climate outlook for the region for the months from planting to harvest (rainfall, temperature, tropical cyclone expectations) and the current ENSO advisory.
6. Current published prices of the main inputs in the Philippines where available (a bag of urea, complete, potash; common insecticides/fungicides), for a rough cost picture.

Prefer PhilRice, DA, BPI, NSIC, IRRI, UPLB, ATI, PCAARRD, PAGASA, FPA and reputable seed/agrochemical company pages. Note the year of each finding. Where something cannot be found online, say so plainly. Write plain prose notes under the six headings, at most 1,100 words, no JSON, no markdown tables.
PROMPT;
    }

    /** The question, spelled out so the answer is bounded, stage-based and honest. */
    private function prompt(array $p): string
    {
        $f = $this->facts($p);
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ENSO: ' . $enso . "\n" : '';
        $forecast = $this->forecastLines($p['location']);
        $unitWord = self::YIELD_UNITS[$p['yieldUnit']] ?? 'cavans per hectare';

        return <<<PROMPT
You are an agronomic decision-support analyst for Philippine farming. Write a SEMI-COMPLETE SEASON PROTOCOL for one field, hung on the crop's GROWTH STAGES — never on day counts. The farmer will act when the crop reaches a stage, as their own eyes tell them; the protocol says what to do at each stage and what to have ready.

FACTS GIVEN
- Location as the farmer wrote it: {$p['location']}
- Crop: {$f['crop']['label']}; variety: {$f['variety']}
- Planting: {$f['when']}; method: {$f['method']}
- Field size: {$f['area']} hectare(s)
- Soil, as the farmer describes it: {$f['soil']}; water: {$f['water']}
- Field troubles the farmer reports: {$f['problems']}
- The farmer's aim this season: {$f['priority']}
- Target yield: {$f['target']}
- The farmer's own notes: {$f['notes']}
- Weather now: {$forecast}
{$ensoBlock}
WHAT TO READ FROM
Research notes gathered from the web just now follow at the end of this brief (the variety's traits, the official nutrient and pest recommendations for this crop and region, water management, the seasonal outlook, input prices). Rely on them first, over memory; where they and memory disagree, the notes win; where a thing is neither in the notes nor established agronomy, say so in dataGaps rather than inventing it.

GROUND RULES
- Every quantity is for THIS field: give fertilizer per hectare (bagsPerHa, a 50-kg bag) and the model will scale to the field size; be specific with products and rates, and bend them to the aim (highest yield = the fuller recommended rate with a top-up; lower cost = the lean end, dropping what pays least; in the middle = the standard recommendation) and to the soil and troubles (acid soil, saline soil, poor drainage and the rest each change something).
- Stage names must be the crop's real stages in order (for rice e.g. Land preparation, Seedling/Nursery, Transplanting or Establishment, Tillering, Panicle initiation, Booting & heading, Flowering, Grain filling, Ripening & harvest; for corn Land preparation, Emergence, V4–V6, V8–V10, Tasseling & silking, Grain fill, Maturity; for vegetables the equivalent). Timing is said as the stage, with plain signs of it a farmer can see, plus a rough "about N weeks after planting" only as a hint.
- Insecticides, fungicides, herbicides and foliars: name them by what to look for and the threshold that justifies spending, then the product class or active ingredient; never spray-by-calendar. Say what to PREPARE (have in the shed) versus what to use only if the threshold is reached.
- Irrigation: what the crop needs at each stage and how to run the water with THIS water source, including what to do if the sky turns dry or wet.
- Yield: say plainly whether the target is realistic for this variety, place and season, and what realistic is.
- Be scientific and neutral: products as classes or actives, brands only where the notes name a specific registered product; no marketing tone. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"headline":"","assumed":{"variety":"","maturityDays":"","season":"","note":""},"stages":[{"stage":"","signs":"","hint":"","tasks":[""]}],"fertilizer":{"program":[{"stage":"","timing":"","products":[{"name":"","bagsPerHa":0,"why":""}],"note":""}],"note":""},"protection":{"insects":[{"pest":"","stage":"","watchFor":"","threshold":"","action":"","prepare":""}],"diseases":[{"disease":"","stage":"","watchFor":"","action":"","prepare":""}],"weeds":[{"weed":"","when":"","control":""}]},"foliar":[{"product":"","stage":"","why":"","optional":true}],"irrigation":[{"stage":"","need":"","how":"","ifDry":"","ifWet":""}],"weather":{"outlook":"","enso":"","risks":[""]},"yieldOutlook":{"target":"","realistic":"","note":""},"prepare":[{"item":"","qty":"","unit":"","whenNeeded":"","estCost":""}],"watch":[""],"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- headline: one line, ≤ 16 words, the season in a breath.
- assumed: the variety actually used for the numbers (the farmer's, or the one you assumed — say so in note), its maturityDays, the season this planting falls in.
- stages: SIX to TEN stages in order; signs = what the farmer sees when the crop is there (≤ 25 words); hint = a rough "about week N–M after transplanting/sowing" (WEEKS, never days — no DAS/DAT anywhere in this document) or "n/a"; tasks = 2–5 short lines of what to do then (fertilizer, water, scouting, weeding, harvest prep).
- fertilizer.program: one entry per application, in stage order; timing = the stage and the visible sign to apply at (e.g. "when the seedlings have settled and new leaves show", "at the first sign of the panicle inside the stem"), never a day count; each product with name (e.g. "Urea 46-0-0", "Complete 14-14-14", "Ammonium sulfate 21-0-0", "Muriate of potash 0-0-60", "Organic fertilizer"), bagsPerHa (a number, 50-kg bags per hectare; use decimals), why ≤ 20 words; note ≤ 40 words on the whole program (kg N-P-K per ha, the LCC/MOET check if rice).
- protection: insects 3–6, diseases 2–5, weeds 2–4, each with the stage it matters (a stage name, not a day count), what to look for, the threshold (insects), the action, and what to prepare.
- foliar: 0–4 entries; optional true when it only pays under the highest-yield aim.
- irrigation: one entry per stage that needs a decision, with ifDry / ifWet contingencies ≤ 20 words each.
- weather: outlook ≤ 60 words for the months from planting to harvest; enso ≤ 40 words; risks 2–5 short items.
- yieldOutlook: target = the farmer's target restated in {$unitWord} (or "not set"); realistic = what this variety realistically gives here, in the same unit; note ≤ 40 words.
- prepare: the shopping list for the whole field, 6–14 items (seed, every fertilizer product with total bags, the sprays to have on hand, foliars, tools/water if notable), qty as a number, unit, whenNeeded as a stage, estCost = the estimated peso cost of that quantity as a plain number (e.g. 7200) or "" when you truly cannot estimate it.
- watch: 5–8 short lines — the things that go wrong here and the sign to act on.
- confidence "low"/"moderate"/"high"; dataGaps at most five; summary ≤ 110 words, plain and warm but factual, ending with the reminder that the calendar is only a hint and the crop's stage is the clock.
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
        if (! is_array($json) || ! isset($json['stages'], $json['fertilizer'], $json['summary'])) {
            return null;
        }
        if (! is_array($json['stages']) || count($json['stages']) < 3) {
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
