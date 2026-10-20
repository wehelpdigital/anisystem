<?php

namespace App\Support;

/**
 * NPK Plus's need model, run on the server (2026-10-16, the owner: the Crop
 * Protocol "is not inline with the logic of the NPK Plus calculator").
 *
 * The calculator works the need out in the page, as the farmer types
 * (needModel() and its helpers in resources/views/npk-plus/index.blade.php).
 * This is its twin for the analyses that run on the server: the same goal
 * (a target as set, with a warning above the variety's potential, else 80 percent of the
 * potential, else the national average; a thin stand and a field that
 * cannot carry the goal lower it), the same soil factors, soil test bands
 * and field layer, the same N, P2O5 and K2O arithmetic and the same
 * secondary and micro element scores, all on App\Support\NpkModel's
 * numbers. Change the arithmetic in BOTH places.
 *
 * Since 2026-10-17 (the owner: "consider the micronutrients and not only
 * npk ... the results of the # of nutrients needed and calculation" must not
 * be "far away different from the npk plus calculator") the twin is whole:
 * the secondary and micro elements carry their need in kg per hectare (the
 * corrective dose sized to the goal), the soil test reads zinc, boron and
 * sulfur, and a plan can be given (plan: perHa nutrients and the acidity it
 * adds, acidEq), so the lockouts a heavy plan makes and the acid that frees
 * phosphorus and micros on an alkaline soil count as they do on the page.
 * The Crop Protocol checks every program it writes this way.
 *
 * In: crop (a catalogue key), method (rice: transplanted, direct_wet,
 * direct_dry), potential and target (t/ha), seed {amount, unit, germination}
 * for areaHa, texture (sandy, loam, clay, unsure), conditions (acidic,
 * neutral, alkaline, sodic, saline, acid_sulfate, low_om, peat,
 * waterlogged), ph, tests {N|P2O5|K2O: low|medium|high, Zn|B|S: low|ok},
 * water (irrigated, partial, rainfed), profile (FieldProfile answers), prev
 * (legume, rice_straw), plan {perHa: {N, P2O5, ..., Zn}, acidEq: kg lime}.
 * Since 2026-10-19 also season {rainMm, waterShare} (seasonWater(): the
 * page's seasonPlanks(), the twenty years of weather tilted by the ENSO
 * outlook), intensive and zincRecent (the page's switches; from the lot's
 * cropsYear and zincGiven answers when not given) and organicKg.
 */
final class NpkNeed
{
    public const NPK = ['N', 'P2O5', 'K2O'];

    /** The elements a soil test can say are low or enough by their critical level (NpkModel::ELEMENTS). */
    public const TESTED_MICROS = ['Zn', 'B', 'S', 'Fe', 'Mn', 'Cu'];

    /** The usual germination the seeding rates in the guides assume. */
    public const GERM_USUAL = 85;

    /** What a farmer can plant by, and its word. */
    public const SEED_UNITS = [
        'kg' => ['Kilograms', 'kg', 'Seed by weight, or tubers, cloves and sets'],
        'g' => ['Grams', 'g', 'Small seed, like most vegetables'],
        'seeds' => ['Seeds', 'seeds', 'Counted one by one'],
        'seedlings' => ['Seedlings', 'seedlings', 'Transplants from a seedbed or a tray'],
        'hills' => ['Hills', 'hills', 'Planting holes, with one or more seeds each'],
        'cuttings' => ['Cuttings', 'cuttings', 'Stem cuttings or cane setts'],
        'plants' => ['Plants or trees', 'plants', 'Suckers, grafted seedlings and trees'],
    ];

    /** The unit a crop is usually planted by (NPK Plus's SEED_DEF); kg for the rest. */
    public const SEED_DEFAULT = [
        'cuttings' => ['cassava', 'sweetpotato', 'sugarcane'], 'g' => ['carrot', 'pechay', 'lettuce'],
        'seeds' => ['squash', 'cucumber', 'watermelon', 'ampalaya', 'okra'],
        'seedlings' => ['cabbage', 'broccoli', 'tomato', 'eggplant', 'chili', 'bellpepper', 'onion'],
        'plants' => ['pineapple', 'banana', 'papaya', 'mango', 'coconut', 'calamansi', 'coffee', 'cacao', 'taro'],
    ];

    /** What raised a risk, in words. */
    public const FACTOR = ['sandy' => 'sandy soil', 'clay' => 'clay soil', 'acid' => 'acid soil', 'strongAcid' => 'very acid soil', 'alkaline' => 'alkaline soil',
        'strongAlk' => 'strongly alkaline soil', 'sodic' => 'sodic soil', 'saline' => 'salty soil', 'acid_sulfate' => 'acid sulfate soil', 'low_om' => 'low organic matter',
        'peat' => 'peat soil', 'waterlogged' => 'waterlogged soil', 'flooded' => 'a flooded paddy', 'calcareous' => 'limy (calcareous) soil',
        'high_p' => 'heavy phosphorus in the plan locks it up', 'high_k' => 'heavy potassium in the plan crowds it out',
        'liming' => 'the lime in the plan locks it up', 'excess_zn' => 'heavy zinc in the plan crowds it out',
        // 2026-10-19: the researched factors the first build left out.
        'high_rainfall' => 'heavy rain over the season washes it out', 'drought' => 'a dry season',
        'intensive_cropping' => 'two or three crops a year', 'upland' => 'dry seeded rice on soil that is not flooded',
        // 2026-10-21: the area's zinc, from the soil atlas (SoilAtlas::ZINC_AREA).
        'area_zn_low' => 'soils around here often test low in zinc', 'area_zn_mlow' => 'soils around here often test moderately low in zinc'];

    public const NAMES = ['N' => 'Nitrogen', 'P2O5' => 'Phosphorus', 'K2O' => 'Potassium', 'Ca' => 'Calcium', 'Mg' => 'Magnesium', 'S' => 'Sulfur', 'Zn' => 'Zinc',
        'B' => 'Boron', 'Fe' => 'Iron', 'Mn' => 'Manganese', 'Cu' => 'Copper', 'Mo' => 'Molybdenum', 'Si' => 'Silicon', 'Co' => 'Cobalt'];

    /** NPK Plus's crop key for a catalogue crop and the way it goes in the ground; null when it has no numbers. */
    public static function cropKey(?string $crop, ?string $method = null): ?string
    {
        $key = CropStages::normalize((string) $crop) ?: (string) $crop;
        $key = ['corn' => 'corn_yellow'][$key] ?? $key;
        // Rice sown in place is its own row (more seed, a little less of the soil's help).
        if ($key === 'rice' && in_array($method, ['direct_wet', 'direct_dry'], true) && isset(NpkCrops::TABLE['rice_' . str_replace('direct', 'dsr', $method)])) {
            $key = 'rice_' . str_replace('direct', 'dsr', $method);
        }

        return isset(NpkCrops::TABLE[$key]) ? $key : null;
    }

