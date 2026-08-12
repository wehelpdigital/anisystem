<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a saved map was drawn: in the Collab Room with the team, or alone in
 * the Maps module. Both are the same drawing tool, but they are not the same
 * thing to whoever is about to load one over what is on screen.
 *
 * Existing saves keep NULL, which reads as "my map" — the Collab Room's own
 * saves are the ones that will start declaring themselves from here on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_map_saves', function (Blueprint $table) {
            $table->string('source', 10)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_map_saves', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
