<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the third research run's third batch (2026-10-19):
 * read off the registration papers' pages. Rice hybrids gain their 1,000
 * grain weight, panicle length and, for the newer ones, productive tillers
 * a hill; mungbeans their pods, seeds a pod and seed weight; stringbeans
 * and garlic their trial yields; cacao clones the dry beans a tree the
 * papers print (where pods x beans x bean weight counted about twice the
 * beans a tree gives, the printed figure leads, or the kind's typical one);
 * coffee clones the green beans a tree; three white corn hybrids their ear
 * length and shelling; soybean and peanut lines their pods, seeds and seed
 * weight; inbred rice more spikelets and grain weights from trials; mango,
 * calamansi, coconut, pineapple and banana lines a harvest a tree or a
 * fruit; a few fruit vegetables the seller's fruits and fruit weight.
 * Doubted figures stay out (the 1999 coffee bean weights, a faint decimal,
 * pods counted a season, a trial count read as roots a plant). The typical
 * rows follow (coffee and soybean gain one). VarietyYield::sync() is idempotent: unchanged
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
