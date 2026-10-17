<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The area's typical soil (2026-10-18, the owner: "an added beneficial data in
 * the analysis ... and a way to lessen tokens for each analysis ... based on
 * the location").
 *
 * Soil samples taken across the country's rice and corn land, read into our
 * own tables (as_soil_fertility, as_soil_ph, as_soil_salinity; seed files in
 * database/soil-atlas, loaded by sync()):
 *  - fertility, per barangay, town and province and crop: the share of
 *    samples in each class of pH, nitrogen (as organic matter), phosphorus,
 *    potassium and, where tested, zinc; measured numbers where a table gave
 *    them; the texture class; the usual fertilizer rates by variety type and
 *    season (kg N, P2O5, K2O per hectare, and the bag options);
 *  - pH, per province and town: the rated land in each pH class, read off
 *    the maps (a town's share is its own pixels, scaled to the map's totals);
 *  - salinity, per coastal province and town: hectares by salinity class at
 *    0 to 30, 30 to 60 and 60 to 90 cm, and the EC of the barangays sampled.
 *
 * The rule every reader keeps: this is TYPICAL FOR THE AREA, never a test of
 * the farmer's field. A lot's own answers and its soil test always win; the
 * atlas only fills what is unknown (fill()), and says so. What a farmer sees
 * never names where the numbers came from.
 *
 *   $a = SoilAtlas::for('Nueva Ecija', 'Muñoz City', null, 'rice');   // or null
 *   SoilAtlas::words($a);              // prompt lines, '' for null
 *   SoilAtlas::fill($npkInput, $a);    // NpkNeed input with the unknowns filled
 *   SoilAtlas::forLot($lotRow, 'rice'); // a Global Lots row (province/town/barangay or where)
 */
final class SoilAtlas
{
    use Concerns\ShelfConnection;

    public const TABLES = ['fertility' => 'as_soil_fertility', 'ph' => 'as_soil_ph', 'salinity' => 'as_soil_salinity'];

    /** The class words of a soil test rating (pH has its own). */
    public const CLASS_WORDS = ['L' => 'low', 'ML' => 'moderately low', 'M' => 'medium', 'MH' => 'moderately high', 'H' => 'high'];

    /** What each pH rating covers. Low is either end: the class does not say which. */
    public const PH_CLASS = [
        'H' => ['key' => 'high', 'range' => '5.6 to 6.8', 'band' => 'good', 'words' => 'moderately acid to near neutral, good for most crops', 'mid' => 6.2],
        'MH' => ['key' => 'mhigh', 'range' => '5.1 to 5.5', 'band' => 'strongly acid', 'words' => 'strongly acid', 'mid' => 5.3],
        'ML' => ['key' => 'mlow', 'range' => '4.6 to 5.0', 'band' => 'very acid', 'words' => 'very strongly acid', 'mid' => 4.8],
        'L' => ['key' => 'low', 'range' => '4.5 or below, or 6.9 or above', 'band' => 'extreme', 'words' => 'extreme: either very acid or near neutral to alkaline, the class does not say which', 'mid' => null],
    ];

    /** A rating onto NpkNeed's soil test words (the guide's own steps: low 60, moderately low 40, moderately high 20, high 7 kg P2O5). */
    public const TEST_LEVEL = ['L' => 'low', 'ML' => 'medium', 'M' => 'medium', 'MH' => 'high', 'H' => 'high'];

    public const TEXTURE = ['L' => 'sandy', 'M' => 'loam', 'H' => 'clay'];

    public const TEXTURE_WORDS = ['L' => 'light (sandy)', 'M' => 'medium (loamy)', 'H' => 'heavy (clayey)'];

    public const SAL_CLASS = ['ns' => 'not saline', 'ss' => 'slightly saline', 'ms' => 'moderately saline', 'sev' => 'severely saline', 'vsev' => 'very severely saline'];

    /** EC (dS/m) upper bounds of the salinity classes. */
    public const EC_CLASS = ['ns' => 2.0, 'ss' => 4.0, 'ms' => 8.0, 'sev' => 16.0];

    private const ORDER = ['L' => 0, 'ML' => 1, 'M' => 1.5, 'MH' => 2, 'H' => 3];

    /** One request's lookups. */
    private static array $memo = [];

    /** The shelf connection (Concerns\ShelfConnection), or config('soilatlas.connection') for a rehearsal of the atlas alone. */
    private static function conn(): string
    {
        return (string) (config('soilatlas.connection') ?: config('climate.connection') ?: \Illuminate\Support\Facades\DB::getDefaultConnection());
    }

