<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NPK Plus (2026-10-08): the two gypsums named by what they are, calcium
 * sulfate, dihydrate and anhydrous, and put first on the calcium shelf, so a
 * farmer looking for calcium finds them. Same rows and ids as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_npk_products')) {
            return;
        }
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum')
            ->update(['name' => 'Calcium sulfate dihydrate (gypsum)', 'sortOrder' => 19, 'updated_at' => now()]);
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum-anhydrous')
            ->update(['name' => 'Calcium sulfate anhydrous (anhydrite)', 'sortOrder' => 20, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum')
            ->update(['name' => 'Gypsum dihydrate (calcium sulfate)', 'sortOrder' => 23]);
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum-anhydrous')
            ->update(['name' => 'Gypsum anhydrous (anhydrite)', 'sortOrder' => 23]);
    }
};
