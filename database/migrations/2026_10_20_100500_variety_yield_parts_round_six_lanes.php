<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The sixth research round's lanes on the variety yield parts
 * (2026-10-20): rice hybrids gain spikelets, filled share and grain
 * weight from more field trials (Mestiso 3, 7 and 26) and grain weight,
 * panicle length, tillers and height from more registration pages; the
 * sheets that print one fill value for every grain weight stay out.
 * Sweet potato, cassava and potato lines gain the yields their trials
 * and lists print, Sinta papaya its fruit weight, soybeans their pods,
 * seed weight and days, carrots what a root weighs (the hybrid carrots
 * gain a typical row), and a few corn hybrids their kernel weight and
 * shelling from trials abroad. VarietyYield::sync() is idempotent:
 * unchanged rows are skipped, changed ones replaced, rows gone retired.
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
