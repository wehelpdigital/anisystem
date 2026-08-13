<?php

namespace App\Http\Controllers\Manager;

use App\Support\CropStages;
use App\Support\CropStageTips;
use Illuminate\Http\Request;

/**
 * Growth Stages: where every lot's crop is today, and what that means.
 *
 * The board counts days — DAS from sowing, DAT from transplanting, DAP from
 * planting — and that number is the whole basis of this page. Day 45 of rice
 * counted from transplanting is panicle initiation, which is a fertiliser
 * decision and a promise not to let the field dry; day 45 of corn counted
 * from planting is tasselling, which is a promise about water. Same number,
 * different work.
 *
 * The day-header pill answers this for one day in passing. This is the module
 * you open when the question is the question.
 */
class GrowthStageController extends BaseScheduleController
{
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $on = $this->askedDate($request);

        return view('sm.growth', [
            'schedule' => $schedule,
            'on' => $on,
            'rows' => $this->rowsFor($schedule, $on),
        ]);
    }

    private function askedDate(Request $request): \Carbon\Carbon
    {
        $asked = (string) $request->query('on');

        try {
            return $asked !== '' ? \Carbon\Carbon::parse($asked) : \Carbon\Carbon::today();
        } catch (\Throwable) {
            return \Carbon\Carbon::today();
        }
    }

    /**
     * One row per lot: its crop, how old it is on that date, and the stage
     * that age falls in.
     */
    private function rowsFor($schedule, \Carbon\Carbon $on): array
    {
        $rows = [];
        foreach ($schedule->lots as $lot) {
            $crop = CropStages::normalize($lot->crop);
            $age = $this->ageOf($lot, $crop, $on);
            $stage = $crop && $age ? CropStages::stageFor($crop, $age['day']) : null;

            $rows[] = [
                'lot' => $lot,
                'crop' => $crop,
                'cropLabel' => CropStages::label($lot->crop),
                'icon' => CropStages::icon($lot->crop),
                'age' => $age,
                'stage' => $stage,
                'tips' => $stage ? CropStageTips::for($crop, $stage['index']) : ['do' => [], 'watch' => []],
                'timeline' => $crop ? CropStages::timeline($crop, $age['day'] ?? null) : [],
                // Why a lot cannot be read, said plainly, because "no stage"
                // on its own is not a useful answer.
                'blocked' => $this->whyBlocked($lot, $crop, $age),
            ];
        }

        return $rows;
    }

    /** @return array{day:int, counter:string}|null */
    private function ageOf($lot, ?string $crop, \Carbon\Carbon $on): ?array
    {
        // A crop read from transplanting only counts that way once the lot has
        // actually been transplanted; before that the count runs from day zero.
        if ($crop && CropStages::counter($crop) === 'DAT' && $lot->transplantDate) {
            $t = $lot->transplantDate->copy()->startOfDay();
            if ($on->copy()->startOfDay()->gte($t)) {
                return ['day' => $t->diffInDays($on->copy()->startOfDay()), 'counter' => 'DAT'];
            }
        }

        if (! $lot->dayZeroDate) {
            return null;
        }

        $z = $lot->dayZeroDate->copy()->startOfDay();
        $day = $z->diffInDays($on->copy()->startOfDay(), false);

        return $day < 0 ? null : ['day' => (int) $day, 'counter' => $lot->dayType ?: 'DAS'];
    }

    private function whyBlocked($lot, ?string $crop, ?array $age): ?string
    {
        if (! $crop) {
            return 'No crop set on this lot. Open Lots and say what is growing here.';
        }
        if (! $age) {
            return $lot->dayZeroDate
                ? 'This date is before the lot\'s day zero — nothing is planted yet.'
                : 'No day zero on this lot yet. Set one in Lots, or tick "this is day zero" on the activity that starts the count.';
        }

        return null;
    }
}
