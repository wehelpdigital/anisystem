<?php

namespace App\Support;

use App\Http\Controllers\CropProtocolController;
use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsScheduleLot;

/**
 * An Anee Auto lot's goal, counted by the app and not guessed (2026-10-28, the owner).
 * First ask: "realistic goal is 6.5t/ha when i tried it, can you fix how the logic works? because for
 * example in the npk calculator, it really depends in the # of seeds planted in corn. so there are
 * factors or micronutrients." Then the flow: "first, user should be asked how much to plant, the
 * spacing if needed (all optional). this is before the analysis of anee. then based on the calculation
 * of npk calculator plus the anee analysis, show realistic to achieve then achievable but risky (then
 * show the high potential). then user selects about it (each selection will have its own requirement
 * for fertilizer first so that user can choose what is the target)."
 *
 * The count, each step said in words (steps):
 *  1. the variety's best on record (NutrientBook, the rule NPK Plus uses), else the reading's,
 *     else the Philippine usual harvest times NPK Plus's POTENTIAL_X (1.6), else what the recommended stand
 *     carries at its top, else the crop's own usual harvest (USUAL) times 1.6;
 *  2. what the stand carries: the seed calculator for the spacing or the amount planted, with the
 *     variety's yield parts (the Crop Protocol's standYield); a thin stand by NPK Plus's seed check
 *     when only that is known; a full usual stand when the farmer said nothing;
 *  3. the field: NPK Plus's caps for salt, sodium and short water, the water taken as the lower of
 *     the cap and the share of the season's need it meets (twenty years of the place, ENSO, the pump);
 *  4. the elements likely short on this soil, said as what every goal needs fed.
 * Then three goals (LEVELS), each with the fertilizer it takes (NpkNeed through needFor, one door
 * with NPK Plus and the Crop Protocol), and the farmer picks one: that is the lot's target.
 */
final class AutoGoal
{
    /**
     * The goals: label, icon, the share of the variety's best (null: the traditional yield, counted from
     * the usual harvest), whether the field's caps hold it down, what it rests on, and the secondary and
     * micro elements fed for it (2026-10-28, the owner: "add traditional yield (unsecured) the one that is
     * low yield with low fertilizer use ... in higher yields add the micros"): none for the traditional
     * one, the likely short ones for the realistic one, and those to watch too for the higher ones.
     */
    public const LEVELS = [
        'traditional' => ['Traditional yield (unsecured)', '🌾', null, true, 'What farms around here usually harvest with the usual fertilizer and no micronutrients. Nothing secures it against the shortages of this soil.', []],
        'realistic' => ['Realistic to achieve', '✅', 0.8, true, 'What a well run field usually reaches, held to your stand, your water and your soil.', ['likely']],
        'risky' => ['Achievable but risky', '⚠️', 0.9, true, 'More fertilizer, and the weather and the water must go right.', ['likely', 'watch']],
        'high' => ['High potential (complete micro protocol)', '🏆', 1.0, false, 'The best on record, with every element this soil and crop may run short of fed, a full stand, water whenever it is needed, and the soil put right.', ['likely', 'watch', 'test']],
    ];

    /** The usual harvest beside the farms around (the lot's yieldLast answer): what the traditional yield leans by. */
    private const LAST = ['worse' => 0.85, 'same' => 1.0, 'better' => 1.15, 'better_care' => 1.15];

    /**
     * A crowded stand: past the top of the crop's usual stand each share more takes half that share off the goal
     * (30% too many: 85%; 60%: 70%), never below 60%. An estimate in the spirit of yield trials that fall past the
     * best density; the owner may tune it.
     */
    private const CROWD = ['per' => 0.5, 'floor' => 0.6];

    /**
     * A crop's usual harvest in t a hectare (fresh, as it is sold; trees at bearing age), for the crops neither the
     * variety records, NPK Plus's crop table nor the seed calculator give a yield for. Rough Philippine farm figures, the
     * traditional yield's base and, times NPK Plus's POTENTIAL_X, the potential's.
     */
    /**
     * The close crop whose fertilizer count stands in for a crop NPK Plus has no numbers for: the same family or the same
     * kind of harvest (leaves, fruit, roots, a tree's crop). Its need is taken at the same share of its own usual best.
     */
    private const PROXY = [
        'alugbati' => 'pechay', 'kangkong' => 'pechay', 'mustard' => 'pechay', 'saluyot' => 'pechay', 'malunggay' => 'pechay',
        'cauliflower' => 'broccoli', 'celery' => 'lettuce', 'onion_spring' => 'onion', 'shallot' => 'onion', 'radish' => 'carrot',
        'melon' => 'watermelon', 'sayote' => 'squash', 'vegetables' => 'eggplant', 'strawberry' => 'tomato', 'tobacco' => 'eggplant',
        'cowpea' => 'mungbean', 'limabean' => 'mungbean', 'pigeonpea' => 'mungbean', 'wingedbean' => 'stringbean',
        'turmeric' => 'ginger', 'ubi' => 'taro', 'corn_glutinous' => 'corn_yellow', 'cotton' => 'okra', 'patola' => 'cucumber', 'upo' => 'squash',
        'abaca' => 'banana', 'bamboo' => 'sugarcane', 'oilpalm' => 'coconut', 'rubber' => 'cacao', 'dragonfruit' => 'papaya',
        'citrus' => 'calamansi', 'pomelo' => 'calamansi',
        'atis' => 'mango', 'avocado' => 'mango', 'cashew' => 'mango', 'chico' => 'mango', 'durian' => 'mango', 'guava' => 'mango', 'guyabano' => 'mango',
        'jackfruit' => 'mango', 'lanzones' => 'mango', 'mangosteen' => 'mango', 'rambutan' => 'mango', 'santol' => 'mango', 'starapple' => 'mango', 'tamarind' => 'mango',
    ];

