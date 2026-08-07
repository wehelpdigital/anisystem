<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A member's personal video album on their community profile. Videos are
 * compressed to ≤720p MP4 on upload (with a poster frame) before being stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_profile_videos')) {
            return;
        }
        Schema::create('as_community_profile_videos', function (Blueprint $table) {
            $table->id();
            $table->integer('userId');
            $table->string('videoPath', 255);
            $table->string('posterPath', 255)->nullable();
            $table->string('caption', 255)->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index(['userId', 'deleteStatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_profile_videos');
    }
};
