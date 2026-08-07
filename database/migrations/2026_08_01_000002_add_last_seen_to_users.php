<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Last-activity timestamp so the community can show who is currently online
 * (a green dot on their avatar). Refreshed, throttled, on each request.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('anisystem_users', 'lastSeenAt')) {
            return;
        }
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->timestamp('lastSeenAt')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('lastSeenAt');
        });
    }
};
