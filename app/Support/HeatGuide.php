<?php

namespace App\Support;

/**
 * What a day's heat means for the farm (2026-11-17, the owner: "another tab to show the forecasted temperature and
 * guides based on the future dates"). Plain rules on the lot's own forecast, written in our own words, no credits:
 * how hot it gets and when, what it feels like to a person in the sun, and what to do about it: the work hours,
 * the sprays, the water, the fertilizer, the seedlings, the animals. When the lot is growing a crop in an open
 * season, its stage on that date comes first ("Your rice is flowering").
 *
 * Words only, no dashes (the house label rules). A line is ['i' => icon, 'b' => the bold lead, 't' => the rest].
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

    private const RICE = ['rice', 'rice_dsr_wet', 'rice_dsr_dry'];

    private const CORN = ['corn_yellow', 'corn_sweet', 'corn_glutinous', 'sorghum'];

    private const LEGUMES = ['mungbean', 'peanut', 'soybean', 'stringbean', 'cowpea', 'wingedbean', 'limabean', 'pigeonpea'];

    private const FRUIT_VEG = ['tomato', 'eggplant', 'ampalaya', 'squash', 'cucumber', 'okra', 'chili', 'bellpepper', 'patola', 'upo', 'watermelon', 'melon', 'strawberry'];

    private const COOL_CROPS = ['pechay', 'cabbage', 'lettuce', 'mustard', 'broccoli', 'cauliflower', 'celery', 'carrot', 'radish', 'potato', 'garlic', 'onion', 'shallot'];

    public static function level(?float $max, ?float $feels): string
    {
        if ($max === null) {
            return 'mild';
        }
        $keys = array_keys(self::LEVELS);
        $at = match (true) {
            $max >= 37 => 5, $max >= 35 => 4, $max >= 33 => 3, $max >= 30 => 2, $max >= 25 => 1, default => 0,
        };
        // A wet heat is harder on the crop and on the people than the thermometer says.
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
     * One day's reading and its guide.
     *
     * @param  array  $day    the lot's day: max, min, fmax, pop, uv, code
     * @param  array  $hours  [hour => [t, f, h]] of that day
     * @param  array|null  $grow  ['crop' => key, 'word' => 'rice', 'stage' => 'Flowering', 'lot' => name] on that date
     */
    public static function day(array $day, array $hours, ?array $grow = null): array
    {
        $max = isset($day['max']) ? (float) $day['max'] : null;
        $min = isset($day['min']) ? (float) $day['min'] : null;
        $feels = isset($day['fmax']) ? (float) $day['fmax'] : null;
        $uv = isset($day['uv']) ? (float) $day['uv'] : null;
        $pop = isset($day['pop']) ? (int) $day['pop'] : null;
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
        foreach (self::cropSay($grow, $max, $min) as $l) {
            $lines[] = $l;
        }

        $hot = in_array($level, ['hot', 'veryhot', 'extreme'], true);
        if ($pk === 'danger' || $pk === 'extreme') {
            $lines[] = ['i' => 'people', 'b' => 'Work in the cool hours.', 't' => 'Do the field work before 10 AM and after 3 PM. Rest in the shade at midday, drink a cup of water every 20 minutes even without thirst, and watch each other for dizziness, cramps or a headache.'];
        } elseif ($pk === 'xcaution') {
            $lines[] = ['i' => 'people', 'b' => 'Take shade breaks.', 't' => 'Rest in the shade now and then and keep drinking water, even when you do not feel thirsty.' . ($uv !== null && $uv >= 11 ? ' The sun is very strong: wear a wide hat and long sleeves.' : '')];
        }
        if ($max !== null && $max >= 30) {
            $lines[] = ['i' => 'spray', 'b' => 'Spray in the cool of the day.', 't' => 'Spray before 9 AM or after 4 PM. In the midday heat the spray dries before it works, drifts more and can burn the leaves.' . ($peakWord ? ' The heat peaks around ' . $peakWord . '.' : '')];
        }
        if ($max !== null && $max >= 33) {
            $lines[] = ['i' => 'water', 'b' => 'Water early.', 't' => 'Water or irrigate early in the morning, not at noon: less is lost to the air, and the roots have it before the heat comes.'];
        }
        if ($max !== null && $max >= 32) {
            $lines[] = ['i' => 'fert', 'b' => 'Fertilize in the morning.', 't' => 'Put urea on in the cool morning, on moist soil, then water it in or mix it into the soil. On hot dry ground at midday much of it is lost to the air.'];
        }
        if ($max !== null && $max >= 33) {
            $lines[] = ['i' => 'seedling', 'b' => 'Protect the young plants.', 't' => 'Transplant in the late afternoon so the seedlings settle in the cool night, and shade young vegetable seedlings at midday.'];
        }
        if ($min !== null && $min >= 26) {
            $lines[] = ['i' => 'moon', 'b' => 'A warm night.', 't' => 'The night stays at ' . self::deg($min) . ' or more. Plants keep burning the food they stored in the day, so filling grains and fruits can suffer. Keep the soil moist so they are not thirsty as well.'];
        }
        if ($min !== null && $min <= 17) {
            $lines[] = ['i' => 'moon', 'b' => 'A cold night.', 't' => 'The night falls to ' . self::deg($min) . '. Cover young seedlings, and know that rice forming its panicles can lose grains in nights this cold.'];
        }
        if ($feels !== null && $feels >= 40) {
            $lines[] = ['i' => 'animal', 'b' => 'Look after the animals.', 't' => 'Give them shade and plenty of clean water, and do not move or truck them at midday.'];
        }
        if (! $hot && $level !== 'warm') {
            $lines[] = ['i' => 'ok', 'b' => 'A good day for heavy work.', 't' => 'Weeding, land preparation, harvesting and hauling are easier in this weather.'];
        }
        if ($pop !== null && $pop >= 70) {
            $lines[] = ['i' => 'rain', 'b' => 'Rain is likely.', 't' => 'It cools the afternoon, but keep the sprays and the fertilizer for the dry hours.'];
        }

        $say = match ($level) {
            'extreme' => 'Heat this strong hurts crops and people. Plan the day around it.',
            'veryhot' => 'A very hot day. Plan the work and the water around the afternoon.',
            'hot' => 'A hot afternoon. Keep the heavy work and the sprays for the cool hours.',
            'warm' => 'A warm day, as most are here. Keep the sprays and the fertilizer for the cool hours.',
            'mild' => 'Mild and easy on the crop and the people.',
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

    /** "1 PM" */
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

    /** The crop's own lines for its stage on that date, only when the heat matters to it. */
    private static function cropLines(?array $grow, ?float $max, ?float $min): array
    {
        if (! $grow || empty($grow['crop']) || empty($grow['stage']) || $max === null) {
            return [];
        }
        $crop = (string) $grow['crop'];
        $word = (string) ($grow['word'] ?? 'crop');
        $stage = (string) $grow['stage'];
        $s = mb_strtolower($stage);
        $has = fn (string ...$w) => collect($w)->contains(fn ($x) => str_contains($s, $x));
        $warmNight = $min !== null && $min >= 26;
        $i = 'crop';

        if (in_array($crop, self::RICE, true)) {
            if ($has('flower', 'heading') && $max >= 34) {
                return [['i' => $i, 'b' => 'Your rice is ' . ($has('flower') ? 'flowering' : 'heading') . '.', 't' => 'Above 35°C in the morning bloom, about 9 to 11 AM, the pollen dies and the grains stay empty. Keep about 5 cm of water in the field until flowering ends: it cools the plants.']];
            }
            if ($has('panicle', 'booting') && $max >= 34) {
                return [['i' => $i, 'b' => 'Your rice is forming its panicles.', 't' => 'Do not let the field dry out now: heat with thirst means fewer grains in each panicle.']];
            }
            if ($has('grain') && ($max >= 34 || $warmNight)) {
                return [['i' => $i, 'b' => 'Your rice is filling its grains.', 't' => 'Hot days and warm nights give lighter, chalky grains. Keep the soil wet until about two weeks before harvest.']];
            }
            if ($has('ripen', 'harvest') && $max >= 31) {
                return [['i' => $i, 'b' => 'A good day to dry palay.', 't' => 'Spread it thin and turn it every hour or so: in strong sun, grains that dry too fast crack and break in the mill.']];
            }
            if ($has('germin', 'seedling') && $max >= 34) {
                return [['i' => $i, 'b' => 'Your rice seedlings dry out fast.', 't' => 'Keep the soil moist, and never let a dry crust form over the seeds.']];
            }
            if ($max >= 35) {
                return [['i' => $i, 'b' => 'Your rice is at ' . self::stageWord($stage) . '.', 't' => 'Young rice takes heat better, but keep 3 to 5 cm of water so it is not stressed.']];
            }

            return [];
        }
        if (in_array($crop, self::CORN, true)) {
            if ($has('tassel', 'silk') && $max >= 33) {
                return [['i' => $i, 'b' => 'Your ' . $word . ' is ' . ($has('silk') ? 'silking' : 'tasselling') . '.', 't' => 'Above 35°C the pollen dies and dry silks catch none, so the cobs fill poorly. Water before the hottest days so the silks stay fresh.']];
            }
            if ($has('grain') && $max >= 34) {
                return [['i' => $i, 'b' => 'Your ' . $word . ' is filling its kernels.', 't' => 'Heat and thirst now give small, light kernels: keep the soil moist.']];
            }
            if ($has('rapid') && $max >= 34) {
                return [['i' => $i, 'b' => 'Your ' . $word . ' is growing fast.', 't' => 'It drinks the most water now. Water before the hot afternoons.']];
            }
            if ($has('maturity', 'harvest') && $max >= 31) {
                return [['i' => $i, 'b' => 'A good day to dry the ' . $word . '.', 't' => 'Spread the cobs or the kernels in the sun and turn them often.']];
            }

            return [];
        }
        if (in_array($crop, self::LEGUMES, true) && $has('flower', 'pod') && $max >= 34) {
            return [['i' => $i, 'b' => 'Your ' . $word . ' is ' . ($has('flower') ? 'flowering' : 'filling its pods') . '.', 't' => 'Flowers drop above 35°C. Water at dawn and keep a mulch on the soil to hold the moisture.']];
        }
        if (in_array($crop, self::FRUIT_VEG, true) && $has('flower', 'fruit') && ($max >= 33 || ($min !== null && $min >= 25))) {
            return [['i' => $i, 'b' => 'Your ' . $word . ' is ' . ($has('flower') ? 'flowering' : 'setting fruit') . '.', 't' => 'Above 35°C by day, or nights above 25°C, the flowers drop and the fruits do not set. Water at dawn, mulch the beds, and a shade net at midday helps.']];
        }
        if (in_array($crop, self::COOL_CROPS, true) && $max >= 32) {
            return [['i' => $i, 'b' => 'Your ' . $word . ' likes it cool.', 't' => 'In this heat it can bolt or turn bitter. Water at dawn and at dusk, and shade it at midday.']];
        }
        if ($max >= 35) {
            return [['i' => $i, 'b' => 'Your ' . $word . ' is at ' . self::stageWord($stage) . '.', 't' => 'Heat this strong stresses it. Water early and keep the soil covered so it does not dry out.']];
        }

        return [];
    }

    /**
     * The crop's lines, and when the heat is no worry to it, that said too: a farmer reading the day with a crop in
     * the field wants to know either way.
     */
    public static function cropSay(?array $grow, ?float $max, ?float $min): array
    {
        $lines = self::cropLines($grow, $max, $min);
        if ($lines || ! $grow || empty($grow['stage']) || $max === null || $max < 30 || in_array((string) ($grow['crop'] ?? ''), self::COOL_CROPS, true)) {
            return $lines;
        }

        return [['i' => 'crop', 'b' => 'Your ' . ($grow['word'] ?? 'crop') . ' can take this heat.', 't' => 'At ' . self::stageWord((string) $grow['stage']) . ' the harm starts at about 35°C, and this day stays below that. Keep the usual water.']];
    }

    /** "Booting & heading" said as words: "booting and heading". */
    public static function stageWord(string $stage): string
    {
        return mb_strtolower(str_replace(' & ', ' and ', $stage));
    }
}
