<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Collab Room's photo table: one shared photo per schedule, drawn over
 * together in realtime. The strokes live in their own event table rather than
 * the whiteboard's, on purpose — the whiteboard's board token, its
 * release-on-empty binding and its archive all read that table wholesale, and
 * photo strokes leaking into those calculations is how a teammate's note gets
 * rebound by somebody circling a carabao.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_photo_boards')) {
            Schema::create('as_schedule_photo_boards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scheduleId')->unique();
                // Where the photo lives (MediaStore path, possibly mm:-prefixed).
                $table->string('imagePath', 500)->nullable();
                $table->unsignedBigInteger('setBy')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('as_schedule_photo_events')) {
            Schema::create('as_schedule_photo_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('scheduleId')->index();
                $table->unsignedBigInteger('userId');
                // 'draw' or 'clear'; the photo itself changing also retires rows.
                $table->string('type', 12)->default('draw');
                $table->string('mode', 12)->nullable();
                $table->string('color', 16)->nullable();
                $table->unsignedSmallInteger('width')->nullable();
                // Continuation strokes share a uid so a pen line streams in
                // pieces and still renders as one line — same trick as the board.
                $table->string('strokeUid', 40)->nullable();
                $table->longText('points')->nullable();
                $table->string('shapeText', 500)->nullable();
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamps();
            });
        }

        // A team image says so wherever it lands in the Gallery.
        Schema::table('as_gallery_images', function (Blueprint $table) {
            if (! Schema::hasColumn('as_gallery_images', 'isTeam')) {
                $table->tinyInteger('isTeam')->default(0)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_photo_events');
        Schema::dropIfExists('as_schedule_photo_boards');
        Schema::table('as_gallery_images', function (Blueprint $table) {
            if (Schema::hasColumn('as_gallery_images', 'isTeam')) {
                $table->dropColumn('isTeam');
            }
        });
    }
};
