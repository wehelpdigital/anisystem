<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A post could carry one photo, and a farmer's answer is usually three: the
 * leaf, the whole hill, and the bag of whatever they sprayed.
 *
 * The old column stays and keeps meaning what it meant — the first picture —
 * so every renderer that reads it (the profile wall, a shared card, the
 * notification preview) goes on working untouched. The list is what a post
 * with several is drawn from.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_community_wall_posts', 'imagePaths')) {
            return;
        }

        Schema::table('as_community_wall_posts', function (Blueprint $table) {
            $table->json('imagePaths')->nullable()->after('imagePath');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_community_wall_posts', 'imagePaths')) {
            return;
        }

        Schema::table('as_community_wall_posts', function (Blueprint $table) {
            $table->dropColumn('imagePaths');
        });
    }
};
