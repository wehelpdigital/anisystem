<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The variety yield parts after the book fixes (2026-10-20): Bigante Plus
 * is Rc 548H, so its trade names, its seed pack and its seller page's
 * figures move there from Rc 314H; Rc 696H and Rc 716H gain their lines
 * (LP 906, SAC 01); the sweet potato line names follow the books; cacao
 * clones are fitted on their tonnes of dry beans; LuzonFed G6 gains its
 * stalk diameter; cane rows read the stalk model. Also the sixth round of
 * research: grain weight for more rice hybrids and inbreds from their
 * registration pages, spikelets and filled share from more trials, and
 * what a plant gives for more onions, cabbages, carrots, radishes and a
 * pechay; and the copies left out where a later registration sheet
 * repeats an earlier variety's grain weight and panicle length.
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
