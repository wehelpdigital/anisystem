<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A saved report or analysis can be renamed and given a description —
 * the shelf stops being a list of machine-written titles.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('as_farm_reports', 'description')) {
            Schema::table('as_farm_reports', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }
        if (! Schema::hasColumn('as_plant_analyses', 'description')) {
            Schema::table('as_plant_analyses', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_farm_reports', 'description')) {
            Schema::table('as_farm_reports', fn (Blueprint $table) => $table->dropColumn('description'));
        }
        if (Schema::hasColumn('as_plant_analyses', 'description')) {
            Schema::table('as_plant_analyses', fn (Blueprint $table) => $table->dropColumn('description'));
        }
    }
};
