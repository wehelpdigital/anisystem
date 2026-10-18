<?php

namespace App\Support;

/**
 * Benta o Bilad (2026-10-19, the owner): sell the palay wet, straight from the
 * harvester, or dry it first and sell it dry? Which leaves more money.
 *
 * The arithmetic, the same in the page (resources/views/benta-bilad/index,
 * calc()) and here (what a saved calculation keeps):
 *
 *  1. The harvester is paid first, the same either way: a share of the sacks
 *     (a percent, or a set number of sacks, taken wet at the field), or cash.
 *  2. The sacks left weigh sacks x kg a sack: that is the wet palay.
 *  3. Drying takes out water, from the moisture at harvest down to the moisture
 *     of dry palay: dry weight = wet weight x (100 - wet %) / (100 - dry %),
 *     and a little more is lost on the drying floor (spilled, birds, rain).
 *     Or, by the weight of a sack (2026-10-19, the owner): a sack that weighs
 *     W kg wet weighs D kg once dry, D a range (low to high), the floor's loss
 *     already in it; dry weight = wet weight x D / W, counted at the middle of
 *     the range, with both ends said beside it.
 *  4. Sell wet: wet kg x wet price, less the harvester's cash.
 *     Dry then sell: dry kg x dry price, less the drying labor, the other
 *     drying costs and the harvester's cash.
 *  5. The difference decides; under about 1% of the money (or 200 pesos) it is
 *     a draw. The break even dry price is the dry price at which both pay the
 *     same: drying pays only above it.
 */
final class BentaBilad
{
    /** What a field starts on. */
    public const DEFAULTS = ['kgPerSack' => 50, 'wetMoisture' => 24, 'dryMoisture' => 14, 'lossPct' => 1];

    /** How the harvester is paid. */
    public const HARVESTER = [
        'pct' => ['A share of the sacks', 'A percent of the sacks harvested, like 1 in every 10'],
        'sacks' => ['A set number of sacks', 'A fixed number of sacks, whatever the harvest'],
        'cash' => ['A set amount in pesos', 'Paid in cash, for the whole field'],
    ];

    /** How the drying labor is paid. */
    public const LABOR = [
        'sack' => ['Per sack', 'A rate for every sack dried'],
        'day' => ['Per day', 'A daily wage, for so many workers and days'],
        'total' => ['For the whole job', 'One amount for all the drying'],
    ];

    public const AREA_UNITS = ['ha' => ['Hectares', 'ha'], 'sqm' => ['Square meters', 'm²']];

    /** How the drying's weight is counted (2026-10-19, the owner): the farmer picks one. */
    public const DRY_BY = [
        'pct' => ['By moisture', 'The moisture at harvest and when dry, and the share lost while drying'],
        'kg' => ['By the weight of a sack', 'What one sack weighs wet, and what it weighs once dry'],
    ];

    /** A draw is closer than this share of the money, or this many pesos. */
    public const EVEN_SHARE = 0.01;

    public const EVEN_PESOS = 200;

    /** The standard weight of a sack of dry palay, for "about so many dry sacks". */
    public const DRY_SACK_KG = 50;

    /** Inputs read and held to sensible ranges; null when a must is missing. */
    public static function clean(array $in): ?array
    {
        $n = fn ($k, $min, $max, $def = null) => isset($in[$k]) && is_numeric($in[$k]) ? max($min, min($max, (float) $in[$k])) : $def;
        $c = [
            'sacks' => $n('sacks', 0, 1000000),
            'kgPerSack' => $n('kgPerSack', 10, 150, self::DEFAULTS['kgPerSack']),
            'area' => $n('area', 0, 1000000),
            'areaUnit' => ($in['areaUnit'] ?? 'ha') === 'sqm' ? 'sqm' : 'ha',
            'harvesterMode' => array_key_exists($in['harvesterMode'] ?? '', self::HARVESTER) ? $in['harvesterMode'] : 'pct',
            'harvester' => $n('harvester', 0, 100000000, 0),
            'laborMode' => array_key_exists($in['laborMode'] ?? '', self::LABOR) ? $in['laborMode'] : 'sack',
            'laborRate' => $n('laborRate', 0, 100000000, 0),
            'laborWorkers' => $n('laborWorkers', 0, 1000, 1),
            'laborDays' => $n('laborDays', 0, 365, 1),
            'otherCost' => $n('otherCost', 0, 100000000, 0),
            'wetMoisture' => $n('wetMoisture', 10, 45, self::DEFAULTS['wetMoisture']),
            'dryMoisture' => $n('dryMoisture', 5, 25, self::DEFAULTS['dryMoisture']),
            'lossPct' => $n('lossPct', 0, 30, self::DEFAULTS['lossPct']),
            'dryBy' => ($in['dryBy'] ?? 'pct') === 'kg' ? 'kg' : 'pct',
            'dryKgLo' => $n('dryKgLo', 1, 150),
            'dryKgHi' => $n('dryKgHi', 1, 150),
            'priceWet' => $n('priceWet', 0, 100000),
            'priceDry' => $n('priceDry', 0, 100000),
        ];
        if ($c['harvesterMode'] === 'pct') {
            $c['harvester'] = min(90, $c['harvester']);
        }
        if ($c['dryBy'] === 'kg') {
            // The range reads either way round; one figure is a range of one.
            $lo = $c['dryKgLo'] ?? $c['dryKgHi'];
            $hi = $c['dryKgHi'] ?? $lo;
            $c['dryKgLo'] = $lo !== null ? min($lo, $hi) : null;
            $c['dryKgHi'] = $lo !== null ? max($lo, $hi) : null;
            if ($c['dryKgLo'] === null || $c['dryKgHi'] >= $c['kgPerSack']) {
                return null;
            }
        } elseif ($c['dryMoisture'] >= $c['wetMoisture']) {
            return null;
        }
        if (! $c['sacks'] || ! $c['priceWet'] || ! $c['priceDry']) {
            return null;
        }

        return $c;
    }

