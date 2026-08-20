<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Somebody said "this does not belong here".
 *
 * A report is a message from a member to the people who run the place, so it
 * keeps who sent it, what it is about, why, and — separately — what the house
 * did about it. The content itself is untouched: a report is an opinion until
 * a moderator agrees with it.
 *
 * The table is shared with the mother app, which is where the reports are
 * read and acted on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_community_reports')) {
            return;
        }

        Schema::create('as_community_reports', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('reporterUserId')->index();
            // What is being reported: a wall post, a comment on one, a
            // discussion topic, a reply in one, a story, or a room.
            $t->string('targetType', 24);
            $t->unsignedBigInteger('targetId');
            // Why, in the reporter's words: one of the offered reasons, plus
            // whatever they wanted to add.
            $t->string('reason', 60);
            $t->text('details')->nullable();
            // A copy of what was said, as it stood when it was reported —
            // the original can be edited or deleted before anybody looks.
            $t->text('snapshot')->nullable();
            $t->unsignedBigInteger('targetUserId')->nullable()->index();
            // What the house did: open → reviewed | dismissed | actioned.
            $t->string('status', 16)->default('open')->index();
            $t->text('note')->nullable();
            $t->unsignedBigInteger('reviewedByUserId')->nullable();
            $t->timestamp('reviewedAt')->nullable();
            $t->unsignedTinyInteger('deleteStatus')->default(1);
            $t->timestamps();

            // The list a moderator opens is "what is still open, newest
            // first", and the app asks "has this person already reported
            // this thing" before offering to report it again.
            $t->index(['targetType', 'targetId']);
            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_reports');
    }
};
