<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Layers on every map (2026-11-20, the owner: "have the option to have layers module so that i can easily select and
 * lock a layer shape like a whole square or rectangle or lot or hide it so that i don't need to see it while working
 * on another one in the same drawing").
 *
 * Each shape keeps its own: `name` (what the Layers list calls it; null = its kind and number), `hidden` (off the
 * screen and out of saved pictures) and `locked` (cannot be picked up, moved, reshaped or erased; taps pass through
 * to the map). Kept on the shape, so a teammate, a reload and a saved map all see the same layers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_map_objects')) {
            return;
        }
        Schema::table('as_schedule_map_objects', function (Blueprint $t) {
            if (! Schema::hasColumn('as_schedule_map_objects', 'name')) {
                $t->string('name', 80)->nullable()->after('label');
            }
            if (! Schema::hasColumn('as_schedule_map_objects', 'hidden')) {
                $t->boolean('hidden')->default(false)->after('fill');
            }
            if (! Schema::hasColumn('as_schedule_map_objects', 'locked')) {
                $t->boolean('locked')->default(false)->after('hidden');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_map_objects', function (Blueprint $t) {
            foreach (['name', 'hidden', 'locked'] as $c) {
                if (Schema::hasColumn('as_schedule_map_objects', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
