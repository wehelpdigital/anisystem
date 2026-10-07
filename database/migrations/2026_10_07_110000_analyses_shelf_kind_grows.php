<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The analyses shelf's kind was eight letters ('protocol' filled it). The
 * satellite pair and the NPK Plus readings (2026-10-07) need longer names.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_plant_analyses') || ! Schema::hasColumn('as_plant_analyses', 'kind')) {
            return;
        }
        Schema::table('as_plant_analyses', function (Blueprint $table) {
            $table->string('kind', 24)->default('when')->change();
        });
    }

    public function down(): void
    {
        // Left wide: shrinking would cut the longer kinds.
    }
};
