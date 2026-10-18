<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the third research run's second batch (2026-10-19):
 * cacao clones' dry beans a tree from pods, beans a pod and bean weight,
 * banana lines' bunch and finger weight, mungbean pods, seeds a pod and seed
 * weight, more rice hybrid grain weights and filled shares, and the typical
 * rows that follow (cacao, banana and mungbean now have one). Read from the
 * registration papers, sellers and trials. VarietyYield::sync() is
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
