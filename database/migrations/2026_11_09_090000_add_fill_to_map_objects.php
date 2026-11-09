<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How solid a closed shape's inside is: 0 (see through) to 100 (solid), for a
 * box, an area or a drawn shape (2026-11-09, the owner: "a fill option that you
 * can change fill transparency").
 *
 * Null for every row written before this, and the client reads null as the
 * light fill each kind always had, so nothing already on a map changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_schedule_map_objects', 'fill')) {
            return;
        }
        Schema::table('as_schedule_map_objects', function (Blueprint $table) {
            $table->unsignedTinyInteger('fill')->nullable()->after('font');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_schedule_map_objects', 'fill')) {
            return;
        }
        Schema::table('as_schedule_map_objects', function (Blueprint $table) {
            $table->dropColumn('fill');
        });
    }
};
