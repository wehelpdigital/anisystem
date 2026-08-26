<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A blog story wears more than one cover.
 *
 * The card in the community's blog cycles through them and a reader can
 * swipe between them; the old single column stays as the first cover for
 * every article that has not been given more.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_blog_posts')
            || Schema::hasColumn('as_community_blog_posts', 'coverPaths')) {
            return;
        }
        Schema::table('as_community_blog_posts', function (Blueprint $table) {
            $table->json('coverPaths')->nullable()->after('coverImagePath');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_community_blog_posts', 'coverPaths')) {
            Schema::table('as_community_blog_posts', function (Blueprint $table) {
                $table->dropColumn('coverPaths');
            });
        }
    }
};
