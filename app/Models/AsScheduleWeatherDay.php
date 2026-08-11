<?php

namespace App\Models;

/**
 * A saved forecast for one day at one lot location. Written whenever the
 * forecast is fetched and overwritten when it changes, so each row is the
 * latest reading for that date — history for reports and AI, not a log.
 */
class AsScheduleWeatherDay extends BaseModel
{
    protected $table = 'as_schedule_weather_days';

    protected $fillable = [
        'croppingScheduleId', 'locationKey', 'place', 'forecastDate',
        'day', 'hours', 'capturedAt', 'deleteStatus',
    ];

    protected $casts = [
        'croppingScheduleId' => 'integer',
        'forecastDate' => 'date',
        'day' => 'array',
        'hours' => 'array',
        'capturedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];
}