    /**
     * A guide spacing for the crops the seed calculator does not know: rows and plants in cm (trees in cm too), what goes in a
     * hill, and what it is. The usual Philippine farm practice, rounded; the recommendation shows it and a planting is weighed
     * against it.
     */
    private const PLANT = [
        'cowpea' => [60, 20, 2, 'seeds'], 'wingedbean' => [100, 50, 2, 'seeds'], 'limabean' => [100, 50, 2, 'seeds'], 'pigeonpea' => [100, 50, 2, 'seeds'],
        'ubi' => [100, 50, 1, 'setts'], 'turmeric' => [60, 25, 1, 'rhizome pieces'], 'kangkong' => [20, 20, 3, 'cuttings'], 'mustard' => [30, 20, 1, 'seedlings'],
        'cauliflower' => [60, 45, 1, 'seedlings'], 'alugbati' => [50, 30, 1, 'cuttings'], 'saluyot' => [30, 20, 2, 'seeds'], 'celery' => [30, 30, 1, 'seedlings'],
        'sayote' => [300, 300, 1, 'fruits'], 'melon' => [200, 75, 2, 'seeds'], 'onion_spring' => [15, 10, 1, 'seedlings'], 'shallot' => [20, 10, 1, 'bulbs'],
        'tobacco' => [100, 50, 1, 'seedlings'], 'cotton' => [75, 25, 1, 'seeds'], 'strawberry' => [30, 30, 1, 'runners'],
        'abaca' => [200, 200, 1, 'suckers'], 'bamboo' => [500, 500, 1, 'cuttings'], 'oilpalm' => [900, 780, 1, 'palms'], 'rubber' => [600, 300, 1, 'trees'],
        'atis' => [400, 400, 1, 'trees'], 'avocado' => [800, 800, 1, 'trees'], 'cashew' => [800, 800, 1, 'trees'], 'chico' => [800, 800, 1, 'trees'],
        'citrus' => [600, 600, 1, 'trees'], 'dragonfruit' => [300, 200, 4, 'cuttings'], 'durian' => [1000, 1000, 1, 'trees'], 'guava' => [500, 500, 1, 'trees'],
        'guyabano' => [500, 500, 1, 'trees'], 'jackfruit' => [800, 800, 1, 'trees'], 'lanzones' => [600, 600, 1, 'trees'], 'malunggay' => [100, 100, 1, 'cuttings'],
        'mangosteen' => [800, 800, 1, 'trees'], 'pomelo' => [800, 800, 1, 'trees'], 'rambutan' => [1000, 1000, 1, 'trees'], 'santol' => [800, 800, 1, 'trees'],
        'starapple' => [800, 800, 1, 'trees'], 'tamarind' => [1000, 1000, 1, 'trees'],
    ];

    private const USUAL = [
        // Leafy and fruit vegetables, fresh.
        'alugbati' => 10, 'kangkong' => 12, 'mustard' => 12, 'saluyot' => 8, 'cauliflower' => 12, 'celery' => 15, 'onion_spring' => 10, 'shallot' => 8,
        'melon' => 15, 'sayote' => 30, 'vegetables' => 10, 'strawberry' => 10, 'radish' => 15,
        // Legumes: dry grain, but winged bean's fresh pods.
        'cowpea' => 1.0, 'limabean' => 1.2, 'pigeonpea' => 1.0, 'wingedbean' => 8,
        // Roots, fresh.
        'turmeric' => 10, 'ubi' => 12,
        // Field and industrial crops: glutinous corn grain, seed cotton, cured tobacco leaf, abaca fiber, bamboo culms,
        // oil palm fruit bunches, dry rubber.
        'corn_glutinous' => 3.0, 'cotton' => 1.5, 'tobacco' => 2.0, 'abaca' => 1.0, 'bamboo' => 10, 'oilpalm' => 15, 'rubber' => 1.2,
        // Fruit trees at bearing age, fresh fruit (cashew: nuts; malunggay: leaves).
        'atis' => 8, 'avocado' => 8, 'cashew' => 0.8, 'chico' => 10, 'citrus' => 10, 'dragonfruit' => 10, 'durian' => 8, 'guava' => 15, 'guyabano' => 8,
        'jackfruit' => 20, 'lanzones' => 5, 'malunggay' => 10, 'mangosteen' => 4, 'pomelo' => 12, 'rambutan' => 8, 'santol' => 10, 'starapple' => 10, 'tamarind' => 4,
    ];

