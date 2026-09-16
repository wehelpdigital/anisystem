<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every account says which country its farm is in (2026-09-16).
 *
 * ISO 3166-1 alpha-2. Everyone who signed up before this was a Filipino
 * farmer on a Philippine app, so every existing row is 'PH' — the default
 * does that, and the explicit update below makes it so even on a database
 * whose default is not honoured the same way.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('anisystem_users', 'country')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->char('country', 2)->default('PH')->after('province')->index();
            });
        }
        DB::table('anisystem_users')->whereNull('country')->orWhere('country', '')->update(['country' => 'PH']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('anisystem_users', 'country')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->dropColumn('country');
            });
        }
    }
};
