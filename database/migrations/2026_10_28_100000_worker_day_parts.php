<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Half days in a worker's week (2026-10-28, the owner: "weekdays they do not
 * work / work should be selectable if half day am/pm or wholeday as well").
 * `dayParts` is weekday => am|pm for the days they come only in the morning
 * or only in the afternoon (App\Support\WorkDays); the days off stay where
 * they were. On the roster and on each season card, like the days off.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['as_global_workers' => 'offWeekdays', 'as_schedule_workers' => 'skills'] as $table => $after) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'dayParts')) {
                Schema::table($table, function (Blueprint $t) use ($after) {
                    $t->json('dayParts')->nullable()->after($after);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['as_global_workers', 'as_schedule_workers'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'dayParts')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('dayParts'));
            }
        }
    }
};