    /** The inputs a lot keeps, as saved in the season's answers (answers.goals[lotId]): its planting and the goal picked. */
    public static function clean(array $g): array
    {
        $num = fn ($v, $lo, $hi) => is_numeric($v) && (float) $v >= $lo && (float) $v <= $hi ? round((float) $v, 3) : null;

        return array_filter([
            'by' => in_array($g['by'] ?? '', ['spacing', 'amount'], true) ? (string) $g['by'] : null,
            'row' => $num($g['row'] ?? null, 1, 3000), 'plant' => $num($g['plant'] ?? null, 1, 3000), 'perHill' => $num($g['perHill'] ?? null, 1, 50),
            'amount' => $num($g['amount'] ?? null, 0.001, 100000000), 'unit' => isset(NpkNeed::SEED_UNITS[$g['unit'] ?? '']) ? (string) $g['unit'] : null,
            'germ' => $num($g['germ'] ?? null, 1, 100),
            'pick' => isset(self::LEVELS[$g['pick'] ?? '']) ? (string) $g['pick'] : null,
        ], fn ($v) => $v !== null);
    }

    /** The Crop Protocol's way in for an Anee Auto lot's (transplanted, direct, planted, tree). */
    public static function protoMethod(?string $crop, ?string $method): string
    {
        $ms = CropProtocolController::methodsFor((string) $crop);
        $want = match ((string) $method) {
            'transplanted' => ['transplanted', 'nursery'],
            'tree' => ['tree_seedlings', 'suckers', 'nursery'],
            default => ['direct', 'direct_wet', 'direct_dry', 'cuttings', 'tubers', 'sets', 'suckers', 'sprouted', 'nursery'],
        };
        foreach ($want as $m) {
            if (in_array($m, $ms, true)) {
                return $m;
            }
        }

        return (string) ($ms[0] ?? 'direct');
    }

    /** How a lot's planting is asked: its units, its usual spacing and whether germination counts. */
    public static function plantOptions(?string $crop, string $protoMethod): array
    {
        $sp = PlantAmount::spacing($crop, $protoMethod);
        $units = PlantAmount::units($crop, $protoMethod);

        return [
            'units' => $units,
            'unitWords' => (object) array_combine($units, array_map(fn ($u) => (string) (NpkNeed::SEED_UNITS[$u][1] ?? $u), $units)),
            'spacing' => $sp ? ['row' => $sp['row'], 'plant' => $sp['plant'], 'perHill' => $sp['perHill'], 'meters' => $sp['meters'], 'word' => $sp['word'], 'note' => $sp['note'], 'standBy' => $sp['standBy']] : null,
            'germ' => PlantAmount::germ($crop, $protoMethod),
        ];
    }

    /** Every lot of the season: lotId => its goals. */
    public static function forSeason(AsAutoSeason $auto): array
    {
        $out = [];
        $reads = collect((array) (($auto->analysis ?? [])['lots'] ?? []))->keyBy(fn ($r) => (int) ($r['lotId'] ?? 0));
        foreach (AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get() as $al) {
            $lot = AsScheduleLot::find($al->lotId);
            if ($lot && (int) $lot->deleteStatus === 1) {
                $out[(int) $lot->id] = self::forLot($auto, $lot, $al, (array) (($reads->get((int) $lot->id) ?? [])['variety'] ?? []));
            }
        }

        return $out;
    }

