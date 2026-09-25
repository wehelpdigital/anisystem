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

    /**
     * The ground, closer (2026-09-18): the answers that decide WHICH
     * granular and WHEN — a sodic clay and a sandy loam do not want the
     * same bag at the same moment. Every one is optional.
     */
    /* A soil can be two of these at once -- alkaline AND sodic is the usual
       pair, saline AND alkaline another -- so the answer is a list. The pH
       words (acidic / neutral / alkaline) exclude one another; sodium and
       salt are their own axes and combine with any pH. See soilConditions(). */
    public const SOIL_CONDITIONS = [
        'unsure' => 'Not sure / never tested',
        'acidic' => 'Acidic — low pH (moss, ferns, poor legumes)',
        'neutral' => 'Around neutral',
        'alkaline' => 'Alkaline — high pH (pale young leaves, white crust)',
        'sodic' => 'Sodic — high sodium (crusts, seals, water sits, dispersive)',
        'saline' => 'Saline — salty (white crust, burnt leaf tips, brackish water)',
    ];

    public const TEST_LEVELS = [
        'unsure' => 'Unknown',
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    public const PREV_CROPS = [
        'unsure' => 'Not sure / first season',
        'rice' => 'Rice',
        'corn' => 'Corn or another grain',
        'legume' => 'A legume (mungbean, peanut, soybean, beans)',
        'vegetables' => 'Vegetables',
        'root' => 'A root crop',
        'fallow' => 'Fallow / nothing',
    ];

    public const RESIDUES = [
        'unsure' => 'Not sure',
        'removed' => 'Taken off the field',
        'burned' => 'Burned',
        'incorporated' => 'Ploughed in / left to rot',
    ];

    /** The granulars a farmer can usually buy, by grade. */
    /** The same products, spelt out for the prompt. */
    public const GRANULAR_WORDS = [
        'ammosul' => 'ammonium sulfate 21-0-0',
        'ammophos' => 'ammonium phosphate 16-20-0',
        'triple14' => 'complete with sulfur 14-14-14-S',
        'npk_other' => 'other NPK blends sold locally (17-0-17, 16-16-16, 12-12-17 and the like)',
    ];

    public const GRANULARS = [
        'urea' => 'Urea 46-0-0',
        'ammosul' => 'Ammosul 21-0-0',
        'complete' => 'Complete 14-14-14',
        'ammophos' => 'Ammophos 16-20-0',
        'dap' => 'DAP 18-46-0',
        'mop' => 'Muriate of potash 0-0-60',
        'sop' => 'Sulfate of potash 0-0-50',
        'solophos' => 'Solophos 0-18-0',
        'triple14' => 'Triple 14-S (14-14-14-S)',
        'npk_other' => 'Other NPK blends',
    ];

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

    /**
     * The soil conditions as a clean list: known keys only, 'unsure' dropped,
     * one pH word at most (the last one named wins), sodic and saline free to
     * ride with it. A string (the old single answer) is one item.
     */
    public static function soilConditions(mixed $raw): array
    {
        $keys = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? [$raw] : []);
        $out = [];
        $ph = null;
        foreach ($keys as $k) {
            $k = (string) $k;
            if ($k === 'unsure' || ! array_key_exists($k, self::SOIL_CONDITIONS)) {
                continue;
            }
            if (in_array($k, ['acidic', 'neutral', 'alkaline'], true)) {
                $ph = $k;
                continue;
            }
            $out[$k] = true;
        }
        $list = array_keys($out);
        if ($ph) {
            array_unshift($list, $ph);
        }

        return array_slice($list, 0, 3);
    }

    /** The tier wall: the analyses are Anee's, and Anee comes with Libre + Anee and up. */
    private function guardTier(): void
    {
        if (! \App\Support\Tier::farmCan('aiAnalyses')) {
            \App\Support\Tier::farmDenyFor('aiAnalyses', 'The Crop Protocol Analysis comes with {plan}, and with every plan above it.');
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
            'soilConditions' => self::SOIL_CONDITIONS,
            'testLevels' => self::TEST_LEVELS,
            'prevCrops' => self::PREV_CROPS,
            'residues' => self::RESIDUES,
            'granulars' => self::GRANULARS,
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
            // The ground, closer — every one optional.
            'soilCondition' => 'nullable|in:' . implode(',', array_keys(self::SOIL_CONDITIONS)),
            'soilConditions' => 'nullable|array|max:4',
            'soilConditions.*' => 'string|in:' . implode(',', array_keys(self::SOIL_CONDITIONS)),
            'testP' => 'nullable|in:' . implode(',', array_keys(self::TEST_LEVELS)),
            'testK' => 'nullable|in:' . implode(',', array_keys(self::TEST_LEVELS)),
            'prevCrop' => 'nullable|in:' . implode(',', array_keys(self::PREV_CROPS)),
            'residue' => 'nullable|in:' . implode(',', array_keys(self::RESIDUES)),
            'granulars' => 'nullable|array',
            'granulars.*' => 'string|max:24',
            'fertHistory' => 'nullable|string|max:200',
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
            $result = $this->ai->researchThenJson($settings, $this->researchPrompt($p), $prompt, 10000, fn (string $t) => $this->parseReport($t), $beat);
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
     * A fertilizer's N-P2O5-K2O analysis, read off its name: the grade in
     * the name ("14-14-14", "16-20-0") when it carries one, else the common
     * products by their common names. Null when nothing is recognised, so a
     * product the arithmetic cannot read is named rather than counted as 0.
     *
     * @return array{0:float,1:float,2:float}|null
     */
    public static function grade(string $name): ?array
    {
        if (preg_match('/(\d{1,2}(?:\.\d)?)\s*-\s*(\d{1,2}(?:\.\d)?)\s*-\s*(\d{1,2}(?:\.\d)?)/', $name, $m)) {
            return [(float) $m[1], (float) $m[2], (float) $m[3]];
        }
        $n = mb_strtolower($name);
        foreach ([
            ['urea', [46, 0, 0]],
            ['ammonium sulfate', [21, 0, 0]], ['ammonium sulphate', [21, 0, 0]], ['ammosul', [21, 0, 0]],
            ['ammonium phosphate', [16, 20, 0]], ['ammophos', [16, 20, 0]],
            ['diammonium phosphate', [18, 46, 0]], ['dap', [18, 46, 0]],
            ['muriate of potash', [0, 0, 60]], ['potassium chloride', [0, 0, 60]], ['mop', [0, 0, 60]],
            ['sulfate of potash', [0, 0, 50]], ['potassium sulfate', [0, 0, 50]], ['potassium sulphate', [0, 0, 50]],
            ['solophos', [0, 18, 0]], ['single superphosphate', [0, 18, 0]], ['triple superphosphate', [0, 46, 0]],
            ['complete', [14, 14, 14]],
        ] as [$word, $grade]) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $n)) {
                return $grade;
            }
        }

        return null;
    }

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
        // Organic applications are not part of the protocol (the owner's
        // ask, 2026-09-18): one that slips past the prompt is dropped here.
        $organic = fn (string $name) => (bool) preg_match('/organic|compost|manure|vermi|bio-?fert|guano|chicken dung/i', $name);
        foreach ((array) ($report['recommendation']['stages'] ?? []) as $st) {
            $apps = [];
            foreach ((array) ($st['fertilizer'] ?? []) as $ap) {
                if ($organic((string) ($ap['product'] ?? ''))) {
                    continue;
                }
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
        $stages = $report['recommendation']['stages'];
        // No seed on the list either: how much a farmer sows is their own call.
        $seed = fn (string $name) => (bool) preg_match('/\bseeds?\b|seedling|\bbinhi\b|planting material|cuttings?\b|tubers? for planting/i', $name);
        // No supplies list at all (2026-09-18): seed is the farmer's own call and
        // a spray's amount is the label's — the only quantities are the bags.
        $report['recommendation']['supplies'] = [];
        unset($seed);

        /* The nutrient check: what the program actually delivers, from each
         * product's own analysis (46-0-0 is 46% N; a bag is 50 kg), against
         * what the model says the target needs. The model can mis-add; the
         * arithmetic here cannot, so the farmer sees the gap if there is one. */
        $delivered = ['n' => 0.0, 'p' => 0.0, 'k' => 0.0];
        $unknown = [];
        foreach ($report['recommendation']['stages'] as $st) {
            foreach ((array) ($st['fertilizer'] ?? []) as $ap) {
                $grade = self::grade((string) ($ap['product'] ?? ''));
                if ($grade === null) {
                    $unknown[] = (string) ($ap['product'] ?? '');
                    continue;
                }
                $kg = (float) $ap['bagsPerHa'] * 50;
                $delivered['n'] += $kg * $grade[0] / 100;
                $delivered['p'] += $kg * $grade[1] / 100;
                $delivered['k'] += $kg * $grade[2] / 100;
            }
        }
        $npk = (array) ($report['recommendation']['npk'] ?? []);
        $need = (array) ($npk['need'] ?? []);

        /* FIVE PROGRAMS (2026-09-18): each is a flat list of applications
         * naming a stage; here every program is laid onto the stages by
         * name, scaled to the field, totalled per product and checked
         * against the need — the same arithmetic the recommended program
         * gets, so the tabs compare like with like. The recommended program
         * (the first) is what the stages carry; where the model gave one,
         * the stages take its applications so the two never disagree. */
        /* FOLIARS BY NUTRIENT (2026-09-18): the owner wants "Zinc spray", never
         * "zinc sulfate heptahydrate 1%". The prompt says so; this makes sure.
         * A product string is read for the nutrients it names and rewritten as
         * "<Nutrient> spray" (two nutrients: "Zinc and boron spray"); a string
         * naming none is kept but stripped of salts and strengths. */
        $nutrientSpray = function (string $product): string {
            $t = mb_strtolower($product);
            $names = ['zinc' => 'Zinc', 'boron' => 'Boron', 'calcium' => 'Calcium', 'magnesium' => 'Magnesium', 'iron' => 'Iron', 'manganese' => 'Manganese',
                'copper' => 'Copper', 'molybdenum' => 'Molybdenum', 'sulfur' => 'Sulfur', 'sulphur' => 'Sulfur', 'potassium' => 'Potassium', 'phosph' => 'Phosphorus',
                'nitrogen' => 'Nitrogen', 'urea' => 'Nitrogen', 'silicon' => 'Silicon', 'seaweed' => 'Seaweed', 'amino' => 'Amino acid', 'micronutrient' => 'Micronutrient'];
            $found = [];
            foreach ($names as $needle => $label) {
                if (str_contains($t, $needle) && ! in_array($label, $found, true)) {
                    $found[] = $label;
                }
            }
            if ($found) {
                $found = array_slice($found, 0, 2);
                $head = count($found) === 2 ? $found[0] . ' and ' . mb_strtolower($found[1]) : $found[0];

                return $head . ' spray';
            }
            // Nothing recognised: at least no salt, no strength, no "or equivalent".
            $clean = preg_replace('/\b(sulfate|sulphate|heptahydrate|monohydrate|chelate|chelated|edta|solubor|borax|boric acid|nitrate|chloride|oxide|hydroxide)\b/iu', '', $product);
            $clean = preg_replace('/\d+(\.\d+)?\s*%/u', '', $clean);
            $clean = preg_replace('/\s*or equivalent\.?$/iu', '', trim($clean));
            $clean = trim(preg_replace('/\s{2,}/', ' ', $clean));

            return $clean !== '' ? $clean : 'Foliar spray';
        };
        if (! empty($report['recommendation']['foliars']) && is_array($report['recommendation']['foliars'])) {
            $report['recommendation']['foliars'] = array_values(array_map(function ($fol) use ($nutrientSpray) {
                if (is_array($fol) && ! empty($fol['product'])) {
                    $fol['product'] = $nutrientSpray((string) $fol['product']);
                }

                return $fol;
            }, $report['recommendation']['foliars']));
        }

        $norm = fn (string $t) => trim(preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($t)));
        $stageIndex = function (string $name) use ($stages, $norm): int {
            $n = $norm($name);
            foreach ($stages as $i => $st) {
                if ($norm((string) ($st['stage'] ?? '')) === $n) {
                    return $i;
                }
            }
            foreach ($stages as $i => $st) {
                $s = $norm((string) ($st['stage'] ?? ''));
                if ($s !== '' && $n !== '' && (str_contains($s, $n) || str_contains($n, $s))) {
                    return $i;
                }
            }

            return -1;
        };
        $checkOf = function (array $byStage) use ($need, $area) {
            $delivered = ['n' => 0.0, 'p' => 0.0, 'k' => 0.0];
            $unknown = [];
            foreach ($byStage as $apps) {
                foreach ($apps as $ap) {
                    $grade = self::grade((string) ($ap['product'] ?? ''));
                    if ($grade === null) {
                        $unknown[] = (string) ($ap['product'] ?? '');
                        continue;
                    }
                    $kg = (float) $ap['bagsPerHa'] * 50;
                    $delivered['n'] += $kg * $grade[0] / 100;
                    $delivered['p'] += $kg * $grade[1] / 100;
                    $delivered['k'] += $kg * $grade[2] / 100;
                }
            }
            $rows = [];
            foreach (['n' => 'N', 'p' => 'P₂O₅', 'k' => 'K₂O'] as $key => $label) {
                $have = round($delivered[$key]);
                $want = max(0, (float) ($need[$key] ?? 0));
                $gap = $want > 0 ? $have - $want : null;
                $rows[] = [
                    'key' => $key, 'label' => $label,
                    'have' => $have, 'need' => $want > 0 ? round($want) : null,
                    'haveField' => round($delivered[$key] * $area), 'needField' => $want > 0 ? round($want * $area) : null,
                    'verdict' => $gap === null ? 'unchecked' : (abs($gap) <= max(3, $want * 0.1) ? 'ok' : ($gap < 0 ? 'short' : 'over')),
                    'gap' => $gap === null ? null : round($gap),
                ];
            }

            return ['delivered' => ['n' => round($delivered['n']), 'p' => round($delivered['p']), 'k' => round($delivered['k'])], 'check' => $rows, 'unknownProducts' => array_values(array_unique(array_filter($unknown)))];
        };
        $programs = [];
        foreach (array_slice((array) ($report['recommendation']['programs'] ?? []), 0, 5) as $pi => $pg) {
            $byStage = array_fill(0, count($stages), []);
            $orphans = [];
            foreach ((array) ($pg['applications'] ?? []) as $ap) {
                if ($organic((string) ($ap['product'] ?? ''))) {
                    continue;
                }
                $perHa = max(0, (float) ($ap['bagsPerHa'] ?? 0));
                $row = ['product' => trim((string) ($ap['product'] ?? 'Fertilizer')) ?: 'Fertilizer', 'bagsPerHa' => $perHa, 'totalBags' => round($perHa * $area, 1), 'purpose' => (string) ($ap['purpose'] ?? '')];
                $i = $stageIndex((string) ($ap['stage'] ?? ''));
                if ($i < 0) {
                    $orphans[] = $row + ['stage' => (string) ($ap['stage'] ?? '')];
                    continue;
                }
                $byStage[$i][] = $row;
            }
            // An application naming a stage the list does not have is still
            // an application: it counts in the totals and the check, and the
            // page shows it under "other applications".
            $tot = [];
            foreach (array_merge($byStage, [$orphans]) as $apps) {
                foreach ($apps as $row) {
                    $tot[$row['product']] = ($tot[$row['product']] ?? 0) + $row['bagsPerHa'];
                }
            }
            $programs[] = [
                'name' => trim((string) ($pg['name'] ?? '')) ?: ('Option ' . ($pi + 1)),
                'for' => (string) ($pg['for'] ?? ''),
                'byStage' => $byStage,
                'stageBags' => array_map(fn ($apps) => round(array_sum(array_map(fn ($r) => $r['totalBags'], $apps)), 1), $byStage),
                'totals' => collect($tot)->map(fn ($perHa, $name) => ['product' => $name, 'bagsPerHa' => round($perHa, 1), 'bags' => round($perHa * $area, 1)])->values()->all(),
                'totalBags' => round(array_sum($tot) * $area, 1),
                'orphans' => $orphans,
            ] + $checkOf(array_merge($byStage, [$orphans]));
        }
        if ($programs) {
            // The recommended program is what the stages carry.
            foreach ($stages as $i => &$st) {
                $st['fertilizer'] = $programs[0]['byStage'][$i];
                $st['bags'] = $programs[0]['stageBags'][$i];
            }
            unset($st);
            $report['recommendation']['stages'] = $stages;
            $totals = [];
            foreach ($programs[0]['totals'] as $t) {
                $totals[$t['product']] = $t['bagsPerHa'];
            }
            $report['recommendation']['totals'] = $programs[0]['totals'];
            $report['recommendation']['totalBags'] = $programs[0]['totalBags'];
            $delivered = array_map(fn ($v) => (float) $v, $programs[0]['delivered']);
            $unknown = $programs[0]['unknownProducts'];
        }
        $report['recommendation']['programs'] = $programs;
        $check = [];
        foreach (['n' => 'N', 'p' => 'P₂O₅', 'k' => 'K₂O'] as $key => $label) {
            $have = round($delivered[$key]);
            $want = max(0, (float) ($need[$key] ?? 0));
            $gap = $want > 0 ? $have - $want : null;
            $check[] = [
                'key' => $key, 'label' => $label,
                'have' => $have, 'need' => $want > 0 ? round($want) : null,
                'haveField' => round($delivered[$key] * $area), 'needField' => $want > 0 ? round($want * $area) : null,
                // within a tenth of the need either way is "on target"
                'verdict' => $gap === null ? 'unchecked' : (abs($gap) <= max(3, $want * 0.1) ? 'ok' : ($gap < 0 ? 'short' : 'over')),
                'gap' => $gap === null ? null : round($gap),
            ];
        }
        $npk['delivered'] = ['n' => round($delivered['n']), 'p' => round($delivered['p']), 'k' => round($delivered['k'])];
        $npk['check'] = $check;
        $npk['unknownProducts'] = array_values(array_unique(array_filter($unknown)));
        $report['recommendation']['npk'] = $npk;
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
            ->get(['id', 'title', 'description', 'tags', 'credits', 'created_at']);

        return $this->json(true, 'ok', ['rows' => $rows->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'description' => $r->description,
            'tags' => array_values(array_filter((array) (json_decode((string) ($r->tags ?? ''), true) ?: []), 'is_string')),
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
            $fert = collect($rec['stages'] ?? [])->filter(fn ($st) => ! empty($st['fertilizer']))->map(fn ($st) => ($st['stage'] ?? '') . (! empty($st['days']) ? ' (' . $st['days'] . ')' : '') . ': '
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
                . (! empty($rec['granulars']['soilLogic']) ? 'Why these granulars: ' . $rec['granulars']['soilLogic'] . ' ' . ($rec['granulars']['timingLogic'] ?? '') . "\n" : '')
                . (! empty($rec['granulars']['rejected']) ? 'Set aside: ' . collect($rec['granulars']['rejected'])->map(fn ($r) => ($r['what'] ?? '') . ' — ' . ($r['why'] ?? ''))->implode('; ') . "\n" : '')
                . (! empty($rec['programs']) ? 'Fertilizer programs offered: ' . collect($rec['programs'])->map(fn ($g) => ($g['name'] ?? '') . ' (' . ($g['totalBags'] ?? '') . ' bags)')->implode(', ') . ' — the first is the recommended one below.' . "\n" : '')
                . 'Fertilizer by stage: ' . $fert . "\n"
                . 'Totals for the field: ' . collect($rec['totals'] ?? [])->map(fn ($t) => ($t['bags'] ?? '') . ' bags ' . ($t['product'] ?? ''))->implode(', ') . "\n"
                . (! empty($rec['npk']['check']) ? 'Nutrients per hectare (program delivers / target needs): ' . collect($rec['npk']['check'])->map(fn ($c) => $c['label'] . ' ' . $c['have'] . ($c['need'] !== null ? ' / ' . $c['need'] . ' kg (' . $c['verdict'] . ')' : ' kg'))->implode('; ') . "\n" : '')
                . 'Observe / intervene: ' . $watch . "\n"
                . 'Threats: ' . collect($rec['threats'] ?? [])->map(fn ($t) => ($t['threat'] ?? '') . ' at ' . ($t['stage'] ?? '') . ' — ' . ($t['action'] ?? '') . (! empty($t['product']) ? ' (' . $t['product'] . ')' : ''))->implode(' | ') . "\n"
                . (! empty($rec['deficiencies']) ? 'Deficiencies this soil invites: ' . collect($rec['deficiencies'])->map(fn ($d) => ($d['nutrient'] ?? '') . ' (' . ($d['when'] ?? '') . ') — ' . ($d['why'] ?? ''))->implode(' | ') . "\n" : '')
                . (! empty($rec['foliars']) ? 'Foliars: ' . collect($rec['foliars'])->map(fn ($f) => ($f['stage'] ?? '') . ': ' . ($f['product'] ?? '') . ' — ' . ($f['why'] ?? ''))->implode(' | ') . "\n" : '')
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
                'tags' => json_encode(\App\Http\Controllers\UserTagController::tidy($request->input('tags', []))),
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
            'soilConditions' => self::soilConditions($request->input('soilConditions', $request->input('soilCondition'))),
            'testP' => array_key_exists((string) $request->input('testP'), self::TEST_LEVELS) ? (string) $request->input('testP') : 'unsure',
            'testK' => array_key_exists((string) $request->input('testK'), self::TEST_LEVELS) ? (string) $request->input('testK') : 'unsure',
            'prevCrop' => array_key_exists((string) $request->input('prevCrop'), self::PREV_CROPS) ? (string) $request->input('prevCrop') : 'unsure',
            'residue' => array_key_exists((string) $request->input('residue'), self::RESIDUES) ? (string) $request->input('residue') : 'unsure',
            'granulars' => WhatToPlantController::picks(self::GRANULARS, $request->input('granulars')),
            'fertHistory' => trim((string) preg_replace('/\s+/', ' ', (string) $request->input('fertHistory', ''))),
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
        // The app's own stage table for this crop and count, so the document's
        // day numbers agree with what the Growth Stages module will say.
        $counter = self::counterFor((string) $p['method'], (string) $p['crop']);
        $table = \App\Support\CropStages::stagesFor($p['crop'], $counter, null);
        $rows = [];
        foreach (array_values($table) as $i => $st) {
            $from = (int) $st[0];
            $until = isset($table[$i + 1]) ? (int) $table[$i + 1][0] - 1 : null;
            $rows[] = $st[1] . ' ' . $counter . ' ' . $from . ($until !== null ? '–' . $until : '+');
        }
        $stageTable = $rows ? implode('; ', $rows) : 'none on file — use the crop\'s published stage timing';
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
            'soilCondition' => (function () use ($p) {
                // The list, or the single word protocols before 2026-09-19 saved.
                $list = self::soilConditions($p['soilConditions'] ?? ($p['soilCondition'] ?? null));

                return $list
                    ? collect($list)->map(fn ($k) => self::SOIL_CONDITIONS[$k] ?? $k)->implode('; AND ')
                    : 'not tested / not stated — read it from the troubles and the region';
            })(),
            'testP' => (($p['testP'] ?? 'unsure') !== 'unsure') ? (self::TEST_LEVELS[$p['testP']] ?? $p['testP']) : 'not known',
            'testK' => (($p['testK'] ?? 'unsure') !== 'unsure') ? (self::TEST_LEVELS[$p['testK']] ?? $p['testK']) : 'not known',
            'prevCrop' => (($p['prevCrop'] ?? 'unsure') !== 'unsure') ? (self::PREV_CROPS[$p['prevCrop']] ?? $p['prevCrop']) : 'not stated',
            'residue' => (($p['residue'] ?? 'unsure') !== 'unsure') ? (self::RESIDUES[$p['residue']] ?? $p['residue']) : 'not stated',
            'granulars' => ! empty($p['granulars']) ? collect($p['granulars'])->map(fn ($k) => self::GRANULAR_WORDS[$k] ?? self::GRANULARS[$k] ?? $k)->implode(', ') : 'not stated — assume the common products sold in the region',
            'fertHistory' => (($p['fertHistory'] ?? '') !== '') ? $p['fertHistory'] : 'not stated',
            'variety' => $p['variety'] !== '' ? $p['variety'] : 'not named — assume a widely grown, well-documented variety for this crop, season and region, and SAY which you assumed',
            'area' => rtrim(rtrim(number_format((float) $p['area'], 2), '0'), '.'),
            'notes' => $p['notes'] !== '' ? $p['notes'] : 'none',
            'counter' => $counter,
            'stageTable' => $stageTable,
        ];
    }

    /**
     * The count a crop is managed by for a planting method: days after
     * transplanting for a crop set out as seedlings, after sowing for one
     * seeded in place, after planting for sets, cuttings and tubers.
     */
    public static function counterFor(string $method, string $crop): string
    {
        // The crop's own count first (corn is DAP even when sown in place),
        // bent by the method: seedlings set out → DAT; a crop the app counts
        // from transplanting but sown in place → DAS; the rest → DAP.
        $own = \App\Support\CropStages::counter($crop);
        if (in_array($method, ['transplanted', 'nursery', 'tree_seedlings'], true)) {
            return 'DAT';
        }
        if (str_starts_with($method, 'direct')) {
            return $own === 'DAT' ? 'DAS' : ($own === 'AGE' ? 'DAP' : $own);
        }

        return in_array($own, ['DAT', 'AGE'], true) ? 'DAP' : $own;
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
2. The official recommendations in {$rCountry} for this crop's nutrient management by growth stage in this region and season ({$rNutrient}): kg N-P-K per hectare, split timing by stage, common inorganic products (urea 46-0-0, 14-14-14, 16-20-0, 0-0-60, muriate of potash), and the soil-specific adjustments for {$f['soil']} and for these troubles.
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
- The count this crop is managed by: {$f['counter']} (days after transplanting / sowing / planting). The app's own stage table for it: {$f['stageTable']}
- Field size: {$f['area']} hectare(s)
- Soil, as the farmer describes it: {$f['soil']}; water: {$f['water']}
- Soil condition (pH / sodium / salt), as far as the farmer knows — more than one can be true at once, and a combination (an alkaline sodic soil, a saline alkaline soil) is to be reasoned about AS a combination: {$f['soilCondition']}
- Soil test or history, phosphorus: {$f['testP']}; potassium: {$f['testK']}
- The previous crop: {$f['prevCrop']}; its residue was: {$f['residue']}
- What the field got last season, in the farmer's words: {$f['fertHistory']}
- Granulars the farmer can buy locally: {$f['granulars']}
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
- Every quantity is for THIS field: give fertilizer per hectare (bagsPerHa, a 50-kg bag) and the app scales it to the field size; be specific with products and rates, and bend them to the aim (highest yield = the fuller recommended rate with a top-up; lower cost = the lean end, dropping what pays least; in the middle = the standard recommendation) and to the soil and troubles. Each application carries its PURPOSE in a few words (what the plant does with it at that stage, and why THIS product).
- GRANULARS, THOUGHT THROUGH. Which bag, not just how many: choose each product for the soil condition and the water regime, and say why in the purpose and in granulars.soilLogic. A sodic or saline soil does not want a chloride load — muriate of potash (0-0-60) only where potassium is truly short and then late and light, sulfate of potash where it can be bought; ammonium sulfate is the nitrogen that also acidifies an alkaline or sodic soil and supplies sulfur, and gypsum-type sulfate helps sodium leach; an acid soil wants its phosphorus placed (ammophos 16-20-0 or DAP 18-46-0 at planting) because it is fixed fast, and less ammonium sulfate; flooded rice loses urea nitrogen to the air and the water, so it is applied in small splits into shallow water or incorporated, never broadcast onto a full flood; a sandy soil leaks nitrogen and potassium, so smaller, more frequent doses; a heavy clay holds them, so fewer, fuller ones; complete 14-14-14 is a convenience, not a prescription — where the soil test or history says phosphorus or potassium is already high, do not buy it again. Use the granulars the farmer can buy; where a better product is not on that list, say so in cautions and build the program from what is. REASON IN THE OPEN: soilLogic and timingLogic are the agronomist thinking aloud, mechanism by mechanism — what the sodium does to the clay and what chloride does on top of it, what happens to urea broadcast on a flood, why phosphorus placed at planting beats phosphorus broadcast on this ground, what the burned straw or the legume left behind and how that changed the first dressing. Then granulars.rejected: the two to four things a farmer here would reasonably have done instead — the product on the shelf (complete 14-14-14 alone, muriate of potash), the big early dressing, potassium all up front, urea onto standing water — each named in `what` and answered in `why` with the mechanism that goes wrong on THIS ground, not a generality.
- CRITICALITY BY STAGE. Size every application to what the crop can take up THEN. Do not dump: a young crop with a small root system cannot use eight bags of anything, and nitrogen it cannot use leaches, volatilises or burns. Basal at planting is a starter — phosphorus and a little nitrogen and potassium where the roots will be — and the bulk of nitrogen goes where the crop builds yield (tillering and panicle initiation in rice; V6–V8 and before tasselling in corn; branching and fruit set in vegetables). Potassium goes where straw strength and grain fill are decided (panicle initiation, pre-tasselling, fruit set), not all up front, and on a sodic soil not before the crop can drink it. Keep any single nitrogen dressing within the safe range for the crop (rice about 1–2 bags of urea per hectare per split, corn about 2–3), and say the ceiling in timingLogic. Where the previous crop was a legume or the residue was ploughed in, credit the nitrogen it left and cut the first dressing; where the residue was burned, potassium came back but nitrogen did not.
- FIVE PROGRAMS. Give the farmer five fertilizer programs to choose between, each a complete season of applications for the same stages, each honest about what it is for: the first is your recommendation (the one the stages above carry); the others are genuinely different strategies — e.g. a soil-first program (sulfate nitrogen and placed phosphorus for this soil condition), a lean low-cost program, a highest-yield program with the top-up, a simple program from complete alone for a farmer who wants one product, an LCC / soil-test-guided program, a locally-available-only program — pick the five that make sense HERE. Every program must add up to the need for its own aim (the app checks each one against the target's need and shows the shortfall), name its products from the farmer's list where given, and carry a one-line "for" that says who should choose it.
- Stage names must be the crop's real stages in order, following the app's stage table above (split or merge a stage only where the crop truly needs it, keeping the numbers consistent; for corn say the V-stages too, e.g. "Early vegetative (V4–V6)"). EVERY stage carries its day count in `days`: the count and the range, e.g. "DAT 35–49", "DAS 0–7", "DAP 45–54", the last stage "DAT 90+", and land preparation "before DAT 0" — always the numbers, never weeks alone. Adjust the ranges to the variety's own maturity where the notes give it. Timing in words is the stage and a plain sign of it; the days are the second clock.
- OBSERVE AND INTERVENE: at each stage say in one line what to look for, and in one line what to do only if it is seen (the threshold, then the class or active ingredient) — never spray by calendar. Leave both empty at a stage with nothing to watch.
- NO PRICES ANYWHERE. Totals are quantities only.
- NO ORGANIC FERTILIZER: the program is inorganic products only (urea, complete, ammonium sulfate, ammonium phosphate, muriate of potash and the like). Do not recommend organic fertilizer, compost, manure, vermicast or biofertilizer at any stage, and do not list them among the supplies.
- Water: one short plan for the season with THIS water source, and what changes if the sky turns dry or wet.
- Yield: say plainly whether the target is realistic for this variety, place and season, and what realistic is.
- PRODUCTS, NAMED. Where a spray, drench or foliar is called for, name a product the farmer can ask for: a registered product sold in {$countryName} with its active ingredient or content in brackets, ending with the words "or equivalent" (e.g. "Padan 50 SP (cartap hydrochloride) or equivalent", "Sofit 300 EC (pretilachlor) or equivalent"). Prefer the products the notes name; where none is named, give the active ingredient and still say "or equivalent". This is a name to ask for, not a recommendation to buy a brand — no marketing tone. FOLIARS ARE THE EXCEPTION: a foliar is named by its nutrient only — "Zinc spray", "Boron spray", "Calcium spray", "Magnesium spray", "Zinc and boron spray" — never the salt, the chemical or the strength (no "zinc sulfate", no "heptahydrate", no "solubor", no "chelate", no percentages): the farmer asks the store for a zinc foliar and the store knows.
- DEFICIENCIES BY SOIL: analyse deeply which nutrient deficiencies THIS soil type is prone to for THIS crop — the heavy clay that locks zinc under flooding, the sandy ground that leaks nitrogen, potassium and magnesium, the acid soil that starves phosphorus and calcium and frees aluminium, the alkaline or limed soil that hides iron, zinc and manganese, the drained or saline ground with its own hunger — and for each: the nutrient by name, WHY this soil and this crop invite it, the SIGN the farmer sees on the plant, WHEN in the season it shows, and what to do about it in plain words. No amounts, no rates and no product or chemical names here — the nutrient, the reason and the sign are the point.
- NO AMOUNTS FOR SPRAYS: for insecticides, fungicides, herbicides, molluscicides and foliars name the product (as above) and the moment, never a rate, a dose, a litre or a kilo — the dose is the label's and the farmer's own. The only quantities in this protocol are the fertilizer bags.
- FOLIARS: only where they pay for this crop, soil and aim — a micronutrient the soil or crop is known to lack (zinc on flooded or alkaline rice, boron and calcium on fruiting vegetables, magnesium on sandy ground) or a growth foliar the official guides accept — with the stage, the product and why. None when none pays.
- Weeds are threats too: where weeds matter for this crop and method, one threat is the weed pressure, with the herbicide to use (pre- or post-emergence) named as above.
- Plain text only: no emoji shortcodes (nothing like :anee-…:), no markdown.

Return ONLY a valid JSON object — no code fences, no commentary — in exactly this shape:
{"headline":"","background":{"place":"","field":"","weather":{"outlook":"","enso":"","risks":[""]},"variety":{"found":false,"name":"","by":"","released":"","maturityDays":0,"yieldPotential":"","season":"","traits":"","caution":"","source":""}},"recommendation":{"intro":"","granulars":{"soilLogic":"","timingLogic":"","rejected":[{"what":"","why":""}],"cautions":[""]},"stages":[{"stage":"","days":"","signs":"","fertilizer":[{"product":"","bagsPerHa":0,"purpose":""}],"observe":"","intervene":""}],"programs":[{"name":"","for":"","applications":[{"stage":"","product":"","bagsPerHa":0,"purpose":""}]}],"npk":{"need":{"n":0,"p":0,"k":0},"note":""},"deficiencies":[{"nutrient":"","why":"","signs":"","when":"","action":""}],"water":{"plan":"","ifDry":"","ifWet":""},"foliars":[{"stage":"","product":"","why":""}],"threats":[{"threat":"","stage":"","sign":"","action":"","product":""}],"yield":{"target":"","realistic":"","note":""}},"confidence":"moderate","dataGaps":[""],"summary":""}
Rules for the shape:
- headline: one line, ≤ 14 words, the season in a breath.
- background.place: ≤ 40 words — the location, its climate zone and the season this planting falls in. background.field: ≤ 30 words — the soil, the water and the troubles in one breath, and what they ask of the protocol.
- background.weather: outlook ≤ 55 words for the months from planting to harvest; enso ≤ 30 words; risks 2–4 items of ≤ 12 words.
- background.variety: found true only when the notes carry real published specifications; name as published; by = breeder / company / institution; released = year or ""; maturityDays a number (0 when unknown); yieldPotential in words with the unit (e.g. "6–8 t/ha; farms average 4.5"); season = the season it is bred for; traits ≤ 55 words (strengths and weaknesses that matter here); caution ≤ 25 words (its known weakness on this ground, or ""); source = the registry, breeder or agency the notes cite. When nothing was found: found false, name = the variety assumed, traits says why it was assumed.
- recommendation.granulars: soilLogic 90–160 words — why THESE products for this soil condition, water and history, mechanism by mechanism (chloride, sodium, pH, fixation, leaching, volatilisation, what the previous crop and its residue left); timingLogic 90–160 words — why the splits are sized and timed this way (what the root system can take up at each stage and what a bigger dose would do instead — leach, burn, volatilise, lock out; the safe ceiling per dressing in bags; when potassium is wanted and why not sooner); rejected 2–4 items — `what` ≤ 12 words naming the road not taken, `why` ≤ 45 words with the mechanism that fails on this ground; cautions 2–4 items ≤ 18 words (the things that go wrong with granulars on this ground — burning, lockout, leaching, a product that should not be bought).
- recommendation.programs: EXACTLY FIVE, in order, the first being the recommended program whose applications equal the stages' fertilizer above. Each: name ≤ 4 words (e.g. "Soil-first sulfate", "Lean and cheap", "Full yield push", "Complete only", "LCC-guided"), for ≤ 22 words, applications = every application of the season as {stage (exactly one of the stage names above), product, bagsPerHa, purpose ≤ 14 words}, 3–10 applications each.
- recommendation.intro: ≤ 80 words — the approach for this field and aim, and the one or two things that matter most this season.
- recommendation.stages: SIX to TEN stages in order; days = the count and range as defined above (always given); signs ≤ 20 words; fertilizer = the applications at that stage (empty list when none), each with product (e.g. "Urea 46-0-0", "Complete 14-14-14", "Ammonium sulfate 21-0-0", "Ammonium phosphate 16-20-0", "Muriate of potash 0-0-60"), bagsPerHa (a number, 50-kg bags per hectare; decimals allowed), purpose ≤ 14 words; observe ≤ 25 words or ""; intervene ≤ 30 words or "" (the product and the moment, no rate).
- recommendation.npk.need: the kg N, P2O5 and K2O per hectare this variety needs to reach the TARGET yield on this ground (the official recommendation for the region and season, bent to the soil and the aim; where the target is unrealistic, the need for the realistic yield instead) — and the fertilizer program above MUST add up to it: the app totals the program's nutrients from each product's analysis and shows the farmer where it falls short. note ≤ 30 words (the LCC/MOET check if rice, or the soil-test caveat).
- recommendation.deficiencies: 2–5 items, the deficiencies this soil type and crop are prone to, most likely first: nutrient (e.g. "Zinc"), why ≤ 30 words (this soil, this crop), signs ≤ 25 words (what the plant shows), when = the stage or stages it shows, action ≤ 30 words in plain words with no amounts and no product names.
- recommendation.water: plan ≤ 60 words; ifDry ≤ 25 words; ifWet ≤ 25 words.
- recommendation.foliars: 0–4 items, each with the stage, the product as the nutrient only ("Zinc spray", "Boron spray", "Calcium spray" — no chemical, no salt, no strength) and why ≤ 16 words; [] when none pays.
- recommendation.threats: 4–7 items — the insects, diseases and weeds most likely to hurt here, each with the stage it strikes, the sign to act on (≤ 14 words), the action (≤ 18 words) and product = the spray or herbicide to use for it, named as above ending "or equivalent" ("" only for a threat that no product answers, like heat).
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
