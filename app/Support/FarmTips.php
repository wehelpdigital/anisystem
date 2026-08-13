<?php

namespace App\Support;

/**
 * The AI technician's tip of the day.
 *
 * Two pools, deliberately:
 *
 *   1. Whatever the grower's own crops are doing right now — pulled from
 *      CropStageTips, so on a day when the rice is at panicle initiation the
 *      tip is about panicle initiation. A tip about the field you are
 *      standing in beats a tip about farming in general.
 *
 *   2. General practice, for a schedule with no crop set, no day zero, or a
 *      date outside every season — because "no tip today" is a worse answer
 *      than a good general one.
 *
 * The choice is stable for a day and for a user: the same person opening the
 * app three times before lunch gets the same tip, and tomorrow gets another.
 * A random tip on every page load reads as noise.
 */
class FarmTips
{
    /** General practice, grouped so the card can say where a tip comes from. */
    public const GENERAL = [
        'Water' => [
            'Water early morning or late afternoon. Midday watering loses much of it to the air before the roots see any.',
            'A field that dries and floods in turn stresses a crop more than one kept a little dry throughout.',
            'Check the levees after every heavy rain — a leak found today is cheaper than the fertiliser it carries away tomorrow.',
        ],
        'Soil' => [
            'Test the soil before the season, not after a bad one. Lime takes months to work.',
            'Organic matter is the cheapest water storage there is. Return the straw and stubble where you can.',
            'Compaction from wet-season traffic shows up as a crop that will not root deep. Stay off the field when it is soft.',
        ],
        'Fertiliser' => [
            'Split nitrogen rather than giving it all at once — the plant can only use so much at a time, and the rest leaves with the water.',
            'Band fertiliser beside the row rather than broadcasting it; less is wasted and less feeds the weeds.',
            'Dark green is not always healthy. Too much nitrogen makes soft growth that lodges and invites pests.',
        ],
        'Pests' => [
            'Walk the field twice a week and look under leaves. Most outbreaks are cheap to stop and expensive to catch up with.',
            'Spray in the cool of the day. Midday heat evaporates the mix and burns the leaf.',
            'Rotate what you spray. The same chemical every time is how resistance is bred on your own farm.',
            'Keep the bunds and channels clear — tall weeds there are where borers and rats live between crops.',
        ],
        'Weather' => [
            'A forecast is a warning, not a promise. Plan the day so a wrong forecast costs you time, not the crop.',
            'Do not spray before rain. It washes off and you have paid for nothing.',
            'Before a storm, clear the drains. Standing water after a storm does more damage than the wind did.',
        ],
        'Records' => [
            'Write down what you actually did, not what you planned. Next season is planned from the first and ruined by the second.',
            'Photograph anything unusual the day you see it — a leaf, a receipt, a damaged bund. It costs nothing and settles arguments later.',
            'Record the price you were paid, not the price you were quoted.',
        ],
        'Workers' => [
            'Agree the rate before the work, out loud, in front of the crew. Most wage disputes are memory, not dishonesty.',
            'A worker who knows the whole day\'s plan finishes faster than one told task by task.',
        ],
        'Harvest' => [
            'Book drying space before harvest week, not during it. Wet grain waiting for a dryer is grain losing value by the hour.',
            'Harvest in the cool of the morning where you can. Field heat is what shortens shelf life.',
            'Clean the threshing floor first. Grit and stones cost you at the buying station.',
        ],
        'Money' => [
            'Count the cost of a day before it starts — wages plus what you must buy. A day that surprises you is a day you borrowed for.',
            'Keep the season\'s receipts in one place while it is running. Nobody has ever enjoyed reconstructing them afterwards.',
        ],
    ];

    /**
     * A tip for this user, today.
     *
     * @return array{text: string, source: string, scope: string}
     */
    public static function forToday(int $userId, $schedule = null, ?\Carbon\Carbon $on = null): array
    {
        $on = $on ?: \Carbon\Carbon::today();
        $pool = self::poolFor($schedule, $on);

        // Same person, same day, same tip — and a different one tomorrow.
        $seed = crc32($userId . ':' . $on->toDateString());

        return $pool[$seed % max(1, count($pool))];
    }

    /** Everything worth saying to this grower today. */
    private static function poolFor($schedule, \Carbon\Carbon $on): array
    {
        $pool = [];

        foreach (self::cropStageTips($schedule, $on) as $row) {
            $pool[] = $row;
        }

        foreach (self::GENERAL as $group => $tips) {
            foreach ($tips as $t) {
                $pool[] = ['text' => $t, 'source' => $group, 'scope' => 'general'];
            }
        }

        return $pool;
    }

    /**
     * Tips for what the grower's own lots are doing today, weighted by being
     * listed twice — a tip about the field you are standing in should come up
     * more often than one about farming in general.
     */
    private static function cropStageTips($schedule, \Carbon\Carbon $on): array
    {
        if (! $schedule) {
            return [];
        }

        $out = [];
        foreach ($schedule->lots as $lot) {
            $crop = CropStages::normalize($lot->crop);
            if (! $crop) {
                continue;
            }

            // Transplanted only when the lot actually was; otherwise the
            // count runs from sowing and the crop is read as direct seeded.
            $transplanted = CropStages::counter($crop) === 'DAT' && $lot->transplantDate;
            $anchor = $transplanted ? $lot->transplantDate : $lot->dayZeroDate;
            if (! $anchor) {
                continue;
            }

            $day = $anchor->copy()->startOfDay()->diffInDays($on->copy()->startOfDay(), false);
            if ($day < 0) {
                continue;
            }

            $counter = $transplanted ? 'DAT' : ($lot->dayType ?: 'DAS');
            $stage = CropStages::stageFor($crop, (int) $day, $counter);
            if (! $stage) {
                continue;
            }

            $tips = CropStageTips::for($crop, $stage['index'], $counter);
            foreach (array_merge($tips['do'], $tips['watch']) as $t) {
                $row = [
                    'text' => $t,
                    'source' => CropStages::label($crop) . ' · ' . $stage['label'],
                    'scope' => 'crop',
                ];
                // Twice, so the pool leans towards what is actually happening.
                $out[] = $row;
                $out[] = $row;
            }
        }

        return $out;
    }
}
