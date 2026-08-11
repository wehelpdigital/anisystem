<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named snapshots of the Collab Room map — the whole set of shapes at a
 * moment, reopenable later from the map tools. A snapshot may be twinned
 * with a notebook note (its picture); noteId links them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_map_saves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scheduleId')->index();
            $table->unsignedBigInteger('userId');
            $table->string('title', 180);
            $table->longText('objects');
            $table->unsignedBigInteger('noteId')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_map_saves');
    }
};