    /** The crop row the arithmetic reads: NpkCrops' numbers plus its key and words. */
    public static function crop(?string $key): ?array
    {
        if ($key === null || ! isset(NpkCrops::TABLE[$key])) {
            return null;
        }

        return ['key' => $key, 'label' => CropCatalog::label($key)] + NpkCrops::TABLE[$key];
    }

    /** The unit a crop is usually planted by. */
    public static function seedUnitFor(?string $key): string
    {
        foreach (self::SEED_DEFAULT as $unit => $crops) {
            if (in_array($key, $crops, true)) {
                return $unit;
            }
        }

        return 'kg';
    }

    /**
     * The units a crop is planted by, its usual one first (2026-10-21, the owner: corn is never counted
     * in trees): weight where the guides give the seed by weight, and the crop's own count where they
     * give a stand (seeds and hills for a crop sown in place, seedlings, cuttings, or plants and trees).
     *
     * @return list<string> keys of SEED_UNITS
     */
    public static function seedUnitsFor(?string $key): array
    {
        $def = self::seedUnitFor($key);
        $sd = $key !== null ? (NpkModel::SEEDING[$key] ?? null) : null;
        $out = [$def];
        if (! $sd) {
            return array_values(array_unique(array_merge($out, in_array($def, ['kg', 'g'], true) ? ['kg', 'g', 'seeds'] : [])));
        }
        if (! empty($sd['w'])) {
            $out[] = 'kg';
            if ((float) $sd['w'][1] <= 10) {
                $out[] = 'g';
            }
        }
        if (! empty($sd['n'])) {
            $cw = (string) ($sd['countWord'] ?? 'plants');
            $count = match (true) {
                in_array($cw, ['cuttings', 'setts'], true) => [$def === 'plants' ? 'plants' : 'cuttings'],
                in_array($cw, ['trees', 'palms', 'suckers'], true) => ['plants'],
                $cw === 'seedlings' => ['seedlings'],
                $cw === 'hills' => ['seeds', 'hills'],
                default => $def === 'seedlings' ? ['seedlings'] : ($def === 'plants' ? ['plants'] : ['seeds', 'hills']),
            };
            $out = array_merge($out, $count);
        }

        return array_values(array_unique($out));
    }

    /**
     * The whole count: the goal, the stand, the factors, the need. Every
     * number the protocol and its prompt use, with the reasons in words.
     */
    public static function run(array $in): array
    {
        $key = self::cropKey($in['crop'] ?? null, $in['method'] ?? null);
        $c = self::crop($key);
        $profile = (array) ($in['profile'] ?? []);
        $fieldOn = $profile !== [] && FieldProfile::count($profile) > 0;
        $f = self::factors($c, (string) ($in['texture'] ?? 'unsure'), (array) ($in['conditions'] ?? []), isset($in['ph']) && $in['ph'] !== null ? (float) $in['ph'] : null);
        $cls = self::classes((array) ($in['tests'] ?? []));
        // A soil test's organic matter under 2% is low organic matter for the micros too (2026-10-19).
        if (($cls['OM'] ?? null) === 'low' && ! in_array('low_om', $f, true)) {
            $f[] = 'low_om';
        }
        $water = in_array($in['water'] ?? '', ['irrigated', 'pump', 'partial', 'rainfed'], true) ? $in['water'] : 'irrigated';
        // How the lot is farmed (2026-10-19): two or three crops a year, zinc in the last two seasons, from the
        // lot's own answers when the caller did not say (NPK Plus's switches say it on the page).
        $fa = $fieldOn ? FieldProfile::normalize($profile) : [];
        $in['intensive'] ??= in_array($fa['cropsYear'] ?? '', ['two', 'three'], true);
        $in['zincRecent'] ??= ($fa['zincGiven'] ?? '') === 'recent';
        $f = self::moreFactors($f, $in, (string) ($in['texture'] ?? 'unsure'), $water);
        $g = self::goal($c, $in, $fieldOn, $water, (array) ($in['conditions'] ?? []));
        $fl = self::fieldLayer($c, $f, $fieldOn ? FieldProfile::normalize($profile) : [], (array) ($in['prev'] ?? []));
        $plan = (array) ($in['plan'] ?? []);
        $md = self::model($c, $g, $f, $cls, $fl, (array) ($plan['perHa'] ?? []), (float) ($plan['acidEq'] ?? 0), isset($plan['limeEq']) ? (float) $plan['limeEq'] : null);

        return [
            'crop' => $key,
            'known' => $c !== null,
            'goal' => $g,
            'factors' => array_values($f),
            'classes' => $cls,
            'need' => array_map(fn ($v) => (int) round($v), $md['need']),
            'how' => $md['how'],
            'whys' => array_values(array_unique($md['whys'])),
            'credit' => array_map(fn ($v) => (int) round($v), $md['credit']),
            'nMult' => $fl['nMult'],
            'pLock' => $md['pLock'],
            'micro' => $md['micro'],
            'share' => $md['share'],
            'lockouts' => $md['lockouts'],
            'acid' => $md['acid'],
        ];
    }

    /** The factors of a field: texture, pH, conditions; a flooded paddy for rice. */
    public static function factors(?array $c, string $texture, array $conds, ?float $ph): array
    {
        $f = [];
        if ($texture === 'sandy' || $texture === 'clay') {
            $f[] = $texture;
        }
        if ($ph !== null && $ph >= 2 && $ph <= 12) {
            $f[] = match (true) {
                $ph < 5 => 'strongAcid', $ph < 5.5 => 'acid', $ph > 8 => 'strongAlk', $ph > 7.3 => 'alkaline', default => null,
            };
        } else {
            if (in_array('acidic', $conds, true)) {
                $f[] = 'acid';
            }
            if (in_array('alkaline', $conds, true)) {
                $f[] = 'alkaline';
            }
        }
        foreach (['sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged'] as $k) {
            if (in_array($k, $conds, true)) {
                $f[] = $k;
            }
        }
        $f = array_values(array_unique(array_filter($f)));
        // Strongly alkaline and NOT sodic reads as a limy soil, rich in calcium.
        if (in_array('strongAlk', $f, true) && ! in_array('sodic', $f, true)) {
            $f[] = 'calcareous';
        }
        if ($c && in_array($c['key'], ['rice', 'rice_dsr_wet'], true)) {
            $f[] = 'flooded';
            $f = array_values(array_diff($f, ['waterlogged']));
        }
        // 2026-10-19: loam, a near neutral pH (6.0 to 7.3, or the word when no number is known), and rice
        // sown dry on soil that is never flooded.
        if ($texture === 'loam') {
            $f[] = 'loam';
        }
        if ($ph !== null ? ($ph >= 6 && $ph <= 7.3) : in_array('neutral', $conds, true)) {
            $f[] = 'neutral';
        }
        if ($c && $c['key'] === 'rice_dsr_dry') {
            $f[] = 'upland';
        }

        return array_values(array_unique($f));
    }

