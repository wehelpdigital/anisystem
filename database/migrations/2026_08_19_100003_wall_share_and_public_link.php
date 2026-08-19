<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sharing, in the two directions people actually mean by the word.
 *
 * Inward: `sharedPostId` lets a post carry another post, so "share to my wall"
 * is a real post with your own words above someone else's — not a copy that
 * drifts from the original the moment either is edited.
 *
 * Outward: `publicToken` is the unguessable key to a read-only page anybody
 * can open, minted only when a member actually asks for a link. A post nobody
 * shared has no token and therefore no public address at all — the same
 * contract the schedule shares already use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_community_wall_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('sharedPostId')->nullable()->after('body');
            $table->string('publicToken', 64)->nullable()->unique()->after('sharedPostId');
            $table->index(['sharedPostId'], 'wall_shared_of');
        });
    }

    public function down(): void
    {
        Schema::table('as_community_wall_posts', function (Blueprint $table) {
            $table->dropIndex('wall_shared_of');
            $table->dropUnique(['publicToken']);
            $table->dropColumn(['sharedPostId', 'publicToken']);
        });
    }
};
