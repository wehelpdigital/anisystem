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
    /**
     * Every way a crop goes into the ground. Which of these a crop can
     * choose from is the crop's own business (METHODS_BY_CROP below): corn
     * is never raised in a nursery, cassava is never sown from seed, and a
     * mango comes from a grafted seedling or not at all.
     */
    public const METHODS = [
        'transplanted' => ['label' => 'Transplanted', 'sub' => 'Seedlings raised in a seedbed, then set out in puddled paddies', 'icon' => '🌱'],
        'direct_wet' => ['label' => 'Direct-seeded, wet', 'sub' => 'Pre-germinated seed broadcast on puddled soil', 'icon' => '💧'],
        'direct_dry' => ['label' => 'Direct-seeded, dry', 'sub' => 'Dry seed sown into dry or moist soil', 'icon' => '🌤️'],
        'direct' => ['label' => 'Direct-seeded', 'sub' => 'Seed sown straight into the field', 'icon' => '🌱'],
        'nursery' => ['label' => 'From seedlings', 'sub' => 'Raised in a seedbed or seedling tray, then transplanted', 'icon' => '🪴'],
        'sets' => ['label' => 'From sets, bulbs or cloves', 'sub' => 'Small bulbs or cloves planted straight in', 'icon' => '🧅'],
        'cuttings' => ['label' => 'From cuttings', 'sub' => 'Stem cuttings, vine cuttings or setts planted straight in', 'icon' => '🌿'],
        'tubers' => ['label' => 'From tubers or rhizomes', 'sub' => 'Seed tubers, corms, setts or rhizome pieces planted in', 'icon' => '🥔'],
        'suckers' => ['label' => 'From suckers or slips', 'sub' => 'Suckers, slips, crowns or clump divisions from a mother plant', 'icon' => '🌴'],
        'sprouted' => ['label' => 'From a sprouted fruit', 'sub' => 'The whole fruit planted once it has shot', 'icon' => '🍈'],
        'tree_seedlings' => ['label' => 'From nursery seedlings', 'sub' => 'Grafted, budded, tissue-cultured or seed-grown planting material', 'icon' => '🌳'],
    ];

    /**
     * Which methods each crop can choose from, the usual way first. A
     * catalogue key this table does not name falls to its group in
     * METHODS_BY_GROUP.
     */
    public const METHODS_BY_CROP = [
        'rice' => ['transplanted', 'direct_wet', 'direct_dry'],
        'rice_upland' => ['direct_dry'],
        'corn_yellow' => ['direct'], 'corn_sweet' => ['direct'], 'corn_glutinous' => ['direct'], 'sorghum' => ['direct'],
        'mungbean' => ['direct'], 'peanut' => ['direct'], 'soybean' => ['direct'], 'stringbean' => ['direct'],
        'cowpea' => ['direct'], 'wingedbean' => ['direct'], 'limabean' => ['direct'], 'pigeonpea' => ['direct'],
        'sweetpotato' => ['cuttings'], 'cassava' => ['cuttings'], 'taro' => ['tubers', 'suckers'], 'ubi' => ['tubers'],
        'potato' => ['tubers'], 'carrot' => ['direct'], 'radish' => ['direct'], 'ginger' => ['tubers'], 'turmeric' => ['tubers'],
        'pechay' => ['direct', 'nursery'], 'cabbage' => ['nursery'], 'lettuce' => ['nursery', 'direct'], 'kangkong' => ['direct', 'cuttings'],
        'mustard' => ['direct', 'nursery'], 'broccoli' => ['nursery'], 'cauliflower' => ['nursery'], 'alugbati' => ['cuttings', 'direct'],
        'saluyot' => ['direct'], 'celery' => ['nursery'],
        'tomato' => ['nursery', 'direct'], 'eggplant' => ['nursery'], 'ampalaya' => ['direct', 'nursery'], 'squash' => ['direct'],
        'cucumber' => ['direct', 'nursery'], 'okra' => ['direct'], 'chili' => ['nursery'], 'bellpepper' => ['nursery'],
        'patola' => ['direct'], 'upo' => ['direct'], 'sayote' => ['sprouted'], 'watermelon' => ['direct', 'nursery'], 'melon' => ['direct', 'nursery'],
        'onion' => ['nursery', 'direct', 'sets'], 'onion_spring' => ['direct', 'sets'], 'garlic' => ['sets'], 'shallot' => ['sets'],
        'sugarcane' => ['cuttings'], 'pineapple' => ['suckers'], 'tobacco' => ['nursery'], 'cotton' => ['direct'],
        'abaca' => ['suckers', 'tree_seedlings'], 'rubber' => ['tree_seedlings'], 'oilpalm' => ['tree_seedlings'], 'bamboo' => ['cuttings', 'suckers'],
        'banana' => ['suckers', 'tree_seedlings'], 'papaya' => ['nursery', 'direct'],
        'coconut' => ['tree_seedlings'], 'dragonfruit' => ['cuttings'], 'coffee' => ['tree_seedlings'], 'cacao' => ['tree_seedlings'],
        'malunggay' => ['cuttings', 'tree_seedlings'], 'strawberry' => ['suckers', 'nursery'], 'vegetables' => ['nursery', 'direct'],
    ];

    public const METHODS_BY_GROUP = [
        'Cereals & grains' => ['direct'],
        'Legumes' => ['direct'],
        'Root crops' => ['tubers', 'cuttings'],
        'Leafy vegetables' => ['direct', 'nursery'],
        'Fruit vegetables' => ['nursery', 'direct'],
        'Onions & garlic' => ['nursery', 'direct', 'sets'],
        'Industrial crops' => ['nursery', 'cuttings'],
        'Fruit trees' => ['tree_seedlings'],
        'Other' => ['nursery', 'direct'],
    ];

    /** @return list<string> the method keys this crop may pick from, the usual one first */
    public static function methodsFor(string $cropKey): array
    {
        if (isset(self::METHODS_BY_CROP[$cropKey])) {
            return self::METHODS_BY_CROP[$cropKey];
        }
        $group = CropCatalog::CROPS[$cropKey]['group'] ?? '';

        return self::METHODS_BY_GROUP[$group] ?? ['nursery', 'direct'];
    }

    /** What the farmer is chasing this season. */
    public const PRIORITIES = [
        'yield' => ['label' => 'Highest yield', 'sub' => 'Spend what it takes for the biggest harvest', 'icon' => '🌾'],
        'balanced' => ['label' => 'In the middle', 'sub' => 'A good harvest without overspending', 'icon' => '⚖️'],
        'cost' => ['label' => 'Lower cost', 'sub' => 'The leanest inputs that still make a decent crop', 'icon' => '💰'],
    ];

    /** Cavans and tons at home, tons and kilograms elsewhere — per hectare (App\Support\Region). */
    public static function yieldUnits(): array
    {
        $out = [];
        foreach ((array) \App\Support\Region::get('yieldUnits', ['tons' => 'tons']) as $k => $word) {
            $out[$k] = $word . ' per hectare';
        }

        return $out ?: ['tons' => 'tons per hectare'];
    }

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
            'crops' => collect(CropCatalog::visible())->map(fn ($c, $key) => [
                'key' => $key,
                'label' => $c['label'],
                'icon' => $c['icon'],
                'group' => $c['group'],
                'maturity' => $c['maturity'] ?? null,
                'perennial' => CropCatalog::isPerennial($key),
                'methods' => self::methodsFor($key),
            ])->values(),
            'months' => $months,
            'methods' => self::METHODS,
            'runner' => PHP_SAPI . (function_exists('fastcgi_finish_request') ? '+finish' : ''),
            'priorities' => self::PRIORITIES,
            'yieldUnits' => self::yieldUnits(),
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
            'yieldUnit' => 'nullable|in:' . implode(',', array_keys(self::yieldUnits())),
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
        /* The method must be one this crop actually uses -- the page only
           offers those, but the door checks too. */
        if (! in_array($request->input('method'), self::methodsFor($request->input('crop')), true)) {
            return $this->json(false, 'That is not a way this crop is planted.', ['errors' => ['method' => ['Pick a method that suits the crop.']]], 422);
        }

        $price = AiPrices::of('protocol');
        $balance = $this->credits->balance($payer->id);
        if ($balance < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . $price . ' credits for this protocol and have '
                . number_format((int) floor($balance)) . '.',
                ['outOfCredits' => true], 402);
        }

        // One in flight at a time — a double press must not buy two. A row
        // whose heart has stopped is not in flight; it is failed here so it
        // cannot block the next run.
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'protocol')
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(15))
            ->orderByDesc('id')->first();
        if ($standing && ! $this->dead($standing)) {
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
        /* The heartbeat: before every call to the model the row says which
         * phase it is in and touches updated_at. No single call may run
         * longer than the document timeout, so a row that has not beaten
         * for longer than that was killed under it (a deploy, a restart,
         * the process manager) and jobState can say so instead of leaving
         * the farmer waiting. */
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                'report' => json_encode(['phase' => $phase, 'try' => $try]),
                'updated_at' => now(),
            ]);
        };
        try {
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p), $prompt, 7000, fn (string $t) => $this->parseReport($t), $beat);
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
    /**
     * The field's numbers: every application scaled from bags per hectare
     * to bags for THIS field, the per-stage sums, and the season's totals
     * per product. Quantities only — the protocol carries no prices.
     */
    private function tidy(array $report, array $p): array
    {
        $area = max(0.01, (float) $p['area']);
        $totals = [];
        $stages = [];
        foreach ((array) ($report['recommendation']['stages'] ?? []) as $st) {
            $apps = [];
            foreach ((array) ($st['fertilizer'] ?? []) as $ap) {
                $perHa = max(0, (float) ($ap['bagsPerHa'] ?? 0));
                $ap['bagsPerHa'] = $perHa;
                $ap['totalBags'] = round($perHa * $area, 1);
                $apps[] = $ap;
                $name = trim((string) ($ap['product'] ?? 'Fertilizer')) ?: 'Fertilizer';
                $totals[$name] = ($totals[$name] ?? 0) + $perHa;
            }
            $st['fertilizer'] = $apps;
            $st['bags'] = round(array_sum(array_map(fn ($x) => (float) $x['totalBags'], $apps)), 1);
            $stages[] = $st;
        }
        $report['recommendation']['stages'] = $stages;
        $report['recommendation']['totals'] = collect($totals)
            ->map(fn ($perHa, $name) => ['product' => $name, 'bagsPerHa' => round($perHa, 1), 'bags' => round($perHa * $area, 1)])
            ->values()->all();
        $report['recommendation']['totalBags'] = round(array_sum($totals) * $area, 1);
        $report['area'] = $area;
        $report['format'] = 2;

        return $report;
    }

    /**
     * A pending row is dead when its heart has not beaten for longer than
     * any one call to the model may take (the document timeout plus a
     * generous minute), or when it is simply too old. Killed processes
     * write nothing, so this is the only way to tell.
     */
    private function dead(object $r): bool
    {
        $beatAt = \Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at);
        $quiet = AiClient::TIMEOUT_DOCUMENT + 60;

        return $beatAt->lt(now()->subSeconds($quiet))
            || \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(15));
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
            if ($this->dead($r)) {
                $why = \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(15))
                    ? 'The protocol took too long and was stopped. Nothing was charged — please try again.'
                    : 'The protocol was interrupted mid-way (the server restarted under it). Nothing was charged — please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed', 'deleteStatus' => 0,
                    'error' => $why,
                    'updated_at' => now(),
                ]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', [
                'pending' => true, 'id' => (int) $r->id, 'status' => 'pending',
                'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1),
                'since' => (int) max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($r->created_at), true)),
                // Seconds since the job last spoke: the page's own hang check.
                'beatAgo' => (int) max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at), true)),
            ]);
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
        $rec = $report['recommendation'] ?? null;
        if (is_array($rec)) {
            // The two-part protocol (2026-09-18).
            $bg = $report['background'] ?? [];
            $v = $bg['variety'] ?? [];
            $fert = collect($rec['stages'] ?? [])->filter(fn ($st) => ! empty($st['fertilizer']))->map(fn ($st) => ($st['stage'] ?? '') . ': '
                . collect($st['fertilizer'])->map(fn ($x) => ($x['totalBags'] ?? '') . ' bags ' . ($x['product'] ?? '') . (! empty($x['purpose']) ? ' (' . $x['purpose'] . ')' : ''))->implode(', '))->implode(' | ');
            $watch = collect($rec['stages'] ?? [])->filter(fn ($st) => ! empty($st['observe']))->map(fn ($st) => ($st['stage'] ?? '') . ': ' . $st['observe'] . (! empty($st['intervene']) ? ' → ' . $st['intervene'] : ''))->implode(' | ');
            $text = "\n\n--- ATTACHED: Crop Protocol Analysis (the farmer generated this earlier; treat it as shared context) ---\n"
                . 'Case: ' . $r->title . "\n"
                . 'Field: ' . ($params['area'] ?? '') . ' ha; method ' . (self::METHODS[$params['method'] ?? '']['label'] ?? '') . '; priority ' . (self::PRIORITIES[$params['priority'] ?? '']['label'] ?? '')
                . '; target ' . (($params['targetYield'] ?? null) ? $params['targetYield'] . ' ' . (self::yieldUnits()[$params['yieldUnit'] ?? ''] ?? '') : 'not set') . "\n"
                . 'Headline: ' . ($report['headline'] ?? '') . "\n"
                . 'Background — place: ' . ($bg['place'] ?? '') . ' Field: ' . ($bg['field'] ?? '') . "\n"
                . 'Weather ahead: ' . ($bg['weather']['outlook'] ?? '') . (! empty($bg['weather']['enso']) ? ' ENSO: ' . $bg['weather']['enso'] : '') . "\n"
                . 'Variety: ' . ($v['name'] ?? '') . (! empty($v['by']) ? ' by ' . $v['by'] : '') . (! empty($v['maturityDays']) ? ', ' . $v['maturityDays'] . ' days' : '') . (! empty($v['yieldPotential']) ? ', ' . $v['yieldPotential'] : '') . ' — ' . ($v['traits'] ?? '') . "\n"
                . 'Approach: ' . ($rec['intro'] ?? '') . "\n"
                . 'Fertilizer by stage: ' . $fert . "\n"
                . 'Totals for the field: ' . collect($rec['totals'] ?? [])->map(fn ($t) => ($t['bags'] ?? '') . ' bags ' . ($t['product'] ?? ''))->implode(', ') . "\n"
                . 'Observe / intervene: ' . $watch . "\n"
                . 'Threats: ' . collect($rec['threats'] ?? [])->map(fn ($t) => ($t['threat'] ?? '') . ' at ' . ($t['stage'] ?? '') . ' — ' . ($t['action'] ?? ''))->implode(' | ') . "\n"
                . 'Water: ' . ($rec['water']['plan'] ?? '') . "\n"
                . 'Yield: target ' . ($rec['yield']['target'] ?? '') . ', realistic ' . ($rec['yield']['realistic'] ?? '') . "\n"
                . 'Summary: ' . ($report['summary'] ?? '') . "\n"
                . 'Stated confidence: ' . ($report['confidence'] ?? '') . '; data gaps: ' . collect($report['dataGaps'] ?? [])->implode('; ')
                . "\n--- END OF ATTACHED PROTOCOL ---\n";

            return ['title' => $r->title, 'text' => $text];
        }
        $fert = collect($report['fertilizer']['program'] ?? [])->map(fn ($s) => ($s['stage'] ?? '') . ': '
            . collect($s['products'] ?? [])->map(fn ($x) => ($x['totalBags'] ?? '') . ' bags ' . ($x['name'] ?? ''))->implode(', '))->implode(' | ');
        $text = "\n\n--- ATTACHED: Crop Protocol Analysis (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $r->title . "\n"
            . 'Field: ' . ($params['area'] ?? '') . ' ha; method ' . (self::METHODS[$params['method'] ?? '']['label'] ?? '') . '; priority ' . (self::PRIORITIES[$params['priority'] ?? '']['label'] ?? '')
            . '; target ' . (($params['targetYield'] ?? null) ? $params['targetYield'] . ' ' . (self::yieldUnits()[$params['yieldUnit'] ?? ''] ?? '') : 'not set') . "\n"
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
            'yieldUnit' => in_array($request->input('yieldUnit'), array_keys(self::yieldUnits()), true) ? $request->input('yieldUnit') : array_key_first(self::yieldUnits()),
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
        $cropLabel = CropCatalog::label($p['crop']);
        $crop = CropCatalog::CROPS[$p['crop']];
        $when = \Illuminate\Support\Carbon::parse($p['month'] . '-01')->format('F Y');
        $target = $p['targetYield'] !== null
            ? rtrim(rtrim(number_format((float) $p['targetYield'], 2), '0'), '.') . ' ' . (self::yieldUnits()[$p['yieldUnit']] ?? 'per hectare')
            : 'not set — aim for what the variety realistically gives here';

        return [
            'cropLabel' => $cropLabel,
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

        $rCountry = \App\Support\Region::name();
        $rNutrient = \App\Support\Region::agency('nutrient');
        $rMet = \App\Support\Region::agency('met');
        $rSources = \App\Support\Region::agency('sources');
        $rRegion = \App\Support\Region::promptBlock();

        return <<<PROMPT
You are a research assistant for agronomy in {$rCountry} with web search. {$rRegion} SEARCH THE WEB NOW and write research notes for a season protocol. Do not answer from memory alone; every finding must come from a page you read, with the source name and year beside it.

THE CASE
- Crop: {$f['cropLabel']}; variety: {$f['variety']}
- Planting: {$f['when']}, {$f['method']}, at {$p['location']}
- Field: {$f['area']} ha; soil {$f['soil']}; water: {$f['water']}; troubles: {$f['problems']}
- The farmer's aim: {$f['priority']}; target yield: {$f['target']}

FIND, IN THIS ORDER
1. THE VARIETY FIRST: its published specifications — days to maturity, yield potential and typical farm yields, plant height and lodging, the season and method it is bred for, its fertilizer response and any published fertilizer recommendation for it, resistance and tolerance ratings (pests, diseases, drought, flooding, salinity, heat), known weaknesses, who released it and when. Prefer the registry, the breeder's or seed company's own page, and extension notes. If the variety cannot be found, say so plainly and describe the widely grown variety you would assume instead.
2. The official recommendations in {$rCountry} for this crop's nutrient management by growth stage in this region and season ({$rNutrient}): kg N-P-K per hectare, split timing by stage, common products (urea 46-0-0, 14-14-14, 16-20-0, 0-0-60, muriate of potash, organic/biofertilizers), and the soil-specific adjustments for {$f['soil']} and for these troubles.
3. Integrated pest management for this crop in the region: the insects, diseases and weeds that matter by growth stage, their economic thresholds, the recommended controls (cultural, biological, chemical with active ingredients), and any current outbreak advisories.
4. Irrigation and water management by growth stage for this crop and method (e.g. alternate wetting and drying for rice), and what to change under drought or flooding.
5. The seasonal climate outlook from {$rMet} for the region for the months from planting to harvest (rainfall, temperature, severe-weather expectations) and the current ENSO advisory.

Prefer {$rSources}. Note the year of each finding. Where something cannot be found online, say so plainly. Write plain prose notes under the five headings, at most 1,000 words, no JSON, no markdown tables.
PROMPT;
    }

    /** The question, spelled out so the answer is bounded, stage-based and honest. */
    private function prompt(array $p): string
    {
        $f = $this->facts($p);
        $enso = \App\Support\EnsoOutlook::forPrompt();
        $ensoBlock = $enso !== '' ? '- ENSO: ' . $enso . "\n" : '';
        $forecast = $this->forecastLines($p['location']);
        $unitWord = self::yieldUnits()[$p['yieldUnit']] ?? array_values(self::yieldUnits())[0];
        $countryName = \App\Support\Region::name();
        $regionBlock = \App\Support\Region::promptBlock();

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$countryName}. Write a SEASON PROTOCOL for one field in TWO PARTS a farmer reads in five minutes: first the BACKGROUND (the place, the weather ahead, the variety as published), then the RECOMMENDATION (an introduction, then what to apply at each growth stage and why, what to observe and when to step in, the totals to use, the threats to check). Hung on the crop's GROWTH STAGES — never on day counts: the farmer acts when the crop reaches a stage, as their own eyes tell them.