    /** One lot: the stand it plants, the three goals with their fertilizer, the one picked, and the steps behind them. */
    public static function forLot(AsAutoSeason $auto, AsScheduleLot $lot, ?AsAutoLot $al, array $readVariety = []): array
    {
        $a = (array) $auto->answers;
        $goals = (array) ($a['goals'] ?? []);
        $g = self::clean((array) ($goals[(string) $lot->id] ?? []));
        $crop = (string) $lot->crop;
        $method = self::protoMethod($crop, $al?->method);
        $out = ['inputs' => $g, 'pick' => $g['pick'] ?? null, 'method' => $method, 'plant' => self::plantOptions($crop, $method),
            'levels' => null, 'tPerHa' => null, 'steps' => [], 'feed' => [], 'standSay' => '', 'standKnown' => false];
        if ($crop === '') {
            return $out;
        }
        $npkKey = NpkNeed::cropKey($crop, $method);
        // A crop NPK Plus has no numbers for counts its fertilizer as a close crop does (PROXY), at the same share of
        // that crop's usual best, so every crop Anee Auto offers gets the fertilizer each goal takes.
        $own = $npkKey !== null;
        $needCrop = $own ? $crop : (self::PROXY[$crop] ?? null);
        $c = NpkNeed::crop($own ? $npkKey : ($needCrop ? NpkNeed::cropKey($needCrop, null) : null));
        $proxyP = ! $own && ! empty($c['typicalYield']['hi']) ? (float) $c['typicalYield']['hi'] / 0.8 : null;
        // A close crop with no usual harvest of its own (pechay, ampalaya): its best is what its recommended stand carries.
        if (! $own && $needCrop && ! $proxyP) {
            $proxyP = (float) (self::recommend($needCrop, self::protoMethod($needCrop, $method), 1.0)['hi'] ?? 0) ?: null;
        }
        $areaHa = $lot->lotSize ? (float) $lot->lotSize * match ($lot->lotSizeUnit) { 'sqm' => 0.0001, 'acre' => 0.404686, default => 1 } : 1.0;
        $out['areaHa'] = round($areaHa, 4);

        // The need model on the lot's soil, water, season and planting (one door: the Crop Protocol's).
        $soil = (array) ($a['soilRead'] ?? []);
        $waterClass = (string) ((AutoBook::WATER[$a['water'] ?? ''] ?? [])[2] ?? 'irrigated');
        $sp = ($g['by'] ?? null) === 'spacing' && ! empty($g['row']) && ! empty($g['plant']) ? ['row' => $g['row'], 'plant' => $g['plant'], 'perHill' => $g['perHill'] ?? null] : null;
        $byAmount = ($g['by'] ?? null) === 'amount' && ! empty($g['amount']);
        $p = [
            'crop' => $crop, 'method' => $method, 'variety' => (string) $lot->variety, 'area' => $areaHa, 'yieldUnit' => 'tons',
            'seedAmount' => $byAmount ? $g['amount'] : null, 'seedUnit' => $byAmount ? ($g['unit'] ?? 'kg') : null, 'germination' => $g['germ'] ?? null, 'spacing' => $sp,
            'soil' => in_array($soil['texture'] ?? '', ['clay', 'loam', 'sandy', 'silty'], true) ? $soil['texture'] : 'unsure',
            'soilConditions' => array_values(array_intersect((array) ($soil['conditions'] ?? []), ['sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged', 'acidic', 'alkaline'])),
            'ph' => is_numeric($soil['tested'] ?? null) ? (float) $soil['tested'] : null,
            'water' => ['partial' => 'limited', 'rainfed' => 'rainfed'][$waterClass] ?? 'irrigated',
            'fieldProfile' => ! empty($a['fieldProfile']) ? (array) $a['fieldProfile'] : null,
            'location' => trim(implode(', ', array_filter([$lot->locTown, $lot->locProvince]))),
            'day' => $al?->fieldDate?->toDateString(),
        ];

        // 1. The best: the variety's on record, else the reading's, else the usual harvest times NPK Plus's POTENTIAL_X, else what the
        // recommended stand carries at its top (the seed calculator with the crop's yield parts), else the crop's usual
        // harvest (USUAL) the same way, so every crop Anee Auto offers gets its goals.
        $rec = self::recommend($crop, $method, $areaHa, (string) $lot->variety);
        $out['recommend'] = $rec;
        $P = NutrientBook::varietyPotential($npkKey ?? $crop, $lot->variety);
        $from = 'record';
        if (! $P && (float) ($readVariety['yieldPotentialTPerHa'] ?? 0) > 0) {
            [$P, $from] = [(float) $readVariety['yieldPotentialTPerHa'], 'reading'];
        }
        if (! $P && $own && ! empty($c['typicalYield']['hi'])) {
            [$P, $from] = [(float) $c['typicalYield']['hi'] * NpkModel::POTENTIAL_X, 'usual'];
        }
        if (! $P && ! empty($rec['hi'])) {
            [$P, $from] = [(float) $rec['hi'], 'stand'];
        }
        if (! $P && isset(self::USUAL[$crop])) {
            [$P, $from] = [self::USUAL[$crop] * NpkModel::POTENTIAL_X, 'usual'];
        }
        if ($own) {
            $r = rescue(fn () => CropProtocolController::needFor($p + ['potential' => $P ?: null, 'targetYield' => null]), ['known' => false], false);
        } else {
            // The stand from the crop itself (the seed calculator); the soil, the season and the micros from its close crop.
            $r = $needCrop ? rescue(fn () => CropProtocolController::needFor(array_merge($p, ['crop' => $needCrop, 'seedAmount' => null, 'seedUnit' => null, 'germination' => null, 'spacing' => null])
                + ['potential' => $proxyP, 'targetYield' => null]), ['known' => false], false) : ['known' => false];
            $r['stand'] = rescue(fn () => CropProtocolController::standYield($p), null, false);
            $r['seed'] = null;
        }

        // 2. The stand: what the planting carries against the recommended one, said on the lot card before any goal.
        $hills = (($out['plant']['spacing'] ?? [])['standBy'] ?? 'plants') === 'hills';
        $st = $r['stand'] ?? null;
        $sd = $r['seed'] ?? null;
        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
        $num = fn ($v) => number_format(round((float) $v, $v >= 10000 ? -2 : 0));
        $sf = 1.0;
        $crowd = 1.0;
        $over = 0.0;
        $extra = false;
        $seedlings = $byAmount && in_array($method, ['transplanted', 'nursery'], true);
        if ($st && ! empty($st['hi'])) {
            $out['standKnown'] = true;
            $u = (array) ($st['usual'] ?? []);
            $usual = count($u) === 2 ? ' (the usual is ' . $num($u[0]) . ' to ' . $num($u[1]) . ')' : '';
            // Crowding (2026-10-28, the owner: "the goal will also depend in the seeds to plant or spacing (but of course you
            // should see as well if high crowding already like the npk calculator)"): the seed calculator stops counting plants
            // past the top of the usual stand, and a crowded field gives less, not the same: plants fight for light, water and
            // food, ears and panicles stay small, more stay barren, stalks lodge and disease spreads. CROWD below.
            $over = count($u) === 2 && $u[1] > 0 ? (float) $st['plants'] / (float) $u[1] - 1 : 0.0;
            $status = (string) ($st['status'] ?? '');
            // Seedlings raised from an amount of seed: the extras stay in the seedbed, the spacing sets the stand. Too much
            // seed then crowds nothing; too little still thins it.
            $extra = $seedlings && in_array($status, ['crowded', 'danger'], true);
            if ($extra) {
                $status = 'good';
                $over = 0.0;
            }
            // A stand carries its share of what the recommended one does: thinner, less; fuller, no more (the calculator's plateau).
            if (! $extra && ! empty($rec['hi'])) {
                $sf = min(1.0, (float) $st['hi'] / (float) $rec['hi']);
            }
            if (in_array($status, ['crowded', 'danger'], true) && $over > 0) {
                $crowd = max(self::CROWD['floor'], 1 - self::CROWD['per'] * $over);
            }
            $out['standStatus'] = $status;
            $out['standSay'] = $extra ? 'Enough seed for a full stand' . (count($u) === 2 ? ' (the usual is ' . $num($u[0]) . ' to ' . $num($u[1]) . ($hills ? ' hills' : ' plants') . ' a hectare)' : '') . '. The extra seedlings stay in the seedbed: give the spacing for a surer count.'
                : 'About ' . $num($st['plants']) . ($hills ? ' hills stand filled' : ' plants stand') . ' a hectare' . match ($status) {
                    'sparse' => ': very thin' . $usual . '. The goals drop with it.',
                    'thin' => ': a little thin' . $usual . '.',
                    'crowded' => ': crowded' . $usual . '. Crowded plants give less, so the goals drop.',
                    'danger' => ': far too crowded' . $usual . '. The goals drop a lot.',
                    default => $usual . '.',
                };
        } elseif ($sd) {
            // No spacing entry for the crop: NPK Plus's seed check against the usual seed or plants a hectare.
            $out['standKnown'] = true;
            $out['standSay'] = (string) $sd['words'];
            $sf = ! empty($sd['thin']) ? (float) $sd['sf'] : 1.0;
            $out['standStatus'] = $sf < 1 ? 'thin' : 'good';
            $u = (array) ($sd['usual'] ?? []);
            // Seed sown far past the usual (a third more than its top): the extra plants crowd, past what self thinning takes.
            if (! $seedlings && count($u) === 2 && $u[1] > 0 && (float) $sd['perHa'] > (float) $u[1] * 1.3) {
                $over = (float) $sd['perHa'] / ((float) $u[1] * 1.3) - 1;
                $crowd = max(self::CROWD['floor'], 1 - self::CROWD['per'] * $over);
                $out['standStatus'] = $over > 0.3 ? 'danger' : 'crowded';
                $out['standSay'] = preg_replace('/ Yours is much more:.*$/', '', $out['standSay']) . ' Yours is far more: crowded plants give less, so the goals drop.';
            }
        }
        if (! $out['standKnown'] && ($byAmount || $sp)) {
            $out['standKnown'] = true;
            $recPlants = ($rec['by'] ?? '') === 'spacing' && empty($rec['hi']) ? (float) ($rec['plants'] ?? 0) : 0.0;
            $given = $sp ? 1e8 / ((float) $sp['row'] * (float) $sp['plant']) * (float) ($sp['perHill'] ?: ($rec['perHill'] ?? 1))
                : (in_array($g['unit'] ?? '', ['plants', 'seedlings', 'cuttings'], true) && $areaHa > 0 ? (float) $g['amount'] / $areaHa : 0.0);
            if ($recPlants > 0 && $given > 0) {
                // Against the guide spacing (PLANT): a fifth under it is still a full stand, a quarter over it still fine.
                $sf = min(1.0, $given / (0.8 * $recPlants));
                $over = $given / (1.25 * $recPlants) - 1;
                $crowd = $over > 0 ? max(self::CROWD['floor'], 1 - self::CROWD['per'] * $over) : 1.0;
                $out['standStatus'] = $over > 0.3 ? 'danger' : ($over > 0 ? 'crowded' : ($sf < 0.7 ? 'sparse' : ($sf < 1 ? 'thin' : 'good')));
                $num = fn ($v) => number_format(round((float) $v, $v >= 10000 ? -2 : 0));
                $out['standSay'] = 'About ' . $num($given) . ' plants a hectare (the guide is about ' . $num($recPlants) . ')' . match ($out['standStatus']) {
                    'sparse' => ': very thin. The goals drop with it.', 'thin' => ': a little thin.',
                    'crowded' => ': crowded. Crowded plants give less, so the goals drop.', 'danger' => ': far too crowded. The goals drop a lot.', default => '.',
                };
                $over = max(0.0, $over);
            } else {
                $out['standSay'] = 'Noted. There is no usual figure to weigh it against for this crop yet, so a full stand is counted.';
            }
        }
        if (! $P) {
            return $out;
        }
        // The variety's short name: "DEKALB 8282S VT Double PRO RIB Complete (DK 8282S)" reads as DK 8282S.
        $vname = ! $lot->variety ? 'this crop' : (preg_match('/\(([^()]{2,30})\)\s*$/', (string) $lot->variety, $vm) ? $vm[1] : (string) $lot->variety);
        $out['potential'] = round($P, 2);
        $out['steps'][] = ['🏆', match ($from) {
            'record' => 'The best ' . $vname . ' has given on record',
            'reading' => 'The best Anee found for ' . $vname,
            'stand' => 'What the recommended stand of this crop carries at its best',
            default => 'A good field of this crop, from the Philippine usual',
        }, $fmt($P) . ' t/ha'];
        if ($st && ! empty($st['hi']) && $extra) {
            $out['steps'][] = ['🌱', 'Enough seed for a full stand: the spacing sets it', 'holds'];
        } elseif ($st && ! empty($st['hi'])) {
            $out['steps'][] = $sf < 0.995
                ? ['🌱', 'Your stand of about ' . $num($st['plants']) . ($hills ? ' hills' : ' plants') . ' a hectare carries about ' . round($sf * 100) . '% of what the recommended stand does', '× ' . round($sf * 100) . '%']
                : ['🌱', 'Your stand of about ' . $num($st['plants']) . ($hills ? ' hills' : ' plants') . ' a hectare is a full stand', 'holds'];
        } elseif ($sf < 1) {
            $out['steps'][] = ['🌱', 'A thin stand for this crop: ' . ($sd['words'] ?? $out['standSay']), '× ' . round($sf * 100) . '%'];
        } elseif ($sd || $out['standKnown']) {
            $out['steps'][] = ['🌱', 'Your planting makes a full stand', 'holds'];
        } else {
            $out['steps'][] = ['🌱', 'A full usual stand is counted: say how much you plant for surer goals', 'assumed'];
        }
        if ($crowd < 1) {
            $out['steps'][] = ['🌾', 'Crowded: about ' . round($over * 100) . '% more than the top of the usual. Plants fight for light, water and food; ears, pods and fruit stay small and stalks lodge', '× ' . round($crowd * 100) . '%'];
        }

        // 3. The field: the water (the lower of its cap and the season's share), salt and sodium.
        $k = 1.0;
        if ($c) {
            $conds = array_values(array_intersect((array) ($soil['conditions'] ?? []), ['saline', 'sodic']));
            $cap = NpkNeed::fieldCap($c, true, $waterClass, $conds);
            $kind = str_starts_with($c['key'], 'rice') ? 'rice' : 'other';
            $season = $r['season'] ?? null;
            $share = $season && isset($season['waterShare']) ? (float) $season['waterShare'] : null;
            $waterCap = $waterClass === 'irrigated' ? 1.0 : (float) NpkModel::FIELD['caps'][$waterClass][$kind];
            $wk = $share !== null && $share < 0.97 ? min($waterCap, max(NpkModel::FIELD['capFloor'], $share)) : $waterCap;
            if ($wk < 0.995) {
                $k *= $wk;
                $out['steps'][] = ['💧', $share !== null && $share < $waterCap ? 'The water meets about ' . round($share * 100) . '% of what the crop drinks this season' . (! empty($season['enso']) ? ' (' . $season['enso'] . ')' : '')
                    : (AutoBook::WATER[$a['water'] ?? ''][0] ?? 'The water is not sure'), '× ' . round($wk * 100) . '%'];
            }
            foreach ($cap['why'] as [$why, $f]) {
                if (in_array($why, ['saline', 'sodic'], true)) {
                    $k *= (float) $f;
                    $out['steps'][] = [$why === 'sodic' ? '🧂' : '🌊', $why === 'sodic' ? 'Sodium in the soil holds the roots back' : 'Salt in the soil holds the roots back', '× ' . round((float) $f * 100) . '%'];
                }
            }
            $k = max(NpkModel::FIELD['capFloor'], $k);
        }

        // 4. What every goal needs fed: the elements likely short on this soil.
        foreach ((array) (($r['micro'] ?? [])['likely'] ?? []) as $m) {
            $out['feed'][] = $m['name'];
        }
        if ($out['feed']) {
            $out['steps'][] = ['🧪', 'The goals above the traditional one need ' . self::join(array_map('mb_strtolower', $out['feed'])) . ' fed: likely short on this soil', ''];
        }

        // The goals: the traditional yield from the usual harvest of these parts (leaned by how the lot's last harvest
        // compared with its neighbours), then the realistic, risky and high ones as shares of the best, held to the stand
        // (its share of the recommended one, less for crowding) and, but for the high one, to the field. Each with the
        // fertilizer it takes.
        $fp = ! empty($a['fieldProfile']) ? FieldProfile::normalize((array) $a['fieldProfile']) : [];
        $usual = $own && ! empty($c['typicalYield']['hi']) ? (float) $c['typicalYield']['hi'] : (self::USUAL[$crop] ?? $P * 0.45);
        $lean = self::LAST[$fp['yieldLast'] ?? ''] ?? 1.0;
        $levels = [];
        foreach (self::LEVELS as $key => [$label, $icon, $share, $capped, $say]) {
            if ($share === null) {
                $t = $usual * $lean * $k * $sf * $crowd;
            } else {
                $t = $P * $share * $sf * $crowd * ($capped ? $k : 1.0);
            }
            $levels[$key] = ['key' => $key, 'label' => $label, 'icon' => $icon, 'say' => $say, 't' => round($t, 1)];
        }
        // Never out of order: a lower goal never above a higher one.
        $levels['traditional']['t'] = min($levels['traditional']['t'], round($levels['realistic']['t'] * 0.85, 1));
        $levels['risky']['t'] = max($levels['risky']['t'], $levels['realistic']['t']);
        $levels['high']['t'] = max($levels['high']['t'], $levels['risky']['t']);
        if ($lean !== 1.0) {
            $out['steps'][] = ['🌾', 'Your last harvest ' . ($lean > 1 ? 'beat' : 'fell short of') . ' the farms around it: the traditional yield leans ' . ($lean > 1 ? 'up' : 'down'), '× ' . round($lean * 100) . '%'];
        }
        // The goals already carry the stand, so the need is counted without it (NPK Plus would thin the goal a second time).
        $pNeed = array_merge($p, ['seedAmount' => null, 'seedUnit' => null, 'germination' => null, 'spacing' => null], $own ? [] : ['crop' => $needCrop, 'variety' => '']);
        foreach ($levels as $key => $lv) {
            $n = ! $own && ! ($needCrop && $proxyP) ? ['known' => false]
                : rescue(fn () => CropProtocolController::needFor($pNeed + ($own ? ['potential' => $P, 'targetYield' => $lv['t']] : ['potential' => $proxyP, 'targetYield' => $proxyP * $lv['t'] / $P])), ['known' => false], false);
            $need = array_intersect_key((array) ($n['need'] ?? []), array_flip(['N', 'P2O5', 'K2O']));
            // The secondary and micro elements fed for this goal (LEVELS), the likely short ones first.
            $micros = [];
            $feed = self::LEVELS[$key][5];
            $low = 1.0;
            foreach ((array) ($n['micros'] ?? []) as $el => $m) {
                if (in_array($m['level'] ?? '', $feed, true) && empty($m['beneficial']) && (float) $m['need'] > 0) {
                    $share = (float) ($m['soil'] ?? 1);
                    $micros[] = ['el' => $el, 'name' => $m['name'], 'need' => round((float) $m['need'], 2), 'lot' => round((float) $m['need'] * $areaHa, 2), 'likely' => $m['level'] === 'likely', 'soil' => round($share, 2)];
                    $low = min($low, $share);
                }
            }
            usort($micros, fn ($x, $y) => $y['likely'] <=> $x['likely']);
            // What the micronutrients in it add (2026-10-28, the owner: "we should atleast provide a potential for the complete
            // micro protocol ... that should increase the potential"): left unfed, the scarcest of them holds the harvest to the
            // share the soil gives of it on its own (the shortest plank, as NPK Plus's barrel counts it).
            $without = $low < 0.99 ? round($lv['t'] * $low, 1) : null;
            $levels[$key] += ['lotT' => round($lv['t'] * $areaHa, 2), 'need' => $need, 'lotNeed' => array_map(fn ($v) => (int) round($v * $areaHa), $need), 'micros' => $micros,
                // Counted as for a close crop (PROXY): the card says so.
                'needAs' => $own || ! $need ? null : CropCatalog::label((string) $needCrop),
                'without' => $without !== null && $without < $lv['t'] ? $without : null,
                // What the traditional yield leaves unfed: the shortages that stay.
                'unfed' => $feed === [] ? $out['feed'] : []];
        }
        $out['levels'] = $levels;
        $out['tPerHa'] = $out['pick'] ? $levels[$out['pick']]['t'] : null;

        return $out;
    }

