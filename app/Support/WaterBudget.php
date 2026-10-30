<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * A lot's watering, counted from everything known about it (2026-10-26, the
 * owner: "the crop protocol analysis also should include the recommended
 * irrigation schedule as well or watering schedule and base it as well in
 * all factors").
 *
 * How to Plant gives the stages and what farmers do in each; this puts the
 * lot's own numbers on every stage:
 *  - the weather: the same days in each of the last twenty years from the
 *    climate shelf (the rain, and ET0, the water the air pulls), tilted by
 *    the ENSO outlook the report read. A usual year is the middle of the
 *    twenty, a dry year the driest one in five;
 *  - the crop: what it drinks at each stage (its crop factor rising from
 *    planting to its peak and falling toward harvest), how deep its roots
 *    reach as it grows, how far its soil may dry before it suffers;
 *  - the soil: the water its texture holds a meter deep, less where it is
 *    shallow or a hard layer stops the roots, more with much organic matter;
 *    for a paddy, what sinks through it a day (slower on heavy, slow
 *    draining soil or over a plow pan) and the soaking a dry field takes;
 *  - the field: shade, a steep slope (smaller, more frequent waterings) and
 *    salt (a little more each time to wash it down);
 *  - the water: rain only (what the rain leaves short instead), a pump and
 *    the months it runs low, a canal or a pond, and the way it is applied
 *    (furrow, hose, sprinkler or drip: how much of it reaches the roots);
 *  - the lot: its area and its plants, so a watering is said in liters a
 *    square meter, cubic meters for the lot and liters a plant.
 *
 * The count is a soil water bucket run day by day through each of the twenty
 * years: rain fills it, the crop empties it, and a watering refills it when
 * the soil has dried as far as the crop allows. In a paddy the bucket is the
 * standing water: it falls by what the crop drinks and what sinks, and is
 * topped up at its low mark. No paid ask: it is worked out when a report is
 * read, so every saved report has it.
 */
final class WaterBudget
{
    /** The water a meter of soil holds for the roots, mm, by texture. */
    public const TAW = ['sandy' => 70, 'loam' => 150, 'silt' => 170, 'clay' => 180, 'heavy_clay' => 190];

    /** What sinks through a puddled paddy a day, mm, by texture. */
    public const PERC = ['sandy' => 8, 'loam' => 4, 'silt' => 3, 'clay' => 1.5, 'heavy_clay' => 1];

    /** The soaking a dry paddy takes before it can be worked, mm. */
    public const SOAK = ['sandy' => 300, 'loam' => 200, 'silt' => 180, 'clay' => 150, 'heavy_clay' => 130];

    /** The roots, m: [at planting, deepest]. */
    public const ROOT = [
        'default' => [0.15, 0.6], 'rice' => [0.1, 0.3], 'rice_dsr_wet' => [0.1, 0.3], 'rice_dsr_dry' => [0.1, 0.4],
        'corn_yellow' => [0.15, 1.0], 'corn_sweet' => [0.15, 0.9], 'sorghum' => [0.15, 1.0],
        'mungbean' => [0.15, 0.6], 'peanut' => [0.15, 0.6], 'soybean' => [0.15, 0.7], 'stringbean' => [0.15, 0.6],
        'sweetpotato' => [0.15, 0.8], 'cassava' => [0.2, 0.9], 'taro' => [0.15, 0.4], 'potato' => [0.15, 0.5], 'carrot' => [0.1, 0.6], 'ginger' => [0.1, 0.4],
        'pechay' => [0.1, 0.35], 'cabbage' => [0.15, 0.5], 'lettuce' => [0.1, 0.35], 'broccoli' => [0.15, 0.5],
        'tomato' => [0.15, 0.7], 'eggplant' => [0.15, 0.7], 'chili' => [0.15, 0.6], 'bellpepper' => [0.15, 0.6], 'okra' => [0.15, 0.7],
        'ampalaya' => [0.15, 0.7], 'squash' => [0.15, 0.8], 'cucumber' => [0.15, 0.7], 'watermelon' => [0.15, 0.9],
        'onion' => [0.1, 0.35], 'garlic' => [0.1, 0.35], 'sugarcane' => [0.3, 1.2], 'pineapple' => [0.2, 0.5],
        'banana' => [0.3, 0.7], 'papaya' => [0.3, 0.8], 'mango' => [1.0, 1.5], 'coconut' => [1.0, 1.5], 'calamansi' => [0.5, 1.0], 'coffee' => [0.5, 1.0],
    ];

    /** How far the soil may dry before the crop suffers, a share of what it holds. */
    public const P = [
        'default' => 0.45, 'rice' => 0.2, 'rice_dsr_wet' => 0.2, 'rice_dsr_dry' => 0.3,
        'pechay' => 0.3, 'lettuce' => 0.3, 'cabbage' => 0.4, 'broccoli' => 0.4, 'onion' => 0.3, 'garlic' => 0.3, 'potato' => 0.35, 'carrot' => 0.35, 'ginger' => 0.35, 'taro' => 0.3,
        'tomato' => 0.4, 'eggplant' => 0.45, 'chili' => 0.3, 'bellpepper' => 0.3, 'cucumber' => 0.5, 'squash' => 0.45, 'ampalaya' => 0.45, 'watermelon' => 0.4, 'okra' => 0.45,
        'corn_yellow' => 0.55, 'corn_sweet' => 0.5, 'sorghum' => 0.55, 'mungbean' => 0.45, 'peanut' => 0.5, 'soybean' => 0.5, 'stringbean' => 0.45,
        'sweetpotato' => 0.65, 'cassava' => 0.6, 'sugarcane' => 0.65, 'pineapple' => 0.5, 'banana' => 0.35, 'papaya' => 0.4,
    ];

