<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A wall comment's other pictures.
 *
 * The same shape a post already carries: the first picture stays in
 * imagePath, where every renderer that was written before today looks for it,
 * and the whole set lives here. An answer is often "here, look" three times
 * over — a leaf, the same leaf close up, the row it came from — and it was
 * costing three comments to say.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_wall_comments') || Schema::hasColumn('as_community_wall_comments', 'imagePaths')) {
            return;
        }
        Schema::table('as_community_wall_comments', function (Blueprint $t) {
            $t->json('imagePaths')->nullable()->after('imagePath');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('as_community_wall_comments') && Schema::hasColumn('as_community_wall_comments', 'imagePaths')) {
            Schema::table('as_community_wall_comments', function (Blueprint $t) {
                $t->dropColumn('imagePaths');
            });
        }
    }
};
