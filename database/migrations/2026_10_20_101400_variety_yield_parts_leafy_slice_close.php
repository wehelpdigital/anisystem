<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the leafy and legume research list closes
 * (2026-10-21): UPL Mg 1 (Pag-asa 1) gains its pods a plant, seeds a pod,
 * seed weight and pod length from a 1980 Leyte field trial, and the typical
 * mungbean harvest a plant follows; UPL Mg 2, 3 and 5 their days and yields;
 * La Carlota Soy 1 its pods a plant, seed weight and days from its release
 * trials; CSL 14 and EG Ps 5 their pod length, seed weight and days from the
 * 1990 to 1991 national pole sitao trial; Marion Market and Stein's Flat
 * Dutch cabbage their head weight; Mithi, Biyaya 10, Moket and Biyaya peanut
 * their pods, shelling or days; Tiwala 6 soybean and Slobolt lettuce join.
 * Every other old line still open was looked for and found without printed
 * yield parts, so the crop's typical figures keep answering for them.
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
