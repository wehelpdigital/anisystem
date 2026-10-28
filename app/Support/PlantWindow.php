<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * The best weeks to plant a lot, from today to four months ahead (2026-10-28, the owner: "after selecting the crop, and the
 * lot (which includes the characteristics), anee should be able to provide an insight (similar to the when to plant
 * analysis using the same process) when is the best date to plant (always from the current date to 1 year range),
 * before the user selects a day when to plant ... also have 2 considerations, the price forecast upon harvest, and the
 * usual. ... upon selecting the date, have a dynamic calculation on when it will be harvested").
 *
 * The same shelves When to Plant reads, counted without a model so the farmer sees it at once and pays nothing: the
 * field's own twenty years of weekly weather (ClimateStore::weekTable: rain, the water the air pulls, the hot days),
 * the town's storm record (HazardBook::odds), the crop's stage table (CropStages) for when it flowers and is
 * harvested, the water the lot really has (AutoBook::WATER: a canal that comes on time carries the dry weeks, rain
 * only carries none), and the farmgate prices at harvest (MarketBook::harvestValue) for the second ranking.
 *
 * Each planting week is lived through every year on record: the first two weeks (too dry to come up, or washed out),
 * the weeks before flowering (short of water), the two flowering weeks (dry, hot or battered), the harvest weeks (rain
 * on the cut crop) and the storms of the flowering and harvest months. The score is 100 less the share of that harm
 * the years held. The price ranking is that score times what the harvest brings against a usual harvest month, and a
 * week under 50 never moves above a safer one.
 *
 * Four months, not a year (the same day, the owner: "1 year is too much. let's do it 4 months and the analysis should
 * show the best date"): a farmer setting up a season plants within it. The harvest price is weighed against a usual
 * month of the whole year ahead, so a short window still knows a good price from a poor one.
 */
final class PlantWindow
{
    /** How much of a dry spell the lot's water carries: rainfed feels all of it, a reliable canal or pump little. */
    private const WATER_SHARE = ['irrigated' => 0.15, 'partial' => 0.55, 'rainfed' => 1.0];

    /** The weight of each harm in the score. */
    private const W = ['est' => 0.9, 'flood' => 0.5, 'veg' => 0.5, 'flowerDry' => 1.0, 'heat' => 0.6, 'flowerRain' => 0.3, 'harvestRain' => 0.9, 'storm' => 1.0];

    /** A stage that is the crop's flowering, by its name in the stage table. */
    private const FLOWER = '/flower|heading|tassel|silk|pollinat|anthesis|fruit set|booting/i';

    /**
     * $p: crop, method (transplanted|direct|planted), maturity (days to harvest as the lot counts them), nursery
     * (seedbed days for a transplanted crop), lat, lng, location ("Town, Province"), water (AutoBook::WATER key),
     * prepNeed (days of land preparation before planting), today (Y-m-d, Manila).
     *
     * @return array{weeks: list<array>, best: list<int>, bestPrice: list<int>, earliest: string, years: int, place: string, waterSays: string, market: ?array}|null
     */
    public static function for(array $p): ?array
    {
        $crop = (string) ($p['crop'] ?? '');
        if ($crop === '' || ($p['method'] ?? '') === 'tree') {
            return null;
        }
        $loc = ClimateStore::locate((string) ($p['location'] ?? ''), isset($p['lat']) ? (float) $p['lat'] : null, isset($p['lng']) ? (float) $p['lng'] : null, false);
        if (! $loc) {
            return null;
        }
        $wt = ClimateStore::weekTable((float) $loc['lat'], (float) $loc['lng']);
        if (! $wt) {
            return null;
        }
        // Whole years only (the running year is partial).
        $years = array_values(array_filter(array_keys($wt), fn ($y) => count($wt[$y]) >= 50 && isset($wt[$y + 1])));
        if (count($years) < 5) {
            return null;
        }
        $today = Carbon::parse($p['today'] ?? now('Asia/Manila')->toDateString(), 'Asia/Manila')->startOfDay();
        $isRice = str_starts_with($crop, 'rice');
        $counter = ($p['method'] ?? '') === 'transplanted' ? 'DAT' : 'DAS';
        $maturity = max(40, (int) ($p['maturity'] ?? CropCatalog::maturity($crop) ?? 100));
        // The field days to harvest: a transplanted rice spent its first weeks in the seedbed.
        $toHarvest = ($p['method'] ?? '') === 'transplanted' && $isRice ? max(60, $maturity - (int) ($p['nursery'] ?? 18)) : $maturity;
        $stages = CropStages::stagesFor($crop, $counter, $maturity);
        $flower = null;
        foreach ($stages as $st) {
            if (preg_match(self::FLOWER, (string) ($st[1] ?? ''))) {
                $flower = (int) $st[0];
                break;
            }
        }
        // No named flowering: about 55% of the way to harvest.
        $flower ??= (int) round($toHarvest * 0.55);
        $flower = min($flower, $toHarvest - 14);
        $kind = AutoBook::WATER[$p['water'] ?? ''][2] ?? 'rainfed';
        $share = self::WATER_SHARE[$kind] ?? 1.0;
        // A paddy needs standing water to take hold; a dry seeded or planted crop only damp soil.
        $estNeed = $isRice ? 50 : 25;
        $odds = HazardBook::odds((string) ($p['location'] ?? ''), (float) $loc['lat'], (float) $loc['lng']);
        $storm = fn (Carbon $d) => $odds ? ((int) ($odds['months'][$d->month]['chance'] ?? 0) + 15 * (int) ($odds['months'][$d->month]['severe'] ?? 0) / max(1, (int) $odds['years'])) / 100 : 0.0;

        // The sum of a stretch of field days in a year, by the weeks it covers (a stretch that runs into the next year reads it).
        $sum = function (int $year, Carbon $plant, int $a, int $b) use ($wt): array {
            $rain = 0.0;
            $et0 = 0.0;
            $hot = 0;
            $d = $plant->copy()->addDays($a);
            $end = $plant->copy()->addDays($b);
            $yearShift = $year - $plant->year;
            while ($d->lte($end)) {
                $y = $d->year + $yearShift;
                $w = min(53, intdiv($d->dayOfYear - 1, 7) + 1);
                $r = $wt[$y][$w] ?? $wt[$y][$w - 1] ?? null;
                if ($r) {
                    $f = 1 / max(1, (int) $r['days'] ?: 7);
                    $rain += $r['rain'] * $f;
                    $et0 += $r['et0'] * $f;
                    $hot += $r['hot35'] * $f;
                }
                $d->addDay();
            }

            return [$rain, $et0, $hot];
        };
        $clamp = fn ($v) => max(0.0, min(1.0, (float) $v));
        $prepNeed = max(0, (int) ($p['prepNeed'] ?? 0));
        $earliest = $today->copy()->addDays($prepNeed);

        $span = max(8, (int) ceil($today->diffInDays($today->copy()->addMonthsNoOverflow(4)) / 7));
        $weeks = [];
        for ($k = 0; $k < $span; $k++) {
            $plant = $today->copy()->addDays(7 * $k + 3);
            $harvest = $plant->copy()->addDays($toHarvest);
            $harm = array_fill_keys(array_keys(self::W), 0.0);
            $bad = array_fill_keys(array_keys(self::W), 0);
            foreach ($years as $y) {
                [$r0] = $sum($y, $plant, 0, 13);
                $h = [
                    'est' => $clamp(($estNeed - $r0) / $estNeed) * $share,
                    'flood' => $clamp(($r0 - 220) / 220),
                ];
                [$rv, $ev] = $sum($y, $plant, 14, max(15, $flower - 1));
                $kc = $isRice ? 1.1 : 0.85;
                $h['veg'] = $ev > 0 ? $clamp((($ev * $kc) - $rv) / ($ev * $kc)) * $share : 0.0;
                [$rf, $ef, $hf] = $sum($y, $plant, $flower, $flower + 13);
                $h['flowerDry'] = $ef > 0 ? $clamp((($ef * $kc) - $rf) / ($ef * $kc)) * $share : 0.0;
                $h['heat'] = $clamp(($hf - 2) / 5);
                $h['flowerRain'] = $clamp(($rf - 180) / 200);
                [$rh] = $sum($y, $plant, $toHarvest - 7, $toHarvest + 6);
                $h['harvestRain'] = $clamp(($rh - 70) / 130);
                foreach ($h as $key => $v) {
                    $harm[$key] += $v;
                    if ($v >= 0.5) {
                        $bad[$key]++;
                    }
                }
            }
            $n = count($years);
            foreach ($harm as $key => $v) {
                $harm[$key] = $v / $n;
            }
            $flowerDay = $plant->copy()->addDays($flower);
            $harm['storm'] = $clamp(0.4 * $storm($flowerDay) + 0.6 * $storm($harvest->copy()->subDays(5)));
            $total = 0.0;
            foreach (self::W as $key => $w) {
                $total += $w * $harm[$key];
            }
            $score = (int) max(0, min(100, round(100 * (1 - $total / 2.6))));
            $weeks[] = ['date' => $plant->toDateString(), 'harvest' => $harvest->toDateString(), 'flower' => $flowerDay->toDateString(), 'score' => $score,
                'why' => self::why($harm, $bad, $n, $flowerDay, $harvest, $odds), 'prepShort' => $plant->lt($earliest)];
        }

        // The second ranking: the score times what the harvest brings against a usual harvest month, a usual month being
        // the middle of the twelve from the first harvest on.
        $market = null;
        $where = (string) ($p['location'] ?? '');
        foreach ($weeks as $i => $w) {
            $h = Carbon::parse($w['harvest']);
            $v = MarketBook::harvestValue($crop, $where, $h->year * 12 + $h->month - 1);
            if ($v) {
                $weeks[$i]['net'] = $v['net'];
                $weeks[$i]['p'] = round($v['p'], 2);
                $weeks[$i]['unit'] = $v['unit'];
            }
        }
        $first = Carbon::parse($weeks[0]['harvest'] ?? $today->toDateString());
        $vals = [];
        for ($m = 0; $m < 12; $m++) {
            $v = MarketBook::harvestValue($crop, $where, $first->year * 12 + $first->month - 1 + $m);
            if ($v) {
                $vals[] = $v['net'];
            }
        }
        if (count($vals) >= 8 && array_filter($weeks, fn ($w) => isset($w['net']))) {
            $sorted = $vals;
            sort($sorted);
            $mid = $sorted[intdiv(count($sorted), 2)];
            foreach ($weeks as $i => $w) {
                if (isset($w['net']) && $mid > 0) {
                    $weeks[$i]['rel'] = round($w['net'] / $mid, 3);
                    $weeks[$i]['priceScore'] = (int) max(0, min(100, round($w['score'] * $w['net'] / $mid)));
                }
            }
            $market = ['item' => MarketBook::cropWord($crop)];
        }

        $rank = function (string $by) use ($weeks): array {
            $idx = array_keys(array_filter($weeks, fn ($w) => ! $w['prepShort'] && isset($w[$by])));
            // A week Anee scores under 50 never moves above a safer one; between near equals, the nearer week first.
            $key = fn ($i) => [$weeks[$i]['score'] >= 50, $weeks[$i][$by] - 0.06 * $i];
            usort($idx, fn ($a, $b) => $key($b) <=> $key($a));
            // Three weeks at least three weeks apart, so the choices are real choices; the best first.
            $out = [];
            foreach ($idx as $i) {
                if (count($out) === 3) {
                    break;
                }
                if (! array_filter($out, fn ($j) => abs($j - $i) < 3)) {
                    $out[] = $i;
                }
            }

            return $out;
        };
        foreach ($weeks as $i => $w) {
            unset($weeks[$i]['net']);
        }

        return [
            'weeks' => $weeks, 'best' => $rank('score'), 'bestPrice' => $market ? $rank('priceScore') : [],
            'earliest' => $earliest->toDateString(), 'prepNeed' => $prepNeed, 'years' => count($years),
            'place' => (string) ($odds['place'] ?? $loc['label'] ?? ''), 'toHarvest' => $toHarvest, 'flowerDay' => $flower,
            'waterSays' => ['irrigated' => 'Your water carries the dry spells.', 'partial' => 'Your water carries part of a dry spell.', 'rainfed' => 'The crop lives on the rain, so the dry spells weigh fully.'][$kind] ?? '',
            'market' => $market,
        ];
    }

    /** The two harms that weigh most on a week, in plain words. */
    private static function why(array $harm, array $bad, int $n, Carbon $flower, Carbon $harvest, ?array $odds): array
    {
        $say = [
            'est' => fn () => 'too dry to take hold in ' . $bad['est'] . ' of ' . $n . ' years',
            'flood' => fn () => 'heavy rain on the young crop in ' . $bad['flood'] . ' of ' . $n . ' years',
            'veg' => fn () => 'short of water while growing in ' . $bad['veg'] . ' of ' . $n . ' years',
            'flowerDry' => fn () => 'dry at flowering (' . $flower->format('M j') . ') in ' . $bad['flowerDry'] . ' of ' . $n . ' years',
            'heat' => fn () => 'hot days at flowering in ' . $bad['heat'] . ' of ' . $n . ' years',
            'flowerRain' => fn () => 'hard rain at flowering in ' . $bad['flowerRain'] . ' of ' . $n . ' years',
            'harvestRain' => fn () => 'rain on the harvest (' . $harvest->format('M j') . ') in ' . $bad['harvestRain'] . ' of ' . $n . ' years',
            'storm' => function () use ($odds, $flower, $harvest) {
                $f = (int) ($odds['months'][$flower->month]['chance'] ?? 0);
                $h = (int) ($odds['months'][$harvest->month]['chance'] ?? 0);

                return $h >= $f ? 'storms at harvest, in ' . $harvest->format('F') . ' (' . $h . '% of years)' : 'storms at flowering, in ' . $flower->format('F') . ' (' . $f . '% of years)';
            },
        ];
        $w = [];
        foreach (self::W as $k => $weight) {
            $w[$k] = $weight * $harm[$k];
        }
        arsort($w);
        $out = [];
        foreach ($w as $k => $v) {
            if ($v < 0.05 || count($out) === 2) {
                break;
            }
            if (($k === 'storm' && $odds) || ($k !== 'storm' && $bad[$k] > 0)) {
                $out[] = $say[$k]();
            }
        }

        return $out;
    }
}
