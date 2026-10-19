<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The sixth round's fruit lane closes on the variety yield parts
 * (2026-10-20): California Wonder bell pepper its fruit weight from the
 * seller; a tomato's yield a plant that was only an estimate, and a
 * watermelon's fruits a vine that were a thinning practice, are left out.
 * Also the seventh round's first rice hybrid figures: panicle length for
 * Rc 720H to 728H from their registration pages, and Rc 722H's own row.
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
