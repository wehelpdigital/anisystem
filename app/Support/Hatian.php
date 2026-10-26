<?php

namespace App\Support;

/**
 * Hatian Calculator (2026-10-26, the owner): the harvest shared between the
 * owner and the farm manager, with sacks set aside for the owner's home food.
 *
 * The owner's rule: the farm manager's share is counted from the GROSS
 * harvest, all the sacks, never from what is left after the owner's home
 * food. The home food comes out of the owner's own share only.
 *
 * The arithmetic, the same in the page (resources/views/hatian/index, calc())
 * and here (what a saved calculation keeps):
 *
 *  1. The harvest: S sacks, each weighing between a light and a heavy figure
 *     (kgMin to kgMax; the same figure twice when every sack weighs the same).
 *     Counted at the middle (avg); the two ends are the harvest if every sack
 *     were as light, or as heavy, as the range says.
 *  2. The farm manager's share, picked one of three ways:
 *       a percent of the gross: S x p sacks, gross kg x p;
 *       a set number of sacks N: N sacks, N x avg kg, and N / S of the harvest;
 *       a set weight K kilos: K kg weighed out, about K / avg sacks, and
 *       K / gross kg of the harvest.
 *  3. The owner's share = the gross less the manager's.
 *  4. Home food: H sacks, out of the owner's share only. More than the share
 *     is refused.
 *  5. What is left to the owner = the owner's share less the home food.
 *  6. Fairness, said beside the numbers: what the manager would have got had
 *     the share been counted after the home food (the difference is H x p,
 *     the owner's rule protects it); the handover in whole sacks plus the
 *     kilos of the part sack (or one more sack and the kilos given back);
 *     and, when sacks differ in weight, that whole sacks are fair only if
 *     weighed, or picked in turns.
 *  7. With a price a kilo (optional), what each share is worth.
 *  8. Paid in money (2026-10-26, the owner: "also show if the farm manager
 *     gets money and not sacks"): the share is the same, counted the same
 *     way, but the owner keeps every sack and pays its worth at the price a
 *     kilo, which is then needed. The owner keeps the gross less the home
 *     food; what is the owner's is that, less the pay (the same worth as the
 *     owner's share in sacks). A percent paid from a sale must also pay the
 *     percent of the home food the owner kept, or the share is counted after
 *     the home food after all; the page says how much.
 */
final class Hatian
{
    /** A sack of fresh palay at the field. */
    public const DEFAULTS = ['kgMin' => 45, 'kgMax' => 55];

    /** How the farm manager's share is set. */
    public const MODES = [
        'pct' => ['A percent of the harvest', 'Like 10%, or a third, counted from all the sacks'],
        'sacks' => ['A set number of sacks', 'A fixed number of sacks, whatever the harvest'],
        'kg' => ['A set weight in kilos', 'A fixed number of kilos, weighed out'],
    ];

    /** How the farm manager is paid. */
    public const PAID = [
        'sacks' => ['In sacks of palay', 'Your farm manager takes the share in sacks'],
        'money' => ['In money', 'You keep all the sacks and pay what the share is worth'],
    ];

    /** The shares people use, for one tap: [percent, words]. */
    public const QUICK = [[10, '10%'], [12.5, '1 in 8'], [20, '1 in 5'], [25, 'A fourth'], [33.333, 'A third'], [50, 'Half']];

