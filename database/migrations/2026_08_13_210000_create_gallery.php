<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Albums: pictures a grower groups on purpose.
 *
 * The Media Box already gathers every photo the season produced, but it is a
 * shelf — ordered by where each picture came from, not by what it is about.
 * An album is the other thing: "the flooded corner in August", "what the
 * buyer rejected", chosen and named by the person who took them.
 *
 * An image belongs to exactly one album, so moving it between albums is a
 * single field, and deleting an album asks what to do with what is inside.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('croppingScheduleId')->index();
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->unsignedInteger('sortOrder')->default(0);
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });

        Schema::create('as_gallery_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('albumId')->index();
            $table->unsignedBigInteger('croppingScheduleId')->index();
            $table->unsignedBigInteger('userId')->nullable();
            $table->string('path', 500);
            $table->string('caption', 255)->nullable();
            $table->unsignedInteger('sortOrder')->default(0);
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_gallery_images');
        Schema::dropIfExists('as_gallery_albums');
    }
};
