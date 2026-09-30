<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Maps and drawings belong to the grower now, not to one season (2026-09-30).
 *
 * A farm's fields do not change when the season does, so a map drawn in the
 * wet season was lost to the dry one. Both tools moved to Global and Quick
 * Tools. The seasons keep working with maps through LINKS rather than by
 * owning them:
 *
 *   as_schedule_map_saves.scheduleId = 0 means "the owner's own map" (userId).
 *   originScheduleId remembers the season a map was first drawn in.
 *   as_schedule_map_links says which seasons use a map: drawn there, a lot
 *   wearing it, an activity or tag pointing at it. The season's team may
 *   open a linked map; only its owner (or a worker with the Maps pen on a
 *   linked season) may change it.
 *
 * This half is only the schema (additive, harmless to code that does not
 * know about it); 2026_09_30_200100 moves the existing rows.
 *
 * Existing data (moved by the next migration):
 *   - every saved map goes global, and is linked to its season and to any
 *     season whose lots, activities or tags already point at it;
 *   - the note each map filed its picture in moves with it (Global Notes);
 *   - a season's unsaved live canvas is kept as a saved map of the season
 *     owner's, so nothing drawn is stranded (the canvas itself stays, for
 *     the Collab Room);
 *   - a notebook note that was only ever a drawing (made in the Draw module)
 *     moves to the owner's global notes, where the Draw module now keeps
 *     them. A drawing made inside a note with words, or on a day of the
 *     board, stays where it was drawn; the global Draw page lists it there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('as_schedule_map_saves', 'originScheduleId')) {
            Schema::table('as_schedule_map_saves', function (Blueprint $table) {
                $table->unsignedBigInteger('originScheduleId')->nullable()->after('scheduleId');
                $table->index(['scheduleId', 'userId']);
            });
        }

        if (! Schema::hasTable('as_schedule_map_links')) {
            Schema::create('as_schedule_map_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('saveId');
                $table->unsignedBigInteger('scheduleId')->index();
                $table->timestamps();
                $table->unique(['saveId', 'scheduleId']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_map_links');
        if (Schema::hasColumn('as_schedule_map_saves', 'originScheduleId')) {
            Schema::table('as_schedule_map_saves', function (Blueprint $table) {
                $table->dropIndex(['scheduleId', 'userId']);
                $table->dropColumn('originScheduleId');
            });
        }
    }
};
