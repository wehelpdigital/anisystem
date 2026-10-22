<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Technician's Blog gets the public blog's categories (the owner,
 * 2026-10-23: "the technicians blog has no categories yet to show the
 * contents"). Each article keeps the category of the site page it mirrors
 * (App\Support\TechBlog fills it on every sync from now on); the ones
 * already there take it here at once. A post written by hand in the mother
 * app has none and shows under All.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_blog_posts')) {
            return;
        }
        if (! Schema::hasColumn('as_community_blog_posts', 'category')) {
            Schema::table('as_community_blog_posts', function (Blueprint $t) {
                $t->string('category', 80)->nullable()->after('sitePageId');
                $t->index('category');
            });
        }
        if (Schema::hasTable('as_site_pages') && Schema::hasColumn('as_community_blog_posts', 'sitePageId')) {
            DB::table('as_community_blog_posts as b')
                ->join('as_site_pages as p', 'p.id', '=', 'b.sitePageId')
                ->whereNotNull('p.category')
                ->update(['b.category' => DB::raw('LEFT(TRIM(p.category), 80)')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_community_blog_posts', 'category')) {
            Schema::table('as_community_blog_posts', function (Blueprint $t) {
                $t->dropIndex(['category']);
                $t->dropColumn('category');
            });
        }
    }
};
