<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleLot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The products planned in the Coverage Planner, put on the season's plan (2026-10-30, the owner: "have another button
 * to apply the product you plan to apply listed into the current activity plan (do not remove the exising items in
 * the activities, and do not duplicate the fertilizer in the activity day if its already there ... since its already
 * there in the activity then no need to add it in the activity").
 *
 * Each product goes on its own day, for the lot:
 *   there   the lot's tasks that day already carry it, whatever the amount: left as it is, and said back;
 *   join    a task of its kind is on that day for the lot and not done: it goes in as one more line;
 *   new     else a task of its kind is made that day for the lot, one for every product of that kind that day;
 *   twice   the same product on the same day twice in the list: it goes on once.
 * Nothing on the board is removed or changed besides the lines added. A product with no amount goes on as "1 dose",
 * which the planner reads as one usual dose (as it read the plan), with a note to put the amount in. The lines are
 * plain lines (no shed item): the farmer links them to the shed and gives them to workers in Activities.
 */
class RangeEffectApply
{
    public const TITLES = ['fertilizer' => 'Fertilizer application', 'foliar_spray' => 'Foliar spray', 'herbicide' => 'Herbicide spray',
        'pesticide' => 'Insecticide spray', 'fungicide' => 'Fungicide spray', 'microbial' => 'Microbial application', 'other' => 'Application'];

    /** The kind of task a product goes on as, from the book's type and how the farmer puts it on. */
    public static function taskType(string $t, ?string $method): string
    {
        return match (true) {
            $t === 'herbicide' => 'herbicide',
            in_array($t, ['insecticide', 'acaricide', 'molluscicide', 'nematicide', 'rodenticide'], true) => 'pesticide',
            $t === 'fungicide' => 'fungicide',
            $t === 'biological' => 'microbial',
            $method === 'foliar' || ($method !== 'granular' && in_array($t, ['foliar', 'soluble', 'biostimulant', 'pgr'], true)) => 'foliar_spray',
            $method === 'granular' || in_array($t, ['granular', 'amendment', ''], true) => 'fertilizer',
            default => 'other',
        };
    }

    /**
     * What each product would do, or does ($write): rows in the list's order and the counts.
     *
     * @param  array  $tries  [{productId?, ai?, name, date, qty, unit, method, t}]
     * @return array{rows: array, made: array{tasks:int, lines:int, there:int, twice:int, bad:int}}
     */
    public static function run(AsCroppingSchedule $s, AsScheduleLot $lot, array $tries, bool $write): array
    {
        $want = [];
        foreach (array_values($tries) as $i => $t) {
            $date = (string) ($t['date'] ?? '');
            $p = ! empty($t['productId']) ? RangeEffect::productOf((int) $t['productId']) : null;
            $ai = trim((string) ($t['ai'] ?? ''));
            $ing = $ai !== '' ? CompatBook::ingredient($ai) : null;
            $name = $p ? (string) $p['b'] : ($ai !== '' ? (string) ($ing['name'] ?? $ai) : trim((string) ($t['name'] ?? '')));
            $qty = max(0, (float) ($t['qty'] ?? 0));
            $unit = mb_substr(trim((string) ($t['unit'] ?? '')), 0, 30);
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $name === '') {
                $want[] = ['i' => $i, 'name' => $name ?: 'A product', 'date' => $date, 'status' => 'bad', 'task' => '', 'note' => 'It has no day or no name.', 'qty' => $qty, 'unit' => $unit];

                continue;
            }
            $kind = $p ? (string) ($p['t'] ?? '') : (string) ($t['t'] ?? ($ing['kind'] ?? ''));
            $method = in_array($t['method'] ?? null, ['granular', 'foliar'], true) ? $t['method'] : null;
            $keys = array_values(array_unique(array_filter([ProductLibrary::key($name), ProductLibrary::key((string) ($t['name'] ?? '')), $ai !== '' ? ProductLibrary::key($ai) : ''],
                fn ($k) => strlen($k) >= 2)));
            // An ingredient on its own is on the plan in any line that starts with it ("Tricyclazole 75 WP").
            $aiKey = ! $p && $ai !== '' ? ProductLibrary::key($name) : '';
            $want[] = ['i' => $i, 'name' => $name, 'date' => $date, 'pid' => $p ? (int) ($p['id'] ?? 0) : 0, 'keys' => $keys, 'aiKey' => $aiKey, 'type' => self::taskType($kind, $method),
                'qty' => $qty, 'unit' => $unit, 'status' => null, 'task' => '', 'note' => ''];
        }

        // The lot's tasks on those days, in the version the board shows.
        $dates = array_values(array_unique(array_map(fn ($w) => $w['date'], array_filter($want, fn ($w) => $w['status'] === null))));
        $versions = $s->versions()->get();
        $version = $versions->firstWhere('isActive', true) ?? $versions->firstWhere('isOriginal', true) ?? $versions->first();
        $acts = collect();
        if ($version && $dates) {
            $acts = AsScheduleActivity::where('croppingScheduleId', $s->id)->where('versionId', $version->id)->where('deleteStatus', 1)->where('isDraft', 0)
                ->where(function ($q) use ($dates) {
                    foreach ($dates as $d) {
                        $q->orWhereDate('targetDate', $d);
                    }
                })->with(['items', 'lots'])->orderBy('sequenceOrder')->orderBy('id')->get();
        }
        // A task that names no lot is every lot's, as the board and the planner read it.
        $live = $s->lots()->where('deleteStatus', 1)->pluck('id')->map(fn ($i) => (int) $i)->all();
        $byDate = [];
        $countOn = [];
        foreach ($acts as $a) {
            $d = Carbon::parse($a->targetDate)->toDateString();
            $countOn[$d] = ($countOn[$d] ?? 0) + 1;
            $ids = $a->lots->pluck('id')->map(fn ($i) => (int) $i)->filter(fn ($i) => in_array($i, $live, true))->values()->all();
            if (! $ids || in_array((int) $lot->id, $ids, true)) {
                $byDate[$d][] = $a;
            }
        }
        // A line is the product when its name is the product's (or the book reads it as the same product).
        $book = [];
        $isIt = function ($it, array $w) use (&$book): bool {
            $n = trim($it->displayName());
            $k = ProductLibrary::key($n);
            if (in_array($k, $w['keys'], true) || ($w['aiKey'] !== '' && strlen($w['aiKey']) >= 4 && str_starts_with($k, $w['aiKey']))) {
                return true;
            }
            if (! $w['pid'] || $n === '') {
                return false;
            }
            $book[$n] ??= ProductLibrary::find($n);

            return $book[$n] && (int) ($book[$n]['id'] ?? 0) === $w['pid'];
        };
        $say = fn ($q, $u) => rtrim(rtrim(number_format((float) $q, 2, '.', ''), '0'), '.') . ($u !== '' ? ' ' . $u : '');
        $seen = [];
        foreach ($want as &$w) {
            if ($w['status'] !== null) {
                continue;
            }
            $sk = $w['date'] . '|' . ($w['pid'] ?: implode(',', $w['keys']));
            if (isset($seen[$sk])) {
                $w['status'] = 'twice';
                $w['note'] = 'It is in your list twice on this day: it goes on once.';

                continue;
            }
            $seen[$sk] = true;
            $there = null;
            foreach ($byDate[$w['date']] ?? [] as $a) {
                foreach ($a->items as $it) {
                    if ($isIt($it, $w)) {
                        $there = [$a, $it];
                        break 2;
                    }
                }
            }
            if ($there) {
                [$a, $it] = $there;
                $w['status'] = 'there';
                $w['task'] = (string) $a->activityTitle;
                $q = (float) $it->quantity;
                $u = (string) ($it->unitOfMeasure ?: $it->displayUnit() ?: '');
                $same = $w['qty'] <= 0 || (abs($q - $w['qty']) < 0.0001 && mb_strtolower($u) === mb_strtolower($w['unit']));
                $w['note'] = $same ? '' : 'On the plan as ' . $say($q, $u) . '; you planned ' . $say($w['qty'], $w['unit']) . '. Change it in Activities if you mean to.';

                continue;
            }
            $join = collect($byDate[$w['date']] ?? [])->first(fn ($a) => ! $a->isDone && ! $a->isHidden && $a->activityType === $w['type']);
            $w['status'] = $join ? 'join' : 'new';
            $w['task'] = $join ? (string) $join->activityTitle : self::TITLES[$w['type']];
            $w['join'] = $join?->id;
        }
        unset($w);

        $made = ['tasks' => 0, 'lines' => 0, 'there' => 0, 'twice' => 0, 'bad' => 0];
        foreach ($want as $w) {
            if (isset($made[$w['status']])) {
                $made[$w['status']]++;
            }
        }
        if ($write && array_filter($want, fn ($w) => in_array($w['status'], ['join', 'new'], true))) {
            DB::transaction(function () use ($s, $lot, &$want, &$made, $countOn) {
                $v = PlanPort::version($s);
                $fresh = [];
                $stamp = now('Asia/Manila');
                foreach ($want as &$w) {
                    if (! in_array($w['status'], ['join', 'new'], true)) {
                        continue;
                    }
                    if ($w['status'] === 'join') {
                        $a = AsScheduleActivity::where('croppingScheduleId', $s->id)->where('deleteStatus', 1)->find((int) $w['join']);
                    } else {
                        $gk = $w['date'] . '|' . $w['type'];
                        if (! isset($fresh[$gk])) {
                            $countOn[$w['date']] = ($countOn[$w['date']] ?? 0) + 1;
                            $fresh[$gk] = AsScheduleActivity::create([
                                'croppingScheduleId' => $s->id, 'versionId' => $v->id, 'activityTitle' => self::TITLES[$w['type']], 'targetDate' => $w['date'],
                                'priority' => 'medium', 'activityType' => $w['type'], 'timeRequired' => 'n/a',
                                'description' => HtmlSanitizer::rich('<p>Put on the plan from the Coverage Planner. Give it to a worker, link it to the shed or change the amounts here.</p>'),
                                'isDayZero' => false, 'isTransplant' => false, 'isDraft' => false, 'isHidden' => false, 'isDone' => false,
                                'workerChecklist' => false, 'workerSelfCheck' => false, 'sequenceOrder' => ($countOn[$w['date']] - 1) * 10, 'deleteStatus' => 1,
                            ]);
                            $fresh[$gk]->lots()->attach($lot->id);
                            $made['tasks']++;
                        }
                        $a = $fresh[$gk];
                    }
                    if (! $a) {
                        continue;
                    }
                    AsScheduleActivityItem::create([
                        'activityId' => $a->id, 'itemType' => 'custom', 'itemName' => mb_substr($w['name'], 0, 255),
                        'quantity' => $w['qty'] > 0 ? round($w['qty'], 4) : 1, 'unitOfMeasure' => $w['qty'] > 0 ? ($w['unit'] !== '' ? $w['unit'] : null) : 'dose',
                        'inventoryItemId' => null, 'newBuy' => 0,
                        'notes' => mb_substr($w['qty'] > 0 ? 'From the Coverage Planner.' : 'From the Coverage Planner: one usual dose, as its label says. Put the amount in.', 0, 190),
                        'deleteStatus' => 1, 'created_at' => $stamp, 'updated_at' => $stamp,
                    ]);
                    $w['task'] = (string) $a->activityTitle;
                    $made['lines']++;
                }
                unset($w);
            });
        }
        $made['new'] = count(array_filter($want, fn ($w) => $w['status'] === 'new'));
        $made['join'] = count(array_filter($want, fn ($w) => $w['status'] === 'join'));

        return ['rows' => array_map(fn ($w) => array_intersect_key($w, array_flip(['i', 'name', 'date', 'status', 'task', 'note', 'qty', 'unit'])), $want), 'made' => $made];
    }
}
