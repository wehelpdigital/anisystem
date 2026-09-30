<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The owner's call (2026-10-01): a blog post says where it is shown, the
 * public blog, the members' Technician's Blog, or both (as_site_pages.showIn:
 * public | tech | both), and only blog posts reach the Technician's Blog;
 * the crop guides and crop problems stay on the public site.
 *
 * as_writer_jobs holds Anee writing a page for the mother app's builder
 * ("Write with Anee"): the brief, where the job is, and the page it wrote.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_site_pages') && ! Schema::hasColumn('as_site_pages', 'showIn')) {
            Schema::table('as_site_pages', function (Blueprint $t) {
                $t->string('showIn', 8)->default('both')->after('status');
            });
        }

        if (! Schema::hasTable('as_writer_jobs')) {
            Schema::create('as_writer_jobs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('pageId')->nullable();
                $t->longText('brief');
                $t->string('status', 12)->default('pending');
                $t->string('phase', 24)->nullable();
                $t->unsignedTinyInteger('try')->nullable();
                $t->longText('result')->nullable();
                $t->string('error', 500)->nullable();
                $t->string('createdBy', 80)->nullable();
                $t->timestamps();
            });
        }

        // The Technician's Blog drops the guides now: redraw it from the pages.
        if (Schema::hasColumn('as_community_blog_posts', 'sitePageId')) {
            \App\Support\TechBlog::sync();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_writer_jobs');
        if (Schema::hasColumn('as_site_pages', 'showIn')) {
            Schema::table('as_site_pages', fn (Blueprint $t) => $t->dropColumn('showIn'));
        }
    }
};
