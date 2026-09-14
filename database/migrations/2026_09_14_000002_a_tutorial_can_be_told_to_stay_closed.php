<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Don't show this again", remembered.
 *
 * Every screen with a tutorial video offers it until the person says
 * they have seen enough, and that answer has to follow them: the phone in
 * the field and the laptop at home are the same farmer, and a card
 * dismissed on one should stay dismissed on the other. So it is a row per
 * person per screen rather than a note in one browser's storage.
 *
 * "Close" -- without the never-again -- is not recorded here at all. It
 * quiets the card for the rest of the sitting and no longer, which is a
 * browser's business and lives in sessionStorage.
 *
 * as_-prefixed, like everything this app owns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_tutorial_dismissals')) {
            return;
        }

        Schema::create('as_tutorial_dismissals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->string('tutorialKey', 64);
            $table->timestamps();
            $table->unique(['userId', 'tutorialKey']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_tutorial_dismissals');
    }
};