    /** The share of the rain that reaches the roots. */
    public const RAIN_EFF = 0.8;

    /** ET0 a day when the place has no kept history, mm, by month (lowland Philippines). */
    public const ET0_MONTH = [1 => 3.6, 4.2, 5.0, 5.4, 5.0, 4.2, 3.8, 3.7, 3.7, 3.7, 3.4, 3.3];

    /** The water that reaches a paddy of what leaves the pump or the canal gate. */
    public const PADDY_EFF = 0.85;

    /** Water the soil gives up as a paddy's water drops 15 cm below the surface, mm (safe wetting and drying). */
    public const AWD_LOW = 25;

    /** The share of the roots' depth a watering refills: most of what a crop drinks comes from there. */
    private const MANAGED = 0.7;

    /** In a stage that must not go dry the soil is let dry only this share as far. */
    private const CRITICAL_P = 0.6;

    /** A small pump, liters an hour, for saying a watering in pump hours. */
    private const PUMP_LPH = 20000;


    /**
     * The lot's watering, counted.
     *
     * @param  array  $L  the report's lot: crop, method, harvestDays, areaHa, place
     * @param  array  $rows  ProtocolSchedules' watering rows
     * @param  array  $f  facts: day0, profile (the field answers), soil (the lot's soil word), water, pump [year, stops],
     *                    tilt (the ENSO rain tilt), perHa (plants a hectare), method (how the crop is watered, in words), critical (row keys)
     * @return array{factors: list<string>, rows: array<string, array>, season: ?array, climate: bool}|null
     */
    public static function lot(array $L, array $rows, array $f): ?array
    {
        $day0 = (string) ($f['day0'] ?? '');
        if ($day0 === '' || ! $rows) {
            return null;
        }
        $crop = (string) ($L['crop'] ?? '');
        $key = NpkNeed::cropKey($crop, (string) ($L['method'] ?? '')) ?? (CropStages::normalize($crop) ?: 'default');
        $K = array_merge(NpkModel::CLIMATE['default'], NpkModel::CLIMATE[$key] ?? []);
        $paddy = isset($K['extraMm']);
        $perennial = CropStages::isPerennial($crop);
        $H = max(1, (int) ($L['harvestDays'] ?? 0) ?: (int) max(array_column($rows, 'to')));
        $area = max(0.0, (float) ($L['areaHa'] ?? 0));
        $pa = (array) ($f['profile'] ?? []);

        // The soil.
        $tex = (string) ($pa['texture'] ?? '');
        if (! isset(self::TAW[$tex])) {
            $tex = ['clay' => 'clay', 'loam' => 'loam', 'sandy' => 'sandy', 'silty' => 'silt', 'silt' => 'silt'][(string) ($f['soil'] ?? '')] ?? '';
        }
        $texKnown = $tex !== '';
        $tex = $texKnown ? $tex : 'loam';
        $taw = self::TAW[$tex] * (($pa['organic'] ?? '') === 'high' ? 1.1 : (($pa['organic'] ?? '') === 'low' ? 0.95 : 1));
        $cap = ['shallow' => 0.25, 'medium' => 0.5][$pa['depth'] ?? ''] ?? 9.0;
        if (($pa['hardpan'] ?? '') === 'yes' && ! $paddy) {
            $cap = min($cap, 0.4);
        }
        [$r0, $r1] = self::ROOT[$key] ?? self::ROOT['default'];
        $p0 = (float) (self::P[$key] ?? self::P['default']);
        $drain = (string) ($pa['drainage'] ?? '');
        $perc = self::PERC[$tex] * (['fast' => 1.5, 'day' => 1.0, 'slow' => 0.6, 'stands' => 0.4][$drain] ?? 1.0) * (($pa['hardpan'] ?? '') === 'yes' ? 0.7 : 1.0);

        // The field and the water.
        $sun = (string) ($pa['sun'] ?? '');
        $etF = ['part' => 0.85, 'shade' => 0.7][$sun] ?? 1.0;
        $tilt = (float) ($f['tilt'] ?? 1) ?: 1.0;
        $enF = $tilt < 1 ? 1 + (1 - $tilt) * 0.15 : 1.0;
        $salty = (bool) array_intersect((array) ($pa['salt'] ?? []), ['crust', 'sea', 'tips', 'sodic']);
        $leach = $salty ? 1.15 : 1.0;
        $steep = ($pa['slope'] ?? '') === 'steep';
        $water = (string) ($f['water'] ?? 'irrigated');
        $rainfed = in_array($water, ['rainfed', 'rain'], true);
        $how = self::applied((string) ($f['method'] ?? ''), $area, $paddy);
        $capNet = (float) $how[2];
        $crit = array_flip((array) ($f['critical'] ?? []));
        $eff = $paddy ? self::PADDY_EFF : $how[1];
        $pump = (array) ($f['pump'] ?? []);
        $pumpLow = in_array($pump['year'] ?? '', ['everyYear', 'badYears'], true)
            ? (array_values(array_filter(array_map('intval', (array) ($pump['stops'] ?? [])), fn ($m) => $m >= 1 && $m <= 12)) ?: NpkModel::FIELD['pumpDryMonths']) : [];
        $pumpWhen = ($pump['year'] ?? '') === 'badYears' ? ($tilt < 0.9 ? 'this year' : 'dry') : 'every';

        // The rows the count runs through: the seedbed is its own small plot; a stop row is never watered.
        $order = [];
        foreach ($rows as $r) {
            if (empty($r['nursery'])) {
                $order[] = $r;
            }
        }
        usort($order, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);
        if (! $order) {
            return null;
        }
        $kind = [];
        foreach ($order as $r) {
            $stop = (bool) preg_match('/^\s*(stop|no water)/i', (string) ($r['usualOften'] ?? $r['often'] ?? '')) || (bool) preg_match('/^\s*no water\b/i', (string) ($r['usualAmount'] ?? $r['amount'] ?? ''));
            $kind[$r['key']] = match (true) {
                ($r['task'] ?? '') === 'drain' => 'drain',
                $stop => 'stop',
                $paddy && ! empty($r['prep']) => 'soak',
                ! empty($r['prep']) => 'skip',
                $paddy && ($r['task'] ?? '') === 'maintain' => 'pond',
                $paddy && (bool) preg_match('/tube|15 cm|surface dries|wetting and drying|dry spells? let|before flooding again|flood(?:ing)? again/i', (string) ($r['often'] ?? '') . ' ' . (string) ($r['amount'] ?? '')) => 'awd',
                $paddy && $key !== 'rice_dsr_dry' => 'sat',
                default => 'soil',
            };
        }
        $tStart = (int) min(array_column($order, 'from'));
        $tEnd = (int) max(array_column($order, 'to'));
        $rowAt = function (int $t) use ($order): array {
            $hit = $order[0];
            foreach ($order as $r) {
                if ($r['from'] <= $t) {
                    $hit = $r;
                }
            }

            return $hit;
        };
        $d0 = Carbon::parse($day0)->startOfDay();
        $days = [];
        for ($t = $tStart; $t <= $tEnd; $t++) {
            $d = $d0->copy()->addDays($t);
            $r = $rowAt($t);
            $frac = max(0.0, $t) / $H;
            $days[] = ['t' => $t, 'w' => intdiv($d->dayOfYear - 1, 7) + 1, 'yo' => $d->year - $d0->year, 'm' => (int) $d->month, 'key' => $r['key'],
                'kc' => $t < 0 ? 1.0 : self::kc($frac, (float) $K['kc'], $paddy, $perennial),
                'root' => min($cap, $perennial ? $r1 : $r0 + ($r1 - $r0) * min(1.0, max(0, $t) / (0.5 * $H)))];
        }
        // The two weeks before: how dry the soil is at planting (a dry spell then asks for a watering on the day).
        $pre = [];
        for ($t = $tStart - 14; $t < $tStart; $t++) {
            $d = $d0->copy()->addDays($t);
            $pre[] = ['w' => intdiv($d->dayOfYear - 1, 7) + 1, 'yo' => $d->year - $d0->year, 'm' => (int) $d->month];
        }

        // The weather: each of the twenty years, or a usual month with no rain where the place has no history kept.
        $loc = trim((string) ($L['place'] ?? '')) !== '' ? ClimateStore::locate((string) $L['place'], null, null, false) : null;
        $T = $loc ? ClimateStore::weekTable((float) $loc['lat'], (float) $loc['lng']) : null;
        $years = [];
        if ($T) {
            $maxYo = max(array_column($days, 'yo'));
            foreach (array_keys($T) as $y) {
                $ok = 0;
                foreach ($days as $dd) {
                    $ok += isset($T[$y + $dd['yo']][$dd['w']]) ? 1 : 0;
                }
                if ($ok >= count($days) * 0.9 && isset($T[$y + $maxYo])) {
                    $years[] = $y;
                }
            }
        }
        $climate = count($years) >= 5;

        // One season run through a year's weather.
        $targets = [];
        foreach ($order as $r) {
            $targets[$r['key']] = self::pondTarget((string) ($r['usualAmount'] ?? $r['amount'] ?? ''));
        }
        $run = function (?int $y) use ($days, $pre, $T, $tilt, $etF, $enF, $kind, $targets, $taw, $p0, $perc, $tex, $capNet, $crit): array {
            $out = [];
            // The soil at planting: two weeks of bare soil drying against the rain that fell on it.
            $D = 0.0;
            $preRain = 0.0;
            foreach ($pre as $dd) {
                $wk = $y !== null ? ($T[$y + $dd['yo']][$dd['w']] ?? null) : null;
                $rain = $wk && $wk['days'] ? $wk['rain'] / $wk['days'] * $tilt : 0.0;
                $et0 = $wk && $wk['days'] ? $wk['et0'] / $wk['days'] : self::ET0_MONTH[$dd['m']];
                $D = max(0.0, $D + 0.5 * $et0 * $etF - $rain * self::RAIN_EFF);
                $preRain += $rain * self::RAIN_EFF;
            }
            $D = min($D, $taw * $days[0]['root'] * self::MANAGED);
            $W = null;
            $prev = null;
            foreach ($days as $dd) {
                $k = $dd['key'];
                $out[$k] ??= ['etc' => 0.0, 'rain' => 0.0, 'net' => 0.0, 'count' => 0, 'hot' => 0, 'n' => 0, 'perc' => 0.0, 'soak' => 0.0];
                if ($y !== null) {
                    $wk = $T[$y + $dd['yo']][$dd['w']] ?? null;
                    $rain = $wk && $wk['days'] ? $wk['rain'] / $wk['days'] * $tilt : 0.0;
                    $et0 = $wk && $wk['days'] ? $wk['et0'] / $wk['days'] : self::ET0_MONTH[$dd['m']];
                    $out[$k]['hot'] += $wk && $wk['days'] ? $wk['hot35'] / $wk['days'] : 0;
                } else {
                    $rain = 0.0;
                    $et0 = self::ET0_MONTH[$dd['m']];
                }
                $etc = $et0 * $etF * $enF * $dd['kc'];
                $out[$k]['etc'] += $etc;
                $out[$k]['rain'] += $rain;
                $out[$k]['n']++;
                $kd = $kind[$k];
                // On the rain only nothing is given: what it would take is counted all the same, as what the rain leaves short.
                if (in_array($kd, ['pond', 'awd', 'soak', 'sat'], true)) {
                    $target = $kd === 'sat' ? 10.0 : $targets[$k];
                    if ($kd === 'soak' && $prev !== $k) {
                        // A dry field takes its soaking once, less what the rain of the two weeks before put in it,
                        // then is kept wet while it is worked.
                        $soak = max(0.0, self::SOAK[$tex] - $preRain);
                        $out[$k]['soak'] += $soak;
                        $out[$k]['net'] += $soak;
                        $W = $target;
                    }
                    $W ??= $target;
                    $W = min(max(100.0, $target + 50.0), $W + $rain - $etc - $perc);
                    $out[$k]['perc'] += $perc;
                    $low = $kd === 'awd' ? -self::AWD_LOW : ($kd === 'sat' ? -5.0 : ($target >= 30 ? 10.0 : 5.0));
                    if ($W < $low) {
                        $out[$k]['net'] += $target - $W;
                        $out[$k]['count']++;
                        $W = $target;
                    }
                    $W = max($W, -60.0);
                } elseif ($kd === 'drain') {
                    $W = null;
                    $D = 0.0;
                } elseif ($kd === 'soil' || $kd === 'stop') {
                    $W = null;
                    $tawR = $taw * $dd['root'] * self::MANAGED;
                    $pAdj = min(0.8, max(0.1, $p0 + 0.04 * (5 - $etc))) * (isset($crit[$k]) ? self::CRITICAL_P : 1.0);
                    $D = max(0.0, $D + $etc - $rain * self::RAIN_EFF);
                    if ($kd === 'soil' && $D >= min($pAdj * $tawR, $capNet)) {
                        // Rain only: what the rain leaves short is counted the same way, as the waterings it would take.
                        $out[$k]['net'] += $D;
                        $out[$k]['count']++;
                        $D = 0.0;
                    }
                    $D = min($D, $tawR);
                }
                $prev = $k;
            }

            return $out;
        };
        $runs = $climate ? array_map($run, $years) : [$run(null)];

        // Each row: the middle year and the driest one in five.
        $plans = [];
        $season = ['etc' => [], 'rain' => [], 'net' => [], 'gross' => [], 'count' => []];
        $counted = array_keys(array_filter($kind, fn ($v) => $v !== 'skip'));
        foreach ($runs as $i => $res) {
            $season['etc'][$i] = array_sum(array_map(fn ($k) => $res[$k]['etc'] ?? 0, $counted));
            $season['rain'][$i] = array_sum(array_map(fn ($k) => $res[$k]['rain'] ?? 0, $counted));
            $season['net'][$i] = array_sum(array_map(fn ($k) => $res[$k]['net'] ?? 0, $counted));
            $season['gross'][$i] = array_sum(array_map(fn ($k) => ($res[$k]['net'] ?? 0) / $eff * ($kind[$k] === 'soil' ? $leach : 1.0), $counted));
            $season['count'][$i] = array_sum(array_map(fn ($k) => $res[$k]['count'] ?? 0, $counted));
        }
        foreach ($order as $r) {
            $k = $r['key'];
            $kd = $kind[$k];
            if ($kd === 'skip') {
                continue;
            }
            $col = fn (string $f) => array_map(fn ($res) => (float) ($res[$k][$f] ?? 0), $runs);
            $n = max(1, (int) ($runs[0][$k]['n'] ?? 1));
            $etc = self::mid($col('etc'));
            $etcDay = $etc / $n;
            $rootMid = self::mid(array_map(fn ($dd) => $dd['root'], array_filter($days, fn ($dd) => $dd['key'] === $k))) ?: $r1;
            $tawR = $taw * $rootMid * self::MANAGED;
            $pAdj = min(0.8, max(0.1, $p0 + 0.04 * (5 - $etcDay))) * (isset($crit[$k]) ? self::CRITICAL_P : 1.0);
            $target = $kd === 'sat' ? 10.0 : $targets[$k];
            [$depth, $every] = match ($kd) {
                'sat' => [15.0, 15.0 / max(0.5, $etcDay + $perc)],
                'pond', 'soak' => [$target - ($target >= 30 ? 10.0 : 5.0), ($target - ($target >= 30 ? 10.0 : 5.0)) / max(0.5, $etcDay + $perc)],
                'awd' => [$target + self::AWD_LOW, ($target + self::AWD_LOW) / max(0.5, $etcDay + $perc)],
                default => [min($pAdj * $tawR, $capNet), min($pAdj * $tawR, $capNet) / max(0.5, $etcDay)],
            };
            $every = (int) max(1, min(21, round($every)));
            $gross = $depth / $eff * ($kd === 'soil' ? $leach : 1.0);
            $months = array_values(array_unique(array_map(fn ($dd) => $dd['m'], array_filter($days, fn ($dd) => $dd['key'] === $k))));
            $plans[$k] = [
                'kind' => $kd, 'days' => $n,
                'etc' => round($etc), 'rain' => $climate ? round(self::mid($col('rain'))) : null, 'rainDry' => $climate ? round(self::pct($col('rain'), 0.2)) : null,
                'perc' => in_array($kd, ['pond', 'awd', 'soak', 'sat'], true) ? round($perc * $n) : null,
                'soak' => $kd === 'soak' ? round(self::mid($col('soak'))) : null, 'soakDry' => $kd === 'soak' ? round(self::pct($col('soak'), 0.8)) : null,
                'net' => round(self::mid($col('net'))), 'netDry' => round(self::pct($col('net'), 0.8)),
                'count' => $climate ? (int) round(self::mid($col('count'))) : null, 'countDry' => $climate ? (int) round(self::pct($col('count'), 0.8)) : null,
                'every' => $every, 'depth' => round($depth), 'gross' => round($gross),
                'm3' => $area > 0 ? round($gross * 10 * $area, $gross * 10 * $area < 10 ? 1 : 0) : null,
                'perPlant' => ! $paddy && ($f['perHa'] ?? 0) > 0 ? round($gross * 10000 / (float) $f['perHa'], 1) : null,
                'hot' => $climate ? (int) round(self::mid($col('hot'))) : 0,
                'months' => $months, 'target' => $target, 'rootCm' => (int) round($rootMid * self::MANAGED * 100),
            ];
        }
        if (! $plans) {
            return null;
        }

        // In words.
        $ctx = ['rainfed' => $rainfed, 'steep' => $steep, 'salty' => $salty, 'area' => $area, 'climate' => $climate, 'paddy' => $paddy, 'how' => $how,
            'pumpLow' => $pumpLow, 'pumpWhen' => $pumpWhen, 'drain' => $drain, 'floods' => ($pa['flooding'] ?? '') === 'often' || in_array('river', (array) ($pa['hazards'] ?? []), true),
            'critical' => (array) ($f['critical'] ?? []), 'water' => $water, 'pond' => in_array('reservoir', (array) ($pa['water'] ?? []), true),
            'firstSoil' => array_key_first(array_filter($plans, fn ($x) => $x['kind'] === 'soil'))];
        $out = [];
        foreach ($order as $r) {
            if (isset($plans[$r['key']])) {
                $out[$r['key']] = $plans[$r['key']] + self::say($r, $plans[$r['key']], $ctx);
            }
        }
        $s = $climate ? [
            'etc' => round(self::mid($season['etc'])), 'rain' => round(self::mid($season['rain'])), 'rainDry' => round(self::pct($season['rain'], 0.2)),
            'net' => round(self::mid($season['net'])), 'netDry' => round(self::pct($season['net'], 0.8)),
            'count' => (int) round(self::mid($season['count'])), 'countDry' => (int) round(self::pct($season['count'], 0.8)),
            'years' => count($years),
        ] : null;
        if ($s) {
            $s['m3'] = $area > 0 ? round(self::mid($season['gross']) * 10 * $area) : null;
            $s['m3Dry'] = $area > 0 ? round(self::pct($season['gross'], 0.8) * 10 * $area) : null;
            $s['words'] = self::seasonWords($s, $ctx, $out, $order);
        }

        return ['factors' => self::factors($ctx + ['tex' => $tex, 'texKnown' => $texKnown, 'taw' => $taw, 'cap' => $cap, 'pa' => $pa, 'tilt' => $tilt, 'perc' => $perc,
            'perHa' => (float) ($f['perHa'] ?? 0), 'root' => $r1, 'years' => count($years), 'etF' => $etF, 'pump' => $pump]),
            'rows' => $out, 'season' => $s, 'climate' => $climate];
    }

