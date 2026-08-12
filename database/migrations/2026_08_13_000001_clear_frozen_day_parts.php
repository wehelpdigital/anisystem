<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Un-freeze the half/whole day on activities that never asked for one.
 *
 * Opening an activity used to seed each worker with the day-part the task
 * implied, and saving wrote that back as though it had been chosen. A task
 * later changed to a whole day then kept paying half, because "half" was
 * sitting in the worker's own row overriding it.
 *
 * Only per-worker payroll activities can hold a deliberate choice — that is
 * the only place the checklist offers one — so a stored day-part anywhere else
 * is that artefact and is cleared. NULL means "as long as the task", which is
 * what those rows meant all along. Rows with an agreed amount are left alone:
 * that figure was certainly typed on purpose.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('as_schedule_activity_workers as p')
            ->join('as_schedule_activities as a', 'a.id', '=', 'p.activityId')
            ->whereNotNull('p.dayPart')
            ->whereNull('p.salaryAmount')
            ->where('a.activityType', '<>', 'worker_payroll')
            ->update(['p.dayPart' => null]);
    }

    public function down(): void
    {
        // Nothing to put back: the cleared values were never chosen.
    }
};
