<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a lot actually is.
 *
 * A lot has carried an address since the module was built — barangay, zone,
 * town, province — which is enough to write on a delivery note and nowhere
 * near enough to find the place. "Lot A — North Field, Brgy. San Isidro" is
 * four hundred hectares of possibility to a technician who has never been
 * there, an agronomist coming out to look at a problem, or a hauler with a
 * truck and an hour of daylight left.
 *
 * A pin is the answer to that, and it is a different kind of fact from an
 * address: two numbers that open the Maps app and take somebody to the gate.
 * It is put here rather than in the map's own object table because it belongs
 * to the LOT — it should survive the map being cleared, re-drawn, or replaced
 * by next season's plan, and it should be readable without loading a map at
 * all.
 *
 * `mapSaveId` remembers which saved map it was pinned on, so "open the map"
 * goes back to the drawing the pin was placed in rather than to a blank one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_lots')) {
            return;
        }

        Schema::table('as_schedule_lots', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_lots', 'pinLat')) {
                // 7 decimal places is about a centimetre — far finer than any
                // phone's GPS, and cheap. 6 would be a tenth of a metre and
                // also fine; the extra digit costs nothing and stops the
                // column being the thing that rounds.
                $table->decimal('pinLat', 10, 7)->nullable()->after('locProvince');
            }
            if (! Schema::hasColumn('as_schedule_lots', 'pinLng')) {
                $table->decimal('pinLng', 10, 7)->nullable()->after('pinLat');
            }
            if (! Schema::hasColumn('as_schedule_lots', 'pinLabel')) {
                // What the pin is: "the gate", "the pump house", "where the
                // truck can turn". Optional — the coordinates are the point.
                $table->string('pinLabel', 120)->nullable()->after('pinLng');
            }
            if (! Schema::hasColumn('as_schedule_lots', 'mapSaveId')) {
                $table->unsignedBigInteger('mapSaveId')->nullable()->after('pinLabel');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_schedule_lots')) {
            return;
        }

        Schema::table('as_schedule_lots', function (Blueprint $table) {
            foreach (['pinLat', 'pinLng', 'pinLabel', 'mapSaveId'] as $col) {
                if (Schema::hasColumn('as_schedule_lots', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