FACTS GIVEN
- {$regionBlock}
- Location as the farmer wrote it: {$p['location']}
- Crop: {$f['cropLabel']}; variety: {$f['variety']}
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
Research notes gathered from the web just now follow at the end of this brief (the variety's published specifications first, then the official nutrient and pest recommendations for this crop and region, water management, the seasonal outlook). Rely on them first, over memory; where they and memory disagree, the notes win; where a thing is neither in the notes nor established agronomy, say so in dataGaps rather than inventing it.

GROUND RULES
- EASY TO READ. Short plain sentences a farmer reads easily; no jargon without a plain word beside it; nothing repeated between the two parts. Every word limit below is a ceiling, not a target.
- THE VARIETY comes from the notes: name, breeder, maturity, yield potential, the season it is bred for, its strengths and weaknesses. Where the farmer's variety could not be found, say so (found false) and name the variety you assumed for the numbers.
- Every quantity is for THIS field: give fertilizer per hectare (bagsPerHa, a 50-kg bag) and the app scales it to the field size; be specific with products and rates, and bend them to the aim (highest yield = the fuller recommended rate with a top-up; lower cost = the lean end, dropping what pays least; in the middle = the standard recommendation) and to the soil and troubles. Each application carries its PURPOSE in a few words (what the plant does with it at that stage).
- Stage names must be the crop's real stages in order (for rice e.g. Land preparation, Seedling/Nursery, Transplanting or Establishment, Tillering, Panicle initiation, Booting & heading, Flowering, Grain filling, Ripening & harvest; for corn Land preparation, Emergence, V4–V6, V8–V10, Tasseling & silking, Grain fill, Maturity; for vegetables the equivalent). Timing is the stage and a plain sign of it, plus "about week N–M" only as a hint (WEEKS, never days — no DAS/DAT anywhere).
- OBSERVE AND INTERVENE: at each stage say in one line what to look for, and in one line what to do only if it is seen (the threshold, then the class or active ingredient) — never spray by calendar. Leave both empty at a stage with nothing to watch.
- NO PRICES ANYWHERE. Totals are quantities only.
- Water: one short plan for the season with THIS water source, and what changes if the sky turns dry or wet.
- Yield: say plainly whether the target is realistic for this variety, place and season, and what realistic is.
- Be scientific and neutral: products as classes or actives, brands only where the notes name a specific registered product; no marketing tone. Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"headline":"","background":{"place":"","field":"","weather":{"outlook":"","enso":"","risks":[""]},"variety":{"found":false,"name":"","by":"","released":"","maturityDays":0,"yieldPotential":"","season":"","traits":"","caution":"","source":""}},"recommendation":{"intro":"","stages":[{"stage":"","signs":"","hint":"","fertilizer":[{"product":"","bagsPerHa":0,"purpose":""}],"observe":"","intervene":""}],"npk":{"n":0,"p":0,"k":0,"note":""},"supplies":[{"item":"","qty":0,"unit":"","when":""}],"water":{"plan":"","ifDry":"","ifWet":""},"threats":[{"threat":"","stage":"","sign":"","action":""}],"yield":{"target":"","realistic":"","note":""}},"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- headline: one line, ≤ 14 words, the season in a breath.
- background.place: ≤ 40 words — the location, its climate zone and the season this planting falls in. background.field: ≤ 30 words — the soil, the water and the troubles in one breath, and what they ask of the protocol.
- background.weather: outlook ≤ 55 words for the months from planting to harvest; enso ≤ 30 words; risks 2–4 items of ≤ 12 words.
- background.variety: found true only when the notes carry real published specifications; name as published; by = breeder / company / institution; released = year or ""; maturityDays a number (0 when unknown); yieldPotential in words with the unit (e.g. "6–8 t/ha; farms average 4.5"); season = the season it is bred for; traits ≤ 55 words (strengths and weaknesses that matter here); caution ≤ 25 words (its known weakness on this ground, or ""); source = the registry, breeder or agency the notes cite. When nothing was found: found false, name = the variety assumed, traits says why it was assumed.
- recommendation.intro: ≤ 80 words — the approach for this field and aim, and the one or two things that matter most this season.
- recommendation.stages: SIX to TEN stages in order; signs ≤ 20 words; hint = "about week N–M after transplanting/sowing" or "n/a"; fertilizer = the applications at that stage (empty list when none), each with product (e.g. "Urea 46-0-0", "Complete 14-14-14", "Ammonium sulfate 21-0-0", "Muriate of potash 0-0-60", "Organic fertilizer"), bagsPerHa (a number, 50-kg bags per hectare; decimals allowed), purpose ≤ 14 words; observe ≤ 25 words or ""; intervene ≤ 30 words or "".
- recommendation.npk: the season's kg N, P2O5 and K2O per hectare that the program adds up to, note ≤ 30 words (the LCC/MOET check if rice, or the soil-test caveat).
- recommendation.supplies: 4–8 items to have for the whole field beyond the fertilizer (seed with kg, the one or two sprays to keep on hand by class, organic matter, tools if notable), qty a number, unit, when = a stage. Quantities only.
- recommendation.water: plan ≤ 60 words; ifDry ≤ 25 words; ifWet ≤ 25 words.
- recommendation.threats: 3–6 items — the things most likely to go wrong here, each with the stage it strikes, the sign to act on (≤ 14 words) and the action (≤ 18 words).
- recommendation.yield: target = the farmer's target restated in {$unitWord} (or "not set"); realistic = what this variety realistically gives here, same unit; note ≤ 30 words.
- confidence "low"/"moderate"/"high"; dataGaps at most four; summary ≤ 70 words, plain and warm but factual, ending with the reminder that the calendar is only a hint and the crop's stage is the clock.
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
        if (! is_array($json) || ! isset($json['background'], $json['recommendation'], $json['summary'])) {
            return null;
        }
        $stages = $json['recommendation']['stages'] ?? null;
        if (! is_array($stages) || count($stages) < 3) {
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
