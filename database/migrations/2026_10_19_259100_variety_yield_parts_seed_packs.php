<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Seed packs on the variety yield parts (2026-10-19): how 56 varieties'
 * seed is sold (the usual pack first: kilos, grams or a seed count, in a
 * bag, can, pack or pouch), as the part `pack` that VarietyYield::for()
 * hands the seed calculator. Rice packs sit on both the transplanted and
 * the direct seeded rows; sellers' varieties our books lack get a row of
 * their own with the pack only. Also the third research run's first
 * figures: rice hybrid spikelets, filled share and grain weight from more
 * trials, inbred figures from two more studies, and the typical rows that
 * follow from them (the yellow corn hybrids gain a typical grain an ear).
 * VarietyYield::sync() is idempotent: unchanged rows are skipped, changed
 * ones replaced, rows gone retired.
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
