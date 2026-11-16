<?php

namespace App\Support;

/**
 * The good days and hours for the farm's work (2026-11-17, the owner, on the Heat Map Forecast: "the main goal is to
 * anything from planting days, spray is good in this day, labor is good in these days, and so on or hours"). Read
 * off the spot's own hourly forecast (HeatForecast's lot hours), never off a crop: the weather says when the work
 * goes well, whatever is growing.
 *
 * Six kinds of work, each hour from 6 AM to 6 PM rated good, fair or poor with the reason, then the day: its rating,
 * its best hours ("from 6 to 9 AM and 4 to 6 PM") and one sentence. Hours already past today are left out of the
 * day's rating and its best hours. Words only, no dashes (the house label rules).
 */
final class FieldHours
{
    /** The work, in the order every view draws it: [name, icon]. */
    public const WORK = [
        'plant' => ['Planting', 'seedling'],
        'spray' => ['Spraying', 'spray'],
        'fert' => ['Fertilizing', 'fert'],
        'labor' => ['Field work', 'people'],
        'water' => ['Watering', 'water'],
        'dry' => ['Harvest and drying', 'sun'],
    ];

    /** The first hour drawn (6 AM, about sunrise) and the hour after the last (6 PM is drawn, 7 PM is not). */
    public const FROM = 6;

    public const TO = 19;

    /** Why an hour is not good, said for each kind of work. */
    private const WHY = [
        'plant' => ['heat' => 'it is too hot for young plants', 'rain' => 'heavy rain would wash out seeds and seedlings'],
        'spray' => ['rain' => 'rain would wash the spray off', 'wind' => 'the wind would drift the spray away', 'heat' => 'the heat dries the spray before it works',
            'still' => 'the air is so still that fine drops hang and drift', 'dryair' => 'the air is so dry the drops dry too fast'],
        'fert' => ['wash' => 'heavy rain ahead would wash it off', 'heat' => 'hot ground loses urea to the air'],
        'labor' => ['heat' => 'the heat is hard on people', 'rain' => 'rain slows the work'],
        'water' => ['rained' => 'rain does the watering', 'raining' => 'it is raining', 'heat' => 'the midday heat takes the water', 'soon' => 'rain may come soon'],
        'dry' => ['rain' => 'rain is likely', 'damp' => 'the air is too damp to dry well', 'cloud' => 'it is too cloudy to dry well', 'late' => 'the sun is too low'],
    ];

    /** A good day's sentence; {w} is its best hours. */
    private const GOOD = [
        'plant' => 'Plant or transplant {w}, out of the heat.',
        'spray' => 'Spray {w}: cool, calm and dry.',
        'fert' => 'Fertilize {w}, on moist soil.',
        'labor' => 'Comfortable to work outside {w}.',
        'water' => 'Water {w}, before the heat.',
        'dry' => 'Sunny and dry {w}: spread it thin and turn it often.',
    ];

    /**
     * Every day of the lot's week: [date => [key => ['r' => g|f|p|null, 'best' => words|null, 'say' => sentence,
     * 'hours' => 'ggffp…' (FROM to TO, x = past, n = not that work's hours), 'why' => [reason words or '']]]].
     */
    public static function week(array $lot, ?string $today = null, ?int $nowHour = null): array
    {
        $H = (array) ($lot['hours'] ?? []);
        $today ??= now('Asia/Manila')->toDateString();
        $nowHour ??= (int) now('Asia/Manila')->format('G');
        $out = [];
        foreach ((array) ($lot['days'] ?? []) as $d) {
            $date = (string) $d['date'];
            $start = HeatForecast::dayStart($lot, $date);
            if ($start === null) {
                continue;
            }
            $past = $date === $today ? $nowHour : ($date < $today ? 24 : -1);
            $dayRain = (float) ($d['rain'] ?? 0);
            $work = [];
            foreach (array_keys(self::WORK) as $key) {
                $codes = '';
                $why = [];
                for ($hr = self::FROM; $hr < self::TO; $hr++) {
                    $k = $start + $hr;
                    if (! isset($H['t'][$k]) || $H['t'][$k] === null) {
                        $codes .= 'n';
                        $why[] = '';
                        continue;
                    }
                    [$r, $reason] = self::rate($key, $H, $k, $hr, $dayRain);
                    $codes .= $hr < $past ? 'x' : $r;
                    $why[] = $reason ? self::WHY[$key][$reason] : '';
                }
                $work[$key] = self::sum($key, $codes, $why, $dayRain);
            }
            $out[$date] = $work;
        }

        return $out;
    }

