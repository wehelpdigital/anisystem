<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two one-sided relationships the community was missing.
 *
 * Following is NOT a connection: a co-farmer link is mutual and asks
 * permission, while following is one person deciding to keep up with another
 * and needs nobody's consent. Kept in its own table for exactly that reason —
 * folding it into as_community_connections would have made "friend" and
 * "reader" the same word.
 *
 * A bookmark is even more private: nothing about it is visible to the person
 * whose post was kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_community_follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('followerUserId');
            $table->unsignedBigInteger('followedUserId');
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();

            // One row per pair: following twice is still following once, and
            // the unique key is what lets a toggle be an upsert.
            $table->unique(['followerUserId', 'followedUserId'], 'follow_pair');
            // "Whose posts do I lift?" is the feed's hottest question.
            $table->index(['followerUserId', 'deleteStatus'], 'follow_by_follower');
            $table->index(['followedUserId', 'deleteStatus'], 'follow_by_followed');
        });

        Schema::create('as_community_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            // Wall posts today; the column is a type so a saved discussion or
            // blog article needs a row, not a table.
            $table->string('targetType', 32)->default('wall');
            $table->unsignedBigInteger('targetId');
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();

            $table->unique(['userId', 'targetType', 'targetId'], 'bookmark_one');
            $table->index(['userId', 'deleteStatus'], 'bookmark_by_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_bookmarks');
        Schema::dropIfExists('as_community_follows');
    }
};
