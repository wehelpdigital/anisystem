<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified documentation for a cropping schedule. Every entry carries a type
 * (introduction / critical_rule / a custom tag), rich-text content, and any
 * number of attached files (stored as a JSON list). Custom tags are a small
 * reusable list per schedule so they show up in the type dropdown again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_doc_tags')) {
            Schema::create('as_schedule_doc_tags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('croppingScheduleId')->index();
                $table->string('name', 100);
                $table->integer('sortOrder')->default(0);
                $table->integer('deleteStatus')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('as_schedule_doc_entries')) {
            Schema::create('as_schedule_doc_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('croppingScheduleId')->index();
                // introduction | critical_rule | custom
                $table->string('type', 20)->default('custom');
                // set only when type = custom
                $table->unsignedBigInteger('tagId')->nullable()->index();
                $table->string('title', 255)->nullable();
                $table->longText('content')->nullable();
                // [{ path, name, size, mime }, ...]
                $table->json('files')->nullable();
                $table->integer('sortOrder')->default(0);
                $table->integer('deleteStatus')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_doc_entries');
        Schema::dropIfExists('as_schedule_doc_tags');
    }
};
