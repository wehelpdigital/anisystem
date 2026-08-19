<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When each member last looked at something.
 *
 * One table for every kind of "new since you were here" badge, because they
 * all ask the identical question and a column per feature would mean a
 * migration every time the community grows another room. `kind` says what was
 * read, `refId` which one of them (null when the thing is a single place, like
 * the blog), and the timestamp is the whole answer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_community_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('kind', 32);
            $table->unsignedBigInteger('refId')->nullable();
            $table->timestamp('lastReadAt')->nullable();
            $table->timestamps();

            // One row per thing per person: reading twice is still one mark.
            $table->unique(['userId', 'kind', 'refId'], 'read_one');
            $table->index(['userId', 'kind'], 'read_by_kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_reads');
    }
};
