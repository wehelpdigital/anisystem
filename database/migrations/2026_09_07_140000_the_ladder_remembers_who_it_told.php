<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which rung the bell last congratulated this member for, so a level-up is
 * announced exactly once.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('anisystem_users', 'lastRankNotified')) {
            return;
        }

        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->unsignedSmallInteger('lastRankNotified')->default(0);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('anisystem_users', 'lastRankNotified')) {
            return;
        }

        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('lastRankNotified');
        });
    }
};