    /**
     * The factors the farmer and the season tell (2026-10-19), the page's rules: heavy rain over the crop's
     * season (1,000 mm or more in its window, not on clay or in a flooded paddy), a dry season (rainfed or
     * partly watered and under 70% of what the crop drinks), two or three crops a year, zinc put on in the
     * last two seasons, and a tonne or more of manure or compost a hectare in the plan.
     * $in: season {rainMm, waterShare}, intensive, zincRecent, organicKg.
     */
    public static function moreFactors(array $f, array $in, string $texture, string $water): array
    {
        $season = (array) ($in['season'] ?? []);
        if (isset($season['rainMm']) && (float) $season['rainMm'] >= 1000 && $texture !== 'clay' && ! in_array('flooded', $f, true)) {
            $f[] = 'high_rainfall';
        }
        if (isset($season['waterShare']) && $water !== 'irrigated' && (float) $season['waterShare'] < 0.7) {
            $f[] = 'drought';
        }
        if (! empty($in['intensive'])) {
            $f[] = 'intensive_cropping';
        }
        if (! empty($in['zincRecent'])) {
            $f[] = 'zinc_recent';
        }
        // The area's zinc (SoilAtlas::fill, 2026-10-21): only where the lot has no zinc test of its own.
        if (in_array($in['areaZinc'] ?? null, ['low', 'mlow', 'ok'], true) && ! isset(((array) ($in['tests'] ?? []))['Zn'])) {
            $f[] = 'area_zn_' . $in['areaZinc'];
        }
        if ((float) ($in['organicKg'] ?? 0) >= 1000) {
            $f[] = 'organic_inputs';
        }

        return array_values(array_unique($f));
    }

    /**
     * The days NPK Plus reads a season over (the page's seasonDays()): a year
     * for a perennial, else the crop's days to harvest, else 110.
     */
    public static function seasonDays(?string $key): int
    {
        if ($key !== null && CropCatalog::isPerennial($key)) {
            return 365;
        }
        $m = $key !== null ? (CropCatalog::CROPS[$key]['maturity'] ?? null) : null;

        return is_numeric($m) && (int) $m > 0 ? (int) $m : 110;
    }

    /** A season's window as NPK Plus reads it: its first day, its days, the months it touches, how many months ahead it starts. */
    public static function seasonWindow(\Carbon\CarbonInterface $from, int $days): array
    {
        $months = [];
        for ($d = 0; $d < $days; $d += 7) {
            $months[(int) $from->copy()->addDays($d)->format('n')] = true;
        }

        return ['from' => $from->toDateString(), 'days' => $days, 'months' => array_keys($months),
            'monthsAhead' => (int) max(0, now('Asia/Manila')->startOfDay()->diffInMonths($from->copy()->startOfDay(), false))];
    }

    /**
     * How the ENSO outlook tilts a season (the page's ensoTilt()): rain, sun
     * and heat. Full from October to May, when El Nino cuts the rain most,
     * half in the southwest monsoon, and half again for a season more than
     * nine months out, where the outlook is weak.
     */
    public static function ensoTilt(?array $enso, array $window): array
    {
        $T = NpkModel::ENSO_TILT[$enso['phase'] ?? ''] ?? null;
        if (! $enso || ($enso['phase'] ?? '') === 'neutral' || ! $T) {
            return ['rain' => 1.0, 'sun' => 1.0, 'temp' => 0.0];
        }
        $lvl = ['weak' => 0, 'moderate' => 1, 'strong' => 2, 'very strong' => 3][$enso['strength'] ?? ''] ?? 1;
        $months = (array) ($window['months'] ?? []);
        $dry = $months ? count(array_filter($months, fn ($m) => $m >= 10 || $m <= 5)) / count($months) : 1;
        $w = (0.5 + 0.5 * $dry) * ((int) ($window['monthsAhead'] ?? 0) > 9 ? 0.5 : 1);

        return ['rain' => 1 + $T['rain'][$lvl] * $w, 'sun' => 1 + $T['sun'][$lvl] * $w, 'temp' => $T['temp'][$lvl] * $w];
    }

    /**
     * The season's water, counted as NPK Plus's seasonPlanks() counts it
     * (2026-10-19, the owner: the Crop Protocol follows NPK Plus, the weather
     * and the ENSO outlook too): the rain of the same weeks in twenty years,
     * tilted by the outlook, against what the crop drinks (its kc on the
     * air's pull, plus a paddy's seepage). Three quarters of the rain reach
     * the roots; a water source that runs part of the time makes up half the
     * rest. rainMm and waterShare are what moreFactors() reads (heavy rain, a
     * dry season). $hist = ClimateStore::season(), $window = seasonWindow(),
     * $enso = ClimateStore::enso(). Change it with the page's seasonPlanks().
     */
    public static function seasonWater(?array $c, ?array $hist, array $window, ?array $enso, string $water, ?array $pump = null): ?array
    {
        if (! $c || empty($hist['avg'])) {
            return null;
        }
        $K = array_merge(NpkModel::CLIMATE['default'], NpkModel::CLIMATE[$c['key']] ?? []);
        $t = self::ensoTilt($enso, $window);
        $a = $hist['avg'];
        $rain = (float) $a['rain'] * $t['rain'];
        $et0 = (float) $a['et0'] * ($t['rain'] < 1 ? 1 + (1 - $t['rain']) * 0.15 : 1);
        $need = $et0 * (float) $K['kc'] + (float) ($K['extraMm'] ?? 0);
        $eff = $rain * 0.75;
        $pc = $water === 'pump' ? self::pumpCover($pump, $window, $enso) : null;
        $cover = $pc ? $pc['cover'] : ($water === 'partial' ? NpkModel::FIELD['partialCover'] : 0.0);
        $supply = $water === 'irrigated' ? $need : $eff + $cover * max(0, $need - $eff);

        return ['rainMm' => $rain, 'waterShare' => min(1, $supply / max(1, $need)), 'needMm' => $need, 'water' => $water, 'avgRain' => (float) $a['rain'],
            'pumpLow' => $pc && $pc['low'] ? ['months' => $pc['low'], 'days' => $pc['lowDays'], 'given' => $pc['given']] : null, 'pumpYear' => $pc['year'] ?? null,
            'years' => count((array) ($hist['years'] ?? [])), 'tilt' => round($t['rain'], 3),
            'enso' => ($enso['phase'] ?? 'neutral') !== 'neutral' ? ($enso['label'] ?? null) : null, 'from' => $window['from'], 'days' => $window['days']];
    }