    /** The whole comparison, from cleaned inputs. */
    public static function compute(array $c): array
    {
        $H = $c['sacks'];
        $W = $c['kgPerSack'];
        $areaHa = $c['area'] ? ($c['areaUnit'] === 'sqm' ? $c['area'] / 10000 : $c['area']) : null;
        // 1. The harvester, paid the same either way.
        $hSacks = match ($c['harvesterMode']) {
            'pct' => $H * $c['harvester'] / 100,
            'sacks' => min($H, $c['harvester']),
            default => 0.0,
        };
        $hCash = $c['harvesterMode'] === 'cash' ? $c['harvester'] : 0.0;
        $left = max(0.0, $H - $hSacks);
        // 2 and 3. Wet and dry weights: by moisture, or by the weight of a sack (its middle; the loss is in it).
        $wetKg = $left * $W;
        $byKg = ($c['dryBy'] ?? 'pct') === 'kg';
        $keep = $byKg ? (($c['dryKgLo'] + $c['dryKgHi']) / 2) / $W : (100 - $c['wetMoisture']) / (100 - $c['dryMoisture']);
        $afterWater = $wetKg * $keep;
        $lossKg = $byKg ? 0.0 : $afterWater * $c['lossPct'] / 100;
        $dryKg = $afterWater - $lossKg;
        // The drying bill.
        $labor = match ($c['laborMode']) {
            'sack' => $c['laborRate'] * $left,
            'day' => $c['laborRate'] * $c['laborWorkers'] * $c['laborDays'],
            default => $c['laborRate'],
        };
        $dryCost = $labor + $c['otherCost'];
        // 4. Both ways.
        $wetGross = $wetKg * $c['priceWet'];
        $dryGross = $dryKg * $c['priceDry'];
        $netWet = $wetGross - $hCash;
        $netDry = $dryGross - $dryCost - $hCash;
        // 5. The answer.
        $diff = $netDry - $netWet;
        $even = max(self::EVEN_PESOS, self::EVEN_SHARE * max(abs($netWet), abs($netDry)));
        $verdict = $diff > $even ? 'dry' : ($diff < -$even ? 'wet' : 'even');
        $r = fn ($v, $d = 2) => $v === null ? null : round($v, $d);

        return [
            'verdict' => $verdict,
            'diff' => $r($diff),
            'harvesterSacks' => $r($hSacks),
            'harvesterCash' => $r($hCash),
            'harvesterWorth' => $r($hSacks * $W * $c['priceWet']),
            'sacksLeft' => $r($left),
            'wetKg' => $r($wetKg, 1),
            'waterKg' => $r($wetKg - $afterWater, 1),
            'lossKg' => $r($lossKg, 1),
            'dryKg' => $r($dryKg, 1),
            'drySacks' => $r($dryKg / self::DRY_SACK_KG, 1),
            'shrinkPct' => $wetKg > 0 ? $r((1 - $dryKg / $wetKg) * 100, 1) : null,
            'labor' => $r($labor),
            'dryCost' => $r($dryCost),
            'wetGross' => $r($wetGross),
            'dryGross' => $r($dryGross),
            'netWet' => $r($netWet),
            'netDry' => $r($netDry),
            // The dry price at which both pay the same, and the wet price at which they do.
            'breakEvenDry' => $dryKg > 0 ? $r(($wetGross + $dryCost) / $dryKg) : null,
            'breakEvenWet' => $wetKg > 0 ? $r(($dryGross - $dryCost) / $wetKg) : null,
            'areaHa' => $areaHa !== null ? $r($areaHa, 4) : null,
            'sacksPerHa' => $areaHa ? $r($H / $areaHa, 1) : null,
            'netWetPerHa' => $areaHa ? $r($netWet / $areaHa) : null,
            'netDryPerHa' => $areaHa ? $r($netDry / $areaHa) : null,
            // By the weight of a sack: the dry sack's weight at which both pay the same, and both ends of the range.
            'breakEvenSackKg' => $left > 0 && $c['priceDry'] > 0 ? $r(($wetGross + $dryCost) / $c['priceDry'] / $left) : null,
            'range' => $byKg && $c['dryKgHi'] > $c['dryKgLo'] ? [
                'lo' => self::end(self::compute(['dryKgHi' => $c['dryKgLo']] + $c)),
                'hi' => self::end(self::compute(['dryKgLo' => $c['dryKgHi']] + $c)),
            ] : null,
        ];
    }

    /** One end of a dry weight range: what the farmer reads beside the middle. */
    private static function end(array $r): array
    {
        return array_intersect_key($r, array_flip(['verdict', 'diff', 'dryKg', 'netDry']));
    }

    /** A saved calculation's name: "120 sacks · dry it · Oct 19". */
    public static function title(array $c, array $res): string
    {
        $word = ['dry' => 'dry it', 'wet' => 'sell it wet', 'even' => 'about the same'][$res['verdict']] ?? '';

        return rtrim(rtrim(number_format($c['sacks'], 1), '0'), '.') . ' sacks · ' . $word . ' · ' . now('Asia/Manila')->format('M j');
    }
}
