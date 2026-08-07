<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Technician's Blog: articles the AniSenso team publishes for the community
 * (managed in the mother app), plus members' comments. Shown beside the
 * Discussions tab in AniSystem. House conventions: camelCase, integer
 * deleteStatus, no FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_blog_posts')) {
            Schema::create('as_community_blog_posts', function (Blueprint $table) {
                $table->id();
                $table->string('title', 191);
                $table->string('slug', 191)->nullable()->index();
                $table->string('coverImagePath', 500)->nullable();
                $table->string('excerpt', 500)->nullable();
                $table->longText('body')->nullable();
                $table->string('authorName', 120)->nullable();
                $table->boolean('isPublished')->default(0)->index();
                $table->timestamp('publishedAt')->nullable();
                $table->integer('viewCount')->default(0);
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('as_community_blog_comments')) {
            Schema::create('as_community_blog_comments', function (Blueprint $table) {
                $table->id();
                $table->integer('blogPostId')->index();
                $table->integer('userId')->index();
                $table->text('body');
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_blog_comments');
        Schema::dropIfExists('as_community_blog_posts');
    }
};