    /** The crop factor at a share of the season: rising from planting to the peak, then falling toward harvest. */
    public static function kc(float $frac, float $avg, bool $paddy, bool $perennial): float
    {
        if ($perennial) {
            return $avg;
        }
        [$a, $b] = $paddy ? [0.875, 0.75] : [0.4, 0.7];
        $mid = $avg / (0.275 * $a + 0.625 + 0.1 * $b);
        $ini = $mid * $a;
        $end = $mid * $b;

        return match (true) {
            $frac < 0.15 => $ini,
            $frac < 0.40 => $ini + ($mid - $ini) * ($frac - 0.15) / 0.25,
            $frac < 0.80 => $mid,
            default => $mid + ($end - $mid) * min(1.0, ($frac - 0.80) / 0.20),
        };
    }

    /** How the water is put on: [words, the share that reaches the roots, the most one watering of it puts in the soil, mm]. */
    private static function applied(string $method, float $area, bool $paddy): array
    {
        $m = mb_strtolower($method);
        if ($paddy) {
            return ['Flooding the paddy', self::PADDY_EFF, 999.0];
        }
        $hand = (bool) preg_match('/\b(hose|watering can|can\b|by hand|pail)/', $m);

        return match (true) {
            $hand && $area > 0 && $area < 0.2 => ['A hose or a watering can', 0.8, 20.0],
            str_contains($m, 'drip') => ['Drip lines', 0.9, 12.0],
            str_contains($m, 'furrow') => ['Furrow watering', 0.65, 60.0],
            str_contains($m, 'sprinkl') => ['Sprinklers', 0.75, 35.0],
            $hand => ['A hose or a watering can', 0.8, 20.0],
            (bool) preg_match('/flood|basin/', $m) => ['Flooding the field', 0.6, 70.0],
            default => ['Furrow watering', 0.7, 60.0],
        };
    }

