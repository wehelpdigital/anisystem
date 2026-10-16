<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Twenty years of climate, kept, and the nutrient need as a book
 * (2026-10-18, the owner: "make the 20 year weather climate history the
 * default, save that in the database per year or so", and "pre-create a
 * multilevel database of nutrients requirement").
 *
 *   as_weather_cells   + fetchedFrom (the first day it holds) and shape
 *                      (2 = the weeks carry the extremes below)
 *   as_weather_weeks   + the extremes a week held: its wettest day, days of
 *                      10 and 50 mm or more, dry days, the strongest gust and
 *                      the days of storm force gusts (60 km/h or more)
 *   as_enso_seasons    NOAA's ONI, every three month season since 1950, so a
 *                      year in the record is known as El Niño, La Niña or
 *                      neither
 *   as_climate_years   what Anee read on the web about one place in one year
 *                      (its storms, floods, droughts, heat, the rains), kept
 *                      so the next analysis of that place reads it here and
 *                      searches only for the years it lacks
 *   as_ai_research     research notes that do not depend on one field (a
 *                      crop, a variety, a province, a month), kept for a time
 *                      so the next farmer's analysis reads them instead of
 *                      paying for the same search again
 *   as_nutrient_needs  NPK Plus's need model written out (App\Support\
 *                      NutrientBook): every crop at its yield levels on each
 *                      soil type and pH band, and every variety on record at
 *                      its usual goal, stamped with the model's version
 *
 * Additive only: new nullable columns and new tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_weather_cells') && ! Schema::hasColumn('as_weather_cells', 'fetchedFrom')) {
            Schema::table('as_weather_cells', function (Blueprint $t) {
                $t->date('fetchedFrom')->nullable()->after('fetchedThrough');
                $t->unsignedTinyInteger('shape')->default(1)->after('fetchedFrom');
            });
        }
        if (Schema::hasTable('as_weather_weeks') && ! Schema::hasColumn('as_weather_weeks', 'rainMax')) {
            Schema::table('as_weather_weeks', function (Blueprint $t) {
                $t->decimal('rainMax', 6, 1)->nullable();
                $t->unsignedTinyInteger('wet10')->nullable();
                $t->unsignedTinyInteger('wet50')->nullable();
                $t->unsignedTinyInteger('dry1')->nullable();
                $t->decimal('gustMax', 5, 1)->nullable();
                $t->unsignedTinyInteger('gust60')->nullable();
            });
        }
        if (! Schema::hasTable('as_enso_seasons')) {
            Schema::create('as_enso_seasons', function (Blueprint $t) {
                $t->id();
                $t->smallInteger('year');
                $t->char('season', 3);
                $t->unsignedTinyInteger('mid');
                $t->decimal('oni', 4, 2);
                $t->unique(['year', 'season']);
            });
        }
        if (! Schema::hasTable('as_climate_years')) {
            Schema::create('as_climate_years', function (Blueprint $t) {
                $t->id();
                $t->string('placeKey', 120);
                $t->string('place', 160);
                $t->char('country', 2)->default('PH');
                $t->smallInteger('year');
                $t->text('line');
                $t->json('events')->nullable();
                $t->unsignedTinyInteger('storms')->nullable();
                $t->unsignedTinyInteger('floods')->nullable();
                $t->unsignedTinyInteger('droughts')->nullable();
                $t->unsignedTinyInteger('heat')->nullable();
                $t->string('rains', 200)->nullable();
                $t->json('sources')->nullable();
                $t->boolean('searched')->default(false);
                $t->boolean('complete')->default(false);
                $t->timestamp('researchedAt')->nullable();
                $t->timestamps();
                $t->unique(['placeKey', 'year']);
            });
        }
        if (! Schema::hasTable('as_ai_research')) {
            Schema::create('as_ai_research', function (Blueprint $t) {
                $t->id();
                $t->string('kind', 24);
                $t->char('cacheKey', 40);
                $t->string('scope', 255);
                $t->mediumText('notes');
                $t->json('sources')->nullable();
                $t->boolean('searched')->default(false);
                $t->unsignedInteger('tokensIn')->default(0);
                $t->unsignedInteger('tokensOut')->default(0);
                $t->unsignedInteger('hits')->default(0);
                $t->timestamp('lastHitAt')->nullable();
                $t->timestamp('expiresAt')->nullable()->index();
                $t->timestamps();
                $t->unique(['kind', 'cacheKey']);
            });
        }
        if (! Schema::hasTable('as_nutrient_needs')) {
            Schema::create('as_nutrient_needs', function (Blueprint $t) {
                $t->id();
                $t->char('version', 12);
                $t->string('level', 8);
                $t->string('crop', 32);
                $t->string('variety', 120)->nullable();
                $t->decimal('potential', 6, 2)->nullable();
                $t->decimal('yieldT', 7, 2);
                $t->string('texture', 8);
                $t->string('ph', 16);
                $t->unsignedSmallInteger('n')->nullable();
                $t->unsignedSmallInteger('p')->nullable();
                $t->unsignedSmallInteger('k')->nullable();
                $t->json('micros')->nullable();
                $t->json('whys')->nullable();
                $t->timestamp('created_at')->nullable();
                $t->index(['version', 'crop', 'level', 'texture', 'ph']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_nutrient_needs');
        Schema::dropIfExists('as_ai_research');
        Schema::dropIfExists('as_climate_years');
        Schema::dropIfExists('as_enso_seasons');
        if (Schema::hasColumn('as_weather_weeks', 'rainMax')) {
            Schema::table('as_weather_weeks', fn (Blueprint $t) => $t->dropColumn(['rainMax', 'wet10', 'wet50', 'dry1', 'gustMax', 'gust60']));
        }
        if (Schema::hasColumn('as_weather_cells', 'fetchedFrom')) {
            Schema::table('as_weather_cells', fn (Blueprint $t) => $t->dropColumn(['fetchedFrom', 'shape']));
        }
    }
};
