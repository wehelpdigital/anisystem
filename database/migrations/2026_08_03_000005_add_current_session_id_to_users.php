<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single active session per account. We record the session id that currently
 * "owns" the account; a request from any other session is signed out (its
 * remember cookie stays valid, so returning re-logs in and re-claims the slot).
 * This prevents two people using one account at the same time.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('anisystem_users', 'currentSessionId')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->string('currentSessionId', 64)->nullable()->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('anisystem_users', 'currentSessionId')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->dropColumn('currentSessionId');
            });
        }
    }
};