    /** The standing water a paddy stage keeps, mm, from its words ("about 3 to 5 cm", "less than 3 cm"). */
    private static function pondTarget(string $amount): float
    {
        $a = mb_strtolower($amount);
        if (preg_match('/less than (\d+(?:\.\d+)?)\s*cm/', $a, $m)) {
            return (float) $m[1] * 10;
        }
        if (preg_match_all('/(\d+(?:\.\d+)?)\s*(?:to\s*(\d+(?:\.\d+)?)\s*)?cm/', $a, $mm, PREG_SET_ORDER)) {
            $top = 0.0;
            foreach ($mm as $x) {
                $top = max($top, (float) ($x[2] ?? '' ?: $x[1]));
            }

            return min(100.0, max(20.0, $top * 10));
        }

        return str_contains($a, 'thin') ? 20.0 : 50.0;
    }

    /** One stage in words: how often, how much, the water count, and what to watch. */
    private static function say(array $r, array $p, array $c): array
    {
        $kd = $p['kind'];
        $lot = $c['area'] > 0 ? self::m3($p['m3']) . ' for this ' . self::ha($c['area']) . ' lot' : '';
        $plant = $p['perPlant'] !== null ? 'about ' . self::liters($p['perPlant']) . ' a plant' : '';
        $hours = $c['area'] > 0 && $p['m3'] !== null && $p['m3'] >= 5 ? self::hours($p['m3']) : '';
        $times = fn (?int $n) => $n === null ? '' : ($n === 1 ? 'once' : ($n === 2 ? 'twice' : $n . ' times'));
        $often = $amount = '';
        $warn = [];

        if ($kd === 'drain' || $kd === 'stop') {
            $often = '';
            $amount = '';
        } elseif ($c['rainfed']) {
            $often = $p['count'] === null ? 'No water to give: the crop lives on the rain.'
                : ($p['net'] <= 0 && $p['netDry'] <= 0 ? 'No water to give, and the rain usually covers this stage, even in a dry year.'
                    : 'No water to give: the crop lives on the rain, which ' . ($p['net'] > 0 ? 'usually leaves it about ' . (int) $p['net'] . ' mm short here' : 'usually covers it') . ($p['netDry'] > $p['net'] ? ' (' . (int) $p['netDry'] . ' mm short in a dry year)' : '') . '.');
            if ($p['net'] > 0 || $p['netDry'] > 0) {
                $warn[] = in_array($r['key'], $c['critical'], true) || ! empty($r['critical'])
                    ? 'This stage must not go dry and the rain leaves it short. Keep water by for it in drums or a small pond, mulch the rows, and pull the weeds that drink it.'
                    : 'Mulch between the rows and keep the weeds down so the rain lasts longer.';
            }
        } elseif ($kd === 'soil') {
            $step = $c['steep'] ? max(1, (int) round($p['every'] / 2)) : $p['every'];
            $base = 'Every ' . $step . ' ' . ($step === 1 ? 'day' : 'days') . ' when no good rain comes';
            $often = match (true) {
                $p['count'] === null => $base . '.',
                $p['count'] === 0 && $p['countDry'] === 0 => 'The rain usually covers this stage, even in a dry year. Water only if ' . $step . ' days pass with no good rain.',
                $p['count'] === 0 => 'The rain usually covers this stage. In a dry year water about ' . $times($p['countDry'] * ($c['steep'] ? 2 : 1)) . ', every ' . $step . ' days without rain.',
                default => $base . ': about ' . $times($p['count'] * ($c['steep'] ? 2 : 1)) . ' in a usual year' . ($p['countDry'] > $p['count'] ? ', ' . $times($p['countDry'] * ($c['steep'] ? 2 : 1)) . ' in a dry year' : '') . '.',
            };
            $g = $c['steep'] ? $p['gross'] / 2 : $p['gross'];
            $amount = 'About ' . (int) round($g) . ' liters a square meter each time'
                . ($lot !== '' ? ', ' . self::m3($c['steep'] ? $p['m3'] / 2 : $p['m3']) . ' for this ' . self::ha($c['area']) . ' lot' : '')
                . ($plant !== '' ? ', ' . ($c['steep'] ? 'about ' . self::liters($p['perPlant'] / 2) . ' a plant' : $plant) : '')
                . ($hours !== '' && ! $c['steep'] ? ' (' . $hours . ')' : '') . '. That wets the soil about ' . $p['rootCm'] . ' cm deep, where the roots are now.';
            if ($c['steep']) {
                $amount .= ' On this steep field, half as much twice as often, so it soaks in instead of running off.';
            }
            if ($c['salty'] && $r['key'] === ($c['firstSoil'] ?? '')) {
                $amount .= ' On salty soil each watering is a little more than the crop needs, to wash the salt down.';
            }
        } else {
            $gross = (int) round($p['gross']);
            if ($kd === 'soak') {
                $soak = (float) $p['soak'];
                $soakM3 = $c['area'] > 0 ? self::m3(round($soak / self::PADDY_EFF * 10 * $c['area'])) : '';
                $often = ($soak >= 20 ? 'Soak the field once' : 'The rain of the weeks before usually soaks the field' . ($p['soakDry'] >= 20 ? ' (in a dry year it takes about ' . (int) $p['soakDry'] . ' mm)' : ''))
                    . ', then keep water on it while it is worked: a top up about every ' . $p['every'] . ' days when no rain comes.';
                $amount = ($soak >= 20 ? 'About ' . (int) $soak . ' mm to soak it' . ($soakM3 !== '' ? ' (' . $soakM3 . ' for this ' . self::ha($c['area']) . ' lot)' : '') . ', then about ' : 'About ')
                    . $gross . ' mm a top up' . ($lot !== '' ? ', ' . $lot : '') . '.';
            } elseif ($kd === 'sat') {
                $every = 'about every ' . $p['every'] . ' ' . ($p['every'] === 1 ? 'day' : 'days') . ' when no rain comes';
                $often = match (true) {
                    $p['count'] === null => 'Keep the soil soaked with no water standing: water ' . $every . '.',
                    $p['count'] === 0 && $p['countDry'] === 0 => 'The rain usually keeps the soil soaked, even in a dry year. If it dries, water ' . $every . '.',
                    $p['count'] === 0 => 'The rain usually keeps the soil soaked. In a dry year water about ' . $times($p['countDry']) . ', ' . $every . '.',
                    default => 'Keep the soil soaked with no water standing: water ' . $every . ', about ' . $times($p['count']) . ' in a usual year' . ($p['countDry'] > $p['count'] ? ', ' . $times($p['countDry']) . ' in a dry year' : '') . '.',
                };
                $amount = 'About ' . $gross . ' mm each time, just enough to wet the soil with no water left standing' . ($lot !== '' ? ', ' . $lot : '') . ($hours !== '' ? ' (' . $hours . ')' : '') . '.';
            } elseif ($kd === 'pond') {
                $every = 'about every ' . $p['every'] . ' ' . ($p['every'] === 1 ? 'day' : 'days') . ' when no rain comes';
                $often = match (true) {
                    $p['count'] === null => 'Top up ' . $every . '.',
                    $p['count'] === 0 && $p['countDry'] === 0 => 'The rain usually keeps the water up, even in a dry year. Top up ' . $every . '.',
                    $p['count'] === 0 => 'The rain usually keeps the water up. In a dry year top up about ' . $times($p['countDry']) . ', ' . $every . '.',
                    default => 'Top up ' . $every . ': about ' . $times($p['count']) . ' in a usual year' . ($p['countDry'] > $p['count'] ? ', ' . $times($p['countDry']) . ' in a dry year' : '') . '.',
                };
                $amount = 'Back up to ' . self::cmWord($p['target']) . ' of standing water: about ' . $gross . ' mm each top up' . ($lot !== '' ? ', ' . $lot : '') . ($hours !== '' ? ' (' . $hours . ')' : '') . '.';
            } else {
                $often = 'Flood again when the water in a field tube is about 15 cm below the soil, about every ' . $p['every'] . ' days when no rain comes'
                    . match (true) {
                        $p['count'] === null => '',
                        $p['count'] === 0 && $p['countDry'] === 0 => '. The rain usually does it, even in a dry year',
                        $p['count'] === 0 => '. The rain usually does it; in a dry year flood about ' . $times($p['countDry']),
                        default => ': about ' . $times($p['count']) . ' in a usual year' . ($p['countDry'] > $p['count'] ? ', ' . $times($p['countDry']) . ' in a dry year' : ''),
                    } . '.';
                $amount = 'About ' . $gross . ' mm each flooding, back to ' . self::cmWord($p['target']) . ($lot !== '' ? ', ' . $lot : '') . ($hours !== '' ? ' (' . $hours . ')' : '') . '.';
            }
        }

        // The count behind it.
        $budget = '';
        if ($kd !== 'skip') {
            $budget = 'In these ' . $p['days'] . ' days the crop drinks about ' . (int) $p['etc'] . ' mm'
                . ($p['perc'] !== null && $p['perc'] > 0 ? ' and about ' . (int) $p['perc'] . ' mm sinks into the soil' : '')
                . ($p['rain'] !== null ? '. The rain usually gives ' . (int) $p['rain'] . ' mm' . ($p['rainDry'] < $p['rain'] ? ' (' . (int) $p['rainDry'] . ' mm in a dry year)' : '') : '. The rain of this place is not kept yet, so the waterings are not counted')
                . '.';
        }
        // What to watch.
        if (! $c['rainfed'] && $c['pumpLow'] && array_intersect($p['months'], $c['pumpLow']) && ($p['net'] > 0 || $p['netDry'] > 0)) {
            $low = array_values(array_intersect($p['months'], $c['pumpLow']));
            $warn[] = 'Your pump usually runs low in ' . self::monthWords($low) . (['dry' => ' in a very dry year', 'this year' => ' in a very dry year like this one'][$c['pumpWhen']] ?? '')
                . '. Fill a pond or drums before, and give this stage its water first.';
        }
        if (! $c['rainfed'] && in_array($c['water'], ['partial', 'limited'], true) && ! empty($r['critical']) && $p['net'] > 0) {
            $warn[] = 'Your water is limited: give it to this stage before any other.';
        }
        if ($p['hot'] >= 3 && $kd === 'soil') {
            $warn[] = 'About ' . $p['hot'] . ' days of 35°C or more usually fall in these weeks: water early in the morning and check the soil more often.';
        }
        if (! $c['paddy'] && $p['rain'] !== null && $p['rain'] > 1.5 * max(1, $p['etc']) && ($c['floods'] || in_array($c['drain'], ['slow', 'stands'], true))) {
            $warn[] = 'These weeks are usually much wetter than the crop needs and this field drains slowly: keep the drains and the furrow ends open.';
        }

        return ['say' => ['often' => $often, 'amount' => $amount, 'budget' => $budget, 'warn' => $warn, 'noAmount' => $c['rainfed'] && ! in_array($kd, ['drain', 'stop'], true)]];
    }

