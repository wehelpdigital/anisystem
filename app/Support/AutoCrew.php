<?php

namespace App\Support;

use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsScheduleLot;
use App\Services\AutoPlanner;
use Carbon\Carbon;

/**
 * Is the crew enough? (2026-10-28, the owner: "anee should also check if the # of workers are enough, or no based on
 * their availability as well"; "if user decided to add more lot, then anee should auto adjust the schedule and process
 * based on the lots and # of workers").
 *
 * The busiest days of a season are the planting and the harvest: each takes a set number of person days a hectare
 * and must be done within a few days (seedlings age, the grain shatters). Lots that plant or harvest in the same week
 * share the crew. The crew on a day is the workers who come that weekday (a half day counts half) and the farmer.
 * Arithmetic only, from the lots and the workers as they stand.
 */
final class AutoCrew
{
    /** Person days a hectare and the days it should take: [plant, plantDays, plantWord, harvest, harvestDays, harvestWord]. */
    private const RATES = [
        'rice:transplanted' => [20, 3, 'transplanting by hand', 15, 4, 'harvest by hand'],
        'rice:transplanter' => [3, 2, 'transplanting with the machine', 15, 4, 'harvest by hand'],
        'rice:direct' => [2, 2, 'sowing', 15, 4, 'harvest by hand'],
        'corn' => [5, 3, 'planting', 10, 5, 'harvest by hand'],
        'legume' => [5, 3, 'planting', 12, 5, 'pulling and threshing'],
        'root' => [10, 4, 'planting', 15, 6, 'digging'],
        'vegetable' => [14, 3, 'planting', 6, 2, 'each picking'],
    ];

