<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One weather reading per place, shared (2026-10-12): Open-Meteo is asked
 * for a place's forecast only when someone opens it and the kept reading is
 * three hours old or more; everyone at that place reads the kept one
 * (App\Services\WeatherService::reading). placeKey is the place's
 * coordinates to two decimals, which the town geocode gives every lot of
 * the same town.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_weather_forecasts')) {
            return;
        }
        Schema::create('as_weather_forecasts', function (Blueprint $t) {
            $t->id();
            $t->string('placeKey', 24)->unique();
            $t->decimal('lat', 8, 4);
            $t->decimal('lng', 8, 4);
            $t->string('model', 24)->default('best_match');
            $t->longText('payload');
            $t->timestamp('fetchedAt')->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_weather_forecasts');
    }
};