    /** The season in one or two sentences. */
    private static function seasonWords(array $s, array $c, array $rows, array $order): string
    {
        $lot = $c['area'] > 0 ? 'this ' . self::ha($c['area']) . ' lot' : 'this lot';
        if ($c['rainfed']) {
            if ($s['net'] <= 0 && $s['netDry'] <= 0) {
                return 'On the rain only, ' . $lot . ' usually gets all the water it needs this season, even in a dry year.';
            }
            $short = array_values(array_map(fn ($r) => mb_strtolower($r['stage']), array_filter($order, fn ($r) => ($rows[$r['key']]['net'] ?? 0) > 0)));

            return 'On the rain only, ' . $lot . ' usually runs about ' . (int) $s['net'] . ' mm short this season (' . (int) $s['netDry'] . ' mm in a dry year)'
                . ($short ? ', most of it in ' . self::listWords(array_slice($short, 0, 3)) : '') . '.'
                . ($s['m3Dry'] !== null && $s['m3Dry'] > 0 ? ' Keeping water by for a dry year means about ' . self::m3($s['m3Dry']) . ' for the lot.' : '');
        }
        $soak = array_sum(array_map(fn ($r) => (float) ($r['soak'] ?? 0), $rows));
        $soakSay = $soak > 0 && $c['area'] > 0 ? 'Apart from about ' . self::m3(round($soak / self::PADDY_EFF * 10 * $c['area'])) . ' to soak the field, ' : '';
        if ($s['count'] === 0 && $s['countDry'] === 0) {
            return ($soakSay !== '' ? $soakSay . 'the rain' : 'The rain') . ' usually waters ' . $lot . ' all season, even in a dry year. Keep the water ready for a long dry spell.';
        }

        return 'This season ' . $lot . ' needs about ' . ($s['m3'] !== null ? self::m3($s['m3']) : (int) $s['net'] . ' mm') . ' of water on top of the rain, in about ' . $s['count'] . ' waterings'
            . ($s['countDry'] > $s['count'] ? ' (' . ($s['m3Dry'] !== null ? self::m3($s['m3Dry']) : (int) $s['netDry'] . ' mm') . ' in ' . $s['countDry'] . ' waterings in a dry year)' : '') . '.'
            . ($c['pond'] && $s['m3Dry'] !== null ? ' Your farm pond should hold about ' . self::m3($s['m3Dry']) . ' for it.' : '');
    }

