<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Rice — upland (Palay sa tuyo)" left the crop catalogue for two
 * direct-seeded crops, wet and dry, both counted in DAS only (2026-09-29).
 *
 * A stored `rice_upland` lands on the dry one, which is what "upland" meant;
 * CropStages::RENAMED reads any value this misses the same way. The one lot
 * that used it on the day (the owner's Masin 4, a wet-season broadcast
 * seeding) was set to the wet crop by hand before this ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_lots')) {
            DB::table('as_schedule_lots')->where('crop', 'rice_upland')->update(['crop' => 'rice_dsr_dry']);
        }
        if (Schema::hasTable('as_protocols')) {
            DB::table('as_protocols')->where('crop', 'rice_upland')->update(['crop' => 'rice_dsr_dry']);
        }
        if (Schema::hasTable('as_cropping_schedules')) {
            DB::table('as_cropping_schedules')->where('cropType', 'Rice — upland (Palay sa tuyo)')
                ->update(['cropType' => 'Rice — Direct Seeded Dry (Palay)']);
        }
    }

    public function down(): void
    {
        // The wet/dry split cannot be undone into one crop without losing
        // which was which, so nothing is put back.
    }
};