    /**
     * The peaks of the season and whether the crew covers each.
     *
     * @return array{crew: float, peaks: list<array{lots: list<string>, what: string, from: string, need: float, perDay: float, days: int, window: int, ok: bool, say: string}>, say: string}
     */
    public static function check(AsAutoSeason $auto): array
    {
        $people = AutoPlanner::people($auto)->all();
        $o = AutoOptions::of((array) $auto->answers);
        $machine = (bool) array_filter((array) $o['fleet'], fn ($f) => $f['type'] === 'transplanter');
        $events = [];
        foreach (AsAutoLot::where('autoId', $auto->id)->get() as $al) {
            $lot = AsScheduleLot::find($al->lotId);
            if (! $lot || ! $lot->crop || ! $al->fieldDate || $al->method === 'tree') {
                continue;
            }
            $ha = $lot->lotSize ? (float) $lot->lotSize * match ($lot->lotSizeUnit) { 'sqm' => 0.0001, 'acre' => 0.404686, default => 1 } : 0.0;
            if ($ha <= 0) {
                continue;
            }
            $r = self::rates((string) $lot->crop, (string) $al->method, $machine);
            $field = Carbon::parse($al->fieldDate->toDateString(), 'Asia/Manila');
            $days = $al->harvestDate ? $field->diffInDays($al->harvestDate) : (int) ($lot->maturityDays() ?: CropCatalog::maturity($lot->crop) ?: 100);
            $events[] = ['lot' => $lot->lotName, 'what' => $r[2], 'from' => $field, 'need' => $r[0] * $ha, 'window' => $r[1]];
            $events[] = ['lot' => $lot->lotName, 'what' => $r[5], 'from' => $field->copy()->addDays($days), 'need' => $r[3] * $ha, 'window' => $r[4]];
        }
        // Work of the same kind that starts within a week of another shares the crew.
        usort($events, fn ($a, $b) => $a['from'] <=> $b['from']);
        $peaks = [];
        foreach ($events as $e) {
            $last = $peaks ? count($peaks) - 1 : null;
            if ($last !== null && $peaks[$last]['what'] === $e['what'] && $e['from']->diffInDays($peaks[$last]['fromC']) <= 7) {
                $peaks[$last]['lots'][] = $e['lot'];
                $peaks[$last]['need'] += $e['need'];
                $peaks[$last]['window'] = max($peaks[$last]['window'], $e['window']) + 1;
                continue;
            }
            $peaks[] = ['lots' => [$e['lot']], 'what' => $e['what'], 'fromC' => $e['from'], 'need' => $e['need'], 'window' => $e['window']];
        }
        $out = [];
        foreach ($peaks as $p) {
            // The crew on the days of that week: the workers who come that weekday and the farmer.
            $per = [];
            for ($i = 0; $i < 7; $i++) {
                $per[] = 1 + self::onDay($people, $p['fromC']->copy()->addDays($i));
            }
            $perDay = round(array_sum($per) / count($per), 1);
            $days = (int) ceil($p['need'] / max(0.5, $perDay));
            $ok = $days <= $p['window'];
            $more = $ok ? 0 : (int) ceil($p['need'] / $p['window'] - $perDay);
            $who = self::andList($p['lots']);
            $out[] = ['lots' => $p['lots'], 'what' => $p['what'], 'from' => $p['fromC']->toDateString(), 'need' => round($p['need'], 1), 'perDay' => $perDay, 'days' => $days, 'window' => $p['window'], 'ok' => $ok, 'more' => $more,
                'say' => ucfirst($p['what']) . ' for ' . $who . ' (' . $p['fromC']->format('M j') . ') takes about ' . self::num($p['need']) . ' person days. '
                    . ($ok ? 'Your crew of about ' . self::num($perDay) . ' a day does it in ' . $days . ($days === 1 ? ' day' : ' days') . ': enough.'
                        : 'Your crew of about ' . self::num($perDay) . ' a day needs ' . $days . ' days, but it should be done in ' . $p['window'] . '. Hire about ' . $more . ' more for those days'
                            . (str_contains($p['what'], 'transplanting by hand') ? ', or a rice transplanter' : (str_contains($p['what'], 'harvest') ? ', or a hired harvester' : '')) . '.')];
        }
        $short = array_filter($out, fn ($x) => ! $x['ok']);
        $crew = $people ? round(array_sum(array_map(fn ($d) => 1 + self::onDay($people, Carbon::now('Asia/Manila')->startOfWeek()->addDays($d)), range(0, 6))) / 7, 1) : 1.0;

        return ['crew' => $crew, 'peaks' => $out,
            'say' => ! $out ? '' : ($short ? count($short) . ' of the busiest stretches need more hands than your crew has.' : 'Your crew covers the planting and the harvest of every lot.')];
    }

    /** How many workers come on a day: a whole day 1, a half day half. */
    private static function onDay(array $people, Carbon $d): float
    {
        $n = 0.0;
        $dow = (int) $d->dayOfWeek;
        foreach ($people as $p) {
            if (in_array($dow, (array) ($p['off'] ?? []), true)) {
                continue;
            }
            $part = (array) ($p['parts'] ?? []);
            $n += in_array($part[$dow] ?? ($part[(string) $dow] ?? 'whole'), ['am', 'pm'], true) ? 0.5 : 1.0;
        }

        return $n;
    }

    private static function rates(string $crop, string $method, bool $machine): array
    {
        if (str_starts_with($crop, 'rice')) {
            return self::RATES[$method === 'transplanted' ? ($machine ? 'rice:transplanter' : 'rice:transplanted') : 'rice:direct'];
        }
        if (str_starts_with($crop, 'corn') || $crop === 'sorghum') {
            return self::RATES['corn'];
        }
        $g = LandPrepPlanner::groupFor($crop);

        return self::RATES[match ($g) { 'legume' => 'legume', 'root' => 'root', default => 'vegetable' }];
    }

    private static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    private static function andList(array $a): string
    {
        $a = array_values(array_unique($a));

        return count($a) < 2 ? (string) ($a[0] ?? '') : implode(', ', array_slice($a, 0, -1)) . ' and ' . end($a);
    }
}
