<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * How old the crop in a lot is, counted the way the whole app counts.
 *
 * One home for the two rules Growth Stages and the schedules shelf both
 * live by: an anchor is the lot's own date or the earliest anchoring
 * activity, whichever is earlier; and the lot's day type decides which
 * counter a date is read in. Two copies of this logic is how a lot came
 * to read "no day zero" on one page while counting fine on another.
 */
class LotCalendar
{
    /**
     * The anchors as the activities board reads them: the lot's own dates,
     * overridden by the EARLIEST day-zero / transplant activity covering it.
     *
     * @return array{0: array<int, Carbon>, 1: array<int, Carbon>}
     */
    public static function effectiveAnchors($schedule): array
    {
        $dayZero = [];
        $transplant = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $dayZero[$lot->id] = Carbon::parse($lot->dayZeroDate);
            }
            if ($lot->transplantDate) {
                $transplant[$lot->id] = Carbon::parse($lot->transplantDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (! $a->targetDate || (! $a->isDayZero && ! $a->isTransplant)) {
                continue;
            }
            $aDate = Carbon::parse($a->targetDate);
            foreach ($a->lots as $lot) {
                if ($a->isDayZero && (! isset($dayZero[$lot->id]) || $aDate->lt($dayZero[$lot->id]))) {
                    $dayZero[$lot->id] = $aDate->copy();
                }
                if ($a->isTransplant && (! isset($transplant[$lot->id]) || $aDate->lt($transplant[$lot->id]))) {
                    $transplant[$lot->id] = $aDate->copy();
                }
            }
        }

        return [$dayZero, $transplant];
    }

    /**
     * How old the crop in this lot is on a date, in the count the lot keeps.
     *
     * A DAT lot flips to a fresh count on its transplant date and reads
     * against the transplanted calendar from then on; a DAS lot was direct
     * seeded and never flips, whatever dates it carries; a DAP lot counts
     * from planting. The anchors arrive resolved (see effectiveAnchors).
     *
     * @return array{day:int, counter:string}|null
     */
    public static function ageOf($lot, Carbon $on, ?Carbon $dayZero, ?Carbon $transplant): ?array
    {
        $mode = strtoupper((string) ($lot->dayType ?: 'DAT'));

        /* A tree keeps no day count.
         *
         * Its stages are read against its age in months, so that is what
         * comes back — with the unit said out loud, because a caller handed
         * "day 66" for a five-and-a-half-year-old mango would draw it as a
         * seedling nine weeks out of the nursery. */
        if ($mode === 'TREE' || CropStages::isPerennial($lot->crop ?? null)) {
            if (! $lot->treePlantedAt) {
                return null;
            }
            $planted = Carbon::parse($lot->treePlantedAt)->startOfDay();
            $here = $on->copy()->startOfDay();
            if ($here->lt($planted)) {
                return null;
            }

            return ['day' => (int) $planted->diffInMonths($here), 'counter' => 'AGE', 'unit' => 'month'];
        }

        if ($mode === 'DAT' && $transplant) {
            $t = $transplant->copy()->startOfDay();
            if ($on->copy()->startOfDay()->gte($t)) {
                return ['day' => $t->diffInDays($on->copy()->startOfDay()), 'counter' => 'DAT'];
            }
        }

        if (! $dayZero) {
            return null;
        }

        $z = $dayZero->copy()->startOfDay();
        $day = $z->diffInDays($on->copy()->startOfDay(), false);
        // Before the transplant a two-phase lot is still counting from sowing,
        // so it is DAS — calling that number DAT would read it against the
        // wrong table.
        $counter = $mode === 'DAP' ? 'DAP' : 'DAS';

        return $day < 0 ? null : ['day' => (int) $day, 'counter' => $counter];
    }
}
