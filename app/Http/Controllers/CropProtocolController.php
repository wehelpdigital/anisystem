<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\WeatherService;
use App\Support\AiPrices;
use App\Support\AiPricing;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\FieldProfile;
use App\Support\NpkModel;
use App\Support\NpkNeed;
use App\Support\NpkPlan;
use App\Support\ProtocolCare;
use App\Support\ProtocolCrew;
use App\Support\Tier;
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
 *
 * SEVERAL LOTS (2026-10-18, the owner: "just like the land prep module, you
 * can select multiple lots and for each lot you select what crop to plant,
 * the variety, month to plant, and how will you plant it, as well as the goal
 * and yield ... add a question on how many workers you have ... auto adjust
 * schedule if there's conflicting or warn if # of workers is lacking"). The
 * walk starts from the farm's lots (Global Lots); each lot brings its place,
 * size and field answers (its soil test too, so phosphorus and potassium are
 * never asked), and picks its own crop, way in, variety, month (a day if
 * known), goal and target. The report (format 4) is the whole farm and every
 * lot:
 *  - the need of each lot is NPK Plus's (App\Support\NpkNeed), every program
 *    checked against it, and what still falls short closed by the app
 *    (App\Support\ProtocolCare: straight fertilizer for N, P and K, the usual
 *    source or a spray for each secondary and micro element);
 *  - protection by stage for each crop from our field catalogue, Anee choosing
 *    what to watch and when it pays to act, the products from our product
 *    book, with the farmer's own call said plainly (ProtocolCare::DISCLAIMER);
 *  - one work calendar across every lot (App\Support\ProtocolCrew): the farm's
 *    workers by their own days, Anee moving jobs inside their safe windows, or
 *    the farmer's dates kept with the short days named.
 * Anee writes in several asks, each small enough for a slow day: the research
 * per crop (kept on the shelf), the farm (the background, each crop's stages
 * and protection), then each lot's fertilizer and nutrition. The price grows
 * with the lots and the crops (App\Support\AiPricing, kind protocol).
 */
class CropProtocolController extends Controller
{
    /** The house's flat price for one protocol, in credits. */
    public const PRICE = AiPrices::DEFAULTS['protocol'];

    /** The most lots one protocol plans. */
    public const MAX_LOTS = 8;

    /** How long one of Anee's asks may take (a slow day's Pro wrote 6,000 tokens in 211 s). */
    private const AI_TIMEOUT = 300;

    /** The longest a whole run may stand pending before it is called stopped (eight lots on a slow day). */
    private const MAX_MINUTES = 40;

    /** A lot's last crop, from its field answers, as the protocol reads it: [prevCrop, residue]. */
    private const PREV_OF = ['rice_straw' => ['rice', 'incorporated'], 'rice_no_straw' => ['rice', 'unsure'], 'corn' => ['corn', 'unsure'], 'legume' => ['legume', 'unsure'],
        'vegetable' => ['vegetables', 'unsure'], 'fallow' => ['fallow', 'unsure'], 'new_land' => ['fallow', 'unsure']];

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
    // Shared with every analysis since 2026-09-29 (App\Support\SoilConditions),
    // which added acid sulfate.
    public const SOIL_CONDITIONS = \App\Support\SoilConditions::OPTIONS;

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
        'direct_wet' => ['label' => 'Direct seeded, wet', 'sub' => 'Soaked, sprouted seed broadcast on puddled soil', 'icon' => '💧'],
        'direct_dry' => ['label' => 'Direct seeded, dry', 'sub' => 'Dry seed sown into dry or moist soil', 'icon' => '🌤️'],
        'direct' => ['label' => 'Direct seeded', 'sub' => 'Seed sown straight into the field', 'icon' => '🌱'],
        'nursery' => ['label' => 'From seedlings', 'sub' => 'Raised in a seedbed or seedling tray, then transplanted', 'icon' => '🪴'],
        'sets' => ['label' => 'From sets, bulbs or cloves', 'sub' => 'Small bulbs or cloves planted straight in', 'icon' => '🧅'],
        'cuttings' => ['label' => 'From cuttings', 'sub' => 'Stem cuttings, vine cuttings or setts planted straight in', 'icon' => '🌿'],
        'tubers' => ['label' => 'From tubers or rhizomes', 'sub' => 'Seed tubers, corms, setts or rhizome pieces planted in', 'icon' => '🥔'],
        'suckers' => ['label' => 'From suckers or slips', 'sub' => 'Suckers, slips, crowns or clump divisions from a mother plant', 'icon' => '🌴'],
        'sprouted' => ['label' => 'From a sprouted fruit', 'sub' => 'The whole fruit planted once it has shot', 'icon' => '🍈'],
        'tree_seedlings' => ['label' => 'From nursery seedlings', 'sub' => 'Grafted, budded, tissue cultured or grown from seed', 'icon' => '🌳'],
    ];

    /**
     * Which methods each crop can choose from, the usual way first. A
     * catalogue key this table does not name falls to its group in
     * METHODS_BY_GROUP.
     */
    public const METHODS_BY_CROP = [
        'rice' => ['transplanted', 'direct_wet', 'direct_dry'],
        'rice_dsr_wet' => ['direct_wet'],
        'rice_dsr_dry' => ['direct_dry'],
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

    /** Tons in each yield unit: a cavan is 50 kg, as NPK Plus counts it. */
    public static function yieldTons(): array
    {
        return array_intersect_key(['cavans' => 0.05, 'tons' => 1, 'kg' => 0.001], self::yieldUnits()) + array_fill_keys(array_keys(self::yieldUnits()), 1);
    }

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
        return \App\Support\SoilConditions::normalize($raw);
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

        // The price by the size of the run: lots, and the crops or varieties Anee reads up on.
        $quoteFor = [];
        if ($canUse) {
            for ($n = 1; $n <= self::MAX_LOTS; $n++) {
                for ($c = 0; $c < min($n, 4); $c++) {
                    $quoteFor[$n][$c] = AiPricing::credits('protocol', ['lots' => $n, 'cases' => $c]);
                }
            }
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
                // NPK Plus's row for it (its seeding rate, its need), and how it is usually planted.
                'npk' => NpkNeed::cropKey($key),
                'seedUnit' => NpkNeed::seedUnitFor(NpkNeed::cropKey($key)),
                // The field questions' crop (partials/field-profile), and the catalogue's.
                'fq' => \App\Support\FieldCatalogue::cropKey($key),
            ])->values(),
            'lotMax' => self::MAX_LOTS,
            'today' => now('Asia/Manila')->toDateString(),
            'quoteFor' => $quoteFor,
            'prices' => ['base' => AiPrices::of('protocol'), 'lot' => AiPrices::of('protocolLot'), 'case' => AiPrices::of('protocolCase')],
            'people' => \Illuminate\Support\Facades\Route::has('landprep.people') ? route('landprep.people') : null,
            'ph' => \App\Support\Region::ph(),
            // The planting step, as NPK Plus asks it: how much goes in, by what unit, against the usual.
            'seedUnits' => NpkNeed::SEED_UNITS,
            'seeding' => collect(NpkModel::SEEDING)->map(fn ($s) => ['w' => $s['w'], 'n' => $s['n'], 'countWord' => $s['countWord'], 'note' => $s['note']])->all(),
            'germUsual' => NpkNeed::GERM_USUAL,
            'yieldT' => self::yieldTons(),
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
            // The engine's quote (App\Support\AiPricing): one size, today's flat price unless the cost says more.
            'quote' => $canUse ? (float) AiPricing::credits('protocol', ['lots' => 1, 'cases' => 0]) : null,
            'aneeFace' => $settings->faceUrl(),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null
                : ($settings->isUsable()
                    ? Tier::aneeNeeds($payer)
                    : 'Anee is not switched on yet. Please check back soon.'),
        ]]);
    }

    /**
     * The answers NPK Plus's need model reads (2026-10-16, the owner): the
     * variety's potential, how much is planted, a tested pH. Each optional.
     */
    public const NEED_RULES = [
        'potential' => 'nullable|numeric|min:0|max:100000',
        'seedAmount' => 'nullable|numeric|min:0|max:100000000',
        'seedUnit' => 'nullable|in:kg,g,seeds,seedlings,hills,cuttings,plants',
        'germination' => 'nullable|numeric|min:1|max:100',
        'ph' => 'nullable|numeric|min:2|max:12',
    ];

    /**
     * The need before the run, for the last step of the walk: the same count
     * the protocol is held to, so the farmer sees it before paying. No AI,
     * nothing charged, nothing kept.
     */
    public function need(Request $request)
    {
        $this->guardTier();
        // The whole farm (2026-10-18): every lot's need, the work calendar with this crew, and the price.
        if ($request->has('lots')) {
            return $this->check($request);
        }
        $v = Validator::make($request->all(), [
            'crop' => 'required|in:' . implode(',', array_keys(CropCatalog::CROPS)),
            'method' => 'nullable|string|max:24',
            'targetYield' => 'nullable|numeric|min:0|max:100000',
            'yieldUnit' => 'nullable|string|max:12',
            'area' => 'nullable|numeric|min:0|max:10000',
            'soil' => 'nullable|string|max:24',
            'water' => 'nullable|string|max:24',
            'soilConditions' => 'nullable|array|max:7',
            'testP' => 'nullable|string|max:12',
            'testK' => 'nullable|string|max:12',
            'prevCrop' => 'nullable|string|max:24',
            'residue' => 'nullable|string|max:24',
            'fieldProfile' => 'nullable|array',
        ] + self::NEED_RULES);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }

        return $this->json(true, 'ok', ['need' => self::needFor($this->params($request))]);
    }

    /** NPK Plus's need for a protocol's answers, kept in its params and said in its prompt. */
    public static function needFor(array $p): array
    {
        try {
            $r = NpkNeed::run(self::needInput($p));
        } catch (\Throwable $e) {
            report($e);

            return ['known' => false];
        }
        $g = $r['goal'];
        $sd = $g['seed'];
        $micro = ['likely' => [], 'watch' => []];
        // Every secondary and micro element with its need in kg per hectare (2026-10-17, the owner:
        // "consider the micronutrients and not only npk"), NPK Plus's own count.
        $micros = [];
        foreach ($r['micro'] as $el => $m) {
            if (isset($micro[$m['level']])) {
                $micro[$m['level']][] = ['el' => $el, 'name' => NpkNeed::NAMES[$el] ?? $el, 'why' => array_slice($m['why'], 0, 3)];
            }
            if ((float) ($m['need'] ?? 0) > 0) {
                $micros[$el] = ['name' => NpkNeed::NAMES[$el] ?? $el, 'need' => round((float) $m['need'], 3), 'level' => $m['level'], 'why' => array_slice($m['why'], 0, 3),
                    'fert' => (string) (\App\Support\NpkModel::ELEMENTS[$el]['fert'] ?? ''), 'beneficial' => ! empty(\App\Support\NpkModel::ELEMENTS[$el]['beneficial'])];
            }
        }

        return [
            'known' => $r['known'] && $r['need'] !== [],
            'crop' => $r['crop'],
            'need' => $r['need'],
            'how' => $r['how'],
            'goal' => $g['Y'] !== null ? ['t' => round($g['Y'], 2), 'eff' => round((float) $g['Yeff'], 2), 'why' => $g['why'], 'warn' => $g['warn'],
                'capped' => $g['capped'], 'cap' => round($g['cap']['k'], 2), 'capWhy' => array_column($g['cap']['why'], 0)] : null,
            'seed' => $sd['perHa'] !== null ? ['perHa' => round((float) $sd['raw'], $sd['raw'] >= 100 ? 0 : 1), 'unit' => $sd['unitWord'], 'usual' => $sd['usual'],
                'germ' => $sd['germ'], 'sf' => round($sd['sf'], 2), 'thin' => $sd['sf'] < 1, 'words' => $sd['words']] : null,
            'whys' => $r['whys'],
            'credit' => array_filter($r['credit']),
            'pLock' => $r['pLock'],
            'micro' => $micro,
            'micros' => $micros,
            'tested' => array_intersect_key($r['classes'], array_flip(NpkNeed::TESTED_MICROS)),
        ];
    }

    /** A protocol's answers as NPK Plus's inputs: the simple answers first, the field in detail where they are silent. */
    public static function needInput(array $p): array
    {
        $profile = (array) ($p['fieldProfile'] ?? []);
        $L = $profile ? FieldProfile::legacy($profile) : [];
        $tons = self::yieldTons()[$p['yieldUnit'] ?? ''] ?? 1;
        $conds = self::soilConditions($p['soilConditions'] ?? ($p['soilCondition'] ?? null));
        // As NPK Plus reads the same answers: pale, hard soil is low in organic matter; water that stands is waterlogged.
        if (($profile['organic'] ?? '') === 'low') {
            $conds[] = 'low_om';
        }
        if (($profile['drainage'] ?? '') === 'stands') {
            $conds[] = 'waterlogged';
        }
        $prev = [];
        if (($p['prevCrop'] ?? '') === 'legume') {
            $prev[] = 'legume';
        }
        if (($p['prevCrop'] ?? '') === 'rice' && ($p['residue'] ?? '') === 'incorporated') {
            $prev[] = 'rice_straw';
        }
        $soil = (string) ($p['soil'] ?? '');

        return [
            'crop' => $p['crop'] ?? null,
            'method' => $p['method'] ?? null,
            'potential' => ($p['potential'] ?? null) !== null ? (float) $p['potential'] * $tons : null,
            'target' => ($p['targetYield'] ?? null) !== null ? (float) $p['targetYield'] * $tons : null,
            'areaHa' => (float) ($p['area'] ?? 0),
            'seed' => ($p['seedAmount'] ?? null) ? ['amount' => (float) $p['seedAmount'], 'unit' => $p['seedUnit'] ?? 'kg', 'germination' => $p['germination'] ?? null] : null,
            'texture' => in_array($soil, ['sandy', 'clay', 'loam'], true) ? $soil : ($soil === 'silty' ? 'loam' : 'unsure'),
            'conditions' => array_values(array_unique($conds)),
            'ph' => ($p['ph'] ?? null) !== null ? (float) $p['ph'] : ($L['phValue'] ?? null),
            'tests' => self::testsFor($p, $profile),
            'water' => ['irrigated' => 'irrigated', 'limited' => 'partial', 'rainfed' => 'rainfed'][$p['water'] ?? ''] ?? 'irrigated',
            'profile' => $profile,
            'prev' => $prev,
        ];
    }

    /**
     * The soil test NPK Plus would read for this field (2026-10-17): the lot's
     * newest result (numbers before a kit's levels, zinc, boron and sulfur
     * against their critical levels), then the farmer's own answer for
     * phosphorus and potassium on this walk, which wins when given.
     */
    public static function testsFor(array $p, array $profile): array
    {
        $tests = NpkNeed::testClasses(FieldProfile::testLatest((array) ($profile['tests'] ?? [])));
        foreach (['P2O5' => 'testP', 'K2O' => 'testK'] as $n => $k) {
            if (in_array($p[$k] ?? null, ['low', 'medium', 'high'], true)) {
                $tests[$n] = $p[$k];
            }
        }

        return $tests;
    }

    /** The need, its goal and its stand, as the prompt's facts. */
    private static function needLines(array $n, string $unitWord, float $tons): array
    {
        if (empty($n['known'])) {
            return ['seed' => '', 'goal' => '', 'need' => ''];
        }
        $t = fn ($x) => rtrim(rtrim(number_format((float) $x / $tons, 2, '.', ''), '0'), '.') . ' ' . $unitWord;
        $g = $n['goal'] ?? null;
        $s = $n['seed'] ?? null;
        $seed = $s ? '- Seed or planting material: ' . $s['perHa'] . ' ' . $s['unit'] . ' per hectare' . ($s['germ'] ? ' at ' . round($s['germ']) . ' percent germination' : '')
            . ($s['usual'] ? ' (the Philippine guides use ' . $s['usual'][0] . ' to ' . $s['usual'][1] . ' ' . $s['unit'] . ')' : '') . '. ' . ($s['thin'] ? 'A thin stand: the need is sized to the plants there will be, not to a full stand. Say so, and say what a fuller stand would need.' : '') . "\n"
            : "- Seed or planting material: not given\n";
        $goal = $g ? '- The yield goal the need is sized to: ' . $t($g['t']) . ' (' . $g['why'] . ')'
            . (abs($g['eff'] - $g['t']) > 0.01 ? ', counted as ' . $t($g['eff']) . ($s && $s['thin'] ? ' for the thin stand' : '') . ($g['capped'] ? ' because the field\'s water, salt or sodium hold it there' : '') : '')
            . '.' . ($g['warn'] !== '' ? ' ' . $g['warn'] : '') . "\n" : '';
        // The secondary and micro elements the program delivers too, with their amounts (2026-10-17).
        $req = collect($n['micros'] ?? [])->reject(fn ($m) => ! empty($m['beneficial']));
        $ben = collect($n['micros'] ?? [])->filter(fn ($m) => ! empty($m['beneficial']));
        $amount = fn (float $kg) => $kg >= 1 ? rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.') . ' kg' : round($kg * 1000) . ' g';
        $level = ['watch' => 'may run short', 'likely' => 'likely short', 'test' => 'short by the soil test'];
        $micro = $req->map(fn ($m, $el) => $m['name'] . ' ' . $amount((float) $m['need']) . ' ' . $el . ' (' . ($level[$m['level']] ?? $m['level']) . ($m['why'] ? ': ' . implode(', ', $m['why']) : '') . ')')->implode('; ');
        $okByTest = collect($n['tested'] ?? [])->filter(fn ($v) => $v === 'ok')->keys()->map(fn ($el) => NpkNeed::NAMES[$el] ?? $el)->implode(', ');
        $need = '- THE NUTRIENT NEED, already worked out by the app with NPK Plus\'s need model (the official guide rate for this crop scaled to the yield goal above, then sized by the soil, the soil test, the stand and the field\'s history): '
            . NpkNeed::words($n['need']) . '.'
            . ($n['whys'] ? ' What moved it: ' . implode('; ', array_map('lcfirst', $n['whys'])) . '.' : '')
            . (! empty($n['credit']['N']) ? ' ' . $n['credit']['N'] . ' kg N already taken off for the legume before.' : '')
            . (! empty($n['credit']['K2O']) ? ' ' . $n['credit']['K2O'] . ' kg K2O already taken off for the rice straw plowed back.' : '')
            . (! empty($n['pLock']) ? ' This soil locks phosphorus: band or place it, never broadcast it.' : '')
            . "\n" . ($micro !== ''
                ? '- THE SECONDARY AND MICRO ELEMENT NEED, worked out the same way (NPK Plus\'s corrective dose sized to the yield goal, counted only where this crop, this soil or the soil test make a shortfall likely), kg of the ELEMENT per hectare: ' . $micro . '. Every other element: no shortage flagged here.'
                    . ($okByTest !== '' ? ' Enough by the soil test: ' . $okByTest . '.' : '')
                    . ($ben->isNotEmpty() ? ' Beneficial, not required: ' . $ben->map(fn ($m, $el) => $m['name'] . ' ' . $amount((float) $m['need']) . ' ' . $el)->implode(', ') . '.' : '') . "\n"
                : '- Secondary and micro elements: no shortage flagged on this ground by the same count.' . ($okByTest !== '' ? ' Enough by the soil test: ' . $okByTest . '.' : '') . "\n");

        return ['seed' => $seed, 'goal' => $goal, 'need' => $need];
    }

    /**
     * Run the protocol for the farm's lots (2026-10-18): each lot's need,
     * the work calendar, Anee's asks off the request's clock, the charge.
     */
    public function generate(Request $request)
    {
        $this->guardTier();
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi()
                ? 'Anee is not switched on yet. Please check back soon.'
                : Tier::aneeNeeds($payer, 'The analysis'), [], 403);
        }
        $case = $this->caseFrom($request);
        if (is_string($case)) {
            return $this->json(false, $case, [], 422);
        }
        $size = self::sizeOf($case);

        // The price the page showed is the price charged (a page that sends `quote` must match it).
        $quote = AiPricing::quote('protocol', $size);
        $price = AiPricing::agreed($request, $quote, $quote['credits']);
        if ($price === null) {
            return $this->json(false, 'This protocol now costs ' . $quote['credits'] . ' credits. Please run it again.', ['quote' => $quote['credits']], 409);
        }
        $balance = $this->credits->balance($payer->id);
        if ($balance < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . $price . ' credits for this protocol and have '
                . number_format((int) floor($balance)) . '.',
                ['outOfCredits' => true], 402);
        }

        // One in flight at a time: a double press must not buy two. A row
        // whose heart has stopped is not in flight; it is failed here so it
        // cannot block the next run.
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())
            ->where('kind', 'protocol')
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(45))
            ->orderByDesc('id')->first();
        if ($standing && ! $this->dead($standing)) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }

        // Each lot's need, counted as NPK Plus counts it, before Anee writes a bag.
        foreach ($case['lots'] as $i => $l) {
            $case['lots'][$i]['p']['npkNeed'] = self::needFor($l['p']);
        }
        // What only the request can read (the climate shelf as kept, the forecast): read now.
        $ctx = $this->context($case);
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(),
            'kind' => 'protocol',
            'title' => mb_substr(self::titleOf($case), 0, 190),
            'params' => json_encode(self::paramsOf($case, $size, (int) $price)),
            'report' => json_encode(new \stdClass),
            'credits' => 0,
            'status' => 'pending',
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // The answers given on the lots' pages go back into the lots; new names onto the roster.
        $this->rememberLots((int) $payer->id, $case['lots']);
        if (! empty($case['crew']['saveNew'])) {
            $this->rememberWorkers($case['crew']['workers']);
        }

        /* Should PHP itself be cut off mid-run, the row must not stay
         * "pending" -- that blocks every next run. */
        register_shutdown_function(function () use ($id) {
            try {
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                    'status' => 'failed',
                    'error' => 'The protocol took too long and was stopped. Nothing was charged. Please try again.',
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
                'pending' => true, 'id' => $id, 'lots' => count($case['lots']), 'cases' => $size['cases'] + 1,
            ]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $case, $ctx, $price, $size);
            exit;
        }

        @set_time_limit(2400);
        $this->runJob($id, (int) $payer->id, $settings, $case, $ctx, $price, $size);

        return $this->jobState($id);
    }

    /**
     * Before the run (the workers step and the review): every lot's need, the
     * work calendar this crew gives with the usual fertilizer days, and the
     * price for this size. No AI, nothing charged, nothing kept.
     */
    private function check(Request $request)
    {
        $case = $this->caseFrom($request, true);
        if (is_string($case)) {
            return $this->json(false, $case, [], 422);
        }
        $lots = [];
        $sched = [];
        foreach ($case['lots'] as $l) {
            $rec = self::recordOf($l);
            $frame = ProtocolCrew::frame(['crop' => $l['crop'], 'method' => $l['method'], 'days' => $l['days'] ?: ($rec['days'] ?? null), 'areaHa' => $l['areaHa']]);
            $sched[] = ['i' => $l['i'], 'name' => $l['name'], 'month' => $l['month'], 'asked' => $l['day'],
                'tasks' => ProtocolCrew::tasks($frame, ProtocolCrew::skeleton($frame), [], ['weedy' => self::weedy($l['profile'])])];
            $n = $request->boolean('needs', true) ? self::needFor($l['p']) : null;
            $lots[] = ['i' => $l['i'], 'name' => $l['name'], 'harvestDays' => $frame['harvest'], 'seedAge' => $frame['seedAge'],
                'need' => $n ? array_intersect_key($n, array_flip(['known', 'need', 'goal', 'micros', 'whys', 'credit', 'pLock', 'seed'])) : null];
        }
        $s = ProtocolCrew::schedule($sched, $case['crew'], $case['mode']);
        $size = self::sizeOf($case);

        return $this->json(true, 'ok', ['lots' => $lots, 'schedule' => self::scheduleBrief($s, $case), 'size' => $size,
            'quote' => AiPricing::credits('protocol', $size)]);
    }

    /** The calendar in a few lines for the wizard: each lot's day, the short days and late jobs by name, what would fix them. */
    private static function scheduleBrief(array $s, array $case): array
    {
        $title = [];
        foreach ($s['tasks'] as $t) {
            $title[$t['lot'] . '|' . $t['key']] = $t['title'];
        }
        $name = fn (int $i) => $case['lots'][$i]['name'] ?? ('Lot ' . ($i + 1));
        $items = fn (array $d) => array_values(array_map(fn ($x) => ['lot' => $x['lot'], 'name' => $name($x['lot']), 'title' => $title[$x['lot'] . '|' . $x['key']] ?? $x['key'],
            'workers' => round($x['pd'], 1), 'hired' => $x['hired']], $d['items']));
        $days = collect($s['days']);
        $short = $days->filter(fn ($d) => in_array($d['date'], $s['short'], true))->take(8)->map(fn ($d) => ['date' => $d['date'], 'need' => $d['workers'], 'cap' => $d['cap'],
            'over' => $d['over'], 'sprayOver' => $d['sprayOver'], 'items' => $items($d)])->values()->all();
        $busy = $days->sortByDesc('need')->take(5)->map(fn ($d) => ['date' => $d['date'], 'need' => $d['workers'], 'cap' => $d['cap'], 'items' => $items($d)])->values()->all();

        return [
            'mode' => $s['mode'], 'ok' => $s['ok'], 'crew' => $s['crew'], 'start' => $s['start'], 'end' => $s['end'], 'peak' => $s['peak'],
            'lots' => array_values(array_map(fn ($l) => array_merge($l, ['name' => $name($l['i']),
                'why' => $l['why'] ? $l['why'] + ['names' => array_map(fn ($b) => $name($b['lot']) . ': ' . mb_strtolower($title[$b['lot'] . '|' . $b['key']] ?? ''), $l['why']['by'])] : null]), $s['lots'])),
            'short' => $short, 'shortCount' => count($s['short']),
            'late' => array_slice(array_map(fn ($t) => ['lot' => $t['lot'], 'name' => $name($t['lot']), 'title' => $t['title'], 'left' => $t['left'], 'hi' => $t['hi'], 'to' => $t['to']], $s['late']), 0, 8),
            'lateCount' => count($s['late']),
            'busy' => $busy, 'more' => $s['more'], 'moreSprayers' => $s['moreSprayers'], 'workDays' => $days->count(),
        ];
    }

    /** The size the price is read for: the lots, and every crop or variety beyond the first (each is read up on). */
    public static function sizeOf(array $case): array
    {
        return ['lots' => count($case['lots']), 'cases' => max(0, count(self::cases($case)) - 1)];
    }

    /**
     * The wizard's answers, checked and made plain, or the first problem in
     * words. A page from before the lots (one crop, flat answers) is read as
     * one lot. `$lenient` (the check before the run) lets a lot's crop, month
     * or size be missing: a lot with no crop is left out.
     */
    private function caseFrom(Request $request, bool $lenient = false): array|string
    {
        $in = $request->all();
        if (! isset($in['lots']) && isset($in['crop'])) {
            $in['lots'] = [[
                'name' => $in['lotName'] ?? null, 'farmLotId' => $in['lotId'] ?? null, 'areaHa' => $in['area'] ?? null, 'place' => $in['location'] ?? null,
                'profile' => $in['fieldProfile'] ?? null, 'crop' => $in['crop'], 'method' => $in['method'] ?? null, 'variety' => $in['variety'] ?? null,
                'month' => $in['month'] ?? null, 'priority' => $in['priority'] ?? null, 'targetYield' => $in['targetYield'] ?? null, 'yieldUnit' => $in['yieldUnit'] ?? null,
                'potential' => $in['potential'] ?? null, 'seedAmount' => $in['seedAmount'] ?? null, 'seedUnit' => $in['seedUnit'] ?? null, 'germination' => $in['germination'] ?? null,
            ]];
        }
        $req = $lenient ? 'nullable' : 'required';
        $v = Validator::make($in, [
            'lots' => 'required|array|min:1|max:' . self::MAX_LOTS,
            'lots.*.name' => 'nullable|string|max:120',
            'lots.*.farmLotId' => 'nullable|integer',
            'lots.*.areaHa' => $req . '|numeric|min:0.001|max:500',
            'lots.*.place' => 'nullable|string|max:160',
            'lots.*.province' => 'nullable|string|max:120',
            'lots.*.town' => 'nullable|string|max:120',
            'lots.*.profile' => 'nullable|array',
            'lots.*.crop' => $req . '|in:' . implode(',', array_keys(CropCatalog::CROPS)),
            'lots.*.method' => 'nullable|in:' . implode(',', array_keys(self::METHODS)),
            'lots.*.variety' => 'nullable|string|max:80',
            'lots.*.days' => 'nullable|numeric|min:0|max:2000',
            'lots.*.month' => $req . '|date_format:Y-m',
            'lots.*.day' => 'nullable|date_format:Y-m-d',
            'lots.*.priority' => 'nullable|in:' . implode(',', array_keys(self::PRIORITIES)),
            'lots.*.targetYield' => 'nullable|numeric|min:0|max:100000',
            'lots.*.yieldUnit' => 'nullable|string|max:12',
            'lots.*.potential' => 'nullable|numeric|min:0|max:100000',
            'lots.*.seedAmount' => 'nullable|numeric|min:0|max:100000000',
            'lots.*.seedUnit' => 'nullable|in:' . implode(',', array_keys(NpkNeed::SEED_UNITS)),
            'lots.*.germination' => 'nullable|numeric|min:1|max:100',
            'crew' => 'nullable|array',
            'crew.workers' => 'nullable|array|max:100',
            'crew.workers.*.name' => 'nullable|string|max:40',
            'crew.workers.*.days' => 'nullable|array|max:7',
            'crew.workers.*.days.*' => 'integer|min:0|max:6',
            'crew.workers.*.globalId' => 'nullable|integer',
            'crew.sprayers' => 'nullable|integer|min:0|max:50',
            'crew.plantBy' => 'nullable|in:own,hired',
            'crew.saveNew' => 'nullable|boolean',
            'mode' => 'nullable|in:auto,keep',
            'granulars' => 'nullable|array|max:20',
            'granulars.*' => 'string|max:24',
            'notes' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:160',
        ], [], ['lots.*.areaHa' => 'lot size', 'lots.*.crop' => 'crop', 'lots.*.month' => 'planting month', 'lots.*.name' => 'lot name']);
        if ($v->fails()) {
            return $v->errors()->first();
        }
        $granulars = WhatToPlantController::picks(self::GRANULARS, $in['granulars'] ?? []);
        $notes = trim((string) preg_replace('/\s+/', ' ', (string) ($in['notes'] ?? '')));
        $units = self::yieldUnits();
        $tomorrow = now('Asia/Manila')->startOfDay()->addDay();
        $num = fn ($x, $min = 0) => is_numeric($x) && (float) $x > $min ? (float) $x : null;
        $lots = [];
        foreach (array_values((array) $in['lots']) as $k => $l) {
            $name = mb_substr(trim((string) ($l['name'] ?? '')), 0, 120) ?: 'Lot ' . ($k + 1);
            $crop = (string) ($l['crop'] ?? '');
            if ($crop === '' || ! isset(CropCatalog::CROPS[$crop])) {
                if ($lenient) {
                    continue;
                }

                return 'Pick the crop for ' . $name . '.';
            }
            $methods = self::methodsFor($crop);
            $method = (string) ($l['method'] ?? '');
            if (! in_array($method, $methods, true)) {
                if (count($methods) === 1 || $lenient) {
                    $method = $methods[0];
                } else {
                    return 'Pick how ' . self::cropWord($crop) . ' goes in on ' . $name . '.';
                }
            }
            $month = (string) ($l['month'] ?? '');
            if ($month === '') {
                if (! $lenient) {
                    return 'Pick the planting month of ' . $name . '.';
                }
                $month = $tomorrow->copy()->addMonth()->format('Y-m');
            }
            // A day only inside its month, from tomorrow on; a day already gone leaves the month to Anee.
            $day = ! empty($l['day']) && str_starts_with((string) $l['day'], $month) && ! \Illuminate\Support\Carbon::parse((string) $l['day'], 'Asia/Manila')->lt($tomorrow) ? (string) $l['day'] : null;
            $area = $num($l['areaHa'] ?? null) ?? ($lenient ? 1.0 : 0.0);
            if ($area < 0.001) {
                return 'Type the size of ' . $name . '.';
            }
            $place = trim((string) ($l['place'] ?? '')) ?: trim(implode(', ', array_filter([trim((string) ($l['town'] ?? '')), trim((string) ($l['province'] ?? ''))])));
            $lot = [
                'i' => count($lots), 'name' => $name,
                'farmLotId' => isset($l['farmLotId']) && (int) $l['farmLotId'] > 0 ? (int) $l['farmLotId'] : null,
                'areaHa' => round($area, 4), 'place' => $place,
                'crop' => $crop, 'method' => $method, 'variety' => mb_substr(trim((string) ($l['variety'] ?? '')), 0, 80),
                'days' => $num($l['days'] ?? null, 20),
                'month' => $month, 'day' => $day,
                'priority' => isset(self::PRIORITIES[$l['priority'] ?? '']) ? (string) $l['priority'] : 'balanced',
                'targetYield' => $num($l['targetYield'] ?? null), 'yieldUnit' => isset($units[$l['yieldUnit'] ?? '']) ? (string) $l['yieldUnit'] : array_key_first($units),
                'potential' => $num($l['potential'] ?? null),
                'seedAmount' => $num($l['seedAmount'] ?? null), 'seedUnit' => isset(NpkNeed::SEED_UNITS[$l['seedUnit'] ?? '']) ? (string) $l['seedUnit'] : NpkNeed::seedUnitFor(NpkNeed::cropKey($crop)),
                'germination' => $num($l['germination'] ?? null) !== null ? max(1.0, min(100.0, (float) $l['germination'])) : null,
                'profile' => FieldProfile::fromInput($l['profile'] ?? null, $crop),
            ];
            $lots[] = $lot;
        }
        if (! $lots) {
            return 'Add a lot first, and pick what it will grow.';
        }
        // Where the weather is read: the lots' own place (the first lot's), else what was typed.
        $location = '';
        foreach ($lots as $l) {
            if ($l['place'] !== '') {
                $location = $l['place'];
                break;
            }
        }
        $location = $location ?: trim((string) ($in['location'] ?? ''));
        if ($location === '' && ! $lenient) {
            return 'Say where the lots are, so Anee reads the right weather.';
        }
        foreach ($lots as $i => $l) {
            if ($l['place'] === '') {
                $lots[$i]['place'] = $location;
            }
            $lots[$i]['p'] = self::lotParams($lots[$i], $granulars, $notes);
        }
        $crew = ProtocolCrew::crewOf((array) ($in['crew'] ?? []));
        $crew['saveNew'] = ! empty($in['crew']['saveNew']);

        return [
            'lots' => $lots,
            'crew' => $crew,
            'mode' => ($in['mode'] ?? 'auto') === 'keep' ? 'keep' : 'auto',
            'granulars' => $granulars,
            'notes' => $notes,
            'location' => $location,
        ];
    }

    /** A crop in a sentence: "rice", "yellow corn". */
    private static function cropWord(string $crop): string
    {
        return mb_strtolower(self::cropName($crop));
    }

    /** A crop's name without the local name or a dash: "Rice, transplanted". */
    public static function cropName(string $crop): string
    {
        $l = CropCatalog::label($crop);

        return trim((string) preg_replace('/\s*\([^)]*\)\s*$/', '', str_replace([' — ', ' / '], [', ', ' or '], $l)));
    }

    /**
     * One lot as the need model, the checks and the prompt read a protocol's
     * answers (the shape of a one lot protocol before 2026-10-18): the lot's
     * soil, water, soil condition, pH, troubles and last crop read from its
     * own field answers (FieldProfile::legacy), its soil test read by
     * testsFor(), so phosphorus and potassium are never asked.
     */
    public static function lotParams(array $lot, array $granulars, string $notes): array
    {
        $profile = (array) ($lot['profile'] ?? []);
        $L = $profile ? FieldProfile::legacy($profile) : [];
        $prev = self::PREV_OF[(string) ($profile['prevCrop'] ?? '')] ?? ['unsure', 'unsure'];

        return [
            'location' => (string) $lot['place'], 'crop' => (string) $lot['crop'], 'variety' => (string) $lot['variety'], 'month' => (string) $lot['month'],
            'method' => (string) $lot['method'], 'priority' => (string) $lot['priority'],
            'targetYield' => $lot['targetYield'], 'yieldUnit' => (string) $lot['yieldUnit'], 'area' => (float) $lot['areaHa'],
            'soil' => isset(self::SOILS[$L['soil'] ?? '']) ? (string) $L['soil'] : 'unsure',
            'water' => isset(self::WATERS[$L['water'] ?? '']) ? (string) $L['water'] : 'unsure',
            'problems' => array_values(array_filter((array) ($L['problemsVariety'] ?? []), fn ($k) => isset(self::PROBLEMS[$k]))),
            'notes' => $notes, 'lotId' => $lot['farmLotId'], 'lotName' => (string) $lot['name'],
            'soilConditions' => self::soilConditions($L['soilConditions'] ?? []),
            'testP' => 'unsure', 'testK' => 'unsure',
            'prevCrop' => $prev[0], 'residue' => $prev[1],
            'granulars' => $granulars, 'fertHistory' => '',
            'potential' => $lot['potential'], 'seedAmount' => $lot['seedAmount'], 'seedUnit' => (string) $lot['seedUnit'], 'germination' => $lot['germination'],
            'ph' => $L['phValue'] ?? null,
        ] + FieldProfile::forParams($profile, (string) $lot['crop']);
    }

    /** Whether a lot was weedy last season: true, false (few or none), null (not said). */
    private static function weedy(array $profile): ?bool
    {
        $w = (array) ($profile['weeds'] ?? []);
        if (! $w) {
            return null;
        }

        return (bool) array_intersect($w, ['many', 'tough', 'sedges', 'weedy_rice']);
    }

    /** The variety a lot names, as the books (or an earlier lookup) know it. */
    private static function recordOf(array $l): ?array
    {
        if (($l['variety'] ?? '') === '') {
            return null;
        }
        try {
            return \App\Support\VarietyBook::find($l['crop'], $l['variety']);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The crops of a run: lots growing the same crop, the same way, from the
     * same variety are one case (read up on once, their stages and their
     * protection written once).
     */
    private static function cases(array $case): array
    {
        $out = [];
        foreach ($case['lots'] as $l) {
            $k = $l['crop'] . '|' . $l['method'] . '|' . mb_strtolower((string) preg_replace('/\s+/', ' ', trim((string) $l['variety'])));
            $out[$k] ??= ['key' => 'c' . (count($out) + 1), 'crop' => $l['crop'], 'method' => $l['method'], 'variety' => $l['variety'], 'lots' => [], 'months' => []];
            $out[$k]['lots'][] = $l['i'];
            $out[$k]['months'][] = $l['month'];
        }
        foreach ($out as &$c) {
            $c['months'] = array_values(array_unique($c['months']));
            sort($c['months']);
        }
        unset($c);

        return array_values($out);
    }

    /** The case a lot belongs to. */
    private static function caseOf(array $cases, int $i): array
    {
        foreach ($cases as $c) {
            if (in_array($i, $c['lots'], true)) {
                return $c;
            }
        }

        return $cases[0];
    }

    /**
     * Everything the asks read that is not the farmer's answers, read while
     * the request is still open (the climate shelf with fetch=false, the
     * forecast): each lot's calendar frame, each crop's catalogue troubles and
     * weed windows, the sky.
     */
    private function context(array $case): array
    {
        $ctx = ['frames' => [], 'records' => [], 'threats' => [], 'weeds' => [], 'history' => [], 'cases' => self::cases($case)];
        foreach ($case['lots'] as $l) {
            $rec = self::recordOf($l);
            $ctx['records'][$l['i']] = $rec;
            $ctx['frames'][$l['i']] = ProtocolCrew::frame(['crop' => $l['crop'], 'method' => $l['method'], 'days' => $l['days'] ?: ($rec['days'] ?? null), 'areaHa' => $l['areaHa']]);
        }
        foreach ($ctx['cases'] as $c) {
            $had = [];
            foreach ($c['lots'] as $i) {
                foreach ((array) ($case['lots'][$i]['profile']['outbreaks'] ?? []) as $k) {
                    $had[(string) $k][] = $case['lots'][$i]['name'];
                }
            }
            try {
                $ctx['threats'][$c['key']] = ProtocolCare::threats($c['crop'], $had);
                $ctx['weeds'][$c['key']] = ProtocolCare::weeds($c['crop'], $c['method'], $ctx['frames'][$c['lots'][0]]['harvest']);
            } catch (\Throwable $e) {
                report($e);
                $ctx['threats'][$c['key']] = ['pests' => [], 'diseases' => []];
                $ctx['weeds'][$c['key']] = null;
            }
        }
        // The work calendar from the usual fertilizer days, so Anee's work tips agree with the one the report draws.
        try {
            $pre = [];
            foreach ($case['lots'] as $l) {
                $f = $ctx['frames'][$l['i']];
                $pre[] = ['i' => $l['i'], 'name' => $l['name'], 'month' => $l['month'], 'asked' => $l['day'],
                    'tasks' => ProtocolCrew::tasks($f, ProtocolCrew::skeleton($f), [], ['weedy' => self::weedy($l['profile'])])];
            }
            $s = ProtocolCrew::schedule($pre, $case['crew'], $case['mode'], null, false);
            $ctx['planWords'] = implode(' ', ProtocolCrew::words($s, array_map(fn ($l) => $l['name'], $case['lots'])))
                . ($s['peak'] ? ' The busiest day has ' . $s['peak']['workers'] . ' of the ' . $s['peak']['cap'] . ' workers busy.' : '');
        } catch (\Throwable $e) {
            report($e);
            $ctx['planWords'] = '';
        }
        $ctx['forecast'] = $this->forecastLines($case['location']);
        $ctx['enso'] = \App\Support\EnsoOutlook::forPrompt();
        // Twenty years of each place (up to three), for the months its lots stand in the field.
        $places = [];
        foreach ($case['lots'] as $l) {
            $f = $ctx['frames'][$l['i']];
            $start = (int) substr($l['month'], 5, 2);
            $span = (int) ceil(max(30, (int) ($f['harvest'] ?? 120) + (int) ($f['seedAge'] ?? 0)) / 30);
            for ($m = 0; $m <= min(11, $span); $m++) {
                $places[$l['place']][($start - 1 + $m) % 12 + 1] = true;
            }
        }
        foreach (array_slice($places, 0, 3, true) as $place => $months) {
            $focus = array_keys($months);
            sort($focus);
            try {
                $ctx['history'][$place] = \App\Support\ClimateStore::wordsFor((string) $place, null, null, $focus, true, false);
            } catch (\Throwable $e) {
                $ctx['history'][$place] = '';
            }
        }

        return $ctx;
    }

    /** What the row keeps of the run: the lots as answered, the crew, the choice, the size and the price. */
    private static function paramsOf(array $case, array $size, int $price): array
    {
        $first = $case['lots'][0];

        return [
            'format' => 4, 'crop' => $first['crop'], 'month' => $first['month'], 'location' => $case['location'], 'mode' => $case['mode'],
            'crew' => array_diff_key($case['crew'], ['saveNew' => 1]), 'granulars' => $case['granulars'], 'notes' => $case['notes'], 'size' => $size, 'price' => $price,
            'lots' => array_map(fn ($l) => array_diff_key($l, ['p' => 1]) + ['p' => array_diff_key($l['p'], ['fieldProfileSaid' => 1])], $case['lots']),
            // The first lot's answers where a protocol of one lot kept them.
            'area' => (float) $first['areaHa'], 'method' => $first['method'], 'variety' => $first['variety'], 'priority' => $first['priority'],
            'targetYield' => $first['targetYield'], 'yieldUnit' => $first['yieldUnit'], 'lotName' => $first['name'],
        ];
    }

    /** "Protocol · Rice, transplanted (NSIC Rc 222) · Nov 2026 · North field, Muñoz, Nueva Ecija", or for several lots their count and crops. */
    private static function titleOf(array $case): string
    {
        $lots = $case['lots'];
        $months = array_values(array_unique(array_map(fn ($l) => $l['month'], $lots)));
        sort($months);
        $m = fn (string $ym) => \Illuminate\Support\Carbon::parse($ym . '-01')->format('M Y');
        $when = $m($months[0]) . (count($months) > 1 ? ' to ' . $m(end($months)) : '');
        if (count($lots) === 1) {
            $l = $lots[0];

            return 'Protocol · ' . self::cropName($l['crop']) . ($l['variety'] !== '' ? ' (' . $l['variety'] . ')' : '') . ' · ' . $when . ' · ' . $l['name'] . ($l['place'] !== '' ? ', ' . $l['place'] : '');
        }
        $crops = array_values(array_unique(array_map(fn ($l) => preg_replace('/,.*$/', '', self::cropName($l['crop'])), $lots)));
        $place = \App\Support\ClimateRecord::placeKey((string) $case['location'])['place'] ?: $case['location'];

        return 'Protocol · ' . count($lots) . ' lots · ' . implode(', ', $crops) . ' · ' . $when . ($place !== '' ? ' · ' . $place : '');
    }

    /**
     * Keep every lot's answers for the next analysis: a lot from the farm's
     * lots gets the answers given on this walk's pages added to its own (and
     * its size, when it had none), so it is updated, never copied. Best effort.
     */
    private function rememberLots(int $ownerId, array $lots): void
    {
        if (! \App\Support\FarmLots::ready()) {
            return;
        }
        foreach ($lots as $l) {
            $farmId = (int) ($l['farmLotId'] ?? 0);
            if ($farmId < 1 || ! $l['profile']) {
                continue;
            }
            try {
                $had = \App\Support\FarmLots::one($ownerId, $farmId);
                if (! $had) {
                    continue;
                }
                $old = (array) ($had['answers'] ?? []);
                $data = [];
                if (array_replace($old, $l['profile']) != $old) {
                    $data['answers'] = array_replace($old, $l['profile']);
                }
                if (empty($had['areaHa']) && (float) $l['areaHa'] > 0) {
                    $data['areaHa'] = (float) $l['areaHa'];
                }
                if ($data) {
                    \App\Support\FarmLots::save($ownerId, $data, $farmId);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * The workers onto the farm's roster: a named worker typed here joins
     * Global Workers with their weekdays off, unless the same person is there
     * already. Only the owner, only where the plan has Workers, best effort.
     */
    private function rememberWorkers(array $workers): void
    {
        if (WorkerContext::inWorkerContext() || ! Tier::can('workers')) {
            return;
        }
        $me = (int) Auth::id();
        try {
            foreach ($workers as $w) {
                if (! empty($w['globalId'])) {
                    continue;
                }
                $name = trim((string) $w['name']);
                if ($name === '' || preg_match('/^Worker \d+$/', $name)) {
                    continue;
                }
                $key = \App\Models\AsGlobalWorker::personKey($name, null, null);
                if (\App\Models\AsGlobalWorker::where('userId', $me)->where('deleteStatus', 1)->where(fn ($q) => $q->where('personKey', $key)->orWhere('workerName', $name))->exists()) {
                    continue;
                }
                $off = array_values(array_diff([0, 1, 2, 3, 4, 5, 6], array_map('intval', (array) $w['days'])));
                \App\Models\AsGlobalWorker::create(['userId' => $me, 'workerName' => mb_substr($name, 0, 255), 'costPerHalfDay' => 0, 'offWeekdays' => $off ?: null,
                    'notes' => 'Added from the Crop Protocol Analysis.', 'personKey' => $key, 'deleteStatus' => 1]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * The research for one crop, with the web open, kept on the shelf: the
     * same case (crop, variety, way in, province, months) reads the kept
     * notes and pays no search. Mirrors AiClient::researchThenJson's research
     * step: asked again when it answered from memory, stopped after two stalls.
     */
    private function research(AiSetting $settings, string $brief, string $scope, callable $onPhase): array
    {
        $none = ['text' => '', 'sources' => [], 'searched' => false, 'paid' => false, 'shelf' => false, 'tokensIn' => 0, 'tokensOut' => 0];
        if (trim($brief) === '') {
            return $none;
        }
        $kept = \App\Support\ResearchShelf::get('protocol', $brief);
        if ($kept) {
            $onPhase(1);

            return ['text' => trim((string) $kept['notes']), 'sources' => (array) $kept['sources'], 'searched' => true, 'paid' => false, 'shelf' => true, 'tokensIn' => 0, 'tokensOut' => 0];
        }
        $s = $settings->forDocument();
        $best = null;
        $spent = ['tokensIn' => 0, 'tokensOut' => 0];
        $stalls = 0;
        for ($try = 0; $try < 3; $try++) {
            $onPhase($try + 1);
            $ask = $try === 0 ? $brief : "IMPORTANT: the previous attempt answered from memory without using the search tool. You MUST use the web search tool now: run the searches, read the pages, and write the notes from what they say.\n\n" . $brief;
            $got = $this->ai->ask($s, [], $ask, null, 3200, ['search' => true, 'timeout' => AiClient::TIMEOUT_SEARCHED]);
            $spent['tokensIn'] += (int) ($got['tokensIn'] ?? 0);
            $spent['tokensOut'] += (int) ($got['tokensOut'] ?? 0);
            if (($got['ok'] ?? false) && trim((string) $got['text']) !== '') {
                $best = $got;
                if ($got['searched'] ?? false) {
                    break;
                }
            } elseif (++$stalls >= 2) {
                break;
            }
        }
        if (! $best) {
            return ['paid' => $spent['tokensIn'] > 0] + $spent + $none;
        }
        if ($best['searched'] ?? false) {
            \App\Support\ResearchShelf::put('protocol', $brief, $scope, array_merge($best, $spent));
        }

        return ['text' => trim((string) $best['text']), 'sources' => (array) ($best['sources'] ?? []), 'searched' => (bool) ($best['searched'] ?? false),
            'paid' => true, 'shelf' => false] + $spent;
    }

    /** The research, the farm, every lot, the calendar and the charge, off the request's clock. */
    private function runJob(int $id, int $payerId, AiSetting $settings, array $case, array $ctx, int $price, array $size): void
    {
        /* The heartbeat: before every call to the model the row says which
         * phase it is in and touches updated_at, so jobState can tell a slow
         * ask from a run killed under it (a deploy, a restart). */
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update([
                'report' => json_encode(['phase' => $phase, 'try' => $try]),
                'updated_at' => now(),
            ]);
        };
        $usage = ['tokensIn' => 0, 'tokensOut' => 0, 'searched' => false, 'paidSearch' => false, 'searchCalls' => 0, 'shelf' => false];
        $fold = function (array $r) use (&$usage): void {
            $usage['tokensIn'] += (int) ($r['tokensIn'] ?? 0);
            $usage['tokensOut'] += (int) ($r['tokensOut'] ?? 0);
        };
        try {
            $cases = $ctx['cases'];
            // 1. Each crop read up on (or its kept notes read).
            $notes = [];
            $sources = [];
            foreach ($cases as $ci => $c) {
                $brief = $this->caseResearch($c, $case, $ctx);
                $r = $this->research($settings, $brief['text'], $brief['scope'], fn (int $t) => $beat($ci ? 'research' . ($ci + 1) : 'research', $t));
                $fold($r);
                $usage['searched'] = $usage['searched'] || $r['searched'];
                $usage['shelf'] = $usage['shelf'] || $r['shelf'];
                if ($r['paid']) {
                    $usage['paidSearch'] = true;
                    $usage['searchCalls']++;
                }
                $notes[$c['key']] = $r['text'];
                foreach ($r['sources'] as $src) {
                    if (! in_array($src, $sources, true)) {
                        $sources[] = $src;
                    }
                }
                // Learn once: a variety Anee found is kept, so the next run reads it instead of searching.
                if ($brief['asked'] && $r['searched'] && $r['sources']) {
                    try {
                        \App\Support\VarietyLearned::harvest($c['crop'], $brief['asked'], $r['text'], ['via' => 'protocol', 'userId' => $payerId, 'searched' => true, 'sources' => $r['sources']]);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                } elseif (! empty($brief['record']['learned'])) {
                    \App\Support\VarietyBook::used($brief['record']);
                }
            }
            // 2. The farm: the background, every crop's stages and protection.
            $farm = $this->ai->askForJson($settings, $this->farmPrompt($case, $ctx, $notes), min(16000, 6000 + 3500 * count($cases)), fn (string $t) => $this->parseFarm($t, $cases),
                ['onPhase' => fn ($ph, $t = 1) => $beat('farm', $ph === 'document' ? (int) $t : 1), 'timeout' => self::AI_TIMEOUT, 'retryAfterTimeout' => false]);
            $fold($farm);
            if (! ($farm['ok'] ?? false) || ! is_array($farm['data'] ?? null)) {
                \Illuminate\Support\Facades\Log::warning('crop-protocol: the farm part could not be read', ['head' => mb_substr((string) ($farm['text'] ?? ''), 0, 400)]);
                throw new \RuntimeException(! empty($farm['timedOut']) ? 'Anee took too long on the farm part today. Nothing was charged. Please try again in a while.'
                    : ($farm['error'] ?? 'The protocol could not be read. Nothing was charged. Please try again.'));
            }
            // 3. Each lot's fertilizer and nutrition, one ask a lot.
            $lotOut = [];
            foreach ($case['lots'] as $l) {
                $c = self::caseOf($cases, $l['i']);
                $r = $this->ai->askForJson($settings, $this->lotPrompt($case, $l, $c, $ctx, $notes[$c['key']] ?? ''), 9000, fn (string $t) => $this->parseLot($t),
                    ['onPhase' => fn ($ph, $t = 1) => $beat('lot' . ($l['i'] + 1), $ph === 'document' ? (int) $t : 1), 'timeout' => self::AI_TIMEOUT, 'retryAfterTimeout' => false]);
                $fold($r);
                if (! ($r['ok'] ?? false) || ! is_array($r['data'] ?? null)) {
                    \Illuminate\Support\Facades\Log::warning('crop-protocol: a lot could not be read', ['lot' => $l['i'], 'head' => mb_substr((string) ($r['text'] ?? ''), 0, 400)]);
                    throw new \RuntimeException('Anee could not finish the plan for ' . $l['name'] . (! empty($r['timedOut']) ? ' in time' : '') . '. Nothing was charged. Please try again.');
                }
                $lotOut[$l['i']] = $r['data'];
            }
            $beat('compose');
            $report = $this->compose($case, $ctx, $farm['data'], $lotOut);
            $report['webSources'] = $sources;
            $report['searched'] = $usage['searched'];

            // The price agreed when the run began.
            $charged = (float) $price;
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record('protocol', (int) $row->userId, $payerId, $id, $settings, $usage, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged,
                mb_substr('Crop Protocol Analysis: ' . count($case['lots']) . (count($case['lots']) === 1 ? ' lot, ' : ' lots, ') . $case['location'] . $note, 0, 250));
            AiPricing::audit('protocol', $usage, $charged, $size, $settings);
            $report['usage'] = ['tokensIn' => $usage['tokensIn'], 'tokensOut' => $usage['tokensOut'], 'searchCalls' => $usage['searchCalls'], 'kept' => $usage['shelf']];

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

    /** A stage's days on its count: "DAT 7 to 20", the land preparation "before DAT 0", the last "DAT 90 on". */
    private static function daysLabel(array $frame, array $s): string
    {
        $c = $frame['counter'];
        if (! empty($s['prep'])) {
            return 'before ' . $c . ' 0';
        }

        return $c . ' ' . $s['from'] . ($s['to'] !== null && $s['to'] > $s['from'] ? ' to ' . $s['to'] : ' on');
    }

    /** A lot's stages as the prompt lists them. */
    private static function stageList(array $frame): string
    {
        return implode('; ', array_map(fn ($s) => $s['name'] . ' (' . self::daysLabel($frame, $s) . ')', $frame['stages']));
    }

    /**
     * The research brief for one crop: what is not the farmer's own (the
     * crop, the variety, the way in, the province, the months), so the notes
     * are kept and shared by the next run of the same case. A variety the
     * books know is given, not searched; one they do not know asks for the
     * learn once block (App\Support\VarietyLearned).
     *
     * @return array{text:string, scope:string, asked:list<string>, record:?array}
     */
    private function caseResearch(array $c, array $case, array $ctx): array
    {
        $l0 = $case['lots'][$c['lots'][0]];
        $rCountry = \App\Support\Region::name();
        $rNutrient = \App\Support\Region::agency('nutrient');
        $rMet = \App\Support\Region::agency('met');
        $rSources = \App\Support\Region::agency('sources');
        $rRegion = \App\Support\Region::promptBlock();
        $province = \App\Support\ClimateRecord::placeKey((string) $l0['place'])['place'];
        $months = implode(' and ', array_map(fn ($m) => \Illuminate\Support\Carbon::parse($m . '-01')->format('F Y'), $c['months']));
        $label = CropCatalog::label($c['crop']);
        $method = self::METHODS[$c['method']]['label'] ?? $c['method'];
        $rec = $ctx['records'][$l0['i']] ?? null;
        $known = ! empty($case['lots'][$l0['i']]['p']['npkNeed']['known']) || ! empty(NpkNeed::crop(NpkNeed::cropKey($c['crop'], $c['method'])));
        $asked = [];
        if ($c['variety'] === '') {
            $variety = '1. THE VARIETY: none is named. Name the two or three most widely grown, well documented varieties of this crop for this season and province, with their days to maturity, yield potential and main strengths, and say which you would assume.';
        } elseif ($rec) {
            $variety = '1. THE VARIETY IS KNOWN: ' . ltrim(\App\Support\VarietyBook::promptLines([$rec]), '- ')
                . ' Take these as known and do not search for them again. Search only for what adds to them: regional trial results near the farmer, its known weaknesses in the field, and any warning about it.';
        } else {
            $variety = '1. THE VARIETY FIRST: its published specifications: days to maturity, yield potential and typical farm yields, plant height and lodging, the season and method it is bred for, resistance and tolerance ratings (pests, diseases, drought, flooding, salinity, heat), known weaknesses, who released it and when. Prefer the registry, the breeder\'s or seed company\'s own page, and extension notes. If the variety cannot be found, say so plainly and describe the widely grown variety you would assume instead.';
            $asked = [$c['variety']];
        }
        $nutrients = $known
            ? "2. The official guide in {$rCountry} for this crop's fertilizer by growth stage in this region and season ({$rNutrient}): the SPLIT TIMING (which stage gets which share) and the inorganic products sold there. The kilograms of N, P2O5 and K2O per hectare are already counted by the app from the official guide: do not look them up."
            : "2. The official recommendations in {$rCountry} for this crop's nutrient management by growth stage in this region and season ({$rNutrient}): kg N-P-K per hectare, split timing by stage, and the common inorganic products.";
        $text = <<<PROMPT
You are a research assistant for agronomy in {$rCountry} with web search. {$rRegion} SEARCH THE WEB NOW and write research notes for a season protocol. Do not answer from memory alone; every finding must come from a page you read, with the source name and year beside it.

THE CASE
- Crop: {$label}; variety: {$this->varietyWord($c['variety'])}
- Planting: {$months}, {$method}, in the province of {$province}

FIND, IN THIS ORDER
{$variety}
{$nutrients}
3. Integrated pest management for this crop in the region: the insects, diseases and weeds that matter by growth stage, their economic thresholds in plain counts, the recommended controls (cultural, biological, chemical with active ingredients and their IRAC, FRAC or HRAC group), and any current outbreak advisories.
4. Irrigation and water management by growth stage for this crop and method, and what to change under drought or flooding.
5. The seasonal climate OUTLOOK from {$rMet} for the region for the months from planting to harvest (rainfall, temperature, severe weather expected). Only the outlook: the app keeps the area's twenty year weather record and reads the ENSO state itself.
6. REAL HARVESTS: the most recent derbies, techno demo farms and yield contests with this variety, or this crop when the variety has none, in or near this province first, then anywhere in {$rCountry}: the event, the place, the season and year, the yield (t/ha, or cavans per hectare with the kilos per cavan) and the fertilizer program used where reported. Then real farmers' own reported harvests with it in the last two seasons, each with the yield, the place, the season and the source.

Prefer {$rSources}. Note the year of each finding. Where something cannot be found online, say so plainly. Write plain prose notes under the six headings, at most 1,100 words, no JSON, no markdown tables.
PROMPT;
        if ($asked) {
            try {
                $text .= \App\Support\VarietyLearned::askBlock($asked, $c['crop']);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $scope = implode(' · ', array_filter([CropCatalog::label($c['crop']), $c['variety'] ?: null, $c['method'], $province, implode(', ', $c['months'])]));

        return ['text' => $text, 'scope' => $scope, 'asked' => $asked, 'record' => $rec];
    }

    private function varietyWord(string $v): string
    {
        return $v !== '' ? $v : 'not named';
    }

    /** One lot's field, in the words the prompts use. */
    private function lotField(array $l): array
    {
        $f = $this->facts($l['p']);
        $test = \App\Support\FieldProfile::testLatest((array) ($l['profile']['tests'] ?? []));

        return [
            'soil' => $l['p']['soil'] !== 'unsure' ? $f['soil'] : 'not stated in the lot',
            'water' => isset(self::WATERS[$l['p']['water']]) ? $f['water'] : 'not stated in the lot',
            'cond' => $f['soilCondition'], 'guide' => $f['soilGuide'],
            'test' => $test ? \App\Support\FieldProfile::testWords($test) : 'none logged',
            'prev' => $f['prevCrop'], 'residue' => $f['residue'], 'problems' => $f['problems'],
            'detail' => $l['profile'] ? \App\Support\FieldProfile::words($l['profile'], $l['crop']) : 'no answers in the lot yet',
            'f' => $f,
        ];
    }

    /** The farm part: the background, then each crop's stage guide and its protection, watch first. */
    private function farmPrompt(array $case, array $ctx, array $notes): string
    {
        $country = \App\Support\Region::name();
        $region = \App\Support\Region::promptBlock();
        $lots = '';
        foreach ($case['lots'] as $l) {
            $fd = $this->lotField($l);
            $when = $l['day'] ? \Illuminate\Support\Carbon::parse($l['day'])->format('F j, Y') : \Illuminate\Support\Carbon::parse($l['month'] . '-01')->format('F Y');
            $lots .= '- ' . $l['name'] . ' (' . $l['place'] . '): ' . rtrim(rtrim(number_format($l['areaHa'], 2), '0'), '.') . ' ha of ' . self::cropName($l['crop'])
                . ($l['variety'] !== '' ? ', ' . $l['variety'] : '') . ', ' . mb_strtolower(self::METHODS[$l['method']]['label'] ?? $l['method']) . ', planting ' . $when
                . '. Field: soil ' . $fd['soil'] . '; water ' . $fd['water'] . '; troubles last season: ' . $fd['problems'] . ".\n";
        }
        $hist = '';
        foreach ($ctx['history'] as $place => $w) {
            $hist .= $w !== '' ? '- The area\'s weather record at ' . $place . ":\n" . $w . "\n" : '';
        }
        $crops = '';
        foreach ($ctx['cases'] as $c) {
            $l0 = $case['lots'][$c['lots'][0]];
            $frame = $ctx['frames'][$l0['i']];
            $rec = $ctx['records'][$l0['i']] ?? null;
            $names = implode(', ', array_map(fn ($i) => $case['lots'][$i]['name'], $c['lots']));
            $crops .= 'CROP ' . $c['key'] . ': ' . CropCatalog::label($c['crop']) . '; variety ' . $this->varietyWord($c['variety'])
                . ($rec ? ' (' . (! empty($rec['learned']) ? 'known from an earlier lookup' : 'on record') . ': ' . ltrim(\App\Support\VarietyBook::promptLines([$rec]), '- ') . ')' : '')
                . '; ' . (self::METHODS[$c['method']]['label'] ?? $c['method']) . '; counted in ' . $frame['counter'] . ".\n"
                . '- Its stages, in order (use these names exactly): ' . self::stageList($frame) . "\n"
                . '- Lots growing it: ' . $names . "\n"
                . ProtocolCare::promptLines($ctx['threats'][$c['key']] ?? [], $ctx['weeds'][$c['key']] ?? null)
                . '- Research notes for this crop:' . "\n" . (trim($notes[$c['key']] ?? '') !== '' ? trim($notes[$c['key']]) : 'none found; rely on established agronomy and say so in dataGaps') . "\n\n";
        }
        $shape = '{"headline":"","background":{"place":"","weather":{"outlook":"","enso":"","risks":[""]},"history":""},"crops":[{"key":"c1","variety":{"found":false,"name":"","by":"","released":"","maturityDays":0,"yieldPotential":"","season":"","traits":"","caution":"","source":""},"stages":[{"stage":"","signs":"","observe":"","intervene":""}],"protection":{"intro":"","threats":[{"name":"","kind":"insect","stages":[""],"sign":"","threshold":"","cultural":"","ai":[""],"rotate":"","weather":""}]}}],"workTips":[""],"summary":"","confidence":"moderate","dataGaps":[""]}';
        $enso = $ctx['enso'] !== '' ? '- ENSO: ' . $ctx['enso'] . "\n" : '';
        $crew = $case['crew'];
        $plan = ($ctx['planWords'] ?? '') !== ''
            ? '- The work calendar the app laid out for ' . count($crew['workers']) . (count($crew['workers']) === 1 ? ' worker' : ' workers') . ' and ' . $crew['sprayers'] . ($crew['sprayers'] === 1 ? ' sprayer' : ' sprayers')
                . ($crew['plantBy'] === 'hired' ? ', a hired crew planting and harvesting' : ', the farm\'s own workers planting and harvesting')
                . ($case['mode'] === 'auto' ? ', jobs moved inside their safe days when they collide' : ', the farmer\'s dates kept') . ': ' . $ctx['planWords'] . "\n"
            : '';

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$country}. A farmer is planting the lots below this season. Write the FARM PART of their crop protocol: the background, then for every crop its growth stages and its protection plan. Each lot's fertilizer plan is written separately: no fertilizer amounts here.

THE FARM
- {$region}
{$lots}{$plan}- Weather now: {$ctx['forecast']}
{$enso}{$hist}
THE CROPS
{$crops}GROUND RULES
- EASY TO READ: short plain sentences a farmer reads on a phone; a plain word beside any technical one; ranges written with "to", never a dash. Every word limit is a ceiling, not a target.
- THE VARIETY comes from the notes and the record: name, breeder, maturity, yield potential, the season it is bred for, strengths and weaknesses. Where it could not be found, found false and name the variety you assumed.
- STAGES: every stage of every crop, in the order given, named exactly as given: signs at most 18 words (what the farmer sees at that stage), observe at most 22 words (what to check, and how often), intervene at most 26 words (only if it is seen: the threshold, then the step). Leave observe and intervene empty at a stage with nothing to watch.
- PROTECTION, WATCH FIRST, NEVER BY THE CALENDAR: for every crop 5 to 8 threats, the most likely here this season first: the lots' troubles of last season, then what this season's weather (the outlook, ENSO, the twenty year record of these months) favours, then the crop's worst. For each threat: kind (insect, disease, weed or other); stages = the stage names it strikes; sign at most 16 words; threshold at most 24 words = when the numbers make acting worth it (an economic threshold from the notes or established IPM in plain counts, like "2 egg masses a square meter" or "1 in 10 hills hit"; where none exists, how to judge the spread); cultural at most 30 words = what to do first without a spray (resistant variety, clean seed, water, timing, natural enemies, removing sick plants); ai = the active ingredients to use only once the threshold is reached, CHOSEN ONLY FROM THAT THREAT'S LIST ABOVE and written exactly as listed (an empty list where the list is empty or no spray helps); rotate at most 18 words = the group to switch to for a second spray, never the same group twice in a row; weather at most 16 words = when this season's weather raises the risk ("" when it does not matter). Weeds are one threat per crop with ingredients only from its weed windows. No brand names, no product names, no rates or doses: the farmer decides whether to spray from what the field shows.
- protection.intro at most 40 words: how to scout this crop this season (how often, where in the field).
- background.place at most 40 words; weather.outlook at most 55 words for the months the crops stand; enso at most 30 words; risks 2 to 4 items of at most 12 words; history at most 45 words = what the area's twenty years say about these months (the wettest weeks, the typhoon months, the dry spells, and how El Nino or La Nina years differed).
- workTips: 2 to 4 lines of at most 22 words on running the work across the lots (the busy weeks, the hands each needs, hiring help), agreeing with the work calendar above: never ask to move a planting day the calendar has already set, and where the calendar says the crew falls short, say what helps.
- headline at most 14 words. summary at most 70 words, plain and warm but factual, ending with the reminder that the crop's stage is the clock, not the calendar. confidence low, moderate or high. dataGaps at most four.
- Plain text only: no markdown, no emoji shortcodes.

Return ONLY a valid JSON object, no code fences, no commentary, in exactly this shape, with one entry in crops for every crop above (its key as given):
{$shape}
PROMPT;
    }

    /** One lot's fertilizer and nutrition plan, held to NPK Plus's need. */
    private function lotPrompt(array $case, array $l, array $c, array $ctx, string $notes): string
    {
        $p = $l['p'];
        $frame = $ctx['frames'][$l['i']];
        $rec = $ctx['records'][$l['i']] ?? null;
        $fd = $this->lotField($l);
        $f = $fd['f'];
        $country = \App\Support\Region::name();
        $region = \App\Support\Region::promptBlock();
        $unitWord = self::yieldUnits()[$p['yieldUnit']] ?? array_values(self::yieldUnits())[0];
        $known = ! empty($p['npkNeed']['known']);
        $when = $l['day'] ? \Illuminate\Support\Carbon::parse($l['day'])->format('F j, Y') : \Illuminate\Support\Carbon::parse($l['month'] . '-01')->format('F Y') . ' (the day is set by the farm\'s work calendar)';
        $aimRule = $known
            ? 'bend the products and the splits to the aim, the soil and the troubles, while the recommended program meets THE NUTRIENT NEED above (the aim is already in it through the yield goal)'
            : 'bend them to the aim (highest yield = the fuller recommended rate; lower cost = the lean end; in the middle = the standard recommendation) and to the soil and troubles';
        $programRule = $known
            ? 'The recommended program must deliver THE NUTRIENT NEED above, within about a tenth either way; the other two say in their "for" line how they differ (a lean one that falls short says so). The app checks every program against that need and shows the farmer each gap'
            : 'Every program must add up to the need for its own aim; the app checks each one';
        $history = $ctx['history'][$l['place']] ?? '';
        $weather = ($ctx['enso'] !== '' ? 'ENSO: ' . $ctx['enso'] . ' ' : '') . ($history !== '' ? 'The area\'s record for these months: ' . mb_substr(preg_replace('/\s+/', ' ', $history), 0, 1600) : '');
        $recLine = $rec ? '- The variety ' . (! empty($rec['learned']) ? 'from an earlier lookup' : 'on record') . ': ' . ltrim(\App\Support\VarietyBook::promptLines([$rec]), '- ') . "\n" : '';
        $shape = '{"intro":"","granulars":{"soilLogic":"","timingLogic":"","rejected":[{"what":"","why":""}],"cautions":[""]},"programs":[{"name":"","for":"","applications":[{"stage":"","day":0,"product":"","bagsPerHa":0,"kgPerHa":0,"purpose":""}]}],"npk":{"note":""},"deficiencies":[{"nutrient":"","why":"","signs":"","when":"","action":""}],"foliars":[{"stage":"","product":"","why":""}],"water":{"plan":"","ifDry":"","ifWet":""},"yield":{"target":"","realistic":"","note":""},"watch":[""]}';
        $notesText = trim($notes) !== '' ? trim($notes) : 'none found; rely on established agronomy and say so';

        return <<<PROMPT
You are an agronomic decision-support analyst for farming in {$country}. Write the FERTILIZER AND NUTRITION PLAN of ONE LOT for this season, by growth stage, for a farmer who reads it on a phone. The farm's background, stage guide and protection are written separately: only fertilizer, nutrition, water and yield here.

THE LOT
- {$region}
- Lot: {$l['name']}, {$l['place']}; {$f['area']} hectare(s)
- Crop: {$f['cropLabel']}; variety: {$f['variety']}
{$recLine}- Planting: {$when}; method: {$f['method']}; counted in {$frame['counter']}
- Its stages (name each application's stage exactly as here, and give its day on this count inside that stage): {$this->stageListFor($frame)}
- Soil, from the lot's own answers: {$fd['soil']}; water: {$fd['water']}
- Soil condition (pH, sodium, salt, acid sulfate), reasoned as a combination where more than one is true: {$fd['cond']}
{$fd['guide']}
- The lot's newest soil test: {$fd['test']}{$f['ph']}
- The previous crop: {$fd['prev']}; its residue: {$fd['residue']}
- Field troubles: {$fd['problems']}
- Field in detail (the farmer's answers about this lot): {$fd['detail']}
- Fertilizers sold near the farmer: {$f['granulars']}
- The farmer's aim: {$f['priority']}
- The variety's yield potential: {$f['potential']}
- Target yield: {$f['target']}
{$f['seed']}{$f['goal']}{$f['need']}- The farmer's own notes: {$f['notes']}
- The weather this season: {$weather}

RESEARCH NOTES for this crop (rely on them first; where they and memory disagree, the notes win):
{$notesText}

GROUND RULES
- EASY TO READ: short plain sentences; a plain word beside any technical one; ranges with "to", never a dash. Word limits are ceilings.
- Every quantity is per hectare (bagsPerHa, a 50 kg bag) and the app scales it to the lot; {$aimRule}. Each application carries its PURPOSE in a few words.
- GRANULARS, THOUGHT THROUGH: choose each product for the soil condition and the water. A sodic or saline soil does not want a chloride load (muriate of potash only where potassium is truly short, late and light; sulfate of potash where it can be bought); ammonium sulfate is the nitrogen that also acidifies an alkaline or sodic soil and feeds sulfur; an acid soil wants its phosphorus placed at planting; flooded rice loses broadcast urea to the air and the water, so small splits into shallow water; a sandy soil leaks nitrogen and potassium, so smaller and more frequent doses; a heavy clay holds them, so fewer and fuller. Where the soil test or history says phosphorus or potassium is already high, do not buy it again. Use what the farmer can buy; where a better product is not on the list, say so in cautions. soilLogic and timingLogic reason in the open, mechanism by mechanism; rejected names the 2 to 3 things a farmer here would reasonably have done instead and why each fails on THIS ground.
- CRITICALITY BY STAGE: size every application to what the crop can take up then. The basal is a starter; the bulk of nitrogen goes where the crop builds yield (tillering and panicle initiation in rice; V6 to V8 and before tasselling in corn; branching and fruit set in vegetables); potassium where straw strength and grain fill are decided. Keep any single nitrogen dressing within the safe range (rice about 1 to 2 bags of urea a hectare a split, corn about 2 to 3) and say the ceiling in timingLogic. Credit what a legume or ploughed in residue left.
- SECONDARY AND MICRO ELEMENTS ARE PART OF THE PROGRAM: the recommended program carries what the main fertilizers bring (ammonium sulfate 24 percent S, solophos 12 percent S and 20 percent Ca, gypsum 23 percent Ca and 18 percent S, dolomite 21 percent Ca and 12 percent Mg) and adds a soil product for an element in THE SECONDARY AND MICRO ELEMENT NEED only where this soil truly calls for it (zinc sulfate on flooded or alkaline rice, borax for fruiting crops on sandy ground), as kgPerHa and never past the need (boron burns crops just above it). The app adds what is still short after the recommended program, by the kilo or as a spray, and shows it: do not pile products on to be safe.
- THREE PROGRAMS, honest about what each is for: the first is your recommendation; the second a lean low cost program; the third a genuinely different strategy that makes sense HERE (soil first, one simple product, LCC guided, a fuller push). {$programRule}. Each: name at most 4 words; for at most 22 words; applications 3 to 9 = {stage exactly as listed, day on the count inside that stage, product with its grade in the name ("Urea 46-0-0", "Complete 14-14-14", "Ammonium sulfate 21-0-0", "Ammonium phosphate 16-20-0", "Muriate of potash 0-0-60", "Zinc sulfate"), bagsPerHa (decimals allowed) or kgPerHa for a product used by the kilo, purpose at most 14 words}.
- NO ORGANIC FERTILIZER (no compost, manure, vermicast or biofertilizer) and NO PRICES anywhere.
- npk.note at most 30 words: the one check in the field that could change the need (the leaf color chart for rice, a soil test, a thin stand), never a different number.
- DEFICIENCIES BY SOIL: 2 to 4, most likely first, starting from the elements flagged in THE SECONDARY AND MICRO ELEMENT NEED: nutrient; why at most 26 words (this soil, this crop); signs at most 22 words; when = the stage; action at most 26 words in plain words, no amounts and no product names.
- FOLIARS: 0 to 3, only where they pay here, each the stage, the product named by its nutrient only ("Zinc spray", "Boron spray", never a salt, a chemical or a strength) and why at most 16 words.
- WATER: plan at most 55 words for this lot's water source; ifDry and ifWet at most 22 words each.
- YIELD: target = the farmer's target restated in {$unitWord} (or "not set"); realistic = what this variety realistically gives here, the same unit, resting first on the REAL HARVESTS in the notes; note at most 36 words naming the harvest it rests on.
- watch: 0 to 3 lines of at most 22 words on what this lot's own answers warn of (its troubles last season, floods, salt, a thin stand) and what to do.
- intro at most 70 words: the approach for this lot and the one or two things that matter most.
- granulars: soilLogic 70 to 130 words; timingLogic 70 to 130 words; rejected 2 to 3 items (what at most 12 words, why at most 40); cautions 2 to 3 items of at most 18 words.
- Plain text only: no markdown, no emoji shortcodes.

Return ONLY a valid JSON object, no code fences, no commentary, in exactly this shape, with EXACTLY THREE programs:
{$shape}
PROMPT;
    }

    private function stageListFor(array $frame): string
    {
        return self::stageList($frame);
    }

    /** A JSON object from the model's text, its shortcodes swept. Null when it cannot be read. */
    private function readJson(string $text): ?array
    {
        $text = trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text)));
        $from = strpos($text, '{');
        $to = strrpos($text, '}');
        if ($from === false || $to === false || $to <= $from) {
            return null;
        }
        $json = json_decode(substr($text, $from, $to - $from + 1), true);
        if (! is_array($json)) {
            return null;
        }
        array_walk_recursive($json, function (&$v) {
            if (is_string($v)) {
                $v = trim((string) preg_replace('/:[a-z0-9_-]+:/i', '', $v));
            }
        });

        return $json;
    }

    /** The farm part, with an entry for every crop (a crop the model left out keeps the app's own stages and catalogue). */
    private function parseFarm(string $text, array $cases): ?array
    {
        $j = $this->readJson($text);
        if (! $j || ! isset($j['crops']) || ! is_array($j['crops']) || ! isset($j['summary'])) {
            return null;
        }
        $by = [];
        foreach ($j['crops'] as $k => $c) {
            if (is_array($c)) {
                $by[(string) ($c['key'] ?? ('c' . ($k + 1)))] = $c;
            }
        }
        if (! array_intersect(array_keys($by), array_column($cases, 'key'))) {
            return null;
        }
        $j['crops'] = $by;

        return $j;
    }

    /** One lot's plan: at least one program with applications. */
    private function parseLot(string $text): ?array
    {
        $j = $this->readJson($text);
        if (! $j || ! is_array($j['programs'] ?? null)) {
            return null;
        }
        $j['programs'] = array_values(array_filter($j['programs'], fn ($pg) => is_array($pg) && ! empty($pg['applications']) && is_array($pg['applications'])));

        return $j['programs'] ? $j : null;
    }

    /**
     * The report, format 4: the farm (background, crops with their stages and
     * protection, the work calendar, what to buy) and every lot (its need, its
     * programs checked like NPK Plus, what closes the gaps, its jobs by date).
     */
    private function compose(array $case, array $ctx, array $farm, array $lotOut): array
    {
        $cases = $ctx['cases'];
        $crops = [];
        foreach ($cases as $c) {
            $l0 = $case['lots'][$c['lots'][0]];
            $frame = $ctx['frames'][$l0['i']];
            $words = (array) ($farm['crops'][$c['key']] ?? []);
            $stageWords = [];
            foreach ((array) ($words['stages'] ?? []) as $s) {
                $i = is_array($s) ? ProtocolCrew::stageIndex($frame, (string) ($s['stage'] ?? '')) : -1;
                if ($i >= 0 && ! isset($stageWords[$i])) {
                    $stageWords[$i] = $s;
                }
            }
            $stages = [];
            foreach ($frame['stages'] as $i => $s) {
                $stages[] = ['name' => $s['name'], 'from' => $s['from'], 'to' => $s['to'], 'days' => self::daysLabel($frame, $s), 'what' => $s['what'], 'needs' => $s['needs'], 'prep' => ! empty($s['prep']),
                    'signs' => (string) ($stageWords[$i]['signs'] ?? ''), 'observe' => (string) ($stageWords[$i]['observe'] ?? ''), 'intervene' => (string) ($stageWords[$i]['intervene'] ?? '')];
            }
            $crops[$c['key']] = [
                'key' => $c['key'], 'crop' => $c['crop'], 'cropLabel' => self::cropName($c['crop']), 'icon' => CropCatalog::CROPS[$c['crop']]['icon'] ?? '🌱',
                'method' => $c['method'], 'methodLabel' => self::METHODS[$c['method']]['label'] ?? $c['method'], 'variety' => $c['variety'], 'counter' => $frame['counter'],
                'varietyInfo' => (array) ($words['variety'] ?? []), 'record' => ! empty($ctx['records'][$l0['i']]) ? \App\Support\VarietyBook::brief($ctx['records'][$l0['i']]) : null,
                'stages' => $stages,
                'protection' => ProtocolCare::attach((array) ($words['protection'] ?? []), $ctx['threats'][$c['key']] ?? [], $ctx['weeds'][$c['key']] ?? null, $c['crop'], $frame),
                'weeds' => ProtocolCare::weedsForReport($ctx['weeds'][$c['key']] ?? null, $c['crop']),
                'lots' => $c['lots'],
            ];
        }

        $lots = [];
        $schedLots = [];
        foreach ($case['lots'] as $l) {
            $c = self::caseOf($cases, $l['i']);
            $frame = $ctx['frames'][$l['i']];
            $out = $lotOut[$l['i']];
            $p = $l['p'];
            $area = (float) $p['area'];
            $fake = ['recommendation' => [
                'stages' => array_map(fn ($s) => ['stage' => $s['name'], 'days' => self::daysLabel($frame, $s)], $frame['stages']),
                'programs' => array_slice($out['programs'], 0, 3),
                'foliars' => array_values(array_filter((array) ($out['foliars'] ?? []), 'is_array')),
                'npk' => ['need' => [], 'note' => (string) ($out['npk']['note'] ?? '')],
            ]];
            $rec = $this->tidy($fake, $p)['recommendation'];
            $main = $rec['programs'][0] ?? null;
            $conds = self::soilConditions($p['soilConditions'] ?? []);
            $micro = $main ? ProtocolCare::micro((array) ($main['elements'] ?? []), $frame, $conds, $area) : [];
            $fixes = $main ? ProtocolCare::fixes((array) ($main['check'] ?? []), (array) ($main['byStage'] ?? []), $frame, $conds, $area, (array) $p['granulars']) : [];

            // The jobs: the recommended program by its days, the soil top ups with the first application, the sprays.
            $apps = [];
            foreach ((array) ($out['programs'][0]['applications'] ?? []) as $a) {
                if (! is_array($a) || trim((string) ($a['product'] ?? '')) === '') {
                    continue;
                }
                $bags = max(0, (float) ($a['bagsPerHa'] ?? 0));
                $kg = max(0, (float) ($a['kgPerHa'] ?? 0));
                $amt = $bags > 0 ? self::bagWords($bags * $area) : ProtocolCare::amount($kg * $area);
                $apps[] = ['stage' => (string) ($a['stage'] ?? ''), 'day' => $a['day'] ?? null, 'products' => [$amt . ' ' . trim((string) $a['product'])]];
            }
            if (! $apps) {
                $apps = ProtocolCrew::skeleton($frame);
            }
            // What closes a gap goes on with the application already at that stage (one trip), else on its own.
            $join = function (string $stage, ?int $day, string $words) use (&$apps, $frame) {
                $si = ProtocolCrew::stageIndex($frame, $stage);
                foreach ($apps as &$ap) {
                    $ai = ProtocolCrew::stageIndex($frame, (string) ($ap['stage'] ?? ''));
                    if (($si >= 0 && $ai === $si) || ($day !== null && $day <= 3 && ProtocolCrew::dayFor($frame, (string) ($ap['stage'] ?? ''), $ap['day'] ?? null) <= 3)) {
                        $ap['products'][] = $words;

                        return;
                    }
                }
                unset($ap);
                $apps[] = ['stage' => $stage, 'day' => $day, 'products' => [$words]];
            };
            foreach ($fixes as $fx) {
                $join($fx['stage'], null, self::bagWords((float) $fx['bags']) . ' ' . $fx['product'] . ' (to meet the need)');
            }
            foreach ($micro as $m) {
                if ($m['pick'] === 'soil' && $m['soil']) {
                    $join($frame['stages'][0]['name'], -1, ProtocolCare::amount((float) $m['soil']['totalKg']) . ' ' . $m['soil']['product']);
                }
            }
            // The sprays, one trip a stage ("Zinc and boron spray").
            $byStage = [];
            $addSpray = function (string $stage, string $nutrient, string $what) use (&$byStage, $frame) {
                $si = max(0, ProtocolCrew::stageIndex($frame, $stage));
                $byStage[$si] ??= ['stage' => $frame['stages'][$si]['name'], 'names' => [], 'what' => []];
                if (! in_array($nutrient, $byStage[$si]['names'], true)) {
                    $byStage[$si]['names'][] = $nutrient;
                }
                if ($what !== '') {
                    $byStage[$si]['what'][] = $what;
                }
            };
            foreach ($micro as $m) {
                if ($m['pick'] === 'spray' && $m['spray'] && $m['state'] !== 'spray') {
                    $addSpray((string) $m['spray']['stage'], (string) $m['name'], $m['name'] . ' is short on this lot.');
                }
            }
            foreach ((array) ($rec['foliars'] ?? []) as $fo) {
                $t = trim((string) preg_replace('/\s*spray$/i', '', (string) ($fo['product'] ?? '')));
                if ($t !== '') {
                    $addSpray((string) ($fo['stage'] ?? ''), $t, (string) ($fo['why'] ?? ''));
                }
            }
            ksort($byStage);
            $sprays = array_map(function ($s) {
                $n = $s['names'];
                $head = count($n) > 1 ? implode(', ', array_slice($n, 0, -1)) . ' and ' . mb_strtolower((string) end($n)) : ($n[0] ?? 'Foliar');

                return ['stage' => $s['stage'], 'title' => ucfirst(mb_strtolower($head)) . ' spray', 'what' => implode(' ', array_unique($s['what']))];
            }, array_values($byStage));
            $schedLots[] = ['i' => $l['i'], 'name' => $l['name'], 'month' => $l['month'], 'asked' => $l['day'],
                'tasks' => ProtocolCrew::tasks($frame, $apps, array_slice($sprays, 0, 4), ['weedy' => self::weedy($l['profile'])])];

            $lots[$l['i']] = [
                'i' => $l['i'], 'name' => $l['name'], 'place' => $l['place'], 'areaHa' => $area, 'farmLotId' => $l['farmLotId'],
                'crop' => $l['crop'], 'cropLabel' => self::cropName($l['crop']), 'icon' => CropCatalog::CROPS[$l['crop']]['icon'] ?? '🌱',
                'method' => $l['method'], 'methodLabel' => self::METHODS[$l['method']]['label'] ?? $l['method'], 'variety' => $l['variety'], 'case' => $c['key'],
                'counter' => $frame['counter'], 'month' => $l['month'], 'asked' => $l['day'], 'seedAge' => $frame['seedAge'], 'harvestDays' => $frame['harvest'],
                'priority' => $l['priority'], 'priorityLabel' => self::PRIORITIES[$l['priority']]['label'] ?? '', 'targetYield' => $l['targetYield'], 'yieldUnit' => $l['yieldUnit'],
                'yieldWord' => preg_replace('/ per hectare$/', '', (string) (self::yieldUnits()[$l['yieldUnit']] ?? 'tons')), 'potential' => $l['potential'],
                'field' => \App\Support\FieldProfile::pairs((array) $l['profile'], $l['crop']), 'soilConditions' => $conds,
                'need' => $p['npkNeed'] ?? null,
                'rec' => $rec,
                'intro' => (string) ($out['intro'] ?? ''), 'granulars' => (array) ($out['granulars'] ?? []),
                'deficiencies' => array_values(array_filter((array) ($out['deficiencies'] ?? []), 'is_array')),
                'water' => (array) ($out['water'] ?? []), 'yield' => (array) ($out['yield'] ?? []),
                'watch' => array_values(array_filter(array_map('strval', (array) ($out['watch'] ?? [])))),
                'micro' => $micro, 'fixes' => $fixes,
            ];
        }

        $sched = ProtocolCrew::schedule($schedLots, $case['crew'], $case['mode']);
        foreach ($lots as $i => &$L) {
            $L['planting'] = $sched['lots'][$i] ?? null;
            $L['tasks'] = array_values(array_filter($sched['tasks'], fn ($t) => $t['lot'] === $i));
            $h = collect($L['tasks'])->firstWhere('kind', 'harvest');
            $L['harvest'] = $h ? ['from' => $h['from'], 'to' => $h['to'], 'usual' => $h['usual']] : null;
        }
        unset($L);

        // What to buy for the whole farm: the recommended programs, what meets the need, and the top ups.
        $buy = [];
        $addBuy = function (string $name, float $kg, int $lot) use (&$buy) {
            if ($kg <= 0) {
                return;
            }
            $k = mb_strtolower(trim($name));
            $buy[$k] ??= ['product' => trim($name), 'kg' => 0.0, 'lots' => []];
            $buy[$k]['kg'] += $kg;
            $buy[$k]['lots'][$lot] = true;
        };
        foreach ($lots as $i => $L) {
            foreach ((array) ($L['rec']['totals'] ?? []) as $t) {
                $addBuy((string) $t['product'], (float) $t['bags'] * 50, $i);
            }
            foreach ($L['fixes'] as $fx) {
                $addBuy($fx['product'], (float) $fx['bags'] * 50, $i);
            }
            foreach ($L['micro'] as $m) {
                if ($m['pick'] === 'soil' && $m['soil']) {
                    $addBuy($m['soil']['product'], (float) $m['soil']['totalKg'], $i);
                }
            }
        }
        $buy = array_values(array_map(fn ($b) => ['product' => $b['product'], 'kg' => round($b['kg'], 2), 'bags' => round($b['kg'] / 50, 2), 'lots' => array_keys($b['lots'])], $buy));
        usort($buy, fn ($a, $b) => $b['kg'] <=> $a['kg']);

        $names = array_map(fn ($l) => $l['name'], $case['lots']);

        return [
            'format' => 4,
            'headline' => (string) ($farm['headline'] ?? ''),
            'summary' => (string) ($farm['summary'] ?? ''),
            'confidence' => in_array($farm['confidence'] ?? '', ['low', 'moderate', 'high'], true) ? $farm['confidence'] : 'moderate',
            'dataGaps' => array_values(array_slice(array_filter(array_map('strval', (array) ($farm['dataGaps'] ?? []))), 0, 5)),
            'background' => (array) ($farm['background'] ?? []),
            'workTips' => array_values(array_slice(array_filter(array_map('strval', (array) ($farm['workTips'] ?? []))), 0, 4)),
            'farm' => ['lots' => count($lots), 'areaHa' => round(array_sum(array_column($lots, 'areaHa')), 4), 'mode' => $case['mode'], 'location' => $case['location'],
                'crew' => ['workers' => array_map(fn ($w) => ['name' => $w['name'], 'days' => $w['days'], 'roster' => ! empty($w['globalId'])], $case['crew']['workers']),
                    'sprayers' => $case['crew']['sprayers'], 'plantBy' => $case['crew']['plantBy']],
                'granulars' => array_map(fn ($k) => self::GRANULARS[$k] ?? $k, $case['granulars']), 'notes' => $case['notes']],
            'crops' => array_values($crops),
            'lots' => array_values($lots),
            'schedule' => $sched + ['words' => ProtocolCrew::words($sched, $names)],
            'buy' => $buy,
            'disclaimer' => ProtocolCare::DISCLAIMER,
        ];
    }

    /** "2 bags", "1.5 bags", "20 kg" (under a bag). */
    private static function bagWords(float $bags): string
    {
        if ($bags < 1) {
            return ProtocolCare::amount($bags * 50);
        }
        $n = rtrim(rtrim(number_format($bags, 1, '.', ''), '0'), '.');

        return $n . ($n === '1' ? ' bag' : ' bags');
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

        // Anything else the NPK Plus shelf knows (2026-10-16): the farmer's own
        // products, sulfate of potash, gypsum and the rest, read the same way.
        try {
            $hit = NpkPlan::match($name);
        } catch (\Throwable) {
            $hit = null;
        }
        // A shelf product with no N, P or K (gypsum, lime, zinc) is read, and gives none.
        if ($hit && empty($hit['estimate'])) {
            return [(float) ($hit['pct']['N'] ?? 0), (float) ($hit['pct']['P2O5'] ?? 0), (float) ($hit['pct']['K2O'] ?? 0)];
        }

        return null;
    }

    /** An application's 50 kg bags per hectare, from its bags or, for a product used by the kilo, its kgPerHa. */
    private static function bagsOf(array $ap): float
    {
        $bags = max(0, (float) ($ap['bagsPerHa'] ?? 0));
        $kg = max(0, (float) ($ap['kgPerHa'] ?? 0));

        return $bags > 0 ? $bags : round($kg / 50, 4);
    }

    /**
     * A product's whole analysis, percent by element, as the NPK Plus shelf
     * has it (NpkPlan::match: the grade, the name, the farmer's own), so
     * ammonium sulfate brings its sulfur and gypsum its calcium; a grade the
     * shelf does not know is read from its numbers; null when nothing reads.
     */
    private static function analysisOf(string $name): ?array
    {
        try {
            $hit = NpkPlan::match($name);
        } catch (\Throwable) {
            $hit = null;
        }
        if ($hit && empty($hit['estimate']) && ! empty($hit['pct'])) {
            return ['slug' => $hit['slug'] ?? null, 'pct' => array_map('floatval', $hit['pct'])];
        }
        $g = self::grade($name);

        return $g === null ? null : ['slug' => null, 'pct' => array_filter(['N' => (float) $g[0], 'P2O5' => (float) $g[1], 'K2O' => (float) $g[2]])];
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
                $perHa = self::bagsOf($ap);
                $ap['bagsPerHa'] = $perHa;
                $ap['kgPerHa'] = round($perHa * 50, 2);
                $ap['totalBags'] = round($perHa * $area, 2);
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
        /* The need is NPK Plus's count (2026-10-16), not the model's: the
         * programs are checked against the same number the calculator
         * would give for this field. The model's own reading is kept beside
         * it, only to see how far the two drift. */
        $app = (array) ($p['npkNeed'] ?? []);
        if (! empty($app['known'])) {
            $npk['modelNeed'] = $need;
            $need = ['n' => (float) ($app['need']['N'] ?? 0), 'p' => (float) ($app['need']['P2O5'] ?? 0), 'k' => (float) ($app['need']['K2O'] ?? 0)];
            $npk['need'] = $need;
            $npk['source'] = 'npkplus';
            $goal = $app['goal'] ?? null;
            if ($goal) {
                $tons = (float) (self::yieldTons()[$p['yieldUnit'] ?? ''] ?? 1);
                $say = fn ($x) => rtrim(rtrim(number_format((float) $x / $tons, 2, '.', ''), '0'), '.');
                $unit = preg_replace('/ per hectare$/', '', (string) (self::yieldUnits()[$p['yieldUnit'] ?? ''] ?? 'tons'));
                $npk['basis'] = 'The need is counted the way NPK Plus counts it, for ' . $say($goal['eff']) . ' ' . $unit . ' per hectare'
                    . (abs($goal['eff'] - $goal['t']) > 0.01 ? ' (' . $say($goal['t']) . ' ' . $unit . ', ' . $goal['why'] . ', less for ' . (! empty($app['seed']['thin']) ? (! empty($goal['capped']) ? 'a thin stand and what your field can carry' : 'a thin stand') : 'what your field can carry') . ')' : ' (' . $goal['why'] . ')')
                    . ', on your soil and your field\'s history.';
            }
        }

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
        /* THE CHECK, NPK PLUS'S OWN (2026-10-17, the owner: the protocol's
         * numbers must not be "far away different from the npk plus
         * calculator"). Every element a program delivers, from each product's
         * own analysis on the NPK Plus shelf (ammonium sulfate brings its
         * sulfur, solophos its calcium and sulfur), the acid it adds and the
         * lockouts a heavy dose makes, against the need NPK Plus gives for
         * this field with this very program as the plan, read with the page's
         * own thresholds (NpkNeed::check): typed into NPK Plus, the program
         * reads the same there. A crop NPK Plus has no numbers for keeps the
         * old check: N, P2O5 and K2O against the model's own need. */
        $app = (array) ($p['npkNeed'] ?? []);
        $known = ! empty($app['known']);
        $needIn = $known ? self::needInput($p) : null;
        $sprayed = [];
        foreach ((array) ($report['recommendation']['foliars'] ?? []) as $fol) {
            $t = mb_strtolower((string) ($fol['product'] ?? '') . ' ' . (string) ($fol['why'] ?? ''));
            foreach (NpkNeed::NAMES as $el => $name) {
                if (str_contains($t, mb_strtolower($name))) {
                    $sprayed[$el] = (string) ($fol['stage'] ?? '');
                }
            }
        }
        $checkOf = function (array $byStage) use ($need, $area, $known, $needIn, $sprayed) {
            $perHa = [];
            $lines = [];
            $unknown = [];
            foreach ($byStage as $apps) {
                foreach ($apps as $ap) {
                    $an = self::analysisOf((string) ($ap['product'] ?? ''));
                    if ($an === null) {
                        $unknown[] = (string) ($ap['product'] ?? '');
                        continue;
                    }
                    $kg = (float) $ap['bagsPerHa'] * 50;
                    foreach ($an['pct'] as $n => $v) {
                        $perHa[$n] = ($perHa[$n] ?? 0) + $kg * (float) $v / 100;
                    }
                    $lines[] = [$an['slug'], $kg, $an['pct']];
                }
            }
            $run = $known ? NpkNeed::run($needIn + ['plan' => ['perHa' => $perHa, 'acidEq' => NpkNeed::acidEq($lines)]]) : null;
            $want = $run ? ['n' => (float) ($run['need']['N'] ?? 0), 'p' => (float) ($run['need']['P2O5'] ?? 0), 'k' => (float) ($run['need']['K2O'] ?? 0)] : $need;
            $has = ['n' => (float) ($perHa['N'] ?? 0), 'p' => (float) ($perHa['P2O5'] ?? 0), 'k' => (float) ($perHa['K2O'] ?? 0)];
            $rows = [];
            foreach (['n' => 'N', 'p' => 'P₂O₅', 'k' => 'K₂O'] as $key => $label) {
                $have = round($has[$key]);
                $w = max(0, (float) ($want[$key] ?? 0));
                $gap = $w > 0 ? $have - $w : null;
                // NPK Plus's own line: short under 90 percent of the need, too much past 130.
                $r = $w > 0 ? $has[$key] / $w : null;
                $rows[] = [
                    'key' => $key, 'label' => $label,
                    'have' => $have, 'need' => $w > 0 ? round($w) : null,
                    'haveField' => round($has[$key] * $area), 'needField' => $w > 0 ? round($w * $area) : null,
                    'verdict' => $r === null ? 'unchecked' : ($r < 0.9 ? 'short' : ($r > 1.3 ? 'over' : 'ok')),
                    'gap' => $gap === null ? null : round($gap),
                ];
            }
            // The secondary and micro elements, each as the Micros tab reads it; a short one a foliar names is given by spray.
            $elements = [];
            if ($run) {
                foreach (NpkNeed::check($run, $perHa) as $row) {
                    if (in_array($row['key'], NpkNeed::NPK, true)) {
                        continue;
                    }
                    if (in_array($row['state'], ['short', 'noflag'], true) && isset($sprayed[$row['key']]) && $row['need'] !== null) {
                        $row['state'] = 'spray';
                        $row['sprayAt'] = $sprayed[$row['key']];
                    }
                    $row['needField'] = $row['need'] !== null ? round($row['need'] * $area, 3) : null;
                    $row['planField'] = round($row['plan'] * $area, 3);
                    $elements[] = $row;
                }
            }

            return ['delivered' => ['n' => round($has['n']), 'p' => round($has['p']), 'k' => round($has['k'])], 'check' => $rows, 'elements' => $elements,
                'lockouts' => $run['lockouts'] ?? [], 'whys' => $run['whys'] ?? [],
                'unknownProducts' => array_values(array_unique(array_filter($unknown)))];
        };
        $programs = [];
        foreach (array_slice((array) ($report['recommendation']['programs'] ?? []), 0, 5) as $pi => $pg) {
            $byStage = array_fill(0, count($stages), []);
            $orphans = [];
            foreach ((array) ($pg['applications'] ?? []) as $ap) {
                if ($organic((string) ($ap['product'] ?? ''))) {
                    continue;
                }
                $perHa = self::bagsOf($ap);
                $row = ['product' => trim((string) ($ap['product'] ?? 'Fertilizer')) ?: 'Fertilizer', 'bagsPerHa' => $perHa, 'kgPerHa' => round($perHa * 50, 2), 'totalBags' => round($perHa * $area, 2), 'purpose' => (string) ($ap['purpose'] ?? '')];
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
                'totals' => collect($tot)->map(fn ($perHa, $name) => ['product' => $name, 'bagsPerHa' => round($perHa, 1), 'bags' => round($perHa * $area, 2)])->values()->all(),
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
        // The recommended program's check, the same arithmetic as every program's.
        $main = $programs ? $programs[0] : $checkOf(array_map(fn ($st) => (array) ($st['fertilizer'] ?? []), $report['recommendation']['stages']));
        $npk['delivered'] = $main['delivered'];
        $npk['check'] = $main['check'];
        $npk['elements'] = $main['elements'];
        $npk['unknownProducts'] = $main['unknownProducts'];
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
        // One ask may run AI_TIMEOUT (a research ask TIMEOUT_SEARCHED); a run of eight lots is a dozen asks.
        $quiet = max(AiClient::TIMEOUT_DOCUMENT, self::AI_TIMEOUT) + 90;

        return $beatAt->lt(now()->subSeconds($quiet))
            || \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(self::MAX_MINUTES));
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
                $why = \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(self::MAX_MINUTES))
                    ? 'The protocol took too long and was stopped. Nothing was charged. Please try again.'
                    : 'The protocol was stopped halfway because the server restarted. Nothing was charged. Please run it again.';
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
        if ((int) ($report['format'] ?? 0) === 4) {
            return ['title' => $r->title, 'text' => self::contextV4($r->title, $report)];
        }
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
                . FieldProfile::promptLine((array) ($params['fieldProfile'] ?? []), $params['crop'] ?? null, '')
                . 'Weather ahead: ' . ($bg['weather']['outlook'] ?? '') . (! empty($bg['weather']['enso']) ? ' ENSO: ' . $bg['weather']['enso'] : '') . "\n"
                . 'Variety: ' . ($v['name'] ?? '') . (! empty($v['by']) ? ' by ' . $v['by'] : '') . (! empty($v['maturityDays']) ? ', ' . $v['maturityDays'] . ' days' : '') . (! empty($v['yieldPotential']) ? ', ' . $v['yieldPotential'] : '') . ' — ' . ($v['traits'] ?? '') . "\n"
                . 'Approach: ' . ($rec['intro'] ?? '') . "\n"
                . (! empty($rec['granulars']['soilLogic']) ? 'Why these granulars: ' . $rec['granulars']['soilLogic'] . ' ' . ($rec['granulars']['timingLogic'] ?? '') . "\n" : '')
                . (! empty($rec['granulars']['rejected']) ? 'Set aside: ' . collect($rec['granulars']['rejected'])->map(fn ($r) => ($r['what'] ?? '') . ' — ' . ($r['why'] ?? ''))->implode('; ') . "\n" : '')
                . (! empty($rec['programs']) ? 'Fertilizer programs offered: ' . collect($rec['programs'])->map(fn ($g) => ($g['name'] ?? '') . ' (' . ($g['totalBags'] ?? '') . ' bags)')->implode(', ') . ' — the first is the recommended one below.' . "\n" : '')
                . 'Fertilizer by stage: ' . $fert . "\n"
                . 'Totals for the field: ' . collect($rec['totals'] ?? [])->map(fn ($t) => ($t['bags'] ?? '') . ' bags ' . ($t['product'] ?? ''))->implode(', ') . "\n"
                . (! empty($rec['npk']['check']) ? 'Nutrients per hectare (program delivers / target needs): ' . collect($rec['npk']['check'])->map(fn ($c) => $c['label'] . ' ' . $c['have'] . ($c['need'] !== null ? ' / ' . $c['need'] . ' kg (' . $c['verdict'] . ')' : ' kg'))->implode('; ') . "\n" : '')
                . (! empty($rec['npk']['basis']) ? $rec['npk']['basis'] . "\n" : '')
                . (! empty($params['npkNeed']['seed']) ? 'Seed: ' . $params['npkNeed']['seed']['perHa'] . ' ' . $params['npkNeed']['seed']['unit'] . ' per hectare. ' . $params['npkNeed']['seed']['words'] . "\n" : '')
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

    /** A farm protocol (format 4) as the chat reads it: the farm, then every lot. */
    private static function contextV4(string $title, array $r): string
    {
        $bg = (array) ($r['background'] ?? []);
        $s = (array) ($r['schedule'] ?? []);
        $t = "\n\n--- ATTACHED: Crop Protocol Analysis for a farm (the farmer generated this earlier; treat it as shared context) ---\n"
            . 'Case: ' . $title . "\n"
            . 'Headline: ' . ($r['headline'] ?? '') . "\n"
            . 'Place: ' . ($bg['place'] ?? '') . ' Weather ahead: ' . ($bg['weather']['outlook'] ?? '') . (! empty($bg['weather']['enso']) ? ' ENSO: ' . $bg['weather']['enso'] : '') . "\n"
            . 'Work calendar (' . (($s['mode'] ?? '') === 'keep' ? 'the farmer kept the dates' : 'Anee moved jobs inside their safe windows') . '): ' . implode(' ', (array) ($s['words'] ?? [])) . "\n";
        foreach ((array) ($r['crops'] ?? []) as $c) {
            $threats = collect($c['protection']['threats'] ?? [])->map(fn ($x) => ($x['name'] ?? '') . ' at ' . implode(', ', array_column($x['stages'] ?? [], 'name'))
                . (! empty($x['threshold']) ? ' (act when ' . $x['threshold'] . ')' : '') . (! empty($x['ai']) ? ': ' . implode(', ', array_map(fn ($a) => $a['name'] . ($a['group'] ? ' ' . $a['group'] : ''), $x['ai'])) : ''))->implode(' | ');
            $t .= 'Crop ' . ($c['cropLabel'] ?? '') . ($c['variety'] ? ' (' . $c['variety'] . ')' : '') . ', ' . ($c['methodLabel'] ?? '') . '. Protection to watch: ' . $threats . "\n";
        }
        foreach ((array) ($r['lots'] ?? []) as $l) {
            $rec = (array) ($l['rec'] ?? []);
            $fert = collect($rec['stages'] ?? [])->filter(fn ($st) => ! empty($st['fertilizer']))->map(fn ($st) => ($st['stage'] ?? '') . ': '
                . collect($st['fertilizer'])->map(fn ($x) => ($x['totalBags'] ?? '') . ' bags ' . ($x['product'] ?? ''))->implode(', '))->implode(' | ');
            $check = collect($rec['npk']['check'] ?? [])->map(fn ($c) => $c['label'] . ' ' . $c['have'] . ($c['need'] !== null ? ' of ' . $c['need'] . ' kg (' . $c['verdict'] . ')' : ''))->implode('; ');
            $micro = collect($l['micro'] ?? [])->filter(fn ($m) => $m['pick'] !== 'covered')
                ->map(fn ($m) => $m['name'] . ': ' . ($m['pick'] === 'soil' && $m['soil'] ? $m['soil']['say'] : ($m['spray']['name'] ?? '') . ' at ' . ($m['spray']['stage'] ?? '')))->implode('; ');
            $t .= '- ' . $l['name'] . ' (' . $l['place'] . ', ' . $l['areaHa'] . ' ha): ' . $l['cropLabel'] . ($l['variety'] ? ' ' . $l['variety'] : '') . ', planted ' . ($l['planting']['planting'] ?? $l['month'])
                . (! empty($l['harvest']['usual']) ? ', harvest about ' . $l['harvest']['usual'] : '') . ".\n"
                . '  Fertilizer by stage: ' . $fert . "\n"
                . '  Nutrients per hectare (program gives of the need): ' . $check . "\n"
                . ($micro !== '' ? '  Secondary and micro elements to add: ' . $micro . "\n" : '')
                . (! empty($l['fixes']) ? '  To meet the need: ' . collect($l['fixes'])->map(fn ($f) => $f['bags'] . ' bags ' . $f['product'] . ' at ' . $f['stage'])->implode('; ') . "\n" : '')
                . (! empty($l['yield']['realistic']) ? '  Yield: target ' . ($l['yield']['target'] ?? '') . ', realistic ' . $l['yield']['realistic'] . "\n" : '');
        }

        return $t . 'Summary: ' . ($r['summary'] ?? '') . "\n" . 'Note: applying any pesticide is the farmer\'s own decision from what the field shows.' . "\n--- END OF ATTACHED PROTOCOL ---\n";
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('id', $id)
            ->where('kind', 'protocol')
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Protocol deleted.');
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
            return $this->json(false, 'That protocol is not in your saved list.', [], 404);
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
            // The farm's lot it is for (Global Lots, 2026-10-16), when one was picked.
            'lotId' => is_numeric($request->input('lotId')) ? (int) $request->input('lotId') : null,
            'lotName' => mb_substr(trim((string) $request->input('lotName', '')), 0, 120),
            'soilConditions' => self::soilConditions($request->input('soilConditions', $request->input('soilCondition'))),
            'testP' => array_key_exists((string) $request->input('testP'), self::TEST_LEVELS) ? (string) $request->input('testP') : 'unsure',
            'testK' => array_key_exists((string) $request->input('testK'), self::TEST_LEVELS) ? (string) $request->input('testK') : 'unsure',
            'prevCrop' => array_key_exists((string) $request->input('prevCrop'), self::PREV_CROPS) ? (string) $request->input('prevCrop') : 'unsure',
            'residue' => array_key_exists((string) $request->input('residue'), self::RESIDUES) ? (string) $request->input('residue') : 'unsure',
            'granulars' => WhatToPlantController::picks(self::GRANULARS, $request->input('granulars')),
            'fertHistory' => trim((string) preg_replace('/\s+/', ' ', (string) $request->input('fertHistory', ''))),
            // NPK Plus's own questions (2026-10-16): the potential, the seed, a tested pH.
            'potential' => $request->filled('potential') && (float) $request->input('potential') > 0 ? (float) $request->input('potential') : null,
            'seedAmount' => $request->filled('seedAmount') && (float) $request->input('seedAmount') > 0 ? (float) $request->input('seedAmount') : null,
            'seedUnit' => array_key_exists((string) $request->input('seedUnit'), NpkNeed::SEED_UNITS) ? (string) $request->input('seedUnit') : 'kg',
            'germination' => $request->filled('germination') ? max(1.0, min(100.0, (float) $request->input('germination'))) : null,
            'ph' => $request->filled('ph') ? max(2.0, min(12.0, (float) $request->input('ph'))) : null,
        ] + FieldProfile::forParams(FieldProfile::fromInput($request->input('fieldProfile'), (string) $request->input('crop')), (string) $request->input('crop'));
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
            // What each condition asks of the protocol: its correction and lead time.
            'soilGuide' => (function () use ($p) {
                $g = \App\Support\SoilConditions::guidance(self::soilConditions($p['soilConditions'] ?? ($p['soilCondition'] ?? null)));

                return $g !== '' ? "- What that soil condition asks of the protocol (build its correction, timing and lead time in):\n" . $g : '';
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
            // The field in detail, when the farmer gave it: one compact line (or nothing).
            'fieldLine' => FieldProfile::promptLine((array) ($p['fieldProfile'] ?? []), $p['crop']),
            'potential' => ($p['potential'] ?? null) !== null
                ? rtrim(rtrim(number_format((float) $p['potential'], 2), '0'), '.') . ' ' . (self::yieldUnits()[$p['yieldUnit']] ?? 'per hectare') . ' (from the variety record or the seed bag)'
                : 'not given',
            'ph' => ($p['ph'] ?? null) !== null ? '; soil pH ' . $p['ph'] . ' (tested)' : '',
            // The variety as the app's book has it (the registry, and the seller's own figures where it has them).
            'record' => (function () use ($p) {
                if (($p['variety'] ?? '') === '') {
                    return '';
                }
                try {
                    $r = \App\Support\VarietyBook::match($p['crop'], $p['variety']);
                } catch (\Throwable) {
                    $r = null;
                }

                return $r ? '- The variety on record (the NSIC registry, and the figures of its seller where marked): ' . ltrim(\App\Support\VarietyBook::promptLines([$r]), '- ') . "\n" : '';
            })(),
        ] + self::needLines((array) ($p['npkNeed'] ?? []), self::yieldUnits()[$p['yieldUnit']] ?? 'tons per hectare', (float) (self::yieldTons()[$p['yieldUnit']] ?? 1));
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

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
