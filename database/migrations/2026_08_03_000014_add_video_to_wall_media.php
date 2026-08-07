<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wall posts and comments can carry a compressed video (with a poster frame)
 * alongside the existing single image.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['as_community_wall_posts', 'as_community_wall_comments'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'videoPath')) {
                    $t->string('videoPath', 500)->nullable()->after('imagePath');
                }
                if (! Schema::hasColumn($table, 'videoPoster')) {
                    $t->string('videoPoster', 500)->nullable()->after('videoPath');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['as_community_wall_posts', 'as_community_wall_comments'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['videoPath', 'videoPoster'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->dropColumn($col);
                    }
                }
            });
        }
    }
};
