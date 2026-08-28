<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tutorial videos — a help library the anee.io team curates (managed in the
 * mother app). Each entry is a YouTube video with a cover, title and blurb,
 * grouped by category. Shown as its own page beside Community and Support.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_tutorials')) {
            return;
        }

        Schema::create('as_tutorials', function (Blueprint $table) {
            $table->id();
            $table->string('title', 191);
            $table->string('category', 80)->nullable()->index();
            $table->string('youtubeId', 20)->nullable();
            $table->string('coverImagePath', 500)->nullable();
            $table->text('description')->nullable();
            $table->integer('sortOrder')->default(0);
            $table->boolean('isPublished')->default(1)->index();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_tutorials');
    }
};
