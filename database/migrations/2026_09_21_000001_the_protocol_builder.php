<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * THE PROTOCOL BUILDER — a member's own crop protocols, written by hand.
 *
 * One row is one protocol: the crop, its variety, how its days are counted
 * (DAS / DAT / DAP — a DAT protocol counts DAS in the seedbed and DAT after
 * the transplant), and the tasks as one JSON list ordered by day. The
 * undo/redo history rides on the row too (snapshots of the task list, a
 * short stack each way) so the way back survives a closed tab, and Anee's
 * review of the protocol is kept beside it once she has read it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_protocols')) {
            return;
        }
        Schema::create('as_protocols', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index();
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->string('crop', 60)->nullable();
            $table->string('variety', 120)->nullable();
            $table->string('dayType', 8)->default('DAS');
            $table->mediumText('tasks');            // JSON list of tasks
            $table->mediumText('history')->nullable(); // {"undo":[...],"redo":[...]} snapshots of tasks
            $table->unsignedInteger('rev')->default(1); // bumps on every save; a stale tab is turned away
            $table->json('analysis')->nullable();    // Anee's review, or {phase,try} while she reads
            $table->string('analysisStatus', 12)->nullable(); // pending | ready | failed
            $table->text('analysisError')->nullable();
            $table->decimal('analysisCredits', 8, 2)->default(0);
            $table->timestamp('analysisAt')->nullable();
            $table->timestamp('analysisBeatAt')->nullable();
            $table->unsignedBigInteger('portedScheduleId')->nullable();
            $table->timestamp('portedAt')->nullable();
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_protocols');
    }
};
