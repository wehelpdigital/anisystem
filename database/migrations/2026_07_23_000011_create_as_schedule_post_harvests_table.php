<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Post-harvest observations: what actually happened at the end of a season,
 * recorded against the schedule (and optionally a single lot) so the next
 * season can be planned from real numbers rather than memory.
 *
 * Follows the house conventions of the shared `as_*` tables: camelCase
 * columns, integer `deleteStatus` soft delete, no FK constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_post_harvests')) {
            return;
        }

        Schema::create('as_schedule_post_harvests', function (Blueprint $table) {
            $table->id();
            $table->integer('croppingScheduleId')->index();
            $table->integer('lotId')->nullable()->index();
            $table->date('observationDate')->nullable();
            $table->string('title', 191);
            $table->string('category', 32)->default('yield');

            // Harvest figures. Nullable throughout — an observation may be
            // purely qualitative ("lodging on the west corner").
            $table->decimal('yieldAmount', 12, 2)->nullable();
            $table->string('yieldUnit', 24)->nullable();
            $table->decimal('moisturePercent', 5, 2)->nullable();
            $table->decimal('pricePerUnit', 12, 2)->nullable();
            $table->string('buyer', 191)->nullable();

            $table->text('notes')->nullable();
            $table->string('imagePath', 500)->nullable();
            $table->integer('sortOrder')->default(0);
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_post_harvests');
    }
};
