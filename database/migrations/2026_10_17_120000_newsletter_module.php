<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The email newsletter (2026-10-17, the owner). Acumbamail holds the
 * subscribers and sends; this keeps what is sent, in what order, to whom:
 *
 *   as_nl_emails     every email, by priority (high, news, regular) and its
 *                    place in that list: the builder's blocks, or an
 *                    Acumbamail template by id; its audience (segments), the
 *                    day or the hour it is bound to, and which segments have
 *                    had it already.
 *   as_nl_runs       one row per send: the day's plan (which segment got
 *                    which email), or one timed high priority email.
 *   as_nl_campaigns  every Acumbamail campaign a run made.
 *   as_nl_audience   who is on which Acumbamail list, mirrored, so a change
 *                    of plan moves a person between lists without asking
 *                    Acumbamail first.
 *
 * Its settings (the switch, the send time, the sender, the theme, the list
 * ids) are rows of as_site_settings under newsletter.*.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_nl_emails')) {
            Schema::create('as_nl_emails', function (Blueprint $t) {
                $t->id();
                $t->string('priority', 8)->index();           // high, news, regular
                $t->integer('position')->default(0);
                $t->string('kind', 8)->default('builder');     // builder, acumba
                $t->string('title', 190);
                $t->string('subject', 250)->default('');
                $t->string('preheader', 250)->nullable();
                $t->longText('blocks')->nullable();            // the builder's JSON: {kicker, headline, blocks: [...]}
                $t->unsignedBigInteger('acumbaTemplateId')->nullable()->index();
                $t->text('audience')->nullable();              // JSON list of segment keys
                $t->date('sendOn')->nullable();                // only on this day (then it expires)
                $t->dateTime('sendAt')->nullable();            // high priority at this exact time, beside the day's email
                $t->date('newsDate')->nullable();              // the news of this day (expires at its end)
                $t->unsignedBigInteger('newsPageId')->nullable();
                $t->string('status', 12)->default('draft')->index();   // draft, queued, partial, sent, expired
                $t->text('sentCells')->nullable();             // JSON list of segments that had it
                $t->dateTime('lastSentAt')->nullable();
                $t->string('note', 600)->nullable();
                $t->text('meta')->nullable();
                $t->string('createdBy', 120)->nullable();
                $t->string('updatedBy', 120)->nullable();
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_nl_runs')) {
            Schema::create('as_nl_runs', function (Blueprint $t) {
                $t->id();
                $t->date('runDate')->index();
                $t->string('kind', 8)->default('daily');       // daily, timed
                $t->string('status', 12)->default('running');  // running, done, dry, failed
                $t->longText('plan')->nullable();
                $t->text('error')->nullable();
                $t->unsignedTinyInteger('attempts')->default(1);
                $t->dateTime('finishedAt')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_nl_campaigns')) {
            Schema::create('as_nl_campaigns', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('runId')->index();
                $t->unsignedBigInteger('emailId')->index();
                $t->text('cells')->nullable();
                $t->text('lists')->nullable();
                $t->unsignedBigInteger('acumbaCampaignId')->nullable();
                $t->string('status', 12)->default('created');  // created, failed
                $t->text('error')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_nl_audience')) {
            Schema::create('as_nl_audience', function (Blueprint $t) {
                $t->id();
                $t->string('email', 190)->unique();
                $t->unsignedBigInteger('userId')->nullable()->index();
                $t->string('name', 190)->nullable();
                $t->text('cells')->nullable();                 // JSON list of segments it is on at Acumbamail
                $t->boolean('unsubscribed')->default(false);
                $t->dateTime('syncedAt')->nullable();
                $t->string('error', 600)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['as_nl_audience', 'as_nl_campaigns', 'as_nl_runs', 'as_nl_emails'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
