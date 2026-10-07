<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spray direction (2026-10-07): a spray task says where the spray goes (from
 * below, under the leaves, over the canopy, on the soil...). Several may be
 * picked, so it is a JSON list of keys (AsScheduleActivity::SPRAY_DIRECTIONS).
 * The Protocol Builder keeps the same list on its task JSON, no column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_activities') && ! Schema::hasColumn('as_schedule_activities', 'sprayDirections')) {
            Schema::table('as_schedule_activities', function (Blueprint $t) {
                $t->json('sprayDirections')->nullable()->after('extraTypes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_schedule_activities', 'sprayDirections')) {
            Schema::table('as_schedule_activities', fn (Blueprint $t) => $t->dropColumn('sprayDirections'));
        }
    }
};