    /** What the count was based on, in short pills. */
    private static function factors(array $c): array
    {
        $pa = $c['pa'];
        $texWord = ['sandy' => 'Sandy soil', 'loam' => 'Loam soil', 'silt' => 'Silty soil', 'clay' => 'Clay soil', 'heavy_clay' => 'Heavy clay'][$c['tex']];
        $out = [];
        $out[] = ($c['texKnown'] ? $texWord : 'Soil not given, counted as loam') . ', holds about ' . (int) round($c['taw']) . ' mm a meter';
        if ($c['cap'] < 9) {
            $out[] = 'Roots stop at about ' . (int) round($c['cap'] * 100) . ' cm';
        }
        $out[] = 'Roots reach about ' . (int) round(min($c['cap'], $c['root']) * 100) . ' cm';
        if ($c['paddy']) {
            $out[] = 'About ' . rtrim(rtrim(number_format($c['perc'], 1), '0'), '.') . ' mm a day sinks through';
        }
        $drain = ['fast' => 'Drains fast', 'day' => 'Drains within a day', 'slow' => 'Drains slowly', 'stands' => 'Water stands for days'][$pa['drainage'] ?? ''] ?? null;
        if ($drain) {
            $out[] = $drain;
        }
        $src = (array) ($pa['water'] ?? []);
        $names = ['irrigation' => 'canal', 'pump' => 'pump', 'river' => 'river or creek', 'reservoir' => 'farm pond'];
        $have = array_values(array_filter(array_map(fn ($w) => $names[$w] ?? null, $src)));
        $out[] = $c['rainfed'] ? 'Rain only' : ($have ? 'Water from a ' . self::listWords($have) : 'Watered');
        if ($c['pumpLow']) {
            $out[] = 'Pump runs low in ' . self::monthWords($c['pumpLow']) . ($c['pumpWhen'] === 'dry' ? ' in a very dry year' : '');
        }
        $out[] = $c['how'][0] . ', about ' . (int) round($c['how'][1] * 100) . '% reaches the roots';
        if ($c['years'] >= 5) {
            $out[] = 'Rain and heat of these days in ' . $c['years'] . ' years';
        }
        if (abs($c['tilt'] - 1) >= 0.05) {
            $out[] = $c['tilt'] < 1 ? 'ENSO: about ' . (int) round((1 - $c['tilt']) * 100) . '% less rain expected' : 'ENSO: about ' . (int) round(($c['tilt'] - 1) * 100) . '% more rain expected';
        }
        if ($c['etF'] < 1) {
            $out[] = $c['etF'] < 0.8 ? 'Mostly shaded' : 'Shaded part of the day';
        }
        if ($c['steep']) {
            $out[] = 'Steep slope';
        }
        if ($c['salty']) {
            $out[] = 'Salt in the soil';
        }
        if ($c['area'] > 0) {
            $out[] = self::ha($c['area']);
        }
        if ($c['perHa'] > 0 && ! $c['paddy']) {
            $out[] = number_format(round($c['perHa'], -2)) . ' plants a hectare';
        }

        return $out;
    }

