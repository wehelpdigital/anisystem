<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What every analysis reads instead of asking outside again (the owner,
 * 2026-10-19: "in any anee analysis, you can check the enso forecast and
 * weather forecast as well as historical forecast ... save those forecasts as
 * always (get the latest if there's a forecast but history on the other hand
 * is not changeable)"):
 *
 *   as_enso_advisories  NOAA's official ENSO forecast, one row per monthly
 *                       advisory (its date, alert, synopsis, the odds it
 *                       gives, the next date), kept for good; every module
 *                       reads the latest (App\Support\EnsoOutlook)
 *   as_weather_days     the past days of a weather square (the climate
 *                       shelf's 28 km cells), one row a day, read once from
 *                       the archive and kept (App\Support\WeatherDays): a
 *                       season report or a realign never asks for the same
 *                       days twice
 *   as_ai_outlooks      text Anee wrote that depends only on the place, the
 *                       months and the ENSO reading (a season outlook, a
 *                       place's month by month risk), reused for the same key
 *                       while it is fresh (App\Support\OutlookShelf)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_enso_advisories')) {
            Schema::create('as_enso_advisories', function (Blueprint $t) {
                $t->id();
                $t->date('issuedOn')->unique();
                $t->string('alert', 80)->nullable();
                $t->text('synopsis')->nullable();
                $t->text('odds')->nullable();
                $t->date('nextOn')->nullable();
                $t->text('text')->nullable();
                $t->timestamp('fetchedAt')->nullable();
                $t->timestamp('checkedAt')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_weather_days')) {
            Schema::create('as_weather_days', function (Blueprint $t) {
                $t->string('cellKey', 16);
                $t->date('day');
                $t->decimal('rain', 6, 1)->nullable();
                $t->decimal('tmax', 4, 1)->nullable();
                $t->decimal('tmin', 4, 1)->nullable();
                $t->decimal('tmean', 4, 1)->nullable();
                $t->decimal('wind', 5, 1)->nullable();
                $t->decimal('gust', 5, 1)->nullable();
                $t->decimal('et0', 4, 1)->nullable();
                $t->decimal('rad', 5, 1)->nullable();
                $t->unsignedTinyInteger('rh')->nullable();
                $t->unsignedSmallInteger('code')->nullable();
                $t->boolean('final')->default(false);
                $t->timestamp('keptAt')->nullable();
                $t->primary(['cellKey', 'day']);
                $t->index('day');
            });
        }
        if (! Schema::hasTable('as_ai_outlooks')) {
            Schema::create('as_ai_outlooks', function (Blueprint $t) {
                $t->id();
                $t->string('kind', 40);
                $t->char('outlookKey', 40)->unique();
                $t->string('place', 160)->nullable();
                $t->string('months', 64)->nullable();
                $t->string('ensoKey', 40)->nullable();
                $t->longText('payload');
                $t->unsignedInteger('uses')->default(0);
                $t->timestamp('writtenAt')->nullable();
                $t->timestamp('lastUsedAt')->nullable();
                $t->timestamps();
                $t->index(['kind', 'writtenAt']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_ai_outlooks');
        Schema::dropIfExists('as_weather_days');
        Schema::dropIfExists('as_enso_advisories');
    }
};
