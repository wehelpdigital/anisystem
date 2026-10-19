<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the third research run's fourth batch (2026-10-19),
 * read off the registration pages and seller pages: more rice hybrids and
 * the newer inbreds gain their 1,000 grain weight and productive tillers,
 * peanut lines what a plant gives (pods x seeds x seed weight over the
 * shelling), a few fruit vegetables the seller's fruit weight, and the
 * typical rows that follow (peanut gains one). VarietyYield::sync() is
 * idempotent: unchanged rows are skipped, changed ones replaced, rows gone
 * retired.
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
