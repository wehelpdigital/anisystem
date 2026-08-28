<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What each kind of sky is called, looks like, and means for the work.
 *
 * The forecast arrives from Open-Meteo as a number — a WMO code — and the app
 * turned it into an emoji and a word. That is enough to know it will rain and
 * not enough to know what to do about it, which is the only reason a farmer
 * looks at a forecast at all.
 *
 * So each kind of sky gets a row: what to call it in English and in Tagalog,
 * which scene to draw, which colour the panel behind it takes, and one line of
 * what it means for the day's work. In the table rather than in code because
 * the advice is the part somebody will want to reword for their own region,
 * and rewording a sentence should not need a deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_weather_scenes')) {
            return;
        }

        Schema::create('as_weather_scenes', function (Blueprint $table) {
            $table->id();
            // The slug the code asks for: 'rain', 'storm', 'clear_night'…
            $table->string('key', 40)->unique();
            $table->string('label', 60);                 // "Rain"
            $table->string('tagalog', 60)->nullable();    // "Maulan"
            $table->string('scene', 40);                  // which drawing
            $table->string('hue', 24);                    // which gradient
            $table->string('advice', 400)->nullable();    // what it means for the work
            $table->unsignedSmallInteger('sortOrder')->default(0);
            $table->unsignedTinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_weather_scenes');
    }
};
