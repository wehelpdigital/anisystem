<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Seed quality on the variety yield parts (2026-10-19): the Seed Count and
 * Distance Calculator now reads a variety's germination (germ), the least
 * its seed guarantees (germMin), the share of seedbed seedlings fit to plant
 * (seedFit) and the losses after coming up (loss) from the shelf, beside the
 * yield parts. The figures are the printed lot tests and bag guarantees of
 * the varieties sold (seller), re-keyed to the book varieties by name; a
 * seller variety the books lack gets a row of its own. Also the corn trial
 * kernel weights saved so far (extra). VarietyYield::sync() is idempotent:
 * unchanged rows are skipped, changed ones replaced, rows gone retired.
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
