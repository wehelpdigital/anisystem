<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the eighth research round's seed catalogue lane
 * (2026-10-20): seven coffee clones gain their green bean yield a hectare
 * (the grams a tree they carried were that same figure in the wrong
 * column, so those go), two cacao clones their beans a tree, sugarcane
 * lines their cane yield a hectare and their other names, cassava lines
 * their trial yields, two mangoes their fruits a tree, two sitaw lines
 * their pod weight; and the seventh and eighth rounds' rice so far: grain
 * weight for 26 more inbreds and hybrids from their registration pages,
 * spikelets and filled share from a trial, with copied sheet columns left
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
