<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Schedules with zero as_schedule_activity_versions rows leave their
     * activities/date notes with versionId NULL, which the version-scoped
     * relations can never see (whereIn against an empty version set).
     * Mirrors the mother's own 2026_05_18 backfill: create an Original
     * version per schedule that lacks one, and repoint NULL-version rows.
     * Idempotent.
     */
    public function up(): void
    {
        $now = now();

        $scheduleIds = DB::table('as_cropping_schedules')
            ->where('deleteStatus', 1)
            ->whereNotIn('id', function ($sub) {
                $sub->select('croppingScheduleId')
                    ->from('as_schedule_activity_versions')
                    ->where('deleteStatus', 1);
            })
            ->pluck('id');

        foreach ($scheduleIds as $scheduleId) {
            DB::table('as_schedule_activity_versions')->insert([
                'croppingScheduleId' => $scheduleId,
                'versionName' => 'Original',
                'isOriginal' => 1,
                'isActive' => 1,
                'versionOrder' => 0,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Repoint orphaned NULL-version rows at their schedule's active version.
        foreach (['as_schedule_activities', 'as_schedule_date_notes'] as $table) {
            $orphans = DB::table($table)
                ->whereNull('versionId')
                ->where('deleteStatus', 1)
                ->pluck('croppingScheduleId')
                ->unique();

            foreach ($orphans as $scheduleId) {
                $versionId = DB::table('as_schedule_activity_versions')
                    ->where('croppingScheduleId', $scheduleId)
                    ->where('deleteStatus', 1)
                    ->orderByDesc('isActive')
                    ->orderByDesc('isOriginal')
                    ->orderBy('id')
                    ->value('id');

                if ($versionId) {
                    DB::table($table)
                        ->whereNull('versionId')
                        ->where('croppingScheduleId', $scheduleId)
                        ->update(['versionId' => $versionId]);
                }
            }
        }
    }

    public function down(): void
    {
        // Data backfill — intentionally not reversible.
    }
};
