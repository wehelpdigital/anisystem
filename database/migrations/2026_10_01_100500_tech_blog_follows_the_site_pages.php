<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Technician's Blog follows the public site's guides and blog
 * (App\Support\TechBlog): each article remembers the page it came from, the
 * demo articles step aside, and the pages are copied in.
 *
 * The demo articles are hidden, not deleted (deleteStatus 0), with their
 * comments, so they can be brought back by id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_blog_posts')) {
            return;
        }
        if (! Schema::hasColumn('as_community_blog_posts', 'sitePageId')) {
            Schema::table('as_community_blog_posts', function (Blueprint $table) {
                $table->unsignedBigInteger('sitePageId')->nullable()->after('id')->index();
            });
        }

        // Everything written before this is demo content.
        DB::table('as_community_blog_posts')->whereNull('sitePageId')->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        if (Schema::hasTable('as_site_pages')) {
            \App\Support\TechBlog::sync();
        }
    }

    public function down(): void
    {
        // The demo articles can be brought back by id; the column stays, as
        // dropping it would orphan the synced articles.
    }
};