    public static function ready(): bool
    {
        return (bool) Cache::remember('soil-atlas:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable('as_soil_fertility'));
    }

    /* ================================================================ read */

    /**
     * The area's typical soil for a crop, or null when the atlas knows nothing
     * of the place. Place names as a lot stores them (matched by PlaceNames).
     */
    public static function for(?string $province, ?string $town = null, ?string $barangay = null, ?string $crop = null): ?array
    {
        $place = PlaceNames::resolve($province, $town, $barangay);
        if (! $place['province']) {
            return null;
        }
        $crop = self::cropOf($crop);
        $mk = implode('|', [$place['province'], $place['town'], $place['barangay'], $crop]);
        if (array_key_exists($mk, self::$memo)) {
            return self::$memo[$mk];
        }
        if (! self::ready()) {
            return self::$memo[$mk] = null;
        }
        try {
            $key = 'soil-atlas:' . self::conn() . ':v1:' . md5($mk);
            $out = Cache::remember($key, 6 * 3600, fn () => self::build($place, $crop) ?? false);
        } catch (\Throwable $e) {
            report($e);
            $out = null;
        }

        return self::$memo[$mk] = ($out ?: null);
    }

    /** A Global Lots row (or any array with province/town/barangay, or a "Town, Province" where). */
    public static function forLot(?array $lot, ?string $crop = null): ?array
    {
        if (! $lot) {
            return null;
        }
        $p = $lot['province'] ?? null;
        $t = $lot['town'] ?? null;
        $b = $lot['barangay'] ?? null;
        if (! $p && ! empty($lot['where'])) {
            $r = PlaceNames::fromWhere((string) $lot['where']);
            [$p, $t, $b] = [$r['province'], $r['town'], $r['barangay']];
        }

        return self::for($p, $t, $b, $crop);
    }

    /** "Town, Province" or "Barangay, Town, Province", as analyses carry the place. */
    public static function forWhere(?string $where, ?string $crop = null): ?array
    {
        $r = PlaceNames::fromWhere($where);

        return $r['province'] ? self::for($r['province'], $r['town'], $r['barangay'], $crop) : null;
    }

    /** rice, corn, or other: the soil is read from the rice or corn survey, the rates only for their own crop. */
    private static function cropOf(?string $crop): string
    {
        $c = mb_strtolower(trim((string) $crop));
        if (str_starts_with($c, 'corn') || str_contains($c, 'mais') || $c === 'maize') {
            return 'corn';
        }

        return ($c === '' || str_starts_with($c, 'rice') || str_contains($c, 'palay')) ? 'rice' : 'other';
    }

    private static function build(array $place, string $crop): ?array
    {
        $db = self::db();
        $p = $place['province'];
        $t = $place['town'];
        $b = $place['barangay'];
        $rows = $db->table('as_soil_fertility')->where('province', $p)
            ->where(function ($q) use ($t, $b) {
                $q->where('level', 'province');
                if ($t) {
                    $q->orWhere(fn ($q2) => $q2->where('level', 'town')->where('town', $t));
                    if ($b) {
                        $q->orWhere(fn ($q3) => $q3->where('level', 'barangay')->where('town', $t)->where('barangay', $b));
                    }
                }
            })->get();
        $phRows = $db->table('as_soil_ph')->where('province', $p)
            ->where(fn ($q) => $t ? $q->where('level', 'province')->orWhere('town', $t) : $q->where('level', 'province'))->get();
        $salRows = $db->table('as_soil_salinity')->where('province', $p)
            ->where(function ($q) use ($t, $b) {
                $q->where('level', 'province');
                if ($t) {
                    $q->orWhere(fn ($q2) => $q2->where('level', 'town')->where('town', $t));
                    $q->orWhere(fn ($q3) => $q3->where('level', 'barangay')->where('town', $t));
                }
            })->get();
        if ($rows->isEmpty() && $phRows->isEmpty() && $salRows->isEmpty()) {
            return null;
        }
        $dec = fn ($r) => ['level' => $r->level, 'crop' => $r->crop ?? null, 'samples' => (int) ($r->samples ?? 0),
            'year' => $r->year ? (int) $r->year : null, 'town' => $r->town ?? null, 'barangay' => $r->barangay ?? null,
            'totalHa' => isset($r->totalHa) ? (int) $r->totalHa : null] + (json_decode((string) $r->data, true) ?: []);

        // Fertility: this crop's survey first, else rice's (the soil is the soil; only the rates belong to a crop).
        $fert = $rows->map($dec)->all();
        $pick = function (array $rows, string $want) {
            $order = ['barangay' => 0, 'town' => 1, 'province' => 2];
            usort($rows, fn ($x, $y) => [$x['crop'] !== $want, $order[$x['level']]] <=> [$y['crop'] !== $want, $order[$y['level']]]);
            foreach ($rows as $r) {
                $min = $r['level'] === 'barangay' ? 2 : 3;
                if ($r['samples'] >= $min || ($r['guideSamples'] ?? 0) >= $min) {
                    return $r;
                }
            }

            return $rows[0] ?? null;
        };
        $f = $fert ? $pick($fert, $crop) : null;
        $rateRow = null;
        foreach (['barangay', 'town', 'province'] as $lv) {
            foreach ($fert as $r) {
                if ($r['level'] === $lv && $r['crop'] === $crop && ! empty($r['rates'])) {
                    $rateRow = $r;
                    break 2;
                }
            }
        }
        $town = null;
        $prov = null;
        foreach ($fert as $r) {
            if ($r['crop'] === ($f['crop'] ?? $crop)) {
                if ($r['level'] === 'town') {
                    $town = $r;
                } elseif ($r['level'] === 'province') {
                    $prov = $r;
                }
            }
        }

        $out = [
            'place' => ['province' => $p, 'town' => $t, 'barangay' => $b, 'level' => $f['level'] ?? null],
            'crop' => $f['crop'] ?? $crop,
            'asked' => $crop,
            'samples' => (int) ($f['samples'] ?? 0),
            'year' => $f['year'] ?? null,
        ];
        $lv = (array) ($f['levels'] ?? []);
        foreach (['n' => 'N', 'p' => 'P', 'k' => 'K', 'zinc' => 'Zn'] as $k => $src) {
            $out[$k] = self::nutrient((array) ($lv[$src] ?? []));
        }
        $measured = (array) ($f['measured'] ?? []);
        $out['measured'] = $measured ?: null;
        $out['ph'] = self::ph((array) ($lv['pH'] ?? []), $measured['pH'] ?? null, $phRows->map($dec)->all(), $t, $f['crop'] ?? $crop);
        $tex = (array) ($f['texture'] ?? []);
        if (! array_sum($tex) && $town) {
            $tex = (array) ($town['texture'] ?? []);
        }
        if (! array_sum($tex) && $prov) {
            $tex = (array) ($prov['texture'] ?? []);
        }
        $out['texture'] = self::texture($tex);
        $out['general'] = self::nutrient((array) ($lv['gen'] ?? []));
        $out['salinity'] = self::salinity($salRows->map($dec)->all(), $t, $b);
        $out['rates'] = $rateRow ? (array) $rateRow['rates'] : [];
        $out['ratesLevel'] = $rateRow['level'] ?? null;

        // The NpkNeed and SoilConditions vocabulary the analyses take.
        $tests = [];
        foreach (['N' => 'n', 'P2O5' => 'p', 'K2O' => 'k'] as $el => $k) {
            if (! empty($out[$k]['class'])) {
                $tests[$el] = self::TEST_LEVEL[$out[$k]['class']] ?? null;
            }
        }
        $out['tests'] = array_filter($tests);
        $out['phMid'] = $out['ph']['mid'] ?? null;
        $conds = [];
        if ($out['phMid'] !== null && $out['phMid'] < 5.5) {
            $conds[] = 'acidic';
        } elseif ($out['phMid'] !== null && $out['phMid'] > 7.3) {
            $conds[] = 'alkaline';
        }
        if (in_array($out['salinity']['risk'] ?? null, ['moderate', 'severe'], true)) {
            $conds[] = 'saline';
        }
        if (($out['n']['class'] ?? null) === 'L' && ($out['n']['share'] ?? 0) >= 0.6) {
            $conds[] = 'low_om';
        }
        $out['conditions'] = $conds;

        return $out;
    }

    /** A class count onto ['class' => the typical class, 'level' => low|medium|high, 'share' => of samples in it, 'shares']. */
    private static function nutrient(array $counts): array
    {
        $counts = array_filter(array_map('intval', $counts));
        $n = array_sum($counts);
        if (! $n) {
            return [];
        }
        // The middle sample's class: robust where the counts are split.
        $order = self::ORDER;
        uksort($counts, fn ($a, $b) => ($order[$a] ?? 9) <=> ($order[$b] ?? 9));
        $acc = 0;
        $mid = null;
        foreach ($counts as $c => $v) {
            $acc += $v;
            if ($acc >= $n / 2) {
                $mid = $c;
                break;
            }
        }
        $shares = array_map(fn ($v) => round($v / $n, 2), $counts);

        return ['class' => $mid, 'level' => self::TEST_LEVEL[$mid] ?? null, 'word' => self::CLASS_WORDS[$mid] ?? null,
            'share' => $shares[$mid] ?? null, 'shares' => $shares, 'n' => $n];
    }

    private static function ph(array $counts, ?array $measured, array $mapRows, ?string $town, string $crop): array
    {
        $counts = array_filter(array_map('intval', $counts));
        $n = array_sum($counts);
        $out = ['class' => null, 'band' => null, 'range' => null, 'shares' => [], 'n' => $n, 'mid' => null, 'mapShares' => null, 'mapLevel' => null];
        if ($n) {
            arsort($counts);
            $top = array_key_first($counts);
            $out['class'] = $top;
            $out['band'] = self::PH_CLASS[$top]['band'] ?? null;
            $out['range'] = self::PH_CLASS[$top]['range'] ?? null;
            $out['shares'] = array_map(fn ($v) => round($v / $n, 2), $counts);
            $out['mid'] = self::PH_CLASS[$top]['mid'] ?? null;
        }
        if ($measured && isset($measured['median'])) {
            $out['measuredMedian'] = (float) $measured['median'];
            $out['mid'] = round((float) $measured['median'], 1);
        }
        // the rated land, from the map: the town when it was read, else the province
        $best = null;
        foreach ($mapRows as $r) {
            if ($r['crop'] !== $crop && $best) {
                continue;
            }
            if ($town && $r['level'] === 'town' && $r['town'] === $town) {
                $best = $r;
                if ($r['crop'] === $crop) {
                    break;
                }
            } elseif ($r['level'] === 'province' && (! $best || $best['level'] === 'province' && $best['crop'] !== $crop)) {
                $best = $r;
            }
        }
        if ($best) {
            $ha = (array) ($best['ha'] ?? []);
            $tot = array_sum($ha);
            $sh = $tot > 0 ? array_map(fn ($v) => round($v / $tot, 2), $ha) : (array) ($best['mapped'] ?? []);
            $out['mapShares'] = array_filter($sh, fn ($v) => $v > 0);
            $out['mapLevel'] = $best['level'];
            $out['mapHa'] = $tot > 0 ? (int) round($tot) : null;
            if (! empty($best['units'])) {
                $out['paddy'] = $best['units'];
            }
            if (! $n && $out['mapShares']) {
                $byKey = array_flip(array_map(fn ($c) => $c['key'], self::PH_CLASS));
                arsort($out['mapShares']);
                $topKey = array_key_first($out['mapShares']);
                $cls = $byKey[$topKey] ?? null;
                if ($cls) {
                    $out['class'] = $cls;
                    $out['band'] = self::PH_CLASS[$cls]['band'];
                    $out['range'] = self::PH_CLASS[$cls]['range'];
                    $out['mid'] = $out['mid'] ?? self::PH_CLASS[$cls]['mid'];
                }
            }
        }

        return $out;
    }

    private static function texture(array $counts): array
    {
        $counts = array_filter(array_map('intval', $counts));
        $n = array_sum($counts);
        if (! $n) {
            return ['typical' => null, 'shares' => [], 'n' => 0];
        }
        arsort($counts);
        $top = array_key_first($counts);

        return ['typical' => self::TEXTURE[$top] ?? null, 'class' => $top, 'word' => self::TEXTURE_WORDS[$top] ?? null,
            'shares' => array_map(fn ($v) => round($v / $n, 2), $counts), 'n' => $n];
    }

    private static function salinity(array $rows, ?string $town, ?string $brgy): ?array
    {
        $prov = null;
        $trow = null;
        $brow = null;
        $worst = null;
        foreach ($rows as $r) {
            if ($r['level'] === 'province') {
                $prov = $r;
            } elseif ($r['level'] === 'town' && $r['town'] === $town) {
                $trow = $r;
            } elseif ($r['level'] === 'barangay' && $r['town'] === $town) {
                if ($brgy && $r['barangay'] === $brgy) {
                    $brow = $r;
                }
                $ec = $r['ec']['0-30']['max'] ?? null;
                if ($ec !== null && (! $worst || $ec > $worst['ec'])) {
                    $worst = ['barangay' => $r['barangay'], 'ec' => (float) $ec];
                }
            }
        }
        if (! $trow && ! $brow) {
            // A surveyed coast: the town was not among its coastal towns.
            return $prov ? ['risk' => 'none', 'surveyed' => false, 'note' => 'not among the coastal towns checked for salt'] : null;
        }
        $byDepth = [];
        $risk = 'none';
        $rank = ['none' => 0, 'slight' => 1, 'moderate' => 2, 'severe' => 3];
        foreach ((array) ($trow['areas'] ?? []) as $d => $a) {
            $tot = array_sum((array) $a);
            if ($tot <= 0) {
                continue;
            }
            $sh = array_map(fn ($v) => round($v / $tot, 3), (array) $a);
            $byDepth[$d] = $sh + ['ha' => round($tot)];
        }
        if (isset($byDepth['0-30'])) {
            $s = $byDepth['0-30'];
            $bad = ($s['sev'] ?? 0) + ($s['vsev'] ?? 0);
            $mod = $bad + ($s['ms'] ?? 0);
            $any = $mod + ($s['ss'] ?? 0);
            $risk = $bad >= 0.1 ? 'severe' : (($mod >= 0.15 || $bad >= 0.03) ? 'moderate' : ($any >= 0.2 ? 'slight' : 'none'));
        }
        $here = null;
        if ($brow) {
            $ec = $brow['ec'] ?? [];
            $here = ['barangay' => $brow['barangay'], 'ec' => array_map(fn ($e) => $e['max'] ?? null, (array) $ec)];
            $cls = $ec['0-30']['class'] ?? null;
            $br = ['ns' => 'none', 'ss' => 'slight', 'ms' => 'moderate', 'sev' => 'severe', 'vsev' => 'severe'][$cls] ?? null;
            if ($br) {
                $risk = $br; // the barangay's own sample speaks for it
            }
        }

        return ['risk' => $risk, 'surveyed' => true, 'level' => $brow ? 'barangay' : 'town', 'byDepth' => $byDepth,
            'here' => $here, 'worst' => $worst, 'worstEc' => $worst['ec'] ?? null, 'year' => $trow['year'] ?? ($brow['year'] ?? null)];
    }

    /* ================================================================ use */

    /**
     * NpkNeed's input with the area's typical soil filling only what is
     * unknown: a nutrient level the soil test lacks, the texture when unsure,
     * the pH when none is known. `areaSoil` lists what was filled, so a page
     * or a prompt can say "typical for your area". The same rule as the Crop
     * Protocol's ProtocolFacts::fillNeed, so both reach the same need.
     */
    public static function fill(array $in, ?array $a): array
    {
        if (! $a) {
            return $in;
        }
        $filled = [];
        foreach ((array) ($a['tests'] ?? []) as $el => $level) {
            if (in_array($el, ['N', 'P2O5', 'K2O'], true) && in_array($level, ['low', 'medium', 'high'], true) && ! isset($in['tests'][$el])) {
                $in['tests'][$el] = $level;
                $filled[] = $el;
            }
        }
        $tx = $a['texture']['typical'] ?? null;
        if (($in['texture'] ?? 'unsure') === 'unsure' && in_array($tx, ['clay', 'loam', 'sandy'], true)) {
            $in['texture'] = $tx;
            $filled[] = 'texture';
        }
        if (($in['ph'] ?? null) === null && isset($a['phMid']) && is_numeric($a['phMid'])) {
            $in['ph'] = (float) $a['phMid'];
            $filled[] = 'ph';
        }
        if ($filled) {
            $in['areaSoil'] = $filled;
        }

        return $in;
    }

    /**
     * One compact line for a small prompt (the chat's season context, a
     * report): "soil typical for the area (23 samples, not a test of this
     * lot): pH mostly 5.6 to 6.8; nitrogen low, phosphorus low, potassium
     * high; loamy; salt risk moderate". '' for null.
     */
    public static function line(?array $a): string
    {
        if (! $a) {
            return '';
        }
        $bits = [];
        if (! empty($a['ph']['range'])) {
            $sh = $a['ph']['shares'][$a['ph']['class']] ?? null;
            $bits[] = 'pH mostly ' . $a['ph']['range'] . ($sh ? ' (' . round($sh * 100) . '%)' : '');
        }
        $lv = [];
        foreach (['n' => 'nitrogen', 'p' => 'phosphorus', 'k' => 'potassium', 'zinc' => 'zinc'] as $k => $w) {
            if (! empty($a[$k]['level'])) {
                $lv[] = $w . ' ' . $a[$k]['level'];
            }
        }
        if ($lv) {
            $bits[] = implode(', ', $lv);
        }
        if (! empty($a['texture']['typical'])) {
            $bits[] = ['clay' => 'clayey', 'loam' => 'loamy', 'sandy' => 'sandy'][$a['texture']['typical']] ?? $a['texture']['typical'];
        }
        $risk = $a['salinity']['risk'] ?? null;
        if ($risk && ! empty($a['salinity']['surveyed'])) {
            $bits[] = 'salt risk ' . $risk;
        }
        if (! $bits) {
            return '';
        }

        return 'soil typical for the area (' . ($a['samples'] ? $a['samples'] . ' samples, ' : '') . 'not a test of this lot): ' . implode('; ', $bits);
    }

    /** Up to five salt tolerant varieties of the crop on record (App\Support\VarietyRegistry), as words; '' when none. */
    public static function saltTolerant(?string $crop, int $limit = 5): string
    {
        if (! $crop || ! class_exists(VarietyRegistry::class) || ! method_exists(VarietyRegistry::class, 'tolerant')) {
            return '';
        }
        try {
            $rows = VarietyRegistry::tolerant($crop === 'corn' ? 'corn' : 'rice', 'salinity', $limit);
        } catch (\Throwable $e) {
            report($e);

            return '';
        }

        return implode(', ', array_map(fn ($r) => $r['name'] . ' (' . $r['rating'] . (! empty($r['days']) ? ', ' . (int) $r['days'] . ' days' : '') . ')', array_slice($rows, 0, $limit)));
    }

    /**
     * The need's reasons with a filled level said as the area's, never as a
     * test the farmer made: "Your soil test reads low in nitrogen" becomes
     * "Soil around here usually reads low in nitrogen" for the filled
     * elements only (the Crop Protocol and NPK Plus's page say the same).
     */
    public static function areaWhys(array $whys, array $filled): array
    {
        $names = array_intersect_key(['N' => 'nitrogen', 'P2O5' => 'phosphorus', 'K2O' => 'potassium'], array_flip($filled));
        if (! $names) {
            return $whys;
        }

        return array_map(fn ($w) => is_string($w) && preg_match('/^Your soil test reads\b.*\b(?:' . implode('|', $names) . ')\b/i', $w)
            ? (string) preg_replace('/^Your soil test reads/i', 'Soil around here usually reads', $w) : $w, $whys);
    }

    /** The salt risk in one word for a place ('none' when the coast survey did not include it, null when there is no survey). */
    public static function saltRisk(?array $a): ?string
    {
        return $a['salinity']['risk'] ?? null;
    }

    /**
     * Prompt lines for Anee. $use 'prompt' (full), 'short' (three lines for a
     * small prompt), or 'card' (plain sentences for a page). '' for null.
     */
    public static function words(?array $a, string $use = 'prompt'): string
    {
        if (! $a) {
            return '';
        }
        $pl = $a['place'];
        $where = trim(implode(', ', array_filter([$pl['barangay'] && $pl['level'] === 'barangay' ? $pl['barangay'] : null, $pl['town'], $pl['province']])));
        $lines = [];
        $lvWord = ['barangay' => 'the barangay', 'town' => 'the town', 'province' => 'the province'][$pl['level'] ?? 'province'] ?? 'the province';
        $cropWord = $a['crop'] === 'corn' ? 'corn' : 'rice';
        if ($a['samples']) {
            $lines[] = 'Read for ' . $where . ': ' . $a['samples'] . ' soil samples from ' . $cropWord . ' land in ' . $lvWord . ($a['year'] ? ' (' . $a['year'] . ')' : '') . '.'
                . (($a['asked'] ?? '') === 'other' ? ' The soil facts hold for other crops too.' : '');
        }
        $ph = $a['ph'] ?? [];
        if (! empty($ph['shares'])) {
            $parts = [];
            foreach ($ph['shares'] as $c => $s) {
                if ($s >= 0.1) {
                    $parts[] = round($s * 100) . '% at ' . (self::PH_CLASS[$c]['range'] ?? $c) . ' (' . (self::PH_CLASS[$c]['words'] ?? '') . ')';
                }
            }
            $lines[] = 'pH of the samples: ' . implode('; ', $parts) . '.' . (isset($ph['measuredMedian']) ? ' Median measured pH ' . $ph['measuredMedian'] . '.' : '');
        }
        if (! empty($ph['mapShares'])) {
            $names = array_flip(array_map(fn ($c) => $c['key'], self::PH_CLASS));
            $parts = [];
            foreach ($ph['mapShares'] as $k => $s) {
                if ($s >= 0.05) {
                    $parts[] = round($s * 100) . '% at ' . (self::PH_CLASS[$names[$k] ?? '']['range'] ?? $k);
                }
            }
            if ($parts) {
                $lines[] = 'Rated ' . $cropWord . ' land of ' . ($ph['mapLevel'] === 'town' ? 'the town' : 'the province') . ' by pH: ' . implode('; ', $parts) . (! empty($ph['mapHa']) ? ' (' . number_format($ph['mapHa']) . ' ha)' : '') . '.';
            }
        }
        $nut = [];
        foreach (['n' => 'Nitrogen (from organic matter)', 'p' => 'Phosphorus', 'k' => 'Potassium', 'zinc' => 'Zinc'] as $k => $label) {
            if (! empty($a[$k]['word'])) {
                $nut[] = $label . ' ' . $a[$k]['word'] . ($a[$k]['share'] ? ' (' . round($a[$k]['share'] * 100) . '% of samples)' : '');
            }
        }
        if ($nut) {
            $lines[] = 'Typical levels: ' . implode('; ', $nut) . '.';
        }
        if (! empty($a['measured'])) {
            $m = $a['measured'];
            $bits = [];
            foreach (['OM' => ['organic matter', '%'], 'P' => ['phosphorus', ' ppm'], 'K' => ['potassium', ' ppm'], 'Zn' => ['zinc', ' ppm']] as $k => [$w, $u]) {
                if (isset($m[$k]['median'])) {
                    $bits[] = $w . ' ' . $m[$k]['median'] . $u;
                }
            }
            if ($bits) {
                $lines[] = 'Median of the measured samples: ' . implode(', ', $bits) . '.';
            }
        }
        if (! empty($a['texture']['word'])) {
            $lines[] = 'Texture: mostly ' . $a['texture']['word'] . ' (' . round(($a['texture']['shares'][$a['texture']['class']] ?? 0) * 100) . '% of samples).';
        }
        $sal = $a['salinity'] ?? null;
        if ($sal && ! empty($sal['surveyed'])) {
            $d = $sal['byDepth']['0-30'] ?? null;
            $t = 'Salinity: ' . ['none' => 'little or no salt', 'slight' => 'a slight salt risk', 'moderate' => 'a moderate salt risk', 'severe' => 'a severe salt risk'][$sal['risk']];
            if ($d) {
                $bits = [];
                foreach (['ss', 'ms', 'sev', 'vsev'] as $c) {
                    if (($d[$c] ?? 0) >= 0.02) {
                        $bits[] = round($d[$c] * 100) . '% ' . self::SAL_CLASS[$c];
                    }
                }
                $t .= $bits ? ' (coastal land of the town in the top 30 cm: ' . implode(', ', $bits) . ')' : '';
                $deep = $sal['byDepth']['60-90'] ?? null;
                if ($deep && (($deep['ms'] ?? 0) + ($deep['sev'] ?? 0) + ($deep['vsev'] ?? 0)) > (($d['ms'] ?? 0) + ($d['sev'] ?? 0) + ($d['vsev'] ?? 0)) + 0.05) {
                    $t .= '; saltier deeper down, at 60 to 90 cm';
                }
            }
            if (! empty($sal['here']['ec']['0-30'])) {
                $t .= '; the barangay\'s own sample read EC ' . $sal['here']['ec']['0-30'] . ' dS/m in the top 30 cm';
            } elseif (! empty($sal['worst']) && $sal['worst']['ec'] > 4) {
                $t .= '; the saltiest sample in the town read EC ' . $sal['worst']['ec'] . ' dS/m (' . $sal['worst']['barangay'] . ')';
            }
            $lines[] = $t . '.';
            if ($use !== 'short' && in_array($sal['risk'], ['moderate', 'severe'], true) && ($names = self::saltTolerant($a['asked'] === 'other' ? null : $a['crop'])) !== '') {
                $lines[] = 'Salt tolerant ' . $cropWord . ' varieties on record: ' . $names . '.';
            }
        } elseif ($sal && empty($sal['surveyed'])) {
            $lines[] = 'Salinity: not one of the coastal towns found salty in this province.';
        }
        if ($use !== 'short' && ! empty($a['rates'])) {
            $vw = ['inbred' => 'inbred', 'hybrid' => 'hybrid', 'opv' => 'open pollinated', 'white' => 'white', 'yellow' => 'yellow'];
            $bits = [];
            foreach ($a['rates'] as $v => $bySeason) {
                $s = [];
                foreach (['wet', 'dry'] as $se) {
                    if (! empty($bySeason[$se])) {
                        $r = $bySeason[$se];
                        $s[] = $se . ' season ' . (int) $r['N'] . '-' . (int) $r['P2O5'] . '-' . (int) $r['K2O'];
                    }
                }
                if ($s) {
                    $bits[] = ($vw[$v] ?? $v) . ' ' . $cropWord . ': ' . implode(', ', $s);
                }
            }
            if ($bits) {
                $lines[] = 'The usual nutrient rates for this soil (kg N-P2O5-K2O per hectare, ' . ($a['ratesLevel'] === 'barangay' ? 'the barangay' : ($a['ratesLevel'] === 'town' ? 'the town' : 'the province')) . '\'s typical sample): ' . implode('; ', $bits) . '.';
            }
        }
        if (! $lines) {
            return '';
        }
        if ($use === 'card') {
            return implode("\n", $lines);
        }
        if ($use === 'short') {
            $lines = array_slice($lines, 0, 4);
        }

        return "THE AREA'S TYPICAL SOIL (typical for the area from soil samples taken there, NOT a test of this field; the farmer's own answers and any soil test of the lot come first; say \"typical for your area\" when you use it, and never name where these numbers came from):\n- "
            . implode("\n- ", $lines);
    }

    /* ================================================================ load */

    /**
     * Load the seed files into the three tables, replacing what is there, in
     * one transaction (a failed load leaves the old rows). Returns counts.
     */
    public static function sync(?string $dir = null): array
    {
        $dir = $dir ?: database_path('soil-atlas');
        $db = self::db();
        $out = [];
        $files = ['fertility' => 'fertility.json', 'ph' => 'ph.json', 'salinity' => 'salinity.json'];
        $data = [];
        foreach ($files as $k => $f) {
            $path = $dir . DIRECTORY_SEPARATOR . $f;
            $data[$k] = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        }
        $now = now();
        $db->transaction(function () use ($db, $data, $now, &$out) {
            foreach (self::TABLES as $k => $table) {
                if (! self::schema()->hasTable($table)) {
                    continue;
                }
                $db->table($table)->delete();
                $rows = [];
                foreach ($data[$k] as $r) {
                    $rows[] = self::rowFor($k, $r, $now);
                }
                foreach (array_chunk($rows, 400) as $chunk) {
                    $db->table($table)->insert($chunk);
                }
                $out[$table] = count($rows);
            }
        });
        Cache::forget('soil-atlas:' . self::conn() . ':ready');

        return $out;
    }

    private static function rowFor(string $kind, array $r, $now): array
    {
        $base = ['level' => (string) $r['level'], 'province' => mb_substr((string) $r['province'], 0, 120),
            'created_at' => $now, 'updated_at' => $now, 'year' => $r['year'] ?? null];
        if ($kind === 'fertility') {
            return $base + ['town' => $r['town'] ?? null, 'barangay' => $r['barangay'] ?? null, 'crop' => (string) ($r['crop'] ?? 'rice'),
                'samples' => (int) ($r['samples'] ?? 0), 'guideSamples' => (int) ($r['guideSamples'] ?? 0),
                'data' => json_encode(['levels' => $r['levels'] ?? [], 'measured' => $r['measured'] ?? [], 'texture' => $r['texture'] ?? [],
                    'rates' => $r['rates'] ?? [], 'guideSamples' => (int) ($r['guideSamples'] ?? 0)], JSON_UNESCAPED_UNICODE)];
        }
        if ($kind === 'ph') {
            return $base + ['town' => $r['town'] ?? null, 'crop' => (string) ($r['crop'] ?? 'rice'), 'totalHa' => $r['totalHa'] ?? null,
                'data' => json_encode(['ha' => $r['ha'] ?? null, 'units' => $r['units'] ?? null, 'mapped' => $r['mapped'] ?? null], JSON_UNESCAPED_UNICODE)];
        }

        return $base + ['town' => $r['town'] ?? null, 'barangay' => $r['barangay'] ?? null, 'samples' => (int) ($r['samples'] ?? 0),
            'data' => json_encode(['areas' => $r['areas'] ?? null, 'ec' => $r['ec'] ?? null], JSON_UNESCAPED_UNICODE)];
    }
}