    /** One hour for one kind of work: [g|f|p|n, reason key or null]. */
    private static function rate(string $key, array $H, int $k, int $hr, float $dayRain): array
    {
        $v = fn (string $s, int $i) => isset($H[$s][$i]) && is_numeric($H[$s][$i]) ? (float) $H[$s][$i] : null;
        $t = $v('t', $k);
        $f = $v('f', $k) ?? $t;
        $rh = $v('h', $k);
        $w = $v('w', $k);
        $p = $v('p', $k) ?? 0.0;
        $c = $v('c', $k);
        $ahead = function (int $n, string $s, bool $sum) use ($v, $k) {
            $vals = [];
            for ($i = 0; $i <= $n; $i++) {
                $x = $v($s, $k + $i);
                if ($x !== null) {
                    $vals[] = $x;
                }
            }

            return $vals ? ($sum ? array_sum($vals) : max($vals)) : 0.0;
        };
        $rain3 = $ahead(3, 'p', true);
        $pp3 = $ahead(3, 'pp', false);
        $rain12 = $ahead(12, 'p', true);

        // The worst reason decides; a poor one beats a fair one.
        $poor = null;
        $fair = null;
        $check = function (bool $isPoor, bool $isFair, string $why) use (&$poor, &$fair) {
            if ($isPoor && ! $poor) {
                $poor = $why;
            } elseif ($isFair && ! $fair) {
                $fair = $why;
            }
        };
        switch ($key) {
            case 'labor':
                $check($f >= 41, $f >= 33, 'heat');
                $check($p >= 2 || $pp3 >= 80, $p >= 0.3 || $pp3 >= 50, 'rain');
                break;
            case 'spray':
                $check($p >= 0.2 || $rain3 >= 0.5 || $pp3 >= 60, $pp3 >= 30, 'rain');
                $check($w !== null && $w > 20, $w !== null && $w > 15, 'wind');
                $check($t > 33, $t > 30, 'heat');
                $check(false, $w !== null && $w < 2, 'still');
                $check(false, $rh !== null && $rh < 40, 'dryair');
                break;
            case 'fert':
                $check($p >= 2 || $rain12 >= 20, $rain12 >= 8, 'wash');
                $check($t >= 34, $t >= 31, 'heat');
                break;
            case 'plant':
                $check($t >= 34, $t >= 31, 'heat');
                $check($p >= 4 || $rain3 >= 10, $p >= 1, 'rain');
                break;
            case 'water':
                $check($dayRain >= 8, false, 'rained');
                $check($p >= 0.5, false, 'raining');
                $check($t >= 33, $t >= 30, 'heat');
                $check(false, $pp3 >= 50, 'soon');
                break;
            case 'dry':
                if ($hr < 7 || $hr > 16) {
                    return ['n', null];
                }
                $check($p >= 0.1 || $pp3 >= 50, $pp3 >= 25, 'rain');
                $check($rh !== null && $rh >= 85, $rh !== null && $rh >= 75, 'damp');
                $check(false, $c !== null && $c >= 85, 'cloud');
                $check(false, $hr >= 16, 'late');
                break;
        }
        if ($poor) {
            return ['p', $poor];
        }

        return $fair ? ['f', $fair] : ['g', null];
    }

