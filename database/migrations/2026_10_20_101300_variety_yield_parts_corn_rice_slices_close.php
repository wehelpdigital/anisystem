<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * Variety yield parts, the corn and inbred rice research lists close
 * (2026-10-20): NSIC Rc 27 (Katihan 3) gains its 1,000 grain weight and
 * panicle length from its registration data sheet, and the typical inbred
 * grain weight follows; Bio Corn Hi Fly and B9909G gain the seller's row and
 * hill spacing. Every other corn and inbred rice variety still open was
 * looked for and found without printed yield parts, so the crop's typical
 * figures keep answering for them. VarietyYield::sync() is idempotent:
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
