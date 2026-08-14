<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who has seen a team message, and what a message can carry.
 *
 * Seen-marks live in their own table rather than a column on the message:
 * one message is seen by many people at different moments, and a JSON blob
 * on the row cannot be written concurrently by five phones without one of
 * them losing. A row per (message, reader) can.
 *
 * Attachments become a JSON list on the message so a single message can
 * carry a photo, a clip and a file together — the same shape notes already
 * use, so the viewers and the media gatherers already understand it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_message_reads', function (Blueprint $table) {
            $table->id();
            $table->integer('messageId')->index();
            $table->integer('userId')->index();
            $table->timestamp('seenAt')->nullable();
            $table->timestamps();
            // One mark per person per message; a second read is not news.
            $table->unique(['messageId', 'userId'], 'msg_reader_unique');
        });

        Schema::table('as_schedule_messages', function (Blueprint $table) {
            // [{type: image|video|audio|file, path, poster, name, size, mime}]
            $table->json('attachments')->nullable()->after('imagePath');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_message_reads');
        Schema::table('as_schedule_messages', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