    /**
     * What a pump or well makes up of the rain's shortfall over a season
     * (2026-10-21, the owner; the page's pumpCover() is its twin): all year,
     * a little more than the usual pump; in the months it runs low or stops
     * (every dry season, or only in a very dry year: an El Nino of moderate
     * strength or more, or the driest of the twenty years) only pumpLow of
     * it, by the share of the season's days in those months.
     * $pump = FieldProfile::legacy()['pump'] (year, stops: month numbers).
     *
     * @return array{cover: float, low: list<int>, lowDays: int, year: ?string, given: bool}
     */
    public static function pumpCover(?array $pump, array $window, ?array $enso, bool $driest = false): array
    {
        $F = NpkModel::FIELD;
        $year = in_array($pump['year'] ?? null, ['always', 'badYears', 'everyYear', 'unsure'], true) ? $pump['year'] : null;
        $base = (float) ($year === 'always' ? $F['pumpAlways'] : $F['pumpCover']);
        $out = ['cover' => $base, 'low' => [], 'lowDays' => 0, 'year' => $year, 'given' => false];
        $dryYear = ($enso['phase'] ?? '') === 'el_nino' && in_array($enso['strength'] ?? '', ['moderate', 'strong', 'very strong'], true);
        if (! in_array($year, ['badYears', 'everyYear'], true) || ($year === 'badYears' && ! $dryYear && ! $driest) || empty($window['from'])) {
            return $out;
        }
        $stops = array_values(array_filter(array_map('intval', (array) ($pump['stops'] ?? [])), fn ($m) => $m >= 1 && $m <= 12));
        $out['given'] = (bool) $stops;
        $stops = $stops ?: $F['pumpDryMonths'];
        $days = max(1, (int) ($window['days'] ?? 1));
        $from = \Illuminate\Support\Carbon::parse($window['from']);
        $low = [];
        $n = 0;
        for ($d = 0; $d < $days; $d++) {
            $m = (int) $from->copy()->addDays($d)->format('n');
            if (in_array($m, $stops, true)) {
                $n++;
                $low[$m] = true;
            }
        }
        $share = $n / $days;

        return ['cover' => $base * (1 - $share) + (float) $F['pumpLow'] * $share, 'low' => array_keys($low), 'lowDays' => $n, 'year' => $year, 'given' => $out['given']];
    }

    /** Month numbers in words: "March to May", "January and March". */
    public static function monthWords(array $months): string
    {
        $names = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $m = array_values($months);
        if (! $m) {
            return '';
        }
        $run = count($m) > 2;
        for ($i = 1; $i < count($m); $i++) {
            $run = $run && ($m[$i] === $m[$i - 1] % 12 + 1);
        }
        $w = array_map(fn ($x) => $names[$x] ?? '', $m);

        return $run ? $w[0] . ' to ' . end($w) : (count($w) < 2 ? $w[0] : implode(', ', array_slice($w, 0, -1)) . ' and ' . end($w));
    }

    /** The season a need read, in words for the farmer and for the prompt. */
    public static function seasonWords(?array $s, bool $heavy = false, bool $dry = false): string
    {
        if (! $s) {
            return '';
        }
        $tilt = (float) ($s['tilt'] ?? 1);
        $water = (string) ($s['water'] ?? 'irrigated');
        $mm = fn ($v) => number_format(round((float) $v));

        return 'In these weeks the last ' . (int) $s['years'] . ' years brought about ' . $mm($s['avgRain'] ?? $s['rainMm']) . ' mm of rain'
            . (abs($tilt - 1) >= 0.01 && ! empty($s['enso']) ? '. With ' . lcfirst((string) $s['enso']) . ' ahead, expect about ' . $mm($s['rainMm']) . ' mm this time' : '')
            . ', against the ' . $mm($s['needMm']) . ' mm the crop drinks'
            . ($water !== 'irrigated' ? ', so ' . ($water === 'pump' ? 'the rain and the pump meet' : ($water === 'partial' ? 'the rain and the water source meet' : 'the rain meets')) . ' about ' . round(min(1, (float) $s['waterShare']) * 100) . '% of it' : '') . '.'
            // The pump's low months (2026-10-21).
            . (! empty($s['pumpLow']['months']) ? ' The pump runs low ' . (($s['pumpYear'] ?? '') === 'badYears' ? 'in a very dry year like this one, ' : '') . 'in ' . self::monthWords((array) $s['pumpLow']['months'])
                . ' (' . (int) $s['pumpLow']['days'] . ' of these ' . (int) $s['days'] . ' days' . (empty($s['pumpLow']['given']) ? ', the usual dry months since none were given' : '') . '), so the crop lives mostly on the rain then.' : '')
            . ($heavy ? ' That much rain washes calcium, magnesium and boron out, so the need holds more of them.' : '')
            . ($dry ? ' A season that dry makes boron and calcium harder for the roots to take up, so the need holds more of them.' : '');
    }

    /** Soil test bands, as levels: low, medium or high per nutrient; zinc, boron and sulfur low or ok. */
    public static function classes(array $tests): array
    {
        $out = [];
        foreach (self::NPK as $n) {
            if (in_array($tests[$n] ?? null, ['low', 'medium', 'high'], true)) {
                $out[$n] = $tests[$n];
            }
        }
        foreach (self::TESTED_MICROS as $el) {
            if (in_array($tests[$el] ?? null, ['low', 'ok'], true)) {
                $out[$el] = $tests[$el];
            }
        }
        if (($tests['OM'] ?? null) === 'low') {
            $out['OM'] = 'low';
        }

        return $out;
    }

    /**
     * A soil test's numbers and kit levels as levels, the way NPK Plus's
     * soilClass() reads them: P by Olsen or Bray, K in ppm or meq, N from
     * the organic matter or total N, a kit's level for a nutrient with no
     * number, and zinc, boron and sulfur against their critical levels.
     */
    public static function testClasses(?array $t): array
    {
        if (! $t) {
            return [];
        }
        $out = FieldProfile::testLevels($t);
        // NPK Plus reads a number before a kit's level (testLevels takes the kit's first).
        $band = fn (float $v, float $lo, float $hi) => $v < $lo ? 'low' : ($v <= $hi ? 'medium' : 'high');
        if (isset($t['p'])) {
            $out['P2O5'] = ($t['pMethod'] ?? 'olsen') === 'bray' ? $band((float) $t['p'], 15, 30) : $band((float) $t['p'], 10, 20);
        }
        if (isset($t['k'])) {
            $out['K2O'] = $band(($t['kUnit'] ?? 'ppm') === 'meq' ? (float) $t['k'] : (float) $t['k'] / 391, 0.2, 0.4);
        }
        if (isset($t['om'])) {
            $out['N'] = $band((float) $t['om'], 2, 4);
            if ((float) $t['om'] < 2) {
                $out['OM'] = 'low';
            }
        } elseif (isset($t['n'])) {
            $out['N'] = $band((float) $t['n'], 0.1, 0.2);
        }
        foreach (self::TESTED_MICROS as $el) {
            $v = $t[strtolower($el)] ?? null;
            $crit = NpkModel::ELEMENTS[$el]['critical'] ?? null;
            unset($out[$el]);
            if ($v !== null && $crit) {
                $out[$el] = (float) $v < $crit ? 'low' : 'ok';
            }
        }

        return $out;
    }

