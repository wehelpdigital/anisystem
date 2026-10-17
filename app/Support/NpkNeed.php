<?php

namespace App\Support;

/**
 * NPK Plus's need model, run on the server (2026-10-16, the owner: the Crop
 * Protocol "is not inline with the logic of the NPK Plus calculator").
 *
 * The calculator works the need out in the page, as the farmer types
 * (needModel() and its helpers in resources/views/npk-plus/index.blade.php).
 * This is its twin for the analyses that run on the server: the same goal
 * (a target held to the variety's potential, else 80 percent of the
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
 */
final class NpkNeed
{
    public const NPK = ['N', 'P2O5', 'K2O'];

    /** The elements a soil test can say are low or enough by their critical level (NpkModel::ELEMENTS). */
    public const TESTED_MICROS = ['Zn', 'B', 'S'];

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
        'liming' => 'the lime in the plan locks it up', 'excess_zn' => 'heavy zinc in the plan crowds it out'];

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
        $water = in_array($in['water'] ?? '', ['irrigated', 'partial', 'rainfed'], true) ? $in['water'] : 'irrigated';
        $g = self::goal($c, $in, $fieldOn, $water, (array) ($in['conditions'] ?? []));
        $fl = self::fieldLayer($c, $f, $fieldOn ? FieldProfile::normalize($profile) : [], (array) ($in['prev'] ?? []));
        $plan = (array) ($in['plan'] ?? []);
        $md = self::model($c, $g, $f, $cls, $fl, (array) ($plan['perHa'] ?? []), (float) ($plan['acidEq'] ?? 0));

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

        return array_values(array_unique($f));
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
            $Y = $P > 0 ? min($T, $P) : $T;
            $why = $P > 0 && $T > $P ? 'your goal, held to the variety\'s potential' : 'your goal';
            if ($P > 0 && $T > 0.8 * $P && $T <= $P) {
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
    public static function model(?array $c, array $g, array $f, array $cls, array $fl, array $perHa = [], float $acidEq = 0.0): array
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
        if ((float) ($perHa['Ca'] ?? 0) >= 150) {
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
            if ($sens) {
                $score += $e['sensitiveWeight'] ?? 1;
                // The crop's short name, as the page says it (cropName, cropShort): "Rice, transplanted (Palay)" is "rice".
                $why[] = strtolower(explode(',', explode(' (', str_replace(' — ', ', ', (string) $c['label']))[0])[0]) . ' needs more of it';
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
            $out['micro'][$el] = ['level' => $level, 'why' => $why, 'down' => $down, 'need' => (float) $e['base'] * (float) $lv['dose'] * $ys, 'share' => (float) $lv['share']];
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
                'level' => $mm['level'], 'why' => array_slice((array) ($mm['why'] ?? []), 0, 3), 'fert' => (string) ($e['fert'] ?? ''), 'beneficial' => ! empty($e['beneficial'])];
        }

        return $rows;
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
                . ($mm['why'] ? ': ' . implode(', ', array_slice($mm['why'], 0, 3)) : '') . ')';
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
