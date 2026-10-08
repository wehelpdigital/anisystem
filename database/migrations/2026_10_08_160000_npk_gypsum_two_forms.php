<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NPK Plus (2026-10-08): gypsum in its two forms. The gypsum already on
 * the list is the common one, dihydrate (it keeps its id, so saved plans
 * still read it); anhydrous gypsum (anhydrite) joins it, with about a
 * quarter more calcium and sulfur in each bag since it carries no water.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_npk_products')) {
            return;
        }
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum')->update([
            'name' => 'Gypsum dihydrate (calcium sulfate)',
            'note' => 'The common gypsum, with its water. Replaces sodium on sodic soil, then the water carries the sodium out.',
            'updated_at' => now(),
        ]);
        if (DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum-anhydrous')->exists()) {
            return;
        }
        $order = (int) (DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum')->value('sortOrder') ?? 23);
        DB::table('as_npk_products')->insert([
            'userId' => null, 'slug' => 'gypsum-anhydrous', 'name' => 'Gypsum anhydrous (anhydrite)', 'local' => null,
            'category' => 'secondary', 'form' => 'powder', 'unit' => 'kg', 'bagKg' => 50, 'densityKgL' => null,
            'note' => 'Gypsum without its water: about a quarter more calcium and sulfur in each bag, and it dissolves more slowly. Replaces sodium on sodic soil too.',
            'sortOrder' => $order, 'deleteStatus' => 1, 'estimate' => null,
            'N' => 0, 'P2O5' => 0, 'K2O' => 0, 'Ca' => 29, 'Mg' => 0, 'S' => 23, 'Zn' => 0, 'B' => 0, 'Fe' => 0, 'Mn' => 0, 'Cu' => 0, 'Mo' => 0, 'Si' => 0, 'Cl' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum-anhydrous')->delete();
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'gypsum')->update(['name' => 'Gypsum (calcium sulfate)',
            'note' => 'Replaces sodium on sodic soil, then the water carries the sodium out.']);
    }
};
