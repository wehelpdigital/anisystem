<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The vegetable sellers' figures for the variety yield parts (2026-10-19):
 * the first load (2026_10_19_255000) went out while the vegetable research
 * was still running. This loads the finished seed files: fruit weights,
 * fruits a plant and the harvest a plant from the seller sheets, kept only
 * where they hold up (a yield a plant printed in a mislabelled field stays
 * only where fruits times fruit weight back it), and Sugar Baby F1 as its
 * own row apart from the old open pollinated Sugar Baby.
 * VarietyYield::sync() is idempotent: unchanged rows are skipped, changed
 * ones replaced, rows gone from the files retired.
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
