<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The sixth round's leafy lane closes on the variety yield parts
 * (2026-10-20): Batanes Red garlic its cloves a bulb, and two vegetable
 * soybeans lose a fresh green pod yield that read as dry beans. Also the
 * seventh round's first inbred rice figures: grain weight and panicle
 * length for a glutinous rice and two japonicas from their registration
 * pages, and the MS 11 check column for Rc 170. VarietyYield::sync() is
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
