<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canned support answers, with merge fields.
 *
 * Tokens are double-braced — {first_name}, {ticket_no} and friends — and are
 * resolved at send time from the ticket in hand, so a template written once
 * greets every client by their own name. A few starters ship with the shelf.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_support_canned', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('body');
            $table->unsignedTinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });

        $now = now();
        DB::table('as_support_canned')->insert([
            [
                'title' => 'Welcome & first steps',
                'body' => '<p>Hi {first_name},</p><p>Thanks for reaching out! To get you started: open <b>Schedules</b>, tap <b>Add New Cropping Schedule</b>, and the app will walk you through your lots, workers and day-by-day activities.</p><p>If anything is unclear, just reply here — we read every message.</p><p>— {admin_name}, anee.io support</p>',
                'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'title' => 'We are looking into it',
                'body' => '<p>Hi {first_name},</p><p>Thanks for the report on <b>“{subject}”</b> (ticket {ticket_no}). We have reproduced it on our side and the team is on it — we will reply here the moment it is fixed.</p><p>— {admin_name}, anee.io support</p>',
                'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'title' => 'Fixed — please try again',
                'body' => '<p>Hi {first_name},</p><p>Good news — the issue behind ticket {ticket_no} is fixed. Please refresh the app (or sign out and back in) and try again. If it still misbehaves, reply here and we will dig deeper.</p><p>— {admin_name}, anee.io support</p>',
                'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'title' => 'Closing a quiet ticket',
                'body' => '<p>Hi {first_name},</p><p>We have not heard back on ticket {ticket_no}, so we are closing it for now to keep your inbox honest. If the problem is still with you, one reply reopens the conversation — nothing is lost.</p><p>— {admin_name}, anee.io support</p>',
                'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('as_support_canned');
    }
};
