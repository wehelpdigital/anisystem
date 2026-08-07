<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only stroke log for a schedule's collaborative team whiteboard. Each
 * row is one segment (a batch of points) or a "clear" marker; clients replay
 * active rows to rebuild the board and poll for new ones to stay in sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_board_events')) {
            return;
        }
        Schema::create('as_schedule_board_events', function (Blueprint $table) {
            $table->id();
            $table->integer('scheduleId')->index();
            $table->integer('userId');
            $table->string('type', 12)->default('draw');   // draw | clear
            $table->string('strokeUid', 40)->nullable();    // groups a stroke's segments
            $table->string('color', 16)->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->string('mode', 12)->default('pen');     // pen | eraser
            $table->text('points')->nullable();             // JSON: [[x,y], ...] logical px
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_board_events');
    }
};
