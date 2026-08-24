<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A film clip on an answer inside a discussion.
 *
 * The topic that starts a discussion has been able to carry one since the
 * beginning; the answers under it could not, which is backwards — "here is
 * what it looks like when I do it" is an answer, not a question.
 *
 * The same two columns a topic uses: the clip, and the frame shown before it
 * plays. A clip pointed at in the gallery has no poster of its own, so the
 * poster stays nullable.
 *
 * (as_ prefixed, like every table this app owns. A migration aimed at the
 * unprefixed name is a silent no-op under the guard below.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_group_replies')) {
            return;
        }
        Schema::table('as_community_group_replies', function (Blueprint $t) {
            if (! Schema::hasColumn('as_community_group_replies', 'videoPath')) {
                $t->string('videoPath', 255)->nullable()->after('imagePaths');
            }
            if (! Schema::hasColumn('as_community_group_replies', 'videoPoster')) {
                $t->string('videoPoster', 255)->nullable()->after('videoPath');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_community_group_replies')) {
            return;
        }
        Schema::table('as_community_group_replies', function (Blueprint $t) {
            foreach (['videoPath', 'videoPoster'] as $col) {
                if (Schema::hasColumn('as_community_group_replies', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
