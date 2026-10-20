<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the eighth research round's corn lane closes
 * (2026-10-20): P4097 in its four trait versions, PAC 339 in two and PAC 999
 * in two gain their ears a plant and grain an ear from counted kernel rows,
 * kernels a row and kernel weight in a side by side hybrid trial abroad (PAC
 * 999 drops the figures of a trial whose entry may be another hybrid). The
 * yellow corn hybrids' typical grain an ear follows, now counting the trait
 * versions of one hybrid once. VarietyYield::sync() is idempotent: unchanged
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
