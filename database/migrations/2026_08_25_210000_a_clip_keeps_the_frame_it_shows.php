<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The frame a clip shows before it plays, once it has been made.
 *
 * A clip uploaded through the composer gets a poster at the moment it is
 * stored. Everything older than that — and everything referenced out of the
 * gallery rather than uploaded — has none, and a browser will not make one
 * for a list of thumbnails.
 *
 * So one is cut on demand and remembered here, keyed by the clip's own path:
 * the work happens once, the second time it is asked for it is a lookup. The
 * key is a hash because a path is longer than an index wants to be.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_video_posters')) {
            return;
        }
        Schema::create('as_video_posters', function (Blueprint $t) {
            $t->id();
            $t->char('videoKey', 40)->unique();   // sha1 of the video's stored path
            $t->string('videoPath', 500);
            $t->string('posterPath', 500);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_video_posters');
    }
};
