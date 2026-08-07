<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Day-counter type moves from the schedule to the individual lot. Each lot is
 * either "DAP" (Days After Planting — a single counter) or "DAS" (Days After
 * Seeding, which flips to DAT after the lot's transplant date). Existing lots
 * inherit their schedule's old dayType (DAT is folded into the DAS/DAT mode).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_lots', 'dayType')) {
                $table->string('dayType', 8)->default('DAS')->after('transplantDate');
            }
        });

        // Backfill from the parent schedule: DAP stays DAP, DAS/DAT → DAS.
        if (Schema::hasColumn('as_schedule_lots', 'dayType')
            && Schema::hasColumn('as_cropping_schedules', 'dayType')) {
            DB::table('as_schedule_lots')->orderBy('id')->chunkById(500, function ($lots) {
                $schedules = DB::table('as_cropping_schedules')
                    ->whereIn('id', $lots->pluck('croppingScheduleId')->unique())
                    ->pluck('dayType', 'id');
                foreach ($lots as $lot) {
                    $st = $schedules[$lot->croppingScheduleId] ?? 'DAS';
                    DB::table('as_schedule_lots')->where('id', $lot->id)
                        ->update(['dayType' => $st === 'DAP' ? 'DAP' : 'DAS']);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_lots', 'dayType')) {
                $table->dropColumn('dayType');
            }
        });
    }
};
