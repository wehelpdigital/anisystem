<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A discussion gets a face and a banner, and its posts get the same media a
 * wall post has always had.
 *
 * `coverImagePath` already existed and was doing duty as the avatar, which is
 * why the cards looked bare: one picture cannot be both the round badge and
 * the wide banner. The old column keeps its meaning (the badge) and a new
 * `bannerImagePath` carries the cover, so nothing that already points at a
 * group's picture has to be rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_community_groups', function (Blueprint $table) {
            $table->string('bannerImagePath', 500)->nullable()->after('coverImagePath');
        });

        Schema::table('as_community_group_posts', function (Blueprint $table) {
            // A clip and its poster frame, exactly as the wall stores them, so
            // one renderer can draw either kind of post.
            $table->string('videoPath', 500)->nullable()->after('imagePath');
            $table->string('videoPoster', 500)->nullable()->after('videoPath');
        });
    }

    public function down(): void
    {
        Schema::table('as_community_group_posts', function (Blueprint $table) {
            $table->dropColumn(['videoPath', 'videoPoster']);
        });
        Schema::table('as_community_groups', function (Blueprint $table) {
            $table->dropColumn('bannerImagePath');
        });
    }
};
