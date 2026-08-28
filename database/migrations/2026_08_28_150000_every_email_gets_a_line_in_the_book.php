<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The mail book: one row per email this system means to send.
 *
 * Everything goes in here — the reset link somebody is waiting on with their
 * thumb over the screen, the day's tasks a worker gets at six in the morning,
 * the one an owner sent by hand from a day card. Two things follow from that.
 *
 * First, the mother app has one place to look. "Did Nena get her schedule?"
 * has an answer now, with the provider's own message id beside it.
 *
 * Second, sending becomes something that can be retried. A row is queued,
 * then sent or failed; a failure keeps its reason and its attempt count, and
 * the next cron run picks it up again rather than the email simply never
 * arriving and nobody knowing.
 *
 * `sendAfter` is what makes a daily blast possible: the cron writes tomorrow
 * morning's rows whenever it likes, and they sit here until their time.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_email_tasks')) {
            return;
        }
        Schema::create('as_email_tasks', function (Blueprint $t) {
            $t->increments('id');
            // Which mail group and template this came from, for the mother
            // app's list. Free text: a one-off email has no template.
            // The mailer's group, not the app's name: it matches rows in the
            // mother app's tables, which have not been renamed.
            $t->string('groupKey', 40)->default('AniSystem');
            $t->string('templateKey', 60)->nullable()->index();

            $t->string('toEmail', 190)->index();
            $t->string('toName', 190)->nullable();
            $t->string('subject', 255);
            $t->longText('bodyHtml');

            // queued → sent, or queued → failed and back to queued on a retry.
            $t->string('status', 20)->default('queued')->index();
            $t->unsignedInteger('attempts')->default(0);
            $t->text('lastError')->nullable();
            // Resend's own id for the message, so a delivery question can be
            // taken to their dashboard without guessing which email it was.
            $t->string('providerId', 120)->nullable();

            // When it may go. Now, for anything somebody is waiting on.
            $t->timestamp('sendAfter')->nullable()->index();
            $t->timestamp('sentAt')->nullable();

            // What it is about, so a row in the list can be traced back.
            $t->string('relatedType', 40)->nullable();
            $t->unsignedInteger('relatedId')->nullable();
            $t->unsignedInteger('croppingScheduleId')->nullable()->index();
            $t->unsignedInteger('createdByUserId')->nullable();

            $t->integer('deleteStatus')->default(1)->index();
            $t->timestamps();

            // What the cron asks for: due, not yet sent, oldest first.
            $t->index(['status', 'sendAfter'], 'as_email_tasks_due');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_email_tasks');
    }
};
