<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tap-to-react on community group posts and replies. One row per user per
 * target; switching reactions updates the row, tapping the same one removes
 * it (hard delete — a reaction is not audit data).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_reactions')) {
            return;
        }

        Schema::create('as_community_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('targetType', 10);   // post | reply
            $table->integer('targetId');
            $table->integer('userId');
            $table->string('reaction', 16);     // like | love | helpful
            $table->timestamps();

            $table->unique(['targetType', 'targetId', 'userId'], 'community_reaction_unique');
            $table->index(['targetType', 'targetId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_reactions');
    }
};
