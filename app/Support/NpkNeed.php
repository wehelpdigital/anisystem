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
 * numbers. Change the arithmetic in BOTH places. Only the parts that read
 * the farmer's own fertilizer plan (the acidity it adds, the lockouts a
 * heavy plan makes) are left out: here there is no plan yet, the protocol
 * is what writes it.
 *
 * In: crop (a catalogue key), method (rice: transplanted, direct_wet,
 * direct_dry), potential and target (t/ha), seed {amount, unit, germination}
 * for areaHa, texture (sandy, loam, clay, unsure), conditions (acidic,
 * neutral, alkaline, sodic, saline, acid_sulfate, low_om, peat,
 * waterlogged), ph, tests {N|P2O5|K2O: low|medium|high}, water (irrigated,
 * partial, rainfed), profile (FieldProfile answers), prev (legume, rice_straw).
 */
final class NpkNeed
{
    public const NPK = ['N', 'P2O5', 'K2O'];

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
        'peat' => 'peat soil', 'waterlogged' => 'waterlogged soil', 'flooded' => 'a flooded paddy', 'calcareous' => 'limy (calcareous) soil'];

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
        $md = self::model($c, $g, $f, $cls, $fl);

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

    /** Soil test bands, as levels: low, medium or high per nutrient. */
    public static function classes(array $tests): array
    {
        $out = [];
        foreach (self::NPK as $n) {
            if (in_array($tests[$n] ?? null, ['low', 'medium', 'high'], true)) {
                $out[$n] = $tests[$n];
            }
        }

        return $out;
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

    /** NPK Plus's needModel(): the need per hectare, how it was counted, and the secondary and micro scores. */
    public static function model(?array $c, array $g, array $f, array $cls, array $fl): array
    {
        $out = ['need' => [], 'raw' => [], 'how' => [], 'adj' => [], 'share' => [], 'whys' => [], 'credit' => [], 'micro' => [], 'pLock' => false];
        $has = fn (string $k) => in_array($k, $f, true);
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
        foreach ($same as $key => $v) {
            $m[explode(':', $key)[0]] *= $v;
        }
        foreach (self::NPK as $n) {
            $b = ($cls[$n] ?? null) === 'low' ? 'testLow' : (($cls[$n] ?? null) === 'high' ? 'testHigh' : null);
            if (! $b) {
                continue;
            }
            $a = NpkModel::ADJUST[$b];
            $m[$n] *= $a[$n];
            $sh[$n] *= $a['share'][$n];
            $out['whys'][] = $a['why'] . ' in ' . strtolower(self::NAMES[$n]);
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
        // Secondary, micro and beneficial elements: from a bag only where a shortfall is likely.
        $L = NpkModel::MICRO_LEVELS;
        foreach (NpkModel::ELEMENTS as $el => $e) {
            $why = [];
            $score = 0;
            foreach ($e['weights'] ?? [] as $k => $w) {
                if (! $has($k)) {
                    continue;
                }
                $score += $w;
                if ($w > 0) {
                    $why[] = self::FACTOR[$k] ?? $k;
                }
            }
            $sens = in_array($c['key'], $e['sensitive'] ?? [], true);
            if ($sens) {
                $score += $e['sensitiveWeight'] ?? 1;
                $why[] = strtolower(explode(',', $c['label'])[0]) . ' needs more of it';
            }
            $level = $score >= $L['likely']['at'] ? 'likely' : ($score >= $L['watch']['at'] ? 'watch' : 'none');
            if ($fl['zinc'] && $el === 'Zn' && $level !== 'likely') {
                $level = 'likely';
                array_unshift($why, 'zinc ran short last season');
            }
            if (! empty($e['beneficial']) && ! $sens) {
                $level = 'notneeded';
            }
            $out['micro'][$el] = ['level' => $level, 'why' => $why];
        }

        return $out;
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
