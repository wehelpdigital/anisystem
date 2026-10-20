<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the eighth research round's rice lanes (2026-10-20):
 * eight older inbreds (PSB Rc 4, 10, 20, 28, 30, 52, 54 and 80) gain their
 * spikelets a panicle, filled share and grain weight from a field trial
 * that measured them side by side; hybrids measured abroad keep their
 * filled grains in extra where the potential they promise passes what a
 * field gives; a peanut and a soybean their harvest a plant; the typical
 * rows of their kinds follow. Copied registration sheet columns stay left
 * out. VarietyYield::sync() is idempotent: unchanged rows are skipped,
 * changed ones replaced, rows gone retired.
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
