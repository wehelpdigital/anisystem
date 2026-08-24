<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The other clips on a wall comment.
 *
 * The discussions' answers learned this first; a comment under a post on the
 * wall is the same act with a different backdrop, and it was taking one clip
 * where an answer takes three.
 *
 * Same shape as everywhere else: the first stays in videoPath, where the
 * renderer already looks, and the set lives here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_wall_comments') || Schema::hasColumn('as_community_wall_comments', 'videoPaths')) {
            return;
        }
        Schema::table('as_community_wall_comments', function (Blueprint $t) {
            $t->json('videoPaths')->nullable()->after('videoPoster');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_community_wall_comments') && Schema::hasColumn('as_community_wall_comments', 'videoPaths')) {
            Schema::table('as_community_wall_comments', function (Blueprint $t) {
                $t->dropColumn('videoPaths');
            });
        }
    }
};
