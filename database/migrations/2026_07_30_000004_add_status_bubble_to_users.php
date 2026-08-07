<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A short "status bubble" — a thought-bubble that floats over the member's
 * profile picture (e.g. "🌧️ Waiting for rain", "Harvest week!").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('anisystem_users', 'statusBubble')) {
            return;
        }
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->string('statusBubble', 120)->nullable()->after('farmingMethod');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('statusBubble');
        });
    }
};
