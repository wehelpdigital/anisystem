<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Post-harvest observations can carry several photos, not just one. Store the
 * list of relative image paths as JSON. The legacy single `imagePath` column
 * stays (kept in sync with the first image) for backward compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_post_harvests', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_post_harvests', 'imagePaths')) {
                $table->json('imagePaths')->nullable()->after('imagePath');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_post_harvests', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_post_harvests', 'imagePaths')) {
                $table->dropColumn('imagePaths');
            }
        });
    }
};
