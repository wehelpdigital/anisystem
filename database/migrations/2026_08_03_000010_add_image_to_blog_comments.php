<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photos in Technician's Blog comments. Compressed to WebP on upload, like
 * every other community image.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_blog_comments') && ! Schema::hasColumn('as_community_blog_comments', 'imagePath')) {
            Schema::table('as_community_blog_comments', function (Blueprint $table) {
                $table->string('imagePath', 500)->nullable()->after('body');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_community_blog_comments', 'imagePath')) {
            Schema::table('as_community_blog_comments', function (Blueprint $table) {
                $table->dropColumn('imagePath');
            });
        }
    }
};
