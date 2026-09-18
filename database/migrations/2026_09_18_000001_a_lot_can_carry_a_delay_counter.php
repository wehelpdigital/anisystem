<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A lot can carry a delay counter.
 *
 * The calendar counts DAT 20; the crop, set back by a dry spell or a
 * late top-dress, stands where a DAT 10 crop would. The farmer says so
 * in days, and every activity's day chip shows the delayed count beside
 * the calendar's: "DAT+20 | DAS+25 | DELAY DAT+10 | DELAY DAS+15". It is
 * the farmer's own word, kept apart from Realign by Anee's shift.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_lots') && ! Schema::hasColumn('as_schedule_lots', 'delayDays')) {
            Schema::table('as_schedule_lots', function (Blueprint $table) {
                // Days the crop is behind the calendar; null = no delay set.
                $table->unsignedSmallInteger('delayDays')->nullable()->after('growthRealign');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('as_schedule_lots') && Schema::hasColumn('as_schedule_lots', 'delayDays')) {
            Schema::table('as_schedule_lots', function (Blueprint $table) {
                $table->dropColumn('delayDays');
            });
        }
    }
};
