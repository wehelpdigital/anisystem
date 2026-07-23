<?php

namespace App\Services;

use App\Models\AsCroppingSchedule;

/**
 * Works out what is still missing from a cropping schedule so the client can
 * be nudged about it — no day 0 anchored, no lots, no workers, activities with
 * no lot, and so on.
 *
 * Each check returns:
 *   key      stable identifier (used by the UI to key rows)
 *   label    short statement of what is missing
 *   detail   why it matters / what to do
 *   module   which module fixes it (matches the SPA module keys)
 *   severity 'blocking' — the plan does not really work without it
 *            'advice'   — worth doing, but the plan still functions
 */
class ScheduleReadinessService
{
    /**
     * @return array{count:int, blocking:int, items:array<int, array<string, string>>}
     */
    public function check(AsCroppingSchedule $schedule): array
    {
        $items = [];

        $lots = $schedule->lots;
        $activities = $schedule->activities;
        $dayType = $schedule->dayType ?: 'DAS';

        // ---- Settings -------------------------------------------------
        if (blank($schedule->cropType)) {
            $items[] = [
                'key' => 'crop-type',
                'label' => 'No crop type set',
                'detail' => 'Name the crop this season is for, so the plan reads clearly and can be shared later.',
                'module' => 'settings',
                'severity' => 'advice',
            ];
        }

        // ---- Lots -----------------------------------------------------
        if ($lots->isEmpty()) {
            $items[] = [
                'key' => 'no-lots',
                'label' => 'No lots added',
                'detail' => 'Activities are planned per lot. Add at least one field or plot to plan against.',
                'module' => 'lots',
                'severity' => 'blocking',
            ];
        } else {
            // A lot is anchored either by its own dayZeroDate or by the
            // earliest activity flagged as day zero that covers it.
            $anchored = [];
            foreach ($lots as $lot) {
                if ($lot->dayZeroDate) {
                    $anchored[$lot->id] = true;
                }
            }
            foreach ($activities as $activity) {
                if (! $activity->isDayZero || ! $activity->targetDate) {
                    continue;
                }
                foreach ($activity->lots as $lot) {
                    $anchored[$lot->id] = true;
                }
            }

            $missing = $lots->reject(fn ($lot) => isset($anchored[$lot->id]));
            if ($missing->count() === $lots->count()) {
                $items[] = [
                    'key' => 'no-day-zero',
                    'label' => "No {$dayType} 0 anchored",
                    'detail' => "Nothing is counting days yet. Set a day-0 date on a lot, or tick \"this is day zero\" on the sowing activity, and every card gets its {$dayType} number.",
                    'module' => 'lots',
                    'severity' => 'blocking',
                ];
            } elseif ($missing->isNotEmpty()) {
                $names = $missing->pluck('lotName')->filter()->take(3)->implode(', ');
                $items[] = [
                    'key' => 'partial-day-zero',
                    'label' => $missing->count() === 1
                        ? "1 lot has no {$dayType} 0"
                        : "{$missing->count()} lots have no {$dayType} 0",
                    'detail' => trim($names) !== ''
                        ? "{$names} will not show day numbers until a day 0 is set."
                        : "Some lots will not show day numbers until a day 0 is set.",
                    'module' => 'lots',
                    'severity' => 'advice',
                ];
            }
        }

        // ---- Activities -----------------------------------------------
        if ($activities->isEmpty()) {
            $items[] = [
                'key' => 'no-activities',
                'label' => 'No activities yet',
                'detail' => 'This is where the season actually gets planned. Add the first activity to get going.',
                'module' => 'activities',
                'severity' => 'blocking',
            ];
        } else {
            $noDate = $activities->filter(fn ($a) => blank($a->targetDate));
            if ($noDate->isNotEmpty()) {
                $items[] = [
                    'key' => 'activities-no-date',
                    'label' => $noDate->count() === 1
                        ? '1 activity has no date'
                        : "{$noDate->count()} activities have no date",
                    'detail' => 'They sit under "No date" and will not appear anywhere in the timeline.',
                    'module' => 'activities',
                    'severity' => 'blocking',
                ];
            }

            $noLot = $activities->filter(fn ($a) => $a->lots->isEmpty());
            if ($noLot->isNotEmpty()) {
                $items[] = [
                    'key' => 'activities-no-lot',
                    'label' => $noLot->count() === 1
                        ? '1 activity has no lot'
                        : "{$noLot->count()} activities have no lot",
                    'detail' => "Without a lot an activity cannot show a {$dayType} number or be costed per field.",
                    'module' => 'activities',
                    'severity' => 'advice',
                ];
            }
        }

        // ---- Resources -------------------------------------------------
        foreach ([
            ['workers', 'No workers added', 'Labour cost and the workload summary stay empty until workers exist.', 'workers'],
            ['materials', 'No materials added', 'Seed, fertiliser and chemicals cannot be attached to activities yet.', 'materials'],
            ['services', 'No services added', 'Hired work such as land prep or threshing cannot be attached to activities yet.', 'services'],
        ] as [$relation, $label, $detail, $module]) {
            if ($schedule->{$relation}->isEmpty()) {
                $items[] = [
                    'key' => "no-{$relation}",
                    'label' => $label,
                    'detail' => $detail,
                    'module' => $module,
                    'severity' => 'advice',
                ];
            }
        }

        // ---- Drafts left behind ----------------------------------------
        $drafts = $schedule->drafts;
        if ($drafts->isNotEmpty()) {
            $items[] = [
                'key' => 'unpublished-drafts',
                'label' => $drafts->count() === 1
                    ? '1 activity still in drafts'
                    : "{$drafts->count()} activities still in drafts",
                'detail' => 'Drafts are hidden from the timeline. Restore them when they are ready.',
                'module' => 'activities',
                'severity' => 'advice',
            ];
        }

        return [
            'count' => count($items),
            'blocking' => count(array_filter($items, fn ($i) => $i['severity'] === 'blocking')),
            'items' => $items,
        ];
    }
}
