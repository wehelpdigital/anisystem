<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Free-standing sticky notes a grower can drop *between* activities on a day
 * (unlike the single per-day date note). Many per day, each with its own
 * position (sortKey) in the day's card order, draggable to any slot or day.
 * Version-scoped like date notes and markers so forks carry their own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_inline_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('croppingScheduleId')->index();
            $table->unsignedBigInteger('versionId')->nullable()->index();
            $table->date('noteDate');
            $table->integer('sortKey')->default(0);
            $table->text('content')->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
            $table->index(['croppingScheduleId', 'versionId', 'noteDate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_inline_notes');
    }
};
