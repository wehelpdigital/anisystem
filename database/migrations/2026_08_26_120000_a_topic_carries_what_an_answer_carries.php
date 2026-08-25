<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The other pictures and clips on a discussion topic.
 *
 * The answers under a topic learned this first (imagePaths/videoPaths on
 * as_community_group_replies); a topic is the same act with a bigger card,
 * and it was taking one picture and one clip where an answer takes eight
 * and three. Same shape as everywhere else: the first stays in imagePath /
 * videoPath where every older renderer looks, and the set lives here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_group_posts')) {
            return;
        }
        Schema::table('as_community_group_posts', function (Blueprint $t) {
            if (! Schema::hasColumn('as_community_group_posts', 'imagePaths')) {
                $t->json('imagePaths')->nullable()->after('imagePath');
            }
            if (! Schema::hasColumn('as_community_group_posts', 'videoPaths')) {
                $t->json('videoPaths')->nullable()->after('videoPoster');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_community_group_posts')) {
            return;
        }
        Schema::table('as_community_group_posts', function (Blueprint $t) {
            if (Schema::hasColumn('as_community_group_posts', 'imagePaths')) {
                $t->dropColumn('imagePaths');
            }
            if (Schema::hasColumn('as_community_group_posts', 'videoPaths')) {
                $t->dropColumn('videoPaths');
            }
        });
    }
};
