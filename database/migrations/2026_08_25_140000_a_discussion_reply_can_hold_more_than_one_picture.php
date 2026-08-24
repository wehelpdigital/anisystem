<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The other pictures on an answer inside a discussion.
 *
 * The wall's comments learned this first; a discussion is where the long
 * answers actually happen — "here is the leaf, here is the whole hill, here
 * is what I sprayed" — and it was the one place still asking for three
 * separate answers to say it.
 *
 * Same shape as everywhere else: the first picture stays in imagePath, where
 * every renderer written before today looks for it, and the set lives here.
 *
 * (The table is as_ prefixed. A migration aimed at the unprefixed name is a
 * silent no-op — the guard below turns "wrong table" into "nothing to do".)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_group_replies') || Schema::hasColumn('as_community_group_replies', 'imagePaths')) {
            return;
        }
        Schema::table('as_community_group_replies', function (Blueprint $t) {
            $t->json('imagePaths')->nullable()->after('imagePath');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_community_group_replies') && Schema::hasColumn('as_community_group_replies', 'imagePaths')) {
            Schema::table('as_community_group_replies', function (Blueprint $t) {
                $t->dropColumn('imagePaths');
            });
        }
    }
};
