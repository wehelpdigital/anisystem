<?php

use App\Support\VarietyYield;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The sixth research round's inbred rice lane on the variety yield parts
 * (2026-10-20): grain weight and panicle length for 33 more inbreds from
 * their registration pages (2014 to 2016 lines, and Rc 224 from its own
 * sheet), spikelets, filled grains and tillers for Rc 182 from a trial
 * abroad of the same line; the copied sheet columns stay out. Also what
 * the leafy and fruit lanes saved so far: peanuts their pods, seeds and
 * shelling, soybeans and mungbeans their trial yields and days, a sitaw
 * its pod weight, a watermelon its fruits a plant. VarietyYield::sync() is
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
