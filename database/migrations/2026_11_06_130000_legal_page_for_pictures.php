<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The page a picture's license points at (App\Support\PictureRights): as a row,
 * so the sitemap lists it and the owner can edit it in the mother app. Written
 * once, and never over a row that already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_legal_pages') || DB::table('as_legal_pages')->where('slug', 'pictures')->exists()) {
            return;
        }
        $body = @file_get_contents(resource_path('views/legal/defaults/pictures.html'));
        if ($body) {
            DB::table('as_legal_pages')->insert([
                'slug' => 'pictures', 'title' => 'Pictures on anee.io', 'body' => $body,
                'sortOrder' => (int) DB::table('as_legal_pages')->max('sortOrder') + 1, 'isPublished' => 1, 'deleteStatus' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The row may hold the owner's own words by now.
    }
};
