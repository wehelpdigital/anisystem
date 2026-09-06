<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The analysis shelf held one question — WHEN to plant. It now also holds
 * WHAT to plant, and the kind column says which question a row answers.
 * Every row already on the shelf was a when-question, so that is the
 * default the old rows inherit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_plant_analyses', 'kind')) {
            return;
        }
        Schema::table('as_plant_analyses', function (Blueprint $table) {
            $table->string('kind', 8)->default('when')->index()->after('userId');
        });
    }

    public function down(): void
    {
        Schema::table('as_plant_analyses', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
