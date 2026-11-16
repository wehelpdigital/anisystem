<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The road a visitor walked before they signed up (2026-11-16, the owner: "can we track down the travel path of a
 * user who registered so that we can check it what page she accessed first until he/she registered").
 *
 * One row per browser, found by the first party cookie anee_vid. Every public page a guest opens adds a step (when,
 * the path, the page title, where they came from when that was another site). The signup claims the row for the new
 * account; the admin panel's client sheet and the mother app's client details show it. App\Support\VisitorJourney
 * writes it; rows nobody claimed are dropped after 90 days.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_visitor_journeys')) {
            Schema::create('as_visitor_journeys', function (Blueprint $table) {
                $table->id();
                $table->string('vid', 40)->unique();
                $table->unsignedBigInteger('userId')->nullable()->index();
                $table->dateTime('firstAt');
                $table->dateTime('lastAt')->index();
                $table->string('landing', 255)->nullable();      // the first page, with its kept query
                $table->string('referrer', 255)->nullable();     // the first other site that sent them
                $table->json('utm')->nullable();                 // the first ad tags seen
                $table->string('device', 80)->nullable();        // "Phone, Android, Facebook app"
                $table->unsignedInteger('views')->default(0);
                $table->json('steps')->nullable();               // [{t, p, q?, n?, r?, e?}]
                $table->dateTime('signedUpAt')->nullable();
                $table->string('method', 16)->nullable();        // email | google
                $table->dateTime('confirmedAt')->nullable();
                $table->string('choice', 16)->nullable();        // read | dashboard | home (the welcome page)
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_visitor_journeys');
    }
};
