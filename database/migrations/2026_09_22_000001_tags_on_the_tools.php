<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TAGS ON THE TOOLS — a member's own words on the things that are theirs
 * and not a season's: the analyses on the four shelves and the protocols
 * they wrote. One JSON list of words per row, the same shape the contact
 * list has carried since it was built; the vocabulary is gathered across
 * the tools when a picker opens.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['as_plant_analyses', 'as_protocols'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'tags')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->text('tags')->nullable()->after('description');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['as_plant_analyses', 'as_protocols'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tags')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('tags');
                });
            }
        }
    }
};
