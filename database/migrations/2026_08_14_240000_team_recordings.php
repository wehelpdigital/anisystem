<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the Collab Room produced.
 *
 * A recorded walkthrough or a recorded call is not a note and not a photo:
 * it belongs to the team rather than to a day, and the thing people ask
 * afterwards is "where is that video where Juan showed us the pump", not
 * "what was on the 14th". So it gets a table of its own, with a title and a
 * description somebody wrote while they still remembered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_team_recordings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scheduleId')->index();
            $table->unsignedBigInteger('userId')->index();
            // 'camera' — the shared-camera grid; 'call' — a team call.
            $table->string('kind', 20)->default('camera');
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->string('path', 500);
            $table->string('poster', 500)->nullable();
            $table->unsignedInteger('seconds')->nullable();
            $table->unsignedTinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_team_recordings');
    }
};
