<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin moderation: a staff member (in the mother app) can restrict any piece
 * of community content — wall post, profile-wall comment, discussion post or
 * reply. When set, AniSystem hides the body/media and shows an "admin
 * restricted this content" notice in its place. `restrictedReason` is an
 * optional short note shown to moderators.
 */
return new class extends Migration
{
    private array $tables = [
        'as_community_wall_posts',
        'as_community_wall_comments',
        'as_community_group_posts',
        'as_community_group_replies',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'isRestricted')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->boolean('isRestricted')->default(0)->index();
                    $t->string('restrictedReason', 255)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'isRestricted')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn(['isRestricted', 'restrictedReason']);
                });
            }
        }
    }
};
