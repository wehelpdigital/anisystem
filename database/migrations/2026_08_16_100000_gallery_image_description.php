<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A picture in an album gets a description of its own.
 *
 * `caption` is the label under the tile and is already doing that job well —
 * a few words, read at a glance. What it cannot hold is the sentence that
 * explains the picture: which corner, whose pump, what it looked like the
 * week before. Quick Capture now asks for both on every item it files, so
 * the longer half needs somewhere to live that is not a truncated caption.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_gallery_images', function (Blueprint $table) {
            $table->text('description')->nullable()->after('caption');
        });
    }

    public function down(): void
    {
        Schema::table('as_gallery_images', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