    /** The inputs held to their bounds, or null when the harvest or the share is missing. */
    public static function clean(array $in): ?array
    {
        $num = function ($k, float $lo, float $hi, ?float $def = null) use ($in): ?float {
            $v = $in[$k] ?? null;
            if ($v === null || $v === '' || ! is_numeric($v)) {
                return $def;
            }

            return max($lo, min($hi, (float) $v));
        };
        $sacks = $num('sacks', 0, 1000000);
        $lo = $num('kgMin', 1, 150);
        $hi = $num('kgMax', 1, 150);
        // Neither weight: the usual sack; one of them: every sack weighs that.
        [$lo, $hi] = $lo === null && $hi === null ? [self::DEFAULTS['kgMin'], self::DEFAULTS['kgMax']] : [$lo ?? $hi, $hi ?? $lo];
        [$lo, $hi] = [min($lo, $hi), max($lo, $hi)];
        $mode = isset(self::MODES[$in['mode'] ?? '']) ? $in['mode'] : 'pct';
        $share = $num('share', 0, $mode === 'pct' ? 100 : 100000000);
        if (! $sacks || ! $share) {
            return null;
        }

        return [
            'sacks' => $sacks, 'kgMin' => $lo, 'kgMax' => $hi, 'mode' => $mode, 'share' => $share,
            'paid' => isset(self::PAID[$in['paid'] ?? '']) ? $in['paid'] : 'sacks',
            'home' => $num('home', 0, 1000000, 0), 'price' => $num('price', 0, 100000),
            'manager' => mb_substr(trim(strip_tags((string) ($in['manager'] ?? ''))), 0, 60),
        ];
    }

    /** What the inputs cannot give, in words, or ''. */
    public static function problem(array $c, array $r): string
    {
        if ($r['manager']['sacks'] > $c['sacks'] + 1e-9 || $r['manager']['kg'] > $r['gross']['kg'] + 1e-9) {
            return 'The farm manager\'s share is more than the whole harvest.';
        }
        if (($c['paid'] ?? 'sacks') === 'money' && empty($c['price'])) {
            return 'Fill in the price a kilo, to count the pay in money.';
        }
        if ($c['home'] > $r['owner']['sacks'] + 1e-9) {
            return 'Your home food is more than your share. Your share is ' . self::n($r['owner']['sacks']) . ' sacks.';
        }

        return '';
    }

