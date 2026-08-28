<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;

/**
 * One kind of sky: its name, its drawing, its colour and what it means.
 *
 * Open-Meteo answers with a WMO code and nothing else. This is where a code
 * becomes something a farmer can use — "Maulan. Hold the spraying; it washes
 * off before it works" rather than a raincloud emoji.
 *
 * The mapping from code to key lives here in code, because it is a fact about
 * the WMO standard and not an opinion. Everything downstream of the key — the
 * words, the colour, the advice — lives in the table, because those are
 * opinions and somebody will want to change them for their own province.
 */
class AsWeatherScene extends BaseModel
{
    protected $table = 'as_weather_scenes';

    protected $fillable = [
        'key', 'label', 'tagalog', 'greeting', 'scene', 'hue', 'advice', 'sortOrder', 'deleteStatus',
    ];

    protected $casts = ['sortOrder' => 'integer', 'deleteStatus' => 'integer'];

    /** The drawings the front end knows. A key naming anything else falls back. */
    public const SCENES = [
        'clear', 'clear_night', 'partly', 'partly_night', 'cloudy', 'fog',
        'drizzle', 'rain', 'heavy_rain', 'showers', 'showers_night', 'storm', 'snow', 'hot', 'windy',
    ];

    /** The colour families the panels can wear. */
    public const HUES = ['sun', 'sky', 'grey', 'rain', 'storm', 'night', 'heat', 'wind'];

    /**
     * Which sky a WMO code is.
     *
     * Night matters wherever the drawing has a sun in it. Rain at night is
     * still rain and the raincloud is the same picture — but a clear night, a
     * half-clouded night and a shower at night all have to lose the sun, or
     * the app greets somebody with "Maulang gabi" over a picture of noon.
     *
     * Heat and wind ride over the top. A clear day at thirty-six degrees is
     * not the same working day as a clear day at twenty-eight, and the
     * difference is the one that puts people in hospital.
     */
    public static function keyFor(int $code, bool $night = false, ?float $tempC = null, ?float $windKph = null): string
    {
        // A dangerous sky outranks a hot or windy one; nothing outranks a storm.
        $base = match (true) {
            $code === 0 => $night ? 'clear_night' : 'clear',
            $code === 1 => $night ? 'clear_night' : 'clear',
            $code === 2 => $night ? 'partly_night' : 'partly',
            $code === 3 => 'cloudy',
            in_array($code, [45, 48], true) => 'fog',
            in_array($code, [51, 53, 55, 56, 57], true) => 'drizzle',
            in_array($code, [61, 63], true) => 'rain',
            in_array($code, [65, 66, 67], true) => 'heavy_rain',
            in_array($code, [80, 81, 82], true) => $night ? 'showers_night' : 'showers',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'snow',
            in_array($code, [95, 96, 99], true) => 'storm',
            default => 'cloudy',
        };

        if (in_array($base, ['storm', 'heavy_rain', 'rain', 'showers', 'showers_night', 'drizzle', 'snow', 'fog'], true)) {
            return $base;
        }

        // Only a clear or half-clear sky can be reported as hot or windy: a
        // "windy overcast" tells nobody anything they did not already know.
        if ($windKph !== null && $windKph >= 38) {
            return 'windy';
        }
        if ($tempC !== null && $tempC >= 34 && ! $night) {
            return 'hot';
        }

        return $base;
    }

    /**
     * Every sky, keyed, for the front end and for Blade.
     *
     * Cached: this is asked for on the way into three different screens and
     * the answer changes when somebody edits a sentence, not by the minute.
     */
    public static function map(): array
    {
        return Cache::remember('as_weather_scenes.map', 600, function () {
            return self::query()
                ->where('deleteStatus', 1)
                ->orderBy('sortOrder')
                ->get()
                ->keyBy('key')
                ->map(fn ($r) => [
                    'label' => $r->label,
                    'tagalog' => (string) $r->tagalog,
                    'greeting' => (string) $r->greeting,
                    'scene' => in_array($r->scene, self::SCENES, true) ? $r->scene : 'cloudy',
                    'hue' => in_array($r->hue, self::HUES, true) ? $r->hue : 'grey',
                    'advice' => (string) $r->advice,
                ])
                ->all();
        });
    }

    /** One sky, with a safe answer for a key nobody has written a row for. */
    public static function one(string $key): array
    {
        $all = self::map();

        return $all[$key] ?? [
            'label' => 'Mixed', 'tagalog' => '', 'greeting' => '', 'scene' => 'cloudy',
            'hue' => 'grey', 'advice' => '',
        ];
    }
}
