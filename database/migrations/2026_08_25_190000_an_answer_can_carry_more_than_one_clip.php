<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The other clips on an answer inside a discussion.
 *
 * Same shape the pictures use: the first stays in videoPath, where the
 * renderer written yesterday looks for it, and the set lives here. Three is
 * the most an answer takes — a film is two orders of magnitude heavier than
 * a photograph, and an answer that needs four of them is a topic of its own.
 *
 * (as_ prefixed, like every table this app owns.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_group_replies') || Schema::hasColumn('as_community_group_replies', 'videoPaths')) {
            return;
        }
        Schema::table('as_community_group_replies', function (Blueprint $t) {
            $t->json('videoPaths')->nullable()->after('videoPoster');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_community_group_replies') && Schema::hasColumn('as_community_group_replies', 'videoPaths')) {
            Schema::table('as_community_group_replies', function (Blueprint $t) {
                $t->dropColumn('videoPaths');
            });
        }
    }
};
