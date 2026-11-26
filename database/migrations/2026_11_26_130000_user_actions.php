<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a member does in the app, one row each (2026-11-26, the owner: "track inside the app all the activities of the
 * user, then create a general report for it in the admin panel for us to know what are they trying to do? and check
 * also per user").
 *
 * Pages opened stay on as_page_views. This holds the rest: a thing done (saved, added, asked), a save the app refused,
 * a locked door tapped, a window opened, and what broke on the way. App\Support\UserActions writes it after the answer
 * has gone out; the admin panel's Activity page (App\Support\ActivityReport) and the client sheet read it. Every row
 * goes after 400 days (UserActions::prune).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_user_actions')) {
            Schema::create('as_user_actions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('userId');
                $table->unsignedBigInteger('farmId')->nullable();      // the farm worked on, when it is somebody else's
                $table->unsignedBigInteger('scheduleId')->nullable();  // the season, when the deed had one
                $table->string('kind', 10);                            // did | refused | locked | opened | failed
                $table->string('name', 120);                           // the route name, or what the page said (sheet:x, lock:x, js)
                $table->string('label', 160);                          // the same in plain words
                $table->string('area', 40)->nullable();                // which part of the app
                $table->string('path', 191)->nullable();               // the page they were on, ids folded to :id
                $table->unsignedSmallInteger('status')->nullable();    // the answer's HTTP status
                $table->unsignedInteger('ms')->nullable();             // how long the server took
                $table->string('detail', 255)->nullable();             // why it was refused, which door, what broke
                $table->string('device', 12)->nullable();
                $table->dateTime('createdAt')->index();
                $table->index(['userId', 'createdAt']);
                $table->index(['kind', 'createdAt']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_user_actions');
    }
};
