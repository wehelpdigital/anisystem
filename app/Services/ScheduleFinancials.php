<?php

namespace App\Services;

use App\Models\AsCroppingSchedule;

/**
 * Rolls a cropping schedule's spend into the cost buckets a Post-Harvest
 * Report needs: materials, services, labour, and ad-hoc extra expenses.
 *
 * The labour figure mirrors ActivityController::laborSummary exactly
 * (costPerHalfDay × units × rangeDays, units: whole=2, half=1, else 0) so the
 * report and the labour report never disagree — minus the interactive filters,
 * since a report totals the whole schedule.
 */
class ScheduleFinancials
{
    /**
     * @return array{materials:float, services:float, labor:float, expenses:float, total:float}
     */
    public function costs(AsCroppingSchedule $schedule): array
    {
        $schedule->loadMissing([
            'activities.items',
            'activities.workers',
            'dayExpenses',
        ]);

        $materials = 0.0;
        $services  = 0.0;
        $labor     = 0.0;

        foreach ($schedule->activities as $activity) {
            foreach ($activity->items as $item) {
                $line = (float) $item->unitPrice * (float) $item->quantity;
                if ($item->itemType === 'service') {
                    $services += $line;
                } else {
                    $materials += $line;
                }
            }

            // Activity-level service charge (single-service activities).
            $services += (float) ($activity->servicePrice ?? 0);

            // Labour: half=1 unit, whole=2 units, N/A=0. Multi-day activities
            // cost per day across their span.
            $units = match ($activity->timeRequired) {
                'whole' => 2,
                'half'  => 1,
                default => 0,
            };
            if ($units > 0) {
                $start = $activity->targetDate;
                $end   = $activity->targetEndDate ?: $activity->targetDate;
                $rangeDays = 1;
                if ($start && $end) {
                    $rangeDays = (int) $start->diffInDays($end) + 1;
                    if ($rangeDays < 1) $rangeDays = 1;
                }
                foreach ($activity->workers as $worker) {
                    $labor += (float) $worker->costPerHalfDay * $units * $rangeDays;
                }
            }
        }

        $expenses = (float) $schedule->dayExpenses->sum('amount');

        $materials = round($materials, 2);
        $services  = round($services, 2);
        $labor     = round($labor, 2);
        $expenses  = round($expenses, 2);

        return [
            'materials' => $materials,
            'services'  => $services,
            'labor'     => $labor,
            'expenses'  => $expenses,
            'total'     => round($materials + $services + $labor + $expenses, 2),
        ];
    }
}
