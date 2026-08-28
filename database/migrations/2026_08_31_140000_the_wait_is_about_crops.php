<?php

use Illuminate\Database\Migrations\Migration;

/**
 * The waiting card stops talking about livestock, and stops repeating itself.
 *
 * Six of its reminders were about animals — resting the carabao, the drinking
 * trough, deworming the herd. This app plans cropping seasons: rice, maize,
 * vegetables and fruit. They are replaced rather than dropped, because the
 * health-and-safety lines they sat among are still the right KIND of thing to
 * say; they just have to be about the work this app is for.
 *
 * And forty more are added, all of them about a crop — the seedbed, the
 * bunds, splitting the nitrogen, pruning for light, picking young and often,
 * drying to fourteen per cent. Nearly two hundred now, which is what stops
 * the card feeling like it owns six sentences.
 *
 * The seeder retires what is no longer in its list rather than deleting it,
 * so the six about animals are off the screen and still in the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\LoadingLineSeeder)->run();
    }

    public function down(): void
    {
        // Nothing. These are words, and the ones this replaced are still in
        // the table with deleteStatus 0 if anybody wants them back.
    }
};
