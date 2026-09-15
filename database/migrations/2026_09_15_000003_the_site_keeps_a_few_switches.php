<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A shelf of site-wide switches, one row per key, set from the mother app
 * and read by anee.io. The first one on it: whether the public site may
 * be indexed by search engines at all (off until the owner says so; the
 * app behind the login is never indexable, switch or no switch).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_settings')) {
            Schema::create('as_site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 64)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! DB::table('as_site_settings')->where('key', 'seo.publicIndexable')->exists()) {
            DB::table('as_site_settings')->insert([
                'key' => 'seo.publicIndexable', 'value' => '0',
                'created_at' => now('Asia/Manila'), 'updated_at' => now('Asia/Manila'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_site_settings');
    }
};
