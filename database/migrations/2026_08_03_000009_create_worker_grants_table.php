<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Worker login access. A boss (manager) grants one of their workers a login to
 * AniSystem with a chosen level of schedule access (view or edit) and optional
 * community access. The worker sets their own password from an emailed invite
 * link. A worker may hold grants from several bosses (they pick which farm to
 * open after logging in). Boss/Lifetime tiers only.
 *
 * House conventions: camelCase columns, integer deleteStatus, no FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_worker_grants')) {
            return;
        }

        Schema::create('as_worker_grants', function (Blueprint $table) {
            $table->id();
            $table->integer('bossUserId')->index();          // the manager
            $table->integer('workerUserId')->nullable()->index(); // set once accepted
            $table->integer('scheduleWorkerId')->nullable()->index(); // AsScheduleWorker row
            $table->string('invitedEmail', 191)->index();
            $table->string('inviteToken', 64)->nullable()->index();
            $table->enum('scheduleAccess', ['none', 'view', 'edit'])->default('view');
            $table->boolean('communityAccess')->default(1);
            $table->string('status', 16)->default('pending'); // pending|active|revoked
            $table->timestamp('acceptedAt')->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_worker_grants');
    }
};
