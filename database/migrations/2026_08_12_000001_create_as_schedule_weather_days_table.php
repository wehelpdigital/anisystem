<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the forecast said for a given day and lot location, kept so reports and
 * the AI technician can look back at the weather a decision was made under.
 * One row per schedule + location + date: a fresh forecast overwrites it, so
 * the row always holds the latest reading for that day rather than a pile of
 * revisions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('as_schedule_weather_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('croppingScheduleId');
            $table->string('locationKey', 64);
            $table->string('place', 191)->nullable();
            $table->date('forecastDate');
            $table->json('day');                 // the day's own reading
            $table->json('hours')->nullable();   // hour-by-hour, when it was asked for
            $table->timestamp('capturedAt')->nullable();
            $table->integer('deleteStatus')->default(1);
            $table->timestamps();

            $table->unique(['croppingScheduleId', 'locationKey', 'forecastDate'], 'as_wx_day_unique');
            $table->index(['croppingScheduleId', 'forecastDate'], 'as_wx_day_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_weather_days');
    }
};
