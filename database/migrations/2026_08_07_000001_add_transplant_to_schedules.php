<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two-phase (DAS → DAT) day counters for transplanted crops: a lot can carry a
 * transplant date (DAT day 0), and an activity can be flagged as the transplant
 * (which anchors that date). Before the transplant, activities count in DAS from
 * the sowing day-zero; on/after it, they count in DAT from the transplant date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_lots', 'transplantDate')) {
                $table->date('transplantDate')->nullable()->after('dayZeroDate');
            }
        });

        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_activities', 'isTransplant')) {
                $table->boolean('isTransplant')->default(false)->after('isDayZero');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_lots', 'transplantDate')) {
                $table->dropColumn('transplantDate');
            }
        });
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_activities', 'isTransplant')) {
                $table->dropColumn('isTransplant');
            }
        });
    }
};
