<?php

namespace App\Support;

/**
 * What a day's heat means (2026-11-17): its level, what it feels like to a person in the sun, when it peaks, and the
 * heat notes for the day: the people, the nights, the animals. The work itself (planting, spraying, fertilizing,
 * field work, watering, drying) is read hour by hour in App\Support\FieldHours.
 *
 * Never tied to a crop (the owner, 2026-11-17: "do not connect the location to the crops in that lot"): the weather
 * decides, whatever is growing. Words only, no dashes. A line is ['i' => icon, 'b' => the bold lead, 't' => the rest].
 */
final class HeatGuide
{
    /** The day's level by its high, in order. */
    public const LEVELS = [
        'cool' => ['A cool day', 'Cool'],
        'mild' => ['A mild day', 'Mild'],
        'warm' => ['A warm day', 'Warm'],
        'hot' => ['A hot day', 'Hot'],
        'veryhot' => ['A very hot day', 'Very hot'],
        'extreme' => ['Extreme heat', 'Extreme'],
    ];

    /** What the heat feels like to a person, by the feels like temperature. */
    public const PEOPLE = [
        [52, 'extreme', 'Extreme danger'],
        [42, 'danger', 'Danger'],
        [33, 'xcaution', 'Extreme caution'],
        [27, 'caution', 'Caution'],
        [-99, 'ok', 'Comfortable'],
    ];

    public static function level(?float $max, ?float $feels): string
    {
        if ($max === null) {
            return 'mild';
        }
        $keys = array_keys(self::LEVELS);
        $at = match (true) {
            $max >= 37 => 5, $max >= 35 => 4, $max >= 33 => 3, $max >= 30 => 2, $max >= 25 => 1, default => 0,
        };
        // A wet heat is harder on the people and the plants than the thermometer says.
        if ($feels !== null) {
            $at = max($at, match (true) { $feels >= 52 => 5, $feels >= 46 => 4, $feels >= 42 => 3, default => 0 });
        }

        return $keys[$at];
    }

    /** @return array{0: string, 1: string} [key, words] */
    public static function people(?float $feels): array
    {
        foreach (self::PEOPLE as [$from, $key, $words]) {
            if ($feels !== null && $feels >= $from) {
                return [$key, $words];
            }
        }

        return ['ok', 'Comfortable'];
    }

    /**
     * One day's heat reading and its notes.
     *
     * @param  array  $day    the spot's day: max, min, fmax, pop, uv, code
     * @param  array  $hours  [hour => [t, f, h]] of that day
     */
    public static function day(array $day, array $hours): array
    {
        $max = isset($day['max']) ? (float) $day['max'] : null;
        $min = isset($day['min']) ? (float) $day['min'] : null;
        $feels = isset($day['fmax']) ? (float) $day['fmax'] : null;
        $uv = isset($day['uv']) ? (float) $day['uv'] : null;
        $level = self::level($max, $feels);
        [$pk, $pw] = self::people($feels);

        // When the heat peaks, and the hours it stays high.
        $peak = null;
        $above32 = 0;
        $above35 = 0;
        foreach ($hours as $hr => [$t]) {
            if ($t === null) {
                continue;
            }
            if ($peak === null || $t > $hours[$peak][0]) {
                $peak = $hr;
            }
            $above32 += $t >= 32 ? 1 : 0;
            $above35 += $t >= 35 ? 1 : 0;
        }
        $peakWord = $peak !== null ? self::hourWord($peak) : null;

        $lines = [];
        if ($pk === 'danger' || $pk === 'extreme') {
            $lines[] = ['i' => 'people', 'b' => 'Rest in the shade at midday.', 't' => 'Do the hard work before 10 AM and after 3 PM, drink a cup of water every 20 minutes even without thirst, and watch each other for dizziness, cramps or a headache.'];
        } elseif ($pk === 'xcaution') {
            $lines[] = ['i' => 'people', 'b' => 'Take shade breaks.', 't' => 'Rest in the shade now and then and keep drinking water, even when you do not feel thirsty.' . ($uv !== null && $uv >= 11 ? ' The sun is very strong: wear a wide hat and long sleeves.' : '')];
        }
        if ($min !== null && $min >= 26) {
            $lines[] = ['i' => 'moon', 'b' => 'A warm night.', 't' => 'The night stays at ' . self::deg($min) . ' or more. Plants rest poorly on warm nights: keep the soil moist so they are not thirsty as well.'];
        }
        if ($min !== null && $min <= 17) {
            $lines[] = ['i' => 'moon', 'b' => 'A cold night.', 't' => 'The night falls to ' . self::deg($min) . '. Cover young seedlings before dark.'];
        }
        if ($feels !== null && $feels >= 40) {
            $lines[] = ['i' => 'animal', 'b' => 'Look after the animals.', 't' => 'Give them shade and plenty of clean water, and do not move or truck them at midday.'];
        }

        $say = match ($level) {
            'extreme' => 'Heat this strong hurts plants and people. Plan the day around it.',
            'veryhot' => 'A very hot day. Plan the work around the afternoon.',
            'hot' => 'A hot afternoon. Keep the heavy work for the cool hours.',
            'warm' => 'A warm day, as most are here.',
            'mild' => 'Mild and easy on plants and people.',
            default => 'A cool day for the tropics.',
        };

        return [
            'level' => $level,
            'title' => self::LEVELS[$level][0],
            'tag' => self::LEVELS[$level][1],
            'say' => $say,
            'people' => ['key' => $pk, 'words' => $pw],
            'peak' => $peakWord,
            'above32' => $above32,
            'above35' => $above35,
            'lines' => $lines,
        ];
    }

    /** "1 PM", "noon" */
    public static function hourWord(int $h): string
    {
        $h = (($h % 24) + 24) % 24;
        if ($h === 12) {
            return 'noon';
        }

        return ($h % 12 === 0 ? 12 : $h % 12) . ($h < 12 ? ' AM' : ' PM');
    }

    private static function deg(float $v): string
    {
        return (string) (int) round($v) . '°C';
    }
}
