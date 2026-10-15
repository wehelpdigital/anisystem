<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global Lots (2026-10-16, the owner): a lot's size is typed in the unit the
 * farmer knows it by (hectares, square meters or acres, picked from a tag).
 * The size stays in hectares (areaHa); this keeps the unit it was given in,
 * so the lot shows and opens in it again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_field_profiles') || Schema::hasColumn('as_field_profiles', 'areaUnit')) {
            return;
        }
        Schema::table('as_field_profiles', function (Blueprint $t) {
            $t->string('areaUnit', 12)->nullable()->after('areaHa');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_field_profiles') && Schema::hasColumn('as_field_profiles', 'areaUnit')) {
            Schema::table('as_field_profiles', function (Blueprint $t) {
                $t->dropColumn('areaUnit');
            });
        }
    }
};
