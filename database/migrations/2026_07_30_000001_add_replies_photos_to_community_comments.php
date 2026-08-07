<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Threaded comments: wall comments and group replies can now answer a parent
 * comment (parentId, kept two levels deep — a reply to a reply re-attaches to
 * the thread's top comment) and carry a photo (imagePath on the public disk).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('as_community_wall_comments', 'parentId')) {
            Schema::table('as_community_wall_comments', function (Blueprint $table) {
                $table->integer('parentId')->nullable()->index()->after('wallPostId');
                $table->string('imagePath', 500)->nullable()->after('body');
            });
        }

        if (! Schema::hasColumn('as_community_group_replies', 'parentId')) {
            Schema::table('as_community_group_replies', function (Blueprint $table) {
                $table->integer('parentId')->nullable()->index()->after('postId');
                $table->string('imagePath', 500)->nullable()->after('body');
            });
        }
    }

    public function down(): void
    {
        Schema::table('as_community_wall_comments', function (Blueprint $table) {
            $table->dropColumn(['parentId', 'imagePath']);
        });
        Schema::table('as_community_group_replies', function (Blueprint $table) {
            $table->dropColumn(['parentId', 'imagePath']);
        });
    }
};