    /**
     * What to plant when the farmer is not sure (2026-10-28, the owner: "in seeds and spacing, just to guide the user if he
     * doesn't know just add a recommendation part"): the crop's usual spacing that lands the stand in its good range, with
     * the seeds or seedlings it takes (the seed calculator), and the yield that stand carries; else the usual seed or plants
     * a hectare from NPK Plus's seeding table. Null when neither knows the crop.
     */
    public static function recommend(string $crop, string $protoMethod, float $areaHa, string $variety = ''): ?array
    {
        // Mixed vegetables have no one spacing.
        if ($crop === 'vegetables') {
            return ['by' => 'note', 'say' => 'Mixed vegetables: follow the spacing of the main vegetable, or give the plants you set out.', 'lo' => null, 'hi' => null];
        }
        $sp = PlantAmount::spacing($crop, $protoMethod);
        if ($sp) {
            $best = null;
            // The middle spacing first, then a little wider or closer, until the stand sits in the good range.
            foreach ([[1, 1], [1, 2], [2, 1], [2, 2], [1, 0], [0, 1], [0, 0]] as [$ri, $pi]) {
                $row = (float) $sp['row'][$ri];
                $plant = (float) $sp['plant'][$pi];
                $n = rescue(fn () => PlantAmount::count($crop, $protoMethod, ['row' => $row, 'plant' => $plant], null, $areaHa, $variety), null, false);
                if (! $n) {
                    continue;
                }
                $best ??= ['row' => $row, 'plant' => $plant, 'n' => $n];
                if (($n['status'] ?? '') === 'good') {
                    $best = ['row' => $row, 'plant' => $plant, 'n' => $n];
                    break;
                }
            }
            $u = (array) ($best['n']['usualStand'] ?? []);
            if ($best && ($best['n']['status'] ?? '') !== 'good' && count($u) === 2 && (float) $best['n']['standHa'] > 0) {
                $step = $sp['meters'] ? 50.0 : 5.0;
                $plant = max($step, round($best['plant'] * (float) $best['n']['standHa'] / (($u[0] + $u[1]) / 2) / $step) * $step);
                $n = rescue(fn () => PlantAmount::count($crop, $protoMethod, ['row' => $best['row'], 'plant' => $plant], null, $areaHa, $variety), null, false);
                if ($n && ($n['status'] ?? '') === 'good') {
                    $best = ['row' => $best['row'], 'plant' => $plant, 'n' => $n];
                }
            }
            if ($best) {
                $y = rescue(fn () => CropProtocolController::standYield(['crop' => $crop, 'method' => $protoMethod, 'variety' => $variety, 'area' => 1,
                    'spacing' => ['row' => $best['row'], 'plant' => $best['plant'], 'perHill' => $best['n']['perHill']]]), null, false);

                return ['by' => 'spacing', 'row' => $best['row'], 'plant' => $best['plant'], 'perHill' => (int) $best['n']['perHill'], 'meters' => (bool) $sp['meters'],
                    'plants' => (int) round((float) $best['n']['standHa']), 'say' => (string) $best['n']['say'], 'lo' => $y['lo'] ?? null, 'hi' => $y['hi'] ?? null,
                    'word' => (string) ($best['n']['word'] ?? 'seeds')];
            }
        }
        // Not in the seed calculator: the guide spacing of PLANT, counted plainly.
        if (isset(self::PLANT[$crop])) {
            [$row, $plant, $each, $word] = self::PLANT[$crop];
            $plants = 1e8 / ($row * $plant) * $each;
            $m = $row >= 200;
            $d = fn ($cm) => $m ? rtrim(rtrim(number_format($cm / 100, 1), '0'), '.') : (string) round($cm);
            $n = fn ($v) => number_format(round($v, $v >= 10000 ? -2 : 0));

            return ['by' => 'spacing', 'row' => (float) $row, 'plant' => (float) $plant, 'perHill' => $each, 'meters' => $m, 'plants' => (int) round($plants),
                'say' => 'Plant ' . $d($row) . ' by ' . $d($plant) . ($m ? ' m' : ' cm') . ', ' . $each . ' ' . ($each === 1 ? rtrim($word, 's') : $word) . ' a hill: about ' . $n($plants) . ' a hectare'
                    . (abs($areaHa - 1) > 0.001 ? ', ' . $n($plants * $areaHa) . ' for this lot' : '') . '.',
                'lo' => null, 'hi' => null, 'word' => $word];
        }
        // No spacing for it: the usual seed or plants a hectare.
        $key = NpkNeed::cropKey($crop, $protoMethod);
        $sd = $key ? (NpkModel::SEEDING[$key] ?? null) : null;
        if ($sd && (! empty($sd['w']) || ! empty($sd['n']))) {
            $w = ! empty($sd['w']);
            [$lo, $hi] = $w ? $sd['w'] : $sd['n'];
            $units = PlantAmount::units($crop, $protoMethod);
            $word = $w ? 'kg of seed' : (string) (($sd['countWord'] ?? null) ?: 'plants');
            $unit = $w ? (in_array('kg', $units, true) ? 'kg' : ($units[0] ?? 'kg')) : (in_array($word, $units, true) ? $word : (in_array('plants', $units, true) ? 'plants' : ($units[0] ?? 'plants')));
            $n = fn ($v) => $v >= 100 ? number_format(round($v, $v >= 10000 ? -2 : 0)) : rtrim(rtrim(number_format($v, 1, '.', ','), '0'), '.');
            $span = fn ($a, $b) => $n($a) === $n($b) ? $n($a) : $n($a) . ' to ' . $n($b);

            return ['by' => 'amount', 'unit' => $unit, 'amount' => round(($lo + $hi) / 2 * $areaHa, $w ? 1 : 0),
                'say' => 'The usual is ' . $span($lo, $hi) . ' ' . $word . ' a hectare' . (abs($areaHa - 1) > 0.001 ? ': ' . $span($lo * $areaHa, $hi * $areaHa) . ' for this lot' : '') . '.',
                'lo' => null, 'hi' => null];
        }

        return null;
    }

    /** The steps and the three goals as words for a prompt. */
    public static function words(array $goal): string
    {
        $s = implode('; ', array_map(fn ($x) => $x[1] . ($x[2] !== '' ? ': ' . $x[2] : ''), (array) ($goal['steps'] ?? [])));
        foreach ((array) ($goal['levels'] ?? []) as $lv) {
            $s .= '. ' . $lv['label'] . ': ' . $lv['t'] . ' t/ha, needs N ' . ($lv['need']['N'] ?? '?') . ', P2O5 ' . ($lv['need']['P2O5'] ?? '?') . ', K2O ' . ($lv['need']['K2O'] ?? '?') . ' kg/ha';
        }

        return $s;
    }

    private static function join(array $xs): string
    {
        return count($xs) > 1 ? implode(', ', array_slice($xs, 0, -1)) . ' and ' . end($xs) : (string) ($xs[0] ?? '');
    }
}
