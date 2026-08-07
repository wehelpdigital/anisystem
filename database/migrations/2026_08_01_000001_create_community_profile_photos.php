<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A member's personal photo album on their community profile — account photos
 * they upload directly (not wall posts or shared-plan images).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_profile_photos')) {
            return;
        }
        Schema::create('as_community_profile_photos', function (Blueprint $table) {
            $table->id();
            $table->integer('userId');
            $table->string('imagePath', 255);
            $table->string('caption', 255)->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index(['userId', 'deleteStatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_profile_photos');
    }
};
