<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PalayCheck is named nowhere but The Stash (the owner, 2026-10-17: "nah
 * remove this don't say some came from PalayCheck").
 *
 *  - /blog/palaycheck-key-checks leaves the site. Its row is removed the way
 *    the mother app removes a page (deleteStatus 0, the row kept), and its
 *    Technician's Blog article goes with it. The address answers 301 with
 *    /blog/increase-rice-yield-per-hectare, which tells the nine checks of a
 *    rice season in our words (SitePages::MOVED); the sitemap and llms.txt
 *    list live pages only, so they drop it.
 *  - 27 pages say what they said about it in our words (the seeds), synced
 *    here; a page edited in the mother app keeps its edits (none was, and
 *    the page guard scrubs the name from such a page anyway).
 *  - The search keywords naming it ("palay check system") are no longer
 *    offered to the page writers (as_seo_keywords, deleteStatus 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        $ids = DB::table('as_site_pages')->where('section', 'blog')->where('slug', 'palaycheck-key-checks')->pluck('id')->all();
        if ($ids) {
            DB::table('as_site_pages')->whereIn('id', $ids)->where('deleteStatus', 1)
                ->update(['deleteStatus' => 0, 'updated_at' => now()]);
            if (Schema::hasTable('as_community_blog_posts') && Schema::hasColumn('as_community_blog_posts', 'sitePageId')) {
                DB::table('as_community_blog_posts')->whereIn('sitePageId', $ids)->where('deleteStatus', 1)
                    ->update(['deleteStatus' => 0, 'updated_at' => now()]);
            }
        }
        if (Schema::hasTable('as_seo_keywords')) {
            DB::table('as_seo_keywords')->where('deleteStatus', 1)
                ->where(fn ($q) => $q->where('keyword', 'like', '%palay check%')->orWhere('keyword', 'like', '%palaycheck%'))
                ->update(['deleteStatus' => 0, 'updated_at' => now()]);
        }
        // The pages in our words; the Tech Blog articles follow them.
        SitePages::sync();
    }

    public function down(): void
    {
        // The page stays off the site.
    }
};