    /**
     * What a plan does to the soil's acidity, in kg of lime per hectare
     * (NpkModel::ACIDITY), as NPK Plus counts it. lines: [[slug, kg per
     * hectare, pct]]; lime and dolomite take acid away.
     */
    public static function acidEq(array $lines): float
    {
        $AC = NpkModel::ACIDITY;
        $sum = 0.0;
        foreach ($lines as [$slug, $kg, $pct]) {
            $slug = (string) $slug;
            $kg = (float) $kg;
            $pct = (array) $pct;
            if (isset($AC['perKg'][$slug])) {
                $sum += $kg * $AC['perKg'][$slug];

                continue;
            }
            $n = (float) ($pct['N'] ?? 0);
            $sp = (float) ($pct['S'] ?? 0);
            $perN = $AC['perN'][$slug] ?? (($sp >= 20 && $n >= 18) ? 5.35 : $AC['defaultPerN']);
            $sum += $kg * $n / 100 * $perN + ((isset($AC['perS'][$slug]) || ($sp >= 80 && ! ($n > 0))) ? $kg * $sp / 100 * ($AC['perS'][$slug] ?? 3.1) : 0);
        }

        return $sum;
    }

    /**
     * The plan's liming materials in kg of lime equivalent (ACIDITY perKg below 0: agricultural lime,
     * dolomite). Gypsum and calcium nitrate carry calcium but do not raise the pH, so they are not lime.
     */
    public static function limeEq(array $lines): float
    {
        $sum = 0.0;
        foreach ($lines as [$slug, $kg]) {
            $k = NpkModel::ACIDITY['perKg'][(string) $slug] ?? 0;
            if ($k < 0) {
                $sum += (float) $kg * -$k;
            }
        }

        return $sum;
    }

    /** How much is planted per hectare against the usual, and what that does to the goal. */
    public static function seed(?array $c, array $in): array
    {
        $out = ['perHa' => null, 'raw' => null, 'kind' => null, 'usual' => null, 'unitWord' => null, 'sf' => 1.0, 'pop' => 1.0, 'germ' => null, 'words' => '', 'warn' => false];
        $seed = (array) ($in['seed'] ?? []);
        $v = (float) ($seed['amount'] ?? 0);
        $a = (float) ($in['areaHa'] ?? 0);
        $unit = isset(self::SEED_UNITS[$seed['unit'] ?? '']) ? $seed['unit'] : 'kg';
        if ($v <= 0 || $a <= 0) {
            return $out;
        }
        $out['kind'] = in_array($unit, ['kg', 'g'], true) ? 'w' : 'n';
        $out['perHa'] = ($unit === 'g' ? $v / 1000 : $v) / $a;
        $out['raw'] = $out['perHa'];
        // Seed is counted at its germination against the usual 85% the guides assume.
        $germ = (float) ($seed['germination'] ?? 0);
        if (in_array($unit, ['kg', 'g', 'seeds', 'hills'], true) && $germ > 0) {
            $out['germ'] = max(1, min(100, $germ));
            $out['perHa'] = $out['perHa'] * $out['germ'] / self::GERM_USUAL;
        }
        $sd = $c ? (NpkModel::SEEDING[$c['key']] ?? null) : null;
        $u = $sd[$out['kind']] ?? null;
        $out['unitWord'] = $out['kind'] === 'w' ? 'kg' : (($sd['countWord'] ?? null) ?: 'plants');
        if (! $u) {
            $out['words'] = $sd ? 'The usual is given ' . ($out['kind'] === 'w' ? 'as a count of plants' : 'in kilograms') . ', so this cannot be compared.' : '';

            return $out;
        }
        $out['usual'] = $u;
        $tree = ! empty($c['treesPerHa']) || in_array($sd['countWord'] ?? '', ['trees', 'palms'], true);
        $out['words'] = 'The usual is ' . self::span($u) . ' ' . $out['unitWord'] . ' per hectare.';
        if ($tree && $out['kind'] === 'n') {
            $base = (float) ($c['treesPerHa'] ?? 0) ?: ($u[0] + $u[1]) / 2;
            $out['pop'] = max(0.3, min(2, $out['perHa'] / $base));
        } elseif ($out['perHa'] < $u[0] * 0.75) {
            $out['sf'] = max(0.3, $out['perHa'] / ($u[0] * 0.75));
            $out['warn'] = true;
            $out['words'] .= ' Yours is thinner, so the need is counted for ' . round($out['sf'] * 100) . ' percent of the goal.';
        } elseif ($out['perHa'] > $u[1] * 1.5) {
            $out['warn'] = true;
            $out['words'] .= ' Yours is much more: crowded plants need no more fertilizer and give no more harvest.';
        }

        return $out;
    }

    /** The share of the goal the field can carry (NpkModel::FIELD caps), from the detailed answers. */
    public static function fieldCap(?array $c, bool $fieldOn, string $water, array $conds): array
    {
        $out = ['k' => 1.0, 'why' => []];
        if (! $fieldOn || ! $c) {
            return $out;
        }
        $F = NpkModel::FIELD;
        $caps = $F['caps'];
        $kind = str_starts_with($c['key'], 'rice') ? 'rice' : 'other';
        if ($water === 'rainfed') {
            $out['why'][] = ['rainfed', $caps['rainfed'][$kind]];
        } elseif ($water === 'partial') {
            $out['why'][] = ['partial', $caps['partial'][$kind]];
        }
        if (in_array('saline', $conds, true)) {
            $out['why'][] = ['saline', $caps['saline']];
        }
        if (in_array('sodic', $conds, true)) {
            $out['why'][] = ['sodic', $caps['sodic']];
        }
        $k = 1.0;
        foreach ($out['why'] as $w) {
            $k *= $w[1];
        }
        $out['k'] = max($F['capFloor'], $k);

        return $out;
    }

    /** The yield goal the need is sized to, in t/ha, and why. */
    public static function goal(?array $c, array $in, bool $fieldOn, string $water, array $conds): array
    {
        $P = max(0.0, (float) ($in['potential'] ?? 0));
        $T = max(0.0, (float) ($in['target'] ?? 0));
        $ty = $c['typicalYield'] ?? null;
        $Y = null;
        $why = '';
        $warn = '';
        if ($T > 0) {
            // The target as set (2026-10-21, the owner: a record is not a ceiling; DK 8282S gives 12 to 13 t on good farms).
            $Y = $T;
            $why = 'your goal';
            if ($P > 0 && $T > $P) {
                $warn = 'Above the variety\'s best on record (' . rtrim(rtrim(number_format($P, 2, '.', ''), '0'), '.') . ' t a hectare): it is counted as you set it, but it takes a full stand, heavy grain and everything going right.';
            } elseif ($P > 0 && $T > 0.8 * $P) {
                $warn = 'Above 80 percent of the potential, each extra ton takes more fertilizer and more luck with the weather.';
            }
        } elseif ($P > 0) {
            $Y = 0.8 * $P;
            $why = '80 percent of the variety\'s potential, the most a well run field usually reaches';
        } elseif ($ty) {
            $Y = (float) $ty['hi'];
            $why = 'the Philippine average, since no goal or potential was given';
        }
        $sd = self::seed($c, $in);
        $cap = self::fieldCap($c, $fieldOn, $water, $conds);
        $capped = $T <= 0 && $cap['k'] < 1;

        return ['Y' => $Y, 'Yeff' => $Y ? $Y * $sd['sf'] * ($capped ? $cap['k'] : 1) : null, 'why' => $why, 'warn' => $warn,
            'P' => $P ?: null, 'T' => $T ?: null, 'seed' => $sd, 'cap' => $cap, 'capped' => $capped];
    }

