<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The climate shelf (2026-10-08): the ENSO state and the weather history
 * collected by the scheduler and kept here, so NPK Plus reads the season
 * from its own tables instead of asking NOAA and the weather archive on
 * every calculation (App\Support\ClimateStore).
 *
 *   as_enso_readings  one row per reading of NOAA's ONI and advisory
 *   as_weather_cells  a quarter degree square of land we keep history for
 *   as_weather_weeks  each week of each year in a cell: rain, sun, the
 *                     water the air pulls, heat, as sums over its days
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_enso_readings')) {
            Schema::create('as_enso_readings', function (Blueprint $t) {
                $t->id();
                $t->decimal('oni', 4, 2);
                $t->string('season', 12)->nullable();
                $t->string('phase', 10);
                $t->string('strength', 12)->nullable();
                $t->string('label', 60);
                $t->string('alert', 80)->nullable();
                $t->text('synopsis')->nullable();
                $t->timestamp('fetchedAt')->index();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_weather_cells')) {
            Schema::create('as_weather_cells', function (Blueprint $t) {
                $t->id();
                $t->string('cellKey', 24)->unique();
                $t->decimal('lat', 7, 4);
                $t->decimal('lng', 7, 4);
                $t->string('label', 160)->nullable();
                $t->date('fetchedThrough')->nullable();
                $t->timestamp('lastUsedAt')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_weather_weeks')) {
            Schema::create('as_weather_weeks', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('cellId');
                $t->smallInteger('year');
                $t->tinyInteger('week');
                $t->tinyInteger('days');
                $t->decimal('rain', 8, 1)->default(0);
                $t->decimal('rad', 8, 1)->default(0);
                $t->decimal('et0', 8, 1)->default(0);
                $t->decimal('tmean', 8, 1)->default(0);
                $t->decimal('tmax', 8, 1)->default(0);
                $t->tinyInteger('hot30')->default(0);
                $t->tinyInteger('hot32')->default(0);
                $t->tinyInteger('hot35')->default(0);
                $t->tinyInteger('cool15')->default(0);
                $t->unique(['cellId', 'year', 'week']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_weather_weeks');
        Schema::dropIfExists('as_weather_cells');
        Schema::dropIfExists('as_enso_readings');
    }
};
