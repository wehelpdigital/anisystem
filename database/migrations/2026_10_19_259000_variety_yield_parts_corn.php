<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Corn on the variety yield parts (2026-10-19, the second research run):
 * grain an ear for three yellow hybrids from a trial that counted kernel
 * rows, kernels a row and kernel weight (marked as a trial abroad), the
 * fresh ear weight of three sweet corn hybrids from the seller (husk not
 * stated), and in extra the seller brochures' population and spacing and
 * the trial kernel weights. VarietyYield::sync() is idempotent: unchanged
 * rows are skipped, changed ones replaced, rows gone retired.
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
