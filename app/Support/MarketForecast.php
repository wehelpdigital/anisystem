<?php

namespace App\Support;

/**
 * A crop's price, looked ahead from its own past (2026-10-27, the Market
 * Price Forecast). Pure arithmetic on one monthly series, no database:
 *
 *  1. The months of the year: each month's price against the year around it
 *     (a centered twelve month average), the middle of the last ten years.
 *     The harvest glut months sit below 100%, the lean months above.
 *  2. The price without its season, on a log scale, and the line through it
 *     over the last eight years (the middle of all the slopes between months
 *     a year or more apart, so one wild year cannot tilt it). Its slope is
 *     the price's yearly growth, held between minus 15% and plus 20%, and
 *     halved beyond a year ahead.
 *  3. How far the price sits from that line now, and how fast such a gap has
 *     closed before (the month to month pull back of the gaps): a glut or a
 *     spike fades at the crop's own pace.
 *  4. Forecast = line + the gap now, fading + the month of the year.
 *  5. The range: the same forecast made from each of the last six years'
 *     months, checked against what the price then did. Its errors, month by
 *     month ahead, give the likely low and high (the 10th and 90th of them)
 *     and how far off the method usually is.
 * The month index used throughout is year * 12 + month - 1.
 */
final class MarketForecast
{
    private const SEASON_YEARS = 10;

    private const TREND_MONTHS = 96;

    /**
     * @param  array<int, float>  $v  the series, month index => price (gaps left out)
     * @param  int  $toIdx  forecast through this month
     * @return array|null null when the series is too short to read
     */
    public static function run(array $v, int $toIdx): ?array
    {
        $v = self::fill($v);
        if (count($v) < 36) {
            return null;
        }
        $end = max(array_keys($v));
        $fit = self::fit($v, $end);
        if (! $fit) {
            return null;
        }
        $H = max(1, $toIdx - $end);
        $bands = self::bands($v, $end, max(18, $H));
        $out = [];
        for ($h = 1; $h <= $H; $h++) {
            $t = $end + $h;
            $p = self::at($fit, $t, $h);
            [$lo, $hi] = $bands['q'][$h] ?? self::fallbackBand($fit, $h);
            $out[$t] = ['p' => round($p, 2), 'lo' => round($p * exp($lo), 2), 'hi' => round($p * exp($hi), 2)];
        }

        return [
            'end' => $end,
            'seasonal' => array_map(fn ($x) => round($x, 4), $fit['s']),
            'seasonalN' => $fit['sn'],
            'consistency' => array_map(fn ($x) => round($x, 2), $fit['sc']),
            'spread' => $fit['spread'],
            'growth' => round(exp(12 * $fit['b']) - 1, 4),
            'level' => round(exp($fit['a'] + $fit['b'] * $end), 2),
            'gap' => round(exp($fit['e']) - 1, 4),
            'rho' => round($fit['rho'], 3),
            'halfLife' => $fit['rho'] > 0.01 && $fit['rho'] < 0.999 ? round(log(0.5) / log($fit['rho']), 1) : null,
            'forecast' => $out,
            'accuracy' => $bands['acc'],
            'boomBust' => self::boomBust($v, $fit),
        ];
    }