    /** The field in detail as a layer: organic matter, drainage, what the last crop left, last season's zinc. */
    public static function fieldLayer(?array $c, array $f, array $a, array $prev): array
    {
        $out = ['on' => $a !== [] || $prev !== [], 'nMult' => 1.0, 'credit' => ['N' => 0, 'K2O' => 0], 'zinc' => false];
        if (! $out['on'] || ! $c) {
            return $out;
        }
        $F = NpkModel::FIELD;
        if (($a['organic'] ?? '') === 'high' && ! in_array('low_om', $f, true)) {
            $out['nMult'] *= $F['omHighN'];
        }
        if (($a['drainage'] ?? '') === 'slow' && ! in_array('flooded', $f, true) && ! in_array('waterlogged', $f, true)) {
            $out['nMult'] *= $F['slowN'];
        }
        // The last crop: the detailed answer, or the simple one the analysis asked.
        $last = $a['prevCrop'] ?? null;
        if (($last === 'legume' || in_array('legume', $prev, true)) && empty($c['legume'])) {
            $out['credit']['N'] = $F['legumeN'];
        }
        if ($last === 'rice_straw' || in_array('rice_straw', $prev, true)) {
            $out['credit']['K2O'] = $F['strawK'];
        }
        $out['zinc'] = in_array('other:zinc', (array) ($a['outbreaks'] ?? []), true);

        return $out;
    }

