<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The other clips on a wall post.
 *
 * Comments, discussion replies and topics all learned to carry three; the
 * wall post itself was still taking one. Same shape as everywhere else:
 * the first stays in videoPath where every older renderer looks, and the
 * set lives here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_wall_posts') || Schema::hasColumn('as_community_wall_posts', 'videoPaths')) {
            return;
        }
        Schema::table('as_community_wall_posts', function (Blueprint $t) {
            $t->json('videoPaths')->nullable()->after('videoPoster');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_community_wall_posts') && Schema::hasColumn('as_community_wall_posts', 'videoPaths')) {
            Schema::table('as_community_wall_posts', function (Blueprint $t) {
                $t->dropColumn('videoPaths');
            });
        }
    }
};
