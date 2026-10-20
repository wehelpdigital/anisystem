<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the eighth research round's leafy lane closes
 * (2026-10-20): Rare Ball cabbage its head weight, Terracotta carrot what a
 * root weighs, three more cabbages their days; and the round's first corn
 * figures, DK 6919 and DK 6919S their ears a plant and grain an ear from
 * counted kernel rows, kernels a row and kernel weight in a trial abroad,
 * with the yellow corn hybrids' typical grain an ear following. Pod weight
 * a sheet repeated from another variety stays left out. VarietyYield::sync()
 * is idempotent: unchanged rows are skipped, changed ones replaced, rows gone
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
