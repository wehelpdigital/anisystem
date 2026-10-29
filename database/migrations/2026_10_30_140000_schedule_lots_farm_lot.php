<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A season lot's farm lot (2026-10-30, the owner: the season's lots and Global Lots "should be synced including the
 * answers"): as_schedule_lots.farmLotId, the row of as_field_profiles the season lot is. App\Support\LotSync keeps it,
 * linking by name until this is in.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_lots') && ! Schema::hasColumn('as_schedule_lots', 'farmLotId')) {
            Schema::table('as_schedule_lots', function (Blueprint $table) {
                $table->unsignedBigInteger('farmLotId')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_schedule_lots', 'farmLotId')) {
            Schema::table('as_schedule_lots', function (Blueprint $table) {
                $table->dropIndex(['farmLotId']);
                $table->dropColumn('farmLotId');
            });
        }
    }
};
