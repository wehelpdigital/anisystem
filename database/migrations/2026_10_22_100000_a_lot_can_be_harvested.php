<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A lot can be marked harvested (2026-10-22, the owner).
 *
 * The board's Harvest sheet sets it: the day the lot came in, and the
 * observations its harvest record wrote (yield, quality, drying, selling),
 * so taking the mark back can take those rows with it instead of leaving a
 * second harvest behind to be counted twice. Null on every existing lot:
 * nothing was marked before this.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_lots') || Schema::hasColumn('as_schedule_lots', 'harvestedAt')) {
            return;
        }

        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->date('harvestedAt')->nullable()->after('delayDays');
            $table->text('harvestObservationIds')->nullable()->after('harvestedAt');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_schedule_lots', 'harvestedAt')) {
            return;
        }

        Schema::table('as_schedule_lots', function (Blueprint $table) {
            $table->dropColumn(['harvestedAt', 'harvestObservationIds']);
        });
    }
};