    /**
     * NPK Plus's needModel(): the need per hectare, how it was counted, and the
     * secondary and micro elements with their need. perHa and acidEq are the
     * plan's (none: the need before any plan).
     */
    public static function model(?array $c, array $g, array $f, array $cls, array $fl, array $perHa = [], float $acidEq = 0.0, ?float $limeEq = null): array
    {
        $out = ['need' => [], 'raw' => [], 'how' => [], 'adj' => [], 'share' => [], 'whys' => [], 'credit' => [], 'micro' => [], 'pLock' => false, 'lockouts' => [], 'acid' => null];
        $has = fn (string $k) => in_array($k, $f, true);
        // The plan's own acidity (kg of lime per hectare): how far it goes toward its full effect.
        $alk = (bool) array_intersect(['alkaline', 'strongAlk', 'calcareous', 'sodic'], $f);
        $sour = (bool) array_intersect(['strongAcid', 'acid', 'acid_sulfate'], $f);
        $au = $acidEq > 0 ? max(0, min(1, $acidEq / (NpkModel::ACID_AT ?: 400))) : 0;
        if (abs($acidEq) >= 50) {
            $out['acid'] = ['eq' => $acidEq, 'u' => $au, 'alk' => $alk, 'sour' => $sour];
        }
        $base = NpkModel::SOIL_SHARE + [];
        if ($c && isset(NpkModel::SOIL_SHARE_CROP[$c['key']])) {
            $base = NpkModel::SOIL_SHARE_CROP[$c['key']] + $base;
        }
        $m = ['N' => 1.0, 'P2O5' => 1.0, 'K2O' => 1.0];
        $sh = $base;
        $same = [];
        foreach ($f as $k) {
            $a = NpkModel::ADJUST[$k] ?? null;
            if (! $a) {
                continue;
            }
            $used = false;
            $skipped = false;
            foreach (self::NPK as $n) {
                // A factor that does not hold beside another (a sodic soil's high pH keeps phosphorus soluble).
                if (isset($a[$n], $a['unless'][$n]) && $has($a['unless'][$n])) {
                    $skipped = true;

                    continue;
                }
                if (isset($a[$n])) {
                    if ($n === 'P2O5' && $a[$n] > 1) {
                        $out['pLock'] = true;
                    }
                    // Two conditions that lose it the same way count once, the larger.
                    $s = $a['same'][$n] ?? null;
                    if ($s) {
                        $same[$n . ':' . $s] = max($same[$n . ':' . $s] ?? 1, $a[$n]);
                    } else {
                        $m[$n] *= $a[$n];
                    }
                    $used = true;
                }
                if (isset($a['share'][$n])) {
                    $sh[$n] *= $a['share'][$n];
                }
            }
            if ($used) {
                $out['whys'][] = $skipped && ! empty($a['whyUnless']) ? $a['whyUnless'] : $a['why'];
            }
        }
        // The soil test. A low N test and low organic matter are one shortfall (the soil's own nitrogen):
        // its multiplier joins the same group and they count once, the larger (2026-10-19).
        foreach (self::NPK as $n) {
            $b = ($cls[$n] ?? null) === 'low' ? 'testLow' : (($cls[$n] ?? null) === 'high' ? 'testHigh' : null);
            if (! $b) {
                continue;
            }
            $a = NpkModel::ADJUST[$b];
            $s = $a['same'][$n] ?? null;
            if ($s) {
                $same[$n . ':' . $s] = max($same[$n . ':' . $s] ?? 1, $a[$n]);
            } else {
                $m[$n] *= $a[$n];
            }
            $sh[$n] *= $a['share'][$n];
            $out['whys'][] = $a['why'] . ' in ' . strtolower(self::NAMES[$n]);
        }
        foreach ($same as $key => $v) {
            $n = explode(':', $key)[0];
            // An acidifying plan on alkaline soil frees part of the phosphorus lock; on acid soil it adds to it.
            if ($n === 'P2O5' && str_ends_with($key, ':pfix') && $au > 0) {
                if ($alk && $v > 1) {
                    $v = 1 + ($v - 1) * (1 - $au * NpkModel::ACID_UNLOCK);
                    $out['whys'][] = 'the acid fertilizers in your plan free some of the locked phosphorus';
                } elseif ($sour) {
                    $v = $v + 0.1 * $au;
                    $out['whys'][] = 'the acid fertilizers in your plan make the acid soil sourer';
                }
            }
            $m[$n] *= $v;
        }
        // Your field: the nitrogen the organic matter or slow drainage adds or saves.
        if ($fl['on']) {
            $m['N'] *= $fl['nMult'];
        }
        foreach (self::NPK as $n) {
            $out['adj'][$n] = max(0.5, min(1.8, $m[$n]));
            $out['share'][$n] = max(0.3, min(0.97, $sh[$n]));
        }
        if (! $c) {
            return $out;
        }
        $R = $c['recommendedPerHa'] ?? [];
        $Up = $c['uptakePerTon'] ?? [];
        $RE = ($c['efficiency'] ?? []) + NpkModel::RECOVERY;
        $Yg = NpkModel::GUIDE_YIELD[$c['key']] ?? (isset($c['typicalYield']['hi']) ? $c['typicalYield']['hi'] * 1.25 : null);
        $fromTypical = empty($g['T']) && empty($g['P']);
        foreach (self::NPK as $n) {
            $v = null;
            if (! empty($c['legume']) && $n === 'N') {
                $out['how']['N'] = 'legume';
                $out['raw']['N'] = is_array($R['N'] ?? null) ? (float) $R['N'][0] : 20.0;
                $out['need']['N'] = $out['raw']['N'];
                $out['share']['N'] = 0.95;

                continue;
            }
            if (is_array($R[$n] ?? null)) {
                // Nitrogen follows the goal ton for ton; phosphorus and potassium only part way.
                $ratio = ! empty($g['Yeff']) && $Yg ? $g['Yeff'] / $Yg : 1;
                $scale = $n === 'N' ? $ratio : NpkModel::PK_BASE + (1 - NpkModel::PK_BASE) * $ratio;
                $v = ($R[$n][0] + $R[$n][1]) / 2 * $scale * ($fromTypical ? $g['seed']['pop'] : 1);
                $out['how'][$n] = 'guide';
            } elseif (($Up[$n] ?? 0) > 0 && ! empty($g['Yeff'])) {
                $v = ! empty($c['removalOnly']) ? $g['Yeff'] * $Up[$n] : $g['Yeff'] * $Up[$n] * (1 - $base[$n]) / $RE[$n];
                $out['how'][$n] = ! empty($c['removalOnly']) ? 'removal' : 'uptake';
            }
            if ($v !== null) {
                $out['raw'][$n] = $v;
                $out['need'][$n] = $v * $out['adj'][$n];
            }
        }
        // Your field: what the last crop left, never more than part of the need.
        if ($fl['on']) {
            foreach (['N', 'K2O'] as $n) {
                $cr = $fl['credit'][$n] ?? 0;
                if (! $cr || ! isset($out['need'][$n]) || ($out['how'][$n] ?? '') === 'legume') {
                    continue;
                }
                $cut = min($cr, $out['need'][$n] * NpkModel::FIELD['creditFloor']);
                $out['need'][$n] -= $cut;
                $out['credit'][$n] = $cut;
            }
        }
        // Lockouts the plan itself makes: too much phosphorus ties up zinc and
        // iron, too much potassium crowds out magnesium and calcium, lime ties
        // up zinc, boron, manganese, copper and iron, too much zinc crowds out copper.
        $pf = [];
        if ((float) ($perHa['P2O5'] ?? 0) > (! empty($out['need']['P2O5']) ? $out['need']['P2O5'] * 1.5 : 100)) {
            $pf[] = 'high_p';
        }
        if ((float) ($perHa['K2O'] ?? 0) > (! empty($out['need']['K2O']) ? $out['need']['K2O'] * 1.5 : 150)) {
            $pf[] = 'high_k';
        }
        // Lime in the plan: its liming materials, 300 kg of lime equivalent a hectare or more (a caller
        // that gives no limeEq falls back on the plan's net effect on the pH). Gypsum is not lime.
        if (($limeEq ?? max(0.0, -$acidEq)) >= 300) {
            $pf[] = 'liming';
        }
        if ((float) ($perHa['Zn'] ?? 0) > 10) {
            $pf[] = 'excess_zn';
        }
        $out['lockouts'] = $pf;
        $ff = array_values(array_unique(array_merge($f, $pf)));
        // Secondary, micro and beneficial elements: from a bag only where a shortfall is likely.
        $ys = ! empty($c['typicalYield']['hi']) && ! empty($g['Yeff']) ? max(0.8, min(1.4, $g['Yeff'] / $c['typicalYield']['hi'])) : 1;
        $L = NpkModel::MICRO_LEVELS;
        foreach (NpkModel::ELEMENTS as $el => $e) {
            $why = [];
            $down = [];
            $score = 0;
            foreach ($e['weights'] ?? [] as $k => $w) {
                if (! in_array($k, $ff, true)) {
                    continue;
                }
                $score += $w;
                if ($w > 0) {
                    $why[] = self::FACTOR[$k] ?? $k;
                } elseif (! empty($e['down'][$k]) && ! in_array($e['down'][$k], $down, true)) {
                    $down[] = $e['down'][$k];
                }
            }
            $sens = in_array($c['key'], $e['sensitive'] ?? [], true);
            // The crop's short name, as the page says it (cropName, cropShort): "Rice, transplanted (Palay)" is "rice".
            $cropWord = strtolower(explode(',', explode(' (', str_replace(' — ', ', ', (string) $c['label']))[0])[0]);
            if ($sens) {
                $score += $e['sensitiveWeight'] ?? 1;
                $why[] = $cropWord . ' needs more of it';
            }
            // Why the soil gives less of it (NpkModel::SOIL_FACTOR): holds little, locks it up, or loses it.
            $sw = ['low' => [], 'lock' => [], 'loss' => [], 'past' => false, 'crop' => $sens ? $cropWord : null];
            foreach ($e['weights'] ?? [] as $k => $w) {
                if ($w > 0 && in_array($k, $ff, true) && isset(NpkModel::SOIL_FACTOR[$k])) {
                    $sw[NpkModel::SOIL_FACTOR_EL[$el][$k] ?? NpkModel::SOIL_FACTOR[$k][0]][] = $k;
                }
            }
            // Acid around the roots on a high pH soil: zinc, iron, manganese and copper come
            // free again, and on sodic soil the soil's own lime turns to calcium.
            if ($au > 0 && $alk && (in_array($el, ['Zn', 'Fe', 'Mn', 'Cu'], true) || ($el === 'Ca' && $has('sodic')))) {
                $score -= $au;
                $down[] = $el === 'Ca' ? 'the acid fertilizers in your plan turn the soil\'s own lime into calcium' : 'the acid fertilizers in your plan free some of it around the roots';
            }
            $level = $score >= $L['likely']['at'] ? 'likely' : ($score >= $L['watch']['at'] ? 'watch' : 'none');
            if ($fl['zinc'] && $el === 'Zn' && $level !== 'likely') {
                $level = 'likely';
                array_unshift($why, 'zinc ran short last season');
            }
            $sw['past'] = $fl['zinc'] && $el === 'Zn';
            if (! empty($e['beneficial']) && ! $sens) {
                $level = 'notneeded';
            }
            // A soil test settles it.
            if (($cls[$el] ?? null) === 'low') {
                $level = 'test';
                array_unshift($why, 'your soil test reads low');
            } elseif (($cls[$el] ?? null) === 'ok') {
                $level = 'testok';
            }
            $lv = $L[$level] ?? ['dose' => 0, 'share' => 1];
            // What the soil gives on its own (NpkModel::SOIL_STEP, 2026-10-21): from the same score, held to the level's share.
            $soil = max(NpkModel::SOIL_FLOOR, min(1.0, 1 - NpkModel::SOIL_STEP * max(0.0, $score)));
            $soil = match ($level) {
                'testok', 'notneeded' => 1.0,
                'none' => max(NpkModel::SOIL_NONE, $soil),
                default => min($soil, (float) ($lv['share'] ?? 1)),
            };
            $out['micro'][$el] = ['level' => $level, 'why' => $why, 'down' => $down, 'need' => (float) $e['base'] * (float) $lv['dose'] * $ys, 'share' => round($soil, 4), 'soilWhy' => $sw];
        }

        return $out;
    }

