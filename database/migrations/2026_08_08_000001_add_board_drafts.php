<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the whiteboard from one endless canvas into a series of drawings.
 *
 * The board used to accumulate forever, so a team opening the room months later
 * met everything anyone had ever drawn. Now a drawing session ends when the room
 * empties: the canvas is archived as a draft and the next person starts clean.
 *
 * Drafts keep the strokes, not just a picture of them, so a past drawing can be
 * reopened onto the board and carried on — which is also what lets a saved
 * drawing stay editable instead of freezing into a flat PNG.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_board_drafts', function (Blueprint $table) {
            $table->id();
            $table->integer('scheduleId')->index();
            $table->string('title', 180)->nullable();
            $table->unsignedInteger('pageCount')->default(1);
            // Rendered preview for the drafts list; the strokes below are what
            // actually gets reopened.
            $table->string('thumbPath', 255)->nullable();
            $table->longText('payload')->nullable();
            // Set when this drawing was exported to the schedule notebook, so a
            // drawing already kept as a note is not archived a second time.
            $table->integer('savedNoteId')->nullable()->index();
            $table->integer('archivedByUserId')->nullable();
            $table->timestamp('archivedAt')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->index(['scheduleId', 'deleteStatus']);
        });

        Schema::create('as_schedule_board_state', function (Blueprint $table) {
            $table->id();
            $table->integer('scheduleId')->unique();
            // Events up to here are already captured in a draft or note, so a
            // board with nothing newer has nothing worth archiving.
            $table->unsignedBigInteger('savedUpToEventId')->default(0);
            // What the live canvas is currently editing, if anything — set when
            // a draft or note is reopened so edits update it in place.
            $table->integer('currentDraftId')->nullable();
            $table->integer('currentNoteId')->nullable();
            $table->timestamps();
        });

        // Room occupancy. Presence has to be per-room, not the global lastSeenAt
        // on users: "is anyone else drawing right now" is the question that
        // decides whether opening the board wipes a teammate's live work.
        Schema::create('as_schedule_board_presence', function (Blueprint $table) {
            $table->id();
            $table->integer('scheduleId');
            $table->integer('userId');
            $table->timestamp('lastSeenAt')->nullable();
            $table->timestamps();

            $table->unique(['scheduleId', 'userId']);
            $table->index('lastSeenAt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_board_presence');
        Schema::dropIfExists('as_schedule_board_state');
        Schema::dropIfExists('as_schedule_board_drafts');
    }
};