    /** The fitted pieces at the month $end: s (12), a and b (the line), e (the gap now), rho. */
    public static function fit(array $v, int $end): ?array
    {
        [$s, $sn, $sc, $spread] = self::seasonal($v, $end);
        $d = [];
        for ($t = $end - self::TREND_MONTHS + 1; $t <= $end; $t++) {
            if (isset($v[$t]) && $v[$t] > 0) {
                $d[$t] = log($v[$t] / $s[$t % 12]);
            }
        }
        if (count($d) < 24) {
            return null;
        }
        // The line: the middle of the slopes between months a year or more apart.
        $ts = array_keys($d);
        $slopes = [];
        $n = count($ts);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($ts[$j] - $ts[$i] >= 12) {
                    $slopes[] = ($d[$ts[$j]] - $d[$ts[$i]]) / ($ts[$j] - $ts[$i]);
                }
            }
        }
        if (! $slopes) {
            return null;
        }
        $b = self::median($slopes);
        $b = max(log(0.85) / 12, min(log(1.20) / 12, $b));
        $a = self::median(array_map(fn ($t) => $d[$t] - $b * $t, $ts));
        // The gaps from the line, and how fast they closed.
        $e = [];
        foreach ($d as $t => $x) {
            $e[$t] = $x - ($a + $b * $t);
        }
        $num = $den = 0.0;
        foreach ($e as $t => $x) {
            if (isset($e[$t - 1])) {
                $num += $x * $e[$t - 1];
                $den += $e[$t - 1] ** 2;
            }
        }
        $rho = $den > 0 ? max(0.0, min(0.95, $num / $den)) : 0.5;
        $last = max($ts);
        // The gap now: the last month, steadied a little by the one before.
        $eNow = isset($e[$last - 1]) ? 0.7 * $e[$last] + 0.3 * $e[$last - 1] : $e[$last];
        // A gap reported months ago has already faded that much.
        $eNow *= $rho ** max(0, $end - $last);
        $sd = count($e) > 2 ? sqrt(array_sum(array_map(fn ($x) => $x * $x, $e)) / (count($e) - 1)) : 0.15;

        return ['s' => $s, 'sn' => $sn, 'sc' => $sc, 'spread' => $spread, 'a' => $a, 'b' => $b, 'e' => $eNow, 'rho' => $rho, 'end' => $end, 'sd' => $sd];
    }

    /** The forecast at month $t, $h months after the fit's end. */
    public static function at(array $fit, int $t, int $h): float
    {
        $ahead = $h <= 12 ? $h : 12 + ($h - 12) * 0.5;

        return exp($fit['a'] + $fit['b'] * ($fit['end'] + $ahead) + $fit['e'] * ($fit['rho'] ** $h)) * $fit['s'][$t % 12];
    }

    /**
     * The months of the year: [index 12 (mean 1), years behind each, how often the month fell on its side of
     * the average, the usual swing between the lean and the glut months].
     */
    public static function seasonal(array $v, int $end): array
    {
        $ratios = array_fill(0, 12, []);
        $from = $end - self::SEASON_YEARS * 12 + 1;
        for ($t = $from; $t <= $end; $t++) {
            if (! isset($v[$t])) {
                continue;
            }
            $sum = 0.0;
            $ok = true;
            for ($k = -6; $k <= 6; $k++) {
                if (! isset($v[$t + $k])) {
                    $ok = false;
                    break;
                }
                $sum += ($k === -6 || $k === 6 ? 0.5 : 1.0) * $v[$t + $k];
            }
            if ($ok && $sum > 0) {
                $ratios[$t % 12][] = $v[$t] / ($sum / 12);
            }
        }
        // A short series: each month against its own calendar year.
        if (array_sum(array_map('count', $ratios)) < 24) {
            $ratios = array_fill(0, 12, []);
            $years = [];
            foreach ($v as $t => $x) {
                if ($t >= $from - 12) {
                    $years[intdiv($t, 12)][$t % 12] = $x;
                }
            }
            foreach ($years as $row) {
                if (count($row) >= 10) {
                    $mean = array_sum($row) / count($row);
                    foreach ($row as $m => $x) {
                        $ratios[$m][] = $x / $mean;
                    }
                }
            }
        }
        $s = [];
        $sn = [];
        foreach ($ratios as $m => $r) {
            $sn[$m] = count($r);
            $raw = $r ? self::median($r) : 1.0;
            // Few years behind a month: held nearer the average.
            $s[$m] = 1 + ($raw - 1) * min(1.0, count($r) / 5);
        }
        $mean = array_sum($s) / 12;
        $s = array_map(fn ($x) => $x / $mean, $s);
        $sc = [];
        foreach ($ratios as $m => $r) {
            $sc[$m] = $r ? count(array_filter($r, fn ($x) => ($x - 1) * ($s[$m] - 1) >= 0)) / count($r) : 0.0;
        }

        return [$s, $sn, $sc, round(max($s) / max(0.01, min($s)) - 1, 4)];
    }

    /**
     * The likely range, month by month ahead, from forecasts made in the past and what followed:
     * q[h] = [low, high] on the log scale, acc = how far off it usually was six and twelve months ahead.
     */
    private static function bands(array $v, int $end, int $H): array
    {
        $err = [];
        for ($o = $end - 72; $o <= $end - 3; $o += 3) {
            if (! isset($v[$o]) || count(array_filter(array_keys($v), fn ($t) => $t <= $o && $t > $o - 60)) < 40) {
                continue;
            }
            $past = array_filter($v, fn ($t) => $t <= $o, ARRAY_FILTER_USE_KEY);
            $fit = self::fit($past, $o);
            if (! $fit) {
                continue;
            }
            for ($h = 1; $h <= $H && $o + $h <= $end; $h++) {
                if (isset($v[$o + $h]) && $v[$o + $h] > 0) {
                    $err[$h][] = log($v[$o + $h] / self::at($fit, $o + $h, $h));
                }
            }
        }
        $q = [];
        for ($h = 1; $h <= $H; $h++) {
            $pool = array_merge($err[$h - 1] ?? [], $err[$h] ?? [], $err[$h + 1] ?? []);
            // Far ahead the past has fewer checks: the furthest it has, widened.
            if (count($pool) < 8) {
                $k = $h;
                while ($k > 1 && count($err[$k] ?? []) < 8) {
                    $k--;
                }
                $pool = $err[$k] ?? [];
                if (count($pool) < 8) {
                    continue;
                }
                $grow = sqrt($h / max(1, $k));
                $q[$h] = [self::pct($pool, 0.1) * $grow, self::pct($pool, 0.9) * $grow];

                continue;
            }
            $q[$h] = [self::pct($pool, 0.1), self::pct($pool, 0.9)];
        }
        $mape = fn (int $h) => count($err[$h] ?? []) >= 6 ? round(self::median(array_map(fn ($x) => abs(exp($x) - 1), $err[$h])), 4) : null;

        return ['q' => $q, 'acc' => ['m3' => $mape(3), 'm6' => $mape(6), 'm12' => $mape(12), 'checks' => count($err[1] ?? [])]];
    }

    /** A range when the past is too short to check: the gaps' own spread, growing with the months ahead. */
    private static function fallbackBand(array $fit, int $h): array
    {
        $w = 1.2816 * $fit['sd'] * sqrt(max(0.05, 1 - $fit['rho'] ** (2 * $h))) + 0.01 * $h;

        return [-$w, $w];
    }

    /**
     * Boom and bust: how a year above its line is followed by one below (the year to year pull of the yearly
     * gaps, over the years with all twelve months). Negative when a good year tends to bring a poor one.
     */
    private static function boomBust(array $v, array $fit): ?array
    {
        $years = [];
        foreach ($v as $t => $x) {
            $years[intdiv($t, 12)][] = log($x / $fit['s'][$t % 12]) - ($fit['a'] + $fit['b'] * $t);
        }
        $g = [];
        foreach ($years as $y => $row) {
            if (count($row) === 12 && $y >= intdiv($fit['end'], 12) - 15) {
                $g[$y] = array_sum($row) / 12;
            }
        }
        $pairs = [];
        foreach ($g as $y => $x) {
            if (isset($g[$y - 1])) {
                $pairs[] = [$g[$y - 1], $x];
            }
        }
        if (count($pairs) < 7) {
            return null;
        }
        $mx = array_sum(array_column($pairs, 0)) / count($pairs);
        $my = array_sum(array_column($pairs, 1)) / count($pairs);
        $sxy = $sxx = $syy = 0.0;
        foreach ($pairs as [$x, $y]) {
            $sxy += ($x - $mx) * ($y - $my);
            $sxx += ($x - $mx) ** 2;
            $syy += ($y - $my) ** 2;
        }
        $r = $sxx > 0 && $syy > 0 ? $sxy / sqrt($sxx * $syy) : 0;
        $big = array_values(array_filter($g, fn ($x) => abs($x) >= 0.15));

        return ['r' => round($r, 3), 'years' => count($pairs), 'swingYears' => count($big)];
    }

    /** Gaps of one or two months filled in a straight line. */
    private static function fill(array $v): array
    {
        ksort($v);
        $ts = array_keys($v);
        for ($i = 1; $i < count($ts); $i++) {
            $gap = $ts[$i] - $ts[$i - 1];
            if ($gap > 1 && $gap <= 3) {
                for ($k = 1; $k < $gap; $k++) {
                    $v[$ts[$i - 1] + $k] = $v[$ts[$i - 1]] + ($v[$ts[$i]] - $v[$ts[$i - 1]]) * $k / $gap;
                }
            }
        }
        ksort($v);

        return $v;
    }

    public static function median(array $x): float
    {
        return self::pct($x, 0.5);
    }

    public static function pct(array $x, float $q): float
    {
        $x = array_values($x);
        if (! $x) {
            return 0.0;
        }
        sort($x);
        $i = $q * (count($x) - 1);
        $lo = (int) floor($i);
        $hi = (int) ceil($i);

        return (float) ($x[$lo] + ($x[$hi] - $x[$lo]) * ($i - $lo));
    }
}
