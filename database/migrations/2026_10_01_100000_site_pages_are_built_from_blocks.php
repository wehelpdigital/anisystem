<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public site's guides, blog and feature pages (2026-10-01).
 *
 * One row per page: where it lives (section + slug), what search engines read
 * (title, SEO title, meta description, focus keyphrase), and the page itself
 * as blocks the mother app's builder edits by drag and drop.
 *
 * The pages anee.io ships with come from database/site-pages/*.json
 * (App\Support\SitePages::sync). `seedJson` keeps that shipped version, so
 * the mother app can put a page back the way it came; `editedAt` marks a page
 * someone changed there, which a later sync then leaves alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_site_pages')) {
            return;
        }
        Schema::create('as_site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('section', 16);
            $table->string('slug', 120);
            $table->string('lang', 5)->default('en');
            $table->string('category', 60)->nullable();
            $table->string('title', 200);
            $table->string('metaTitle', 120)->nullable();
            $table->string('metaDescription', 320)->nullable();
            $table->string('focusKeyword', 120)->nullable();
            $table->text('keywords')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('heroImage')->nullable();
            $table->longText('blocks')->nullable();
            $table->string('status', 12)->default('published');
            $table->integer('sortOrder')->default(0);
            $table->timestamp('publishedAt')->nullable();
            $table->timestamp('editedAt')->nullable();
            $table->string('editedBy', 80)->nullable();
            $table->char('seedHash', 40)->nullable();
            $table->longText('seedJson')->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
            $table->unique(['section', 'slug']);
            $table->index(['section', 'status', 'deleteStatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_site_pages');
    }
};
