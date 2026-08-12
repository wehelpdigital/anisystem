<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who actually turned up, as opposed to who was put on the work.
 *
 * The board says what was planned; this says what happened. They are different
 * facts and one must not overwrite the other — a worker taken off an activity
 * loses the plan, not the record that they were there on Tuesday.
 *
 * A row exists only once someone has been marked, and absence is the thing
 * worth writing down: no row means "as planned", which is the common case and
 * costs nothing to store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('croppingScheduleId');
            $table->unsignedBigInteger('workerId');
            $table->date('workDate');
            $table->boolean('isPresent')->default(true);
            $table->string('note', 191)->nullable();
            $table->unsignedBigInteger('markedByUserId')->nullable();
            $table->timestamps();

            $table->unique(['croppingScheduleId', 'workerId', 'workDate'], 'attendance_one_per_worker_day');
            $table->index(['croppingScheduleId', 'workDate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_attendance');
    }
};
