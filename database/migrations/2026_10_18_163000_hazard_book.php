<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The hazard book (2026-10-18, the owner: "research the past 20 years of
 * historical data about typhoons, floods, and calamities for each town in
 * the Philippines ... so that anee don't need to search for the history of
 * these anymore").
 *
 *   as_hazard_towns   every town and city of the gazetteer with its point,
 *                     region and weather square (the storm maths needs it)
 *   as_hazard_events  one row per event since 2005: a storm with its names,
 *                     dates in the area, landfalls, peak and track; a flood,
 *                     landslide, surge, drought, dry spell, hot spell, heavy
 *                     rain, earthquake, eruption, pest or cold spell
 *   as_hazard_places  what one event did at one place (town, province or
 *                     region): measured near the town (distance, wind, rain,
 *                     rain below normal, days, heat) or reported for the town
 *                     or its province, with a severity
 *   as_hazard_years   the yearly roll up per place in the risk record's own
 *                     line form, so App\Support\ClimateRecord reads a town's
 *                     twenty years without a web search
 *
 * Nothing here names where a fact came from. Additive only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_hazard_towns')) {
            Schema::create('as_hazard_towns', function (Blueprint $t) {
                $t->id();
                $t->string('placeKey', 200)->unique();
                $t->string('region', 60);
                $t->string('province', 80)->index();
                $t->string('town', 120);
                $t->decimal('lat', 8, 5);
                $t->decimal('lng', 8, 5);
                $t->string('cell', 20)->nullable();
            });
        }
        if (! Schema::hasTable('as_hazard_events')) {
            Schema::create('as_hazard_events', function (Blueprint $t) {
                $t->id();
                $t->string('eventKey', 100)->unique();
                $t->string('kind', 16);
                $t->string('name', 120)->nullable();
                $t->string('localName', 40)->nullable();
                $t->smallInteger('year');
                $t->date('startDate');
                $t->date('endDate')->nullable();
                $t->string('peak', 80)->nullable();
                $t->decimal('strength', 6, 1)->nullable();
                $t->string('landfall', 400)->nullable();
                $t->text('summary')->nullable();
                $t->text('farmImpact')->nullable();
                $t->unsignedBigInteger('damagePhp')->nullable();
                $t->unsignedInteger('areaHa')->nullable();
                $t->json('crops')->nullable();
                $t->json('track')->nullable();
                $t->string('scope', 12)->default('province');
                $t->string('confidence', 12)->default('reported');
                $t->string('enso', 12)->nullable();
                $t->timestamps();
                $t->index(['year', 'kind']);
            });
        }
        if (! Schema::hasTable('as_hazard_places')) {
            Schema::create('as_hazard_places', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('eventId')->index();
                $t->string('level', 10);
                $t->string('placeKey', 200);
                $t->string('region', 60)->nullable();
                $t->string('province', 80)->nullable();
                $t->string('town', 120)->nullable();
                $t->smallInteger('year');
                $t->unsignedTinyInteger('month');
                $t->date('startDate');
                $t->date('endDate')->nullable();
                $t->unsignedSmallInteger('distanceKm')->nullable();
                $t->unsignedSmallInteger('windKmh')->nullable();
                $t->unsignedSmallInteger('rainMm')->nullable();
                $t->unsignedSmallInteger('maxDayMm')->nullable();
                $t->unsignedTinyInteger('pctBelow')->nullable();
                $t->unsignedSmallInteger('days')->nullable();
                $t->decimal('value', 5, 1)->nullable();
                $t->string('severity', 8)->default('moderate');
                $t->string('basis', 10)->default('measured');
                $t->string('effect', 255)->nullable();
                $t->index(['placeKey', 'year']);
            });
        }
        if (! Schema::hasTable('as_hazard_years')) {
            Schema::create('as_hazard_years', function (Blueprint $t) {
                $t->id();
                $t->string('placeKey', 200);
                $t->string('level', 10);
                $t->string('region', 60)->nullable();
                $t->string('province', 80)->nullable();
                $t->string('town', 120)->nullable();
                $t->smallInteger('year');
                $t->text('line');
                $t->json('events')->nullable();
                $t->unsignedTinyInteger('storms')->default(0);
                $t->unsignedTinyInteger('floods')->default(0);
                $t->unsignedTinyInteger('droughts')->default(0);
                $t->unsignedTinyInteger('heat')->default(0);
                $t->unsignedTinyInteger('others')->default(0);
                $t->string('rains', 200)->nullable();
                $t->boolean('complete')->default(false);
                $t->timestamp('builtAt')->nullable();
                $t->unique(['placeKey', 'year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_hazard_years');
        Schema::dropIfExists('as_hazard_places');
        Schema::dropIfExists('as_hazard_events');
        Schema::dropIfExists('as_hazard_towns');
    }
};
