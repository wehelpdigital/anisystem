<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Global Lots (2026-10-16, the owner): "the lot should not have what type of
 * crop, since it is not always linked to the lot: it changes often". The
 * crops saved on the lots so far are cleared; the column stays, unused.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_field_profiles') || ! Schema::hasColumn('as_field_profiles', 'crop')) {
            return;
        }
        DB::table('as_field_profiles')->whereNotNull('crop')->update(['crop' => null]);
    }

    public function down(): void
    {
        // The crops are not brought back.
    }
};
