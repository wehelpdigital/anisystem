<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The diary of days a member showed up.
 *
 * The community ladder pays points for presence, and presence has no table:
 * sessions expire, lastSeenAt holds only the latest moment. One row per
 * member per day, written by UpdateLastSeen the first time that member is
 * seen on that date. Counting starts the day this ships — history that was
 * never recorded is not invented.
 *
 * (anee.io's own tables wear the as_ prefix; an unprefixed name here would
 * be a migration that says DONE and builds nothing anyone reads.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_member_days')) {
            return;
        }
        Schema::create('as_member_days', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('userId');
            $t->date('day');
            $t->unique(['userId', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_member_days');
    }
};
