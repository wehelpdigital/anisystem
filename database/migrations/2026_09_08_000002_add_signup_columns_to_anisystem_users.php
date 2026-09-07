<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-serve registration grows two facts about a member: when their email
 * was proven real (null = still pending, and login is refused), and which
 * Google account signs them in (null = password only). Guarded per column —
 * this table lives on the shared production database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('anisystem_users', 'emailVerifiedAt')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->dateTime('emailVerifiedAt')->nullable()->after('password');
            });
        }

        if (! Schema::hasColumn('anisystem_users', 'googleId')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->string('googleId', 64)->nullable()->after('emailVerifiedAt')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('anisystem_users', 'googleId')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->dropColumn('googleId');
            });
        }

        if (Schema::hasColumn('anisystem_users', 'emailVerifiedAt')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->dropColumn('emailVerifiedAt');
            });
        }
    }
};
