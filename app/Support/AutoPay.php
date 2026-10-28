<?php

namespace App\Support;

use App\Models\AsAutoEvent;
use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsScheduleWorker;
use App\Services\AutoPlanner;
use Carbon\Carbon;

/**
 * Anee Auto's payroll (2026-10-28, the owner: "worker payments and based on the answers, will how the daily
 * management of anee auto be"; the option reads "Anee counts what each worker earns from the work they do, ready
 * for payday").
 *
 * What a worker earns is counted from the tasks marked done with their name on them: a morning task is half a day,
 * an afternoon task the other half, a task with no time of day a whole day, and two tasks in the same half are still
 * one half. Each half day is paid at the worker's card (costPerHalfDay, the daily pay the crew step asks, halved);
 * a season whose crew was only named (no cards, the early wizard) keeps each one's daily pay in answers.crew.*.pay.
 * A payday is a 'paid' event in the season's story, so the log keeps it and what is owed is what was earned less
 * what was paid.
 */
final class AutoPay
{
    public static function on(AsAutoSeason $auto): bool
    {
        return (bool) AutoOptions::of((array) $auto->answers)['payments'];
    }

    /** Half a day's pay of each person the season counts (AutoPlanner::people ids), 0 when not set. */
    public static function rates(AsAutoSeason $auto): array
    {
        $cards = AsScheduleWorker::active()->where('croppingScheduleId', $auto->croppingScheduleId)->pluck('costPerHalfDay', 'id');
        $crew = array_values((array) ($auto->answers['crew'] ?? []));
        $out = [];
        foreach (AutoPlanner::people($auto) as $p) {
            $out[$p['id']] = $p['id'] > 0 ? (float) ($cards[$p['id']] ?? 0) : round((float) ($crew[-1 - $p['id']]['pay'] ?? 0) / 2, 2);
        }

        return $out;
    }

    /** @return array{rows: array, totals: array} */
    public static function book(AsAutoSeason $auto): array
    {
        $cards = AutoPlanner::people($auto)->keyBy('id');
        $rates = self::rates($auto);
        $slots = [];
        $tasks = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'done')->whereNotNull('doneDate')->orderBy('doneDate')->get();
        foreach ($tasks as $t) {
            $halves = $t->timeOfDay === 'morning' ? ['am'] : ($t->timeOfDay === 'afternoon' ? ['pm'] : ['am', 'pm']);
            foreach ((array) (($t->doneData ?? [])['workers'] ?? []) as $w) {
                $w = (int) $w;
                if (! $cards->has($w)) {
                    continue;
                }
                $d = $t->doneDate->toDateString();
                $s = &$slots[$w][$d];
                $s ??= ['am' => false, 'pm' => false, 'tasks' => []];
                foreach ($halves as $h) {
                    $s[$h] = true;
                }
                $s['tasks'][] = $t->title;
                unset($s);
            }
        }
        $paid = [];
        $lastPaid = [];
        foreach (AsAutoEvent::where('autoId', $auto->id)->where('kind', 'paid')->orderBy('id')->get() as $e) {
            $w = (int) (($e->data ?? [])['workerId'] ?? 0);
            $paid[$w] = ($paid[$w] ?? 0) + (float) (($e->data ?? [])['amount'] ?? 0);
            $lastPaid[$w] = Carbon::parse($e->created_at)->timezone('Asia/Manila');
        }

        $rows = [];
        $owedAll = 0.0;
        $earnedAll = 0.0;
        foreach ($cards as $id => $c) {
            $days = $slots[$id] ?? [];
            krsort($days);
            $shown = array_slice($days, 0, 40, true);
            $halves = array_sum(array_map(fn ($s) => (int) $s['am'] + (int) $s['pm'], $days));
            $rate = (float) ($rates[$id] ?? 0);
            $earned = $rate > 0 ? round($halves * $rate, 2) : null;
            $p = round((float) ($paid[$id] ?? 0), 2);
            $owed = $earned !== null ? round(max(0, $earned - $p), 2) : null;
            $earnedAll += (float) $earned;
            $owedAll += (float) $owed;
            $rows[] = [
                'id' => (int) $id, 'name' => (string) $c['name'], 'dayPay' => $rate > 0 ? round($rate * 2, 2) : null,
                'halves' => $halves, 'daysSays' => self::daysSays($halves), 'earned' => $earned, 'paid' => $p, 'owed' => $owed,
                'lastPaid' => isset($lastPaid[$id]) ? $lastPaid[$id]->format('M j') : null,
                'lines' => array_values(array_map(fn ($d, $s) => ['date' => Carbon::parse($d)->format('D, M j'), 'part' => $s['am'] && $s['pm'] ? 'Whole day' : ($s['am'] ? 'Morning' : 'Afternoon'),
                    'tasks' => array_values(array_unique($s['tasks'])), 'pay' => $rate > 0 ? round(((int) $s['am'] + (int) $s['pm']) * $rate, 2) : null], array_keys($shown), $shown)),
            ];
        }

        return ['rows' => $rows, 'totals' => ['earned' => round($earnedAll, 2), 'owed' => round($owedAll, 2), 'paid' => round(array_sum($paid), 2),
            'noRate' => count(array_filter($rows, fn ($r) => $r['dayPay'] === null))]];
    }

    /** "3 and a half days", "half a day", "no work yet". */
    public static function daysSays(int $halves): string
    {
        if ($halves === 0) {
            return 'no work yet';
        }
        $whole = intdiv($halves, 2);
        $half = $halves % 2 === 1;

        return $whole === 0 ? 'half a day' : $whole . ($half ? ' and a half' : '') . ' day' . ($whole === 1 && ! $half ? '' : 's');
    }
}