    /**
     * The plan against the need, the way NPK Plus's NPK and Micros tabs read
     * it, so a program checked here reads as it would there: N, P2O5 and K2O
     * short under 90 percent of the need and too much past 130 percent; an
     * element short under 90 percent and too much past its own limit (over
     * times the need); an element with no need is extra when the plan has
     * it, else not flagged, not needed, or enough by the soil test.
     *
     * @return list<array{key: string, name: string, need: ?float, plan: float, state: string, level?: string, why?: list<string>, fert?: string}>
     */
    public static function check(array $run, array $perHa): array
    {
        $rows = [];
        foreach (self::NPK as $n) {
            $need = $run['need'][$n] ?? null;
            $plan = (float) ($perHa[$n] ?? 0);
            $state = 'noneed';
            if ($need !== null) {
                $r = $need > 0 ? $plan / $need : ($plan > 0 ? 9 : 1);
                $state = $r < 0.9 ? 'short' : ($r > 1.3 ? 'over' : 'right');
            }
            $rows[] = ['key' => $n, 'name' => self::NAMES[$n], 'need' => $need, 'plan' => round($plan, 1), 'state' => $state];
        }
        foreach ((array) ($run['micro'] ?? []) as $el => $mm) {
            $e = NpkModel::ELEMENTS[$el] ?? [];
            $need = (float) ($mm['need'] ?? 0);
            $plan = (float) ($perHa[$el] ?? 0);
            if ($need > 0) {
                $r = $plan / $need;
                $state = $r < 0.9 ? 'short' : ($r > ($e['over'] ?? 5) ? 'over' : 'right');
            } else {
                $state = $plan > 0 ? 'extra' : (in_array($mm['level'], ['notneeded', 'testok'], true) ? $mm['level'] : 'noflag');
            }
            $rows[] = ['key' => $el, 'name' => self::NAMES[$el] ?? $el, 'need' => $need > 0 ? round($need, 3) : null, 'plan' => round($plan, 3), 'state' => $state,
                'level' => $mm['level'], 'why' => array_slice((array) ($mm['why'] ?? []), 0, 3), 'fert' => (string) ($e['fert'] ?? ''), 'beneficial' => ! empty($e['beneficial']),
                'soilShare' => round((float) ($mm['share'] ?? 1), 3), 'soilSay' => self::soilSay($mm)];
        }

        return $rows;
    }

    /**
     * What the soil gives of an element on its own, in a sentence (the page's
     * soilSay(), 2026-10-21): "The soil gives about 84% of it on its own:
     * alkaline soil locks part of it; corn needs more of it than most crops."
     * '' for an element the crop does not need.
     */
    public static function soilSay(array $mm): string
    {
        $level = (string) ($mm['level'] ?? 'none');
        if ($level === 'notneeded') {
            return '';
        }
        if ($level === 'testok') {
            return 'Your soil test reads enough: the soil gives all of it.';
        }
        $pct = (int) round((float) ($mm['share'] ?? 1) * 100);
        if ($level === 'none') {
            return 'Your soil likely gives about all of it (' . $pct . '%) on its own, by its type, pH and place. Only a soil test can say for sure.';
        }
        $sw = (array) ($mm['soilWhy'] ?? []);
        $word = fn ($k) => NpkModel::SOIL_FACTOR[$k][1] ?? $k;
        $and = fn (array $a) => count($a) < 2 ? (string) ($a[0] ?? '') : implode(', ', array_slice($a, 0, -1)) . ' and ' . end($a);
        $bits = [];
        $low = array_values(array_unique(array_map($word, (array) ($sw['low'] ?? []))));
        if ($low) {
            $bits[] = $and($low) . (count($low) > 1 ? ' hold' : ' holds') . ' less of it';
        }
        $lock = array_values(array_unique(array_map($word, (array) ($sw['lock'] ?? []))));
        if ($lock) {
            $bits[] = $and($lock) . (count($lock) > 1 ? ' lock' : ' locks') . ' part of it';
        }
        $loss = array_values(array_unique(array_map($word, (array) ($sw['loss'] ?? []))));
        if ($loss) {
            $bits[] = $and($loss);
        }
        if (! empty($sw['past'])) {
            $bits[] = 'it ran short last season';
        }
        if (! empty($sw['crop'])) {
            $bits[] = $sw['crop'] . ' needs more of it than most crops';
        }

        return ($level === 'test' ? 'Your soil test reads low, so the soil gives about ' : 'The soil gives about ') . $pct . '% of it on its own'
            . ($bits ? ': ' . implode('; ', $bits) : '') . '.';
    }

    /** The secondary and micro elements a field needs from a bag, in words: "Zinc 5 kg Zn (likely short: ...)". */
    public static function microWords(array $micro): string
    {
        $bits = [];
        foreach ($micro as $el => $mm) {
            if ((float) ($mm['need'] ?? 0) <= 0) {
                continue;
            }
            $kg = (float) $mm['need'];
            $bits[] = (self::NAMES[$el] ?? $el) . ' ' . ($kg >= 1 ? rtrim(rtrim(number_format($kg, 1, '.', ''), '0'), '.') . ' kg' : round($kg * 1000) . ' g') . ' ' . $el
                . ' (' . (['watch' => 'may run short', 'likely' => 'likely short', 'test' => 'short by the soil test'][$mm['level']] ?? $mm['level'])
                . ($mm['why'] ? ': ' . implode(', ', array_slice($mm['why'], 0, 3)) : '')
                . '; the soil gives about ' . (int) round((float) ($mm['share'] ?? 1) * 100) . '% of the crop\'s need on its own)';
        }

        return implode('; ', $bits);
    }

    /** The need in a line: "N 120, P2O5 45, K2O 60 kg per hectare". */
    public static function words(array $need): string
    {
        $bits = [];
        foreach (self::NPK as $n) {
            if (isset($need[$n])) {
                $bits[] = $n . ' ' . (int) round($need[$n]);
            }
        }

        return $bits ? implode(', ', $bits) . ' kg per hectare' : '';
    }

    /** A range said plainly: "18 to 24", "60,000 to 91,000". */
    private static function span(array $u): string
    {
        $fmt = fn ($x) => $x >= 1000 ? number_format($x) : rtrim(rtrim(number_format((float) $x, 2, '.', ''), '0'), '.');

        return $u[0] == $u[1] ? $fmt($u[0]) : $fmt($u[0]) . ' to ' . $fmt($u[1]);
    }
}
