<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A chat with Anee from a worker account can be read by the farm owner (2026-11-06, the
 * owner). farmOwnerId names the boss whose farm it was asked in; a chat from a personal
 * account leaves it empty. See App\Support\FarmShelf.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anisystem_ai_conversations') && ! Schema::hasColumn('anisystem_ai_conversations', 'farmOwnerId')) {
            Schema::table('anisystem_ai_conversations', function (Blueprint $t) {
                $t->unsignedBigInteger('farmOwnerId')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('anisystem_ai_conversations') && Schema::hasColumn('anisystem_ai_conversations', 'farmOwnerId')) {
            Schema::table('anisystem_ai_conversations', function (Blueprint $t) {
                $t->dropColumn('farmOwnerId');
            });
        }
    }
};
