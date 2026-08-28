<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two books of little moving pictures, kept where the app can be told to
 * change its mind about them.
 *
 * The first is what a day's work looks like: a knapsack sprayer for a foliar,
 * a plough for land prep, a hand scattering granules for a fertiliser. The
 * second is what a crop looks like at the point of the season it has reached
 * — a grain crop at booting is a different drawing from a root crop at
 * bulking, and both are different from either of them as a seed.
 *
 * Rows rather than a hard-coded list because these are content: the wording
 * under a picture is advice, advice gets corrected, and a season the app has
 * never heard of should be addable without a deploy. The DRAWING is code (it
 * has to be, it is animation), but which drawing, what it is called and what
 * it tells you are all here.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_activity_scenes')) {
            Schema::create('as_activity_scenes', function (Blueprint $t) {
                $t->id();
                // The activityType slug this stands for — 'foliar_spray',
                // 'land_prep', and one row keyed 'quiet' for a day with
                // nothing on it.
                $t->string('key', 60)->unique();
                $t->string('label', 120);
                // Which drawing in the scene book. Usually the same as the
                // key; kept separate so two activity types can share one
                // picture without either of them having to lie about its name.
                $t->string('scene', 60);
                $t->string('hue', 40)->default('leaf');
                // One line about the day, written for somebody looking at the
                // card before they have opened the board.
                $t->text('blurb')->nullable();
                $t->unsignedSmallInteger('sortOrder')->default(0);
                $t->unsignedTinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('as_crop_scenes')) {
            Schema::create('as_crop_scenes', function (Blueprint $t) {
                $t->id();
                // The shape of plant, not the crop: rice and wheat are one
                // family because they are one drawing. Eighty-five crops
                // resolve into a dozen or so silhouettes, and the alternative
                // — eighty-five drawings — would be eighty-five drawings
                // that mostly agreed with each other.
                $t->string('family', 40);
                // Where in the season, 0..5: seed, sprout, vegetative,
                // flowering, filling, harvest. A band rather than a stage
                // name so a crop with five stages and a crop with nine can
                // both be placed.
                $t->unsignedTinyInteger('band');
                $t->string('label', 120);
                $t->string('hue', 40)->default('leaf');
                $t->text('blurb')->nullable();
                $t->unsignedTinyInteger('deleteStatus')->default(1);
                $t->timestamps();
                $t->unique(['family', 'band']);
            });
        }

        // The rows come with the table. A migration that leaves an empty
        // content table behind ships a feature that works on the machine it
        // was written on and nowhere else — which is exactly what happened to
        // the sky table, seeded by hand here and never on the server. It is
        // put right in the same breath.
        (new \Database\Seeders\ActivitySceneSeeder)->run();
        (new \Database\Seeders\CropSceneSeeder)->run();
        if (Schema::hasTable('as_weather_scenes')) {
            (new \Database\Seeders\WeatherSceneSeeder)->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_crop_scenes');
        Schema::dropIfExists('as_activity_scenes');
    }
};