    public static function compute(array $c): array
    {
        $S = (float) $c['sacks'];
        $lo = (float) $c['kgMin'];
        $hi = (float) $c['kgMax'];
        $avg = ($lo + $hi) / 2;
        $gross = ['sacks' => $S, 'kg' => $S * $avg, 'lo' => $S * $lo, 'hi' => $S * $hi];
        $x = (float) $c['share'];
        // The farm manager's share, from the gross.
        if ($c['mode'] === 'pct') {
            $p = $x / 100;
            $m = ['sacks' => $S * $p, 'kg' => $gross['kg'] * $p, 'lo' => $gross['lo'] * $p, 'hi' => $gross['hi'] * $p, 'pct' => $x, 'pctLo' => $x, 'pctHi' => $x];
        } elseif ($c['mode'] === 'sacks') {
            $m = ['sacks' => $x, 'kg' => $x * $avg, 'lo' => $x * $lo, 'hi' => $x * $hi, 'pct' => $x / $S * 100, 'pctLo' => $x / $S * 100, 'pctHi' => $x / $S * 100];
        } else {
            // Kilos weighed out: the same kilos whatever the sacks weigh; the sacks and the share of the harvest move instead.
            $m = ['sacks' => $x / $avg, 'sacksLo' => $x / $hi, 'sacksHi' => $x / $lo, 'kg' => $x, 'lo' => $x, 'hi' => $x,
                'pct' => $x / $gross['kg'] * 100, 'pctLo' => $x / $gross['hi'] * 100, 'pctHi' => $x / $gross['lo'] * 100];
        }
        // The owner's share, the home food out of it, and what is left.
        $o = ['sacks' => $S - $m['sacks'], 'kg' => $gross['kg'] - $m['kg'], 'lo' => $gross['lo'] - $m['lo'], 'hi' => $gross['hi'] - $m['hi']];
        $H = (float) $c['home'];
        $h = ['sacks' => $H, 'kg' => $H * $avg, 'lo' => $H * $lo, 'hi' => $H * $hi];
        $left = ['sacks' => $o['sacks'] - $H, 'kg' => $o['kg'] - $h['kg'], 'lo' => $o['lo'] - $h['lo'], 'hi' => $o['hi'] - $h['hi']];
        $pctOf = fn (float $kg) => $gross['kg'] > 0 ? $kg / $gross['kg'] * 100 : 0;
        // Counted after the home food instead (the way the owner's rule rules out): only a percent changes with it.
        $after = $c['mode'] === 'pct' ? ['sacks' => max(0, $S - $H) * $x / 100] : null;
        // The handover in whole sacks: the full sacks, and the part of one more in kilos; or one more sack and the kilos given back.
        $whole = (int) floor($m['sacks'] + 1e-9);
        $part = $m['sacks'] - $whole;
        $hand = ['whole' => $whole, 'part' => $part, 'partKg' => $part * $avg, 'partLo' => $part * $lo, 'partHi' => $part * $hi,
            'up' => $part > 1e-6 ? $whole + 1 : $whole, 'backKg' => $part > 1e-6 ? (1 - $part) * $avg : 0.0];
        $price = $c['price'] ?? null;
        $worth = fn (array $a) => $price ? ['kg' => $a['kg'] * $price, 'lo' => $a['lo'] * $price, 'hi' => $a['hi'] * $price] : null;
        // Paid in money: the owner keeps every sack but the home food's, and pays the share's worth.
        $pay = null;
        if (($c['paid'] ?? 'sacks') === 'money' && $price) {
            $keep = ['sacks' => $S - $H, 'kg' => $gross['kg'] - $h['kg'], 'lo' => $gross['lo'] - $h['lo'], 'hi' => $gross['hi'] - $h['hi']];
            $pay = $worth($m) + [
                // Every peso on the price moves the pay by the share's kilos.
                'perPeso' => $m['kg'],
                'keep' => $keep + ['worth' => $worth($keep)],
                // A percent paid from what the kept sacks sell for, and the percent of the home food on top.
                'homeTopUp' => $c['mode'] === 'pct' ? ['kg' => $h['kg'] * $x / 100 * $price, 'lo' => $h['lo'] * $x / 100 * $price, 'hi' => $h['hi'] * $x / 100 * $price] : null,
            ];
        }

        return [
            'avg' => $avg, 'even' => $hi - $lo < 1e-9, 'gross' => $gross,
            'manager' => $m + ['worth' => $worth($m)],
            'owner' => $o + ['pct' => $pctOf($o['kg']), 'worth' => $worth($o)],
            'home' => $h + ['pct' => $pctOf($h['kg']), 'worth' => $worth($h)],
            'left' => $left + ['pct' => $pctOf($left['kg']), 'worth' => $worth($left)],
            'after' => $after ? $after + ['less' => $m['sacks'] - $after['sacks'], 'lessKg' => ($m['sacks'] - $after['sacks']) * $avg] : null,
            'hand' => $hand,
            'pay' => $pay,
            // On the light end, a set weight can take more of the harvest than the middle shows.
            'tightAtLight' => $c['mode'] === 'kg' && $left['lo'] < -1e-6,
        ];
    }

    /** The saved calculation's name: who gets what, of how many sacks. */
    public static function title(array $c, array $r): string
    {
        $who = $c['manager'] !== '' ? $c['manager'] : 'Farm manager';
        $share = match ($c['mode']) {
            'pct' => self::n($c['share']) . '%',
            'sacks' => self::n($c['share']) . ' ' . ($c['share'] == 1 ? 'sack' : 'sacks'),
            default => self::n($c['share']) . ' kg',
        };

        return 'Hatian · ' . self::n($c['sacks']) . ' sacks · ' . $who . ' ' . $share . (($c['paid'] ?? 'sacks') === 'money' ? ' in money' : '') . ($c['home'] > 0 ? ' · ' . self::n($c['home']) . ' for home' : '');
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ','), '0'), '.');
    }
}