    /** A day of one kind of work: its rating, best hours and sentence. */
    private static function sum(string $key, string $codes, array $why, float $dayRain): array
    {
        $g = substr_count($codes, 'g');
        $f = substr_count($codes, 'f');
        $p = substr_count($codes, 'p');
        $base = ['hours' => $codes, 'why' => $why, 'from' => self::FROM];
        if ($g + $f + $p === 0) {
            return $base + ['r' => null, 'best' => null, 'say' => 'The day\'s work hours are over.'];
        }
        $need = $key === 'dry' ? 4 : 3;
        $r = $g >= $need ? 'g' : (($g >= 1 || $f >= 3) ? 'f' : 'p');
        $runs = self::runs($codes, $g ? 'g' : 'f');
        $best = $runs ? self::windowWords($runs) : null;
        // The reason heard most among the hours that were not good.
        $count = [];
        foreach (str_split($codes) as $i => $code) {
            if (in_array($code, ['f', 'p'], true) && $why[$i] !== '') {
                $count[$why[$i]] = ($count[$why[$i]] ?? 0) + ($code === 'p' ? 2 : 1);
            }
        }
        arsort($count);
        $reason = array_key_first($count);
        if ($key === 'water' && $dayRain >= 8) {
            return $base + ['r' => 'p', 'best' => null, 'say' => 'Not needed: rain does the watering today, about ' . (int) round($dayRain) . ' mm.'];
        }
        $say = match ($r) {
            'g' => str_replace('{w}', $best ?? 'today', self::GOOD[$key]),
            'f' => ($reason ? ucfirst($reason) . '. ' : '') . ($best ? ($g ? 'Best ' : 'Workable, with care, ') . $best . '.' : 'Take care if you must.'),
            default => ($reason ? ucfirst($reason) . ' most of the day.' : 'Not a good day for it.') . ($best && $f ? ' If you must, ' . $best . '.' : ''),
        };

        return $base + ['r' => $r, 'best' => $best, 'say' => $say];
    }

    /** The longest runs of a code, best first, at most two: [[startHour, endHourExclusive], …]. */
    private static function runs(string $codes, string $want): array
    {
        $runs = [];
        $start = null;
        $len = strlen($codes);
        for ($i = 0; $i <= $len; $i++) {
            $on = $i < $len && $codes[$i] === $want;
            if ($on && $start === null) {
                $start = $i;
            } elseif (! $on && $start !== null) {
                $runs[] = [self::FROM + $start, self::FROM + $i];
                $start = null;
            }
        }
        usort($runs, fn ($a, $b) => ($b[1] - $b[0]) <=> ($a[1] - $a[0]) ?: $a[0] <=> $b[0]);
        $runs = array_slice(array_values(array_filter($runs, fn ($r, $i) => $i === 0 || $r[1] - $r[0] >= 2, ARRAY_FILTER_USE_BOTH)), 0, 2);
        usort($runs, fn ($a, $b) => $a[0] <=> $b[0]);

        return $runs;
    }

    /** "from 6 to 9 AM and 4 to 6 PM", or "at 7 AM". */
    public static function windowWords(array $runs): string
    {
        $parts = array_map(fn ($r) => self::range($r[0], $r[1]), $runs);

        return (count($runs) === 1 && $runs[0][1] - $runs[0][0] === 1 ? 'at ' : 'from ') . implode(' and ', $parts);
    }

    /** One stretch of hours: "6 to 9 AM", "9 AM to 2 PM", "7 AM" for a single hour. */
    public static function range(int $a, int $b): string
    {
        $w = fn (int $h) => $h === 12 ? 'noon' : (($h % 12 ?: 12) . ($h < 12 ? ' AM' : ' PM'));
        $n = fn (int $h) => (string) ($h % 12 ?: 12);
        if ($b - $a === 1) {
            return $w($a);
        }
        if ($b <= 11 || ($a >= 13 && $b <= 23)) {
            return $n($a) . ' to ' . $w($b);
        }

        return $w($a) . ' to ' . $w($b);
    }
}
