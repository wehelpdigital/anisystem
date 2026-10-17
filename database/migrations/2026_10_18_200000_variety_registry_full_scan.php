<?php

use App\Support\VarietyRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/*
 * The rest of the registration papers (2026-10-18): the first load
 * (2026_10_18_140000) went out while the last pages were still being read,
 * so about 400 varieties had their trial facts and the rest only their
 * names. This loads the whole scan from database/variety-registry/registry.json.
 * VarietyRegistry::sync() is idempotent: unchanged rows are skipped, changed
 * ones replaced, rows gone from the file retired.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_variety_registry')) {
            return;
        }
        VarietyRegistry::sync(database_path(VarietyRegistry::FILE));
        \Illuminate\Support\Facades\Cache::forget('variety-registry:' . config('database.default') . ':ready');
    }

    public function down(): void
    {
        // The rows stay; the first migration's down() drops the table.
    }
};
