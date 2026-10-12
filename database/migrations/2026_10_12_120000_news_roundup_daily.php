<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Latest in Agriculture (2026-10-12): the farm news roundup goes out every
 * day there is news, dated today, instead of every three days. The mother
 * app's setting can still change it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_settings')) {
            return;
        }
        DB::table('as_site_settings')->updateOrInsert(['key' => 'news.every_days'], ['value' => '1', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('as_site_settings')->where('key', 'news.every_days')->update(['value' => '3']);
    }
};
