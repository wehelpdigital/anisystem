<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the third research run's fifth batch (2026-10-19),
 * read off registration pages and seller pages: 41 more rice hybrids and
 * inbreds gain their 1,000 grain weight and seven their tillers; lettuce,
 * radish and a mungbean line what a plant gives; sugarcane lines their
 * stalks a clump and stalk thickness, potato and sweet potato lines their
 * days and hectare yields, kept beside the parts. Doubts settled: a sheet
 * that prints one grain weight for every entry and check no longer gives
 * seven hybrids theirs, two coffee bean weights that cannot fit their
 * fruit weights and a garden packet's onion weight are left out, and the
 * mango tree yields, printed for old mother trees, stay beside the parts
 * (marked) instead of standing for an orchard tree. VarietyYield::sync()
 * is idempotent: unchanged rows are skipped, changed ones replaced, rows
 * gone retired.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_variety_yield_parts')) {
            return;
        }
        VarietyYield::sync();
        Cache::forget('variety-yield:' . config('database.default') . ':ready');
    }

    public function down(): void
    {
        // The rows stay; the first migration's down() drops the table.
    }
};
