<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The second research run for the variety yield parts (2026-10-19, the owner
 * said yes): rice spikelets a panicle, filled share and 1,000 grain weight
 * from trials that measured them, so the inbred kind row now carries
 * grains of its own; seller tiller ranges for three hybrids; the Dekalb
 * product pages' kernel rows, shelling, population and spacing (extra);
 * and Cavendish read on its own calculator crop. Figures a researcher
 * doubted (oven dried grain weights, a table that repeats another, seller
 * panicle claims) stay out. VarietyYield::sync() is idempotent: unchanged
 * rows are skipped, changed ones replaced, rows gone from the files retired.
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
