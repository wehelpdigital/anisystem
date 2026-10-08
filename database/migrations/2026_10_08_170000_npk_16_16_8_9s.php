<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NPK Plus (2026-10-08): 16-16-8 with 9% sulfur, sold as MB Basal, on the
 * shared product list beside the other complete fertilizers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_npk_products') || DB::table('as_npk_products')->whereNull('userId')->where('slug', 'complete-16-16-8-s')->exists()) {
            return;
        }
        $order = (int) (DB::table('as_npk_products')->whereNull('userId')->where('slug', 'complete-16')->value('sortOrder') ?? 16);
        DB::table('as_npk_products')->insert([
            'userId' => null, 'slug' => 'complete-16-16-8-s', 'name' => 'Complete fertilizer with sulfur (MB Basal)', 'local' => '16-16-8-9S',
            'category' => 'complete', 'form' => 'granular', 'unit' => 'kg', 'bagKg' => 50, 'densityKgL' => null,
            'note' => 'A basal fertilizer that also feeds sulfur.',
            'sortOrder' => $order, 'deleteStatus' => 1, 'estimate' => null,
            'N' => 16, 'P2O5' => 16, 'K2O' => 8, 'Ca' => 0, 'Mg' => 0, 'S' => 9, 'Zn' => 0, 'B' => 0, 'Fe' => 0, 'Mn' => 0, 'Cu' => 0, 'Mo' => 0, 'Si' => 0, 'Cl' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('as_npk_products')->whereNull('userId')->where('slug', 'complete-16-16-8-s')->delete();
    }
};
