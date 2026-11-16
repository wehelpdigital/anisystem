<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The year at a place, month by month and week by week (2026-11-17, the owner: "create a graphical heatmap
 * presentation as well for months to see in your area ... planting days, spray is good in this day, labor is good in
 * these days"). Read from the twenty years the climate shelf keeps for the place's quarter degree cell
 * (ClimateStore, as_weather_weeks): the usual high and the day's average, the days at 35°C or more, the rain and the
 * rainy days, and the years a storm reached the place. From those, how each month usually suits six kinds of work.
 *
 * Read only: a place the shelf has not filled yet answers pending, and its twenty years are fetched once after the
 * answer has gone out (ClimateStore::fillLater), so the farmer never waits on the archive. Kept a month per cell.
 * Never tied to a crop: the weather decides when the work goes well.
 */
final class HeatYear
{
    /** The work rated month by month, in the order the grid draws it: [name, icon]. */
    public const WORK = [
        'plantRain' => ['Planting, rain only', 'seedling'],
        'plantWater' => ['Planting, with irrigation', 'water'],
        'spray' => ['Spraying', 'spray'],
        'fert' => ['Fertilizing', 'fert'],
        'labor' => ['Field work', 'people'],
        'dry' => ['Harvest and drying', 'sun'],
    ];

    public static function of(float $lat, float $lng, ?string $label = null): array
    {
        $key = 'heatyear:v1:' . ClimateStore::cellKey($lat, $lng) . ':' . now('Asia/Manila')->format('Y-m');
        $hit = rescue(fn () => Cache::get($key), null, false);
        if (is_array($hit)) {
            return $hit;
        }
        $rows = rescue(fn () => ClimateStore::weekRows($lat, $lng), null, false);
        if (! $rows) {
            ClimateStore::fillLater($lat, $lng, $label);

            return ['pending' => true];
        }
        $out = self::build($rows['weeks'], (int) ($rows['cell']->shape ?? 1) >= ClimateStore::SHAPE) + ['cell' => $rows['cell']->cellKey];
        if (! empty($out['months'])) {
            rescue(fn () => Cache::put($key, $out, now()->addDays(31)), null, false);
        }

        return $out;
    }

    /** The months, the weeks and the work, from the kept weeks. $ext: the weeks carry the rainy day counts and gusts. */
    public static function build(array $byYear, bool $ext = true): array
    {
        $last = (int) now('Asia/Manila')->year - 1;
        $first = $last - ClimateStore::YEARS + 1;
        $mon = [];   // [month][year] => sums
        $wk = [];    // [week] => sums over the years
        $years = [];
        for ($y = $first; $y <= $last; $y++) {
            foreach ($byYear[$y] ?? [] as $wn => $r) {
                $days = (int) ($r['days'] ?? 0);
                if ($days < 1) {
                    continue;
                }
                $years[$y] = true;
                $mid = Carbon::create($y, 1, 1)->addDays(($wn - 1) * 7 + 3);
                $mo = $mid->year === $y ? (int) $mid->month : 12;
                $wn = min(52, (int) $wn);
                $add = function (array $s) use ($r, $days) {
                    foreach (['tmax', 'tmean', 'rain', 'hot35', 'dry1', 'wet10'] as $c) {
                        $s[$c] = ($s[$c] ?? 0) + (float) ($r[$c] ?? 0);
                    }
                    $s['days'] = ($s['days'] ?? 0) + $days;
                    $s['gust'] = max((float) ($s['gust'] ?? 0), (float) ($r['gustMax'] ?? 0));

                    return $s;
                };
                $mon[$mo][$y] = $add($mon[$mo][$y] ?? []);
                $wk[$wn] = $add($wk[$wn] ?? []);
            }
        }
        if (! $years) {
            return ['months' => []];
        }

        $months = [];
        for ($mo = 1; $mo <= 12; $mo++) {
            $rows = array_filter($mon[$mo] ?? [], fn ($s) => $s['days'] >= 21);
            if (! $rows) {
                continue;
            }
            $dim = Carbon::create(2001, $mo, 1)->daysInMonth;
            $n = count($rows);
            $per = fn (string $c) => array_sum(array_map(fn ($s) => $s[$c] * $dim / $s['days'], $rows)) / $n;
            $allDays = array_sum(array_column($rows, 'days'));
            $months[$mo] = [
                'm' => $mo,
                'high' => round(array_sum(array_column($rows, 'tmax')) / $allDays, 1),
                'mean' => round(array_sum(array_column($rows, 'tmean')) / $allDays, 1),
                'hot35' => round($per('hot35'), 1),
                'rain' => (int) round($per('rain')),
                'rainy' => $ext ? round($dim - $per('dry1'), 1) : null,
                'wet10' => $ext ? round($per('wet10'), 1) : null,
                'storms' => count(array_filter($rows, fn ($s) => $s['gust'] >= ClimateStore::STORM_GUST)),
                'years' => $n,
            ];
        }

        $weeks = [];
        for ($w = 1; $w <= 52; $w++) {
            $s = $wk[$w] ?? null;
            if (! $s || $s['days'] < 7) {
                continue;
            }
            $weeks[] = [
                'w' => $w,
                'm' => (int) Carbon::create(2001, 1, 1)->addDays(($w - 1) * 7 + 3)->month,
                'high' => round($s['tmax'] / $s['days'], 1),
                'mean' => round($s['tmean'] / $s['days'], 1),
                'hot35' => round($s['hot35'] / $s['days'] * 7, 1),
                'rain' => (int) round($s['rain'] / $s['days'] * 7),
                'rainy' => $ext ? round(($s['days'] - $s['dry1']) / $s['days'] * 7, 1) : null,
            ];
        }

        $work = [];
        foreach (array_keys(self::WORK) as $key) {
            foreach ($months as $mo => $m) {
                $work[$key][$mo] = self::rate($key, $m);
            }
        }
        $best = [];
        foreach ($work as $key => $byMonth) {
            $best[$key] = array_keys(array_filter($byMonth, fn ($x) => $x['r'] === 'g'));
        }
        ksort($years);

        return [
            'span' => array_key_first($years) . ' to ' . array_key_last($years),
            'years' => count($years),
            'months' => array_values($months),
            'weeks' => $weeks,
            'work' => $work,
            'best' => $best,
            'facts' => self::facts($months),
        ];
    }

    /** How a month usually suits a kind of work: ['r' => g|f|p, 'why' => one sentence]. */
    public static function rate(string $key, array $m): array
    {
        $high = (float) $m['high'];
        $rain = (int) $m['rain'];
        $rainy = $m['rainy'];
        $wet10 = $m['wet10'] ?? null;
        $hot = (float) $m['hot35'];
        $storms = (int) $m['storms'];
        $H = (int) round($high);
        $days = fn ($v) => (int) round((float) $v);
        // Without the rainy day counts (an old cell), the rain stands in for them.
        $wetDays = $rainy ?? ($rain > 300 ? 20 : ($rain > 120 ? 12 : 5));
        $g = fn (string $why) => ['r' => 'g', 'why' => $why];
        $f = fn (string $why) => ['r' => 'f', 'why' => $why];
        $p = fn (string $why) => ['r' => 'p', 'why' => $why];

        return match ($key) {
            'plantRain' => match (true) {
                $rain < 80 => $p('Too dry to plant on rain alone: about ' . $rain . ' mm of rain in the month.'),
                $rain > 600 || $storms > 5 => $p('The heaviest rains and storms of the year: newly planted fields can wash out.'),
                $high >= 35.5 => $p('Too hot for young plants without water: highs about ' . $H . '°C.'),
                $rain < 150 => $f('Light rain, about ' . $rain . ' mm: plant only once the soil stays moist, or be ready to water.'),
                $rain > 450 => $f('Very wet, about ' . $rain . ' mm: plant on higher ground and keep the drains open.'),
                $high >= 34 => $f('Hot, highs about ' . $H . '°C: plant in the late afternoon and keep the soil moist.'),
                default => $g('Rain enough for planting, about ' . $rain . ' mm over about ' . $days($wetDays) . ' rainy days.'),
            },
            'plantWater' => match (true) {
                $high >= 35 => $p('Highs about ' . $H . '°C: young plants struggle even with water.'),
                ($wet10 !== null && $wet10 > 14) || $storms > 5 => $p('Storm and flood months: newly planted fields are at risk.'),
                $high >= 33 => $f('Hot, highs about ' . $H . '°C: plant in the late afternoon and water right away.'),
                ($wet10 !== null && $wet10 > 8) || $storms > 3 => $f('Many heavy rain days: keep the drains open, and watch for storms.'),
                default => $g('Good planting weather with water at hand: highs about ' . $H . '°C.'),
            },
            'spray' => match (true) {
                $wetDays > 18 => $p('Rain on about ' . $days($wetDays) . ' days: sprays often wash off.'),
                $wetDays > 10 => $f('Rain on about ' . $days($wetDays) . ' days: spray on dry mornings, and check the next hours\' rain first.'),
                $high > 33 => $f('Mostly dry but hot, highs about ' . $H . '°C: spray only early in the morning or late in the afternoon.'),
                default => $g('Mostly dry, about ' . $days($wetDays) . ' rainy days: good spray days are easy to find.'),
            },
            'fert' => match (true) {
                $rain > 550 => $p('About ' . $rain . ' mm of rain: heavy rains wash fertilizer off the field.'),
                $rain < 50 => $f('Dry, about ' . $rain . ' mm of rain: water it in, or mix it into moist soil.'),
                $rain > 350 => $f('Wet, about ' . $rain . ' mm: split the doses and skip the days before heavy rain.'),
                $high >= 34 => $f('Hot, highs about ' . $H . '°C: put it on in the morning, on moist soil.'),
                default => $g('Moist soil and moderate rain, about ' . $rain . ' mm: fertilizer works well.'),
            },
            'labor' => match (true) {
                $high >= 34 || $hot >= 8 => $p('Highs about ' . $H . '°C' . ($hot >= 1 ? ', with about ' . $days($hot) . ' days at 35°C or more' : '') . ': work early and late, rest at midday.'),
                $high >= 31.5 => $f('Warm, highs about ' . $H . '°C: start early and take shade breaks.'),
                $wet10 !== null && $wet10 > 8 => $f('About ' . $days($wet10) . ' days of heavy rain: wet fields slow the work.'),
                default => $g('Comfortable for field work: highs about ' . $H . '°C.'),
            },
            'dry' => match (true) {
                $wetDays > 15 => $p('Rain on about ' . $days($wetDays) . ' days: sun drying is hard, use a dryer or a shed.'),
                $wetDays > 8 || $rain >= 100 => $f('Rain on about ' . $days($wetDays) . ' days: dry on the sunny days and cover the harvest at night.'),
                default => $g('Mostly sunny and dry: good for harvest and sun drying.'),
            },
            default => $f(''),
        };
    }

    /** The year's high points: the hottest, wettest and driest months, and the storm months. */
    private static function facts(array $months): array
    {
        if (! $months) {
            return [];
        }
        $by = fn (string $c, bool $max) => array_reduce($months, fn ($a, $m) => $a === null || ($max ? $m[$c] > $a[$c] : $m[$c] < $a[$c]) ? $m : $a);

        return [
            'hottest' => ['m' => $by('high', true)['m'], 'v' => $by('high', true)['high']],
            'coolest' => ['m' => $by('high', false)['m'], 'v' => $by('high', false)['high']],
            'wettest' => ['m' => $by('rain', true)['m'], 'v' => $by('rain', true)['rain']],
            'driest' => ['m' => $by('rain', false)['m'], 'v' => $by('rain', false)['rain']],
            'storms' => array_values(array_map(fn ($m) => $m['m'], array_filter($months, fn ($m) => $m['storms'] >= 3))),
        ];
    }
}