    /* ------------------------------------------------------------ helpers */

    private static function mid(array $v): float
    {
        return self::pct($v, 0.5);
    }

    private static function pct(array $v, float $q): float
    {
        $v = array_values($v);
        if (! $v) {
            return 0.0;
        }
        sort($v);
        $i = ($q * (count($v) - 1));
        $lo = (int) floor($i);
        $hi = (int) ceil($i);

        return (float) ($v[$lo] + ($v[$hi] - $v[$lo]) * ($i - $lo));
    }

    private static function m3(float|int|null $v): string
    {
        $v = (float) $v;

        return ($v < 10 ? rtrim(rtrim(number_format($v, 1), '0'), '.') : number_format(round($v))) . ' m³';
    }

    private static function liters(float $v): string
    {
        return ($v < 10 ? rtrim(rtrim(number_format($v, 1), '0'), '.') : number_format(round($v))) . ' liters';
    }

    private static function ha(float $a): string
    {
        return rtrim(rtrim(number_format($a, 2), '0'), '.') . ' ha';
    }

    private static function hours(float $m3): string
    {
        $h = $m3 * 1000 / self::PUMP_LPH;

        return 'about ' . ($h < 1 ? (int) max(10, round($h * 60 / 10) * 10) . ' minutes' : ($h >= 10 ? number_format(round($h)) : rtrim(rtrim(number_format($h, 1), '0'), '.')) . ($h < 1.05 ? ' hour' : ' hours')) . ' of pumping with a small pump';
    }

    private static function cmWord(float $mm): string
    {
        return rtrim(rtrim(number_format($mm / 10, 1), '0'), '.') . ' cm';
    }

    private static function monthWords(array $months): string
    {
        return NpkNeed::monthWords(array_values($months));
    }

    private static function listWords(array $w): string
    {
        $w = array_values($w);

        return count($w) < 2 ? (string) ($w[0] ?? '') : implode(', ', array_slice($w, 0, -1)) . ' and ' . end($w);
    }
}
