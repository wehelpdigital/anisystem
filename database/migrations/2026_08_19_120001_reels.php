<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reels: a short vertical video, browsed in a carousel rather than a feed.
 *
 * Deliberately NOT a new content type. A reel is a wall post whose video
 * happens to be vertical and short, so everything a post can already do —
 * reactions, comments, bookmarks, sharing, the public link — works on the day
 * it ships, and none of it has to be written twice. Three columns are all the
 * difference: the flag that puts it in the carousel, how long it runs, and
 * what it is playing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_community_wall_posts', function (Blueprint $table) {
            $table->boolean('isReel')->default(false)->after('videoPoster');
            $table->unsignedSmallInteger('durationSec')->nullable()->after('isReel');
            $table->string('audioTitle', 160)->nullable()->after('durationSec');
            // The carousel's only question: the newest reels, whoever made them.
            $table->index(['isReel', 'deleteStatus', 'id'], 'wall_reels');
        });
    }

    public function down(): void
    {
        Schema::table('as_community_wall_posts', function (Blueprint $table) {
            $table->dropIndex('wall_reels');
            $table->dropColumn(['isReel', 'durationSec', 'audioTitle']);
        });
    }
};
