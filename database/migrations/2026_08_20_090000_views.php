<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many times a thing has been looked at.
 *
 * A counter on the row rather than a table of view events: the question is
 * only ever "how many", nobody has asked who or when, and a farm's wall would
 * otherwise grow a row per glance. Counted every time — the same person
 * looking twice is two — because that is what the owner asked for and it is
 * what "views" means on every wall people already read.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'as_community_wall_posts',   // posts and stories alike
            'as_community_group_posts',  // a discussion's topics
            'as_community_groups',       // the discussion room itself
        ] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'viewCount')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('viewCount')->default(0)->after('deleteStatus');
            });
        }
    }

    public function down(): void
    {
        foreach (['as_community_wall_posts', 'as_community_group_posts', 'as_community_groups'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'viewCount')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('viewCount'));
            }
        }
    }
};
