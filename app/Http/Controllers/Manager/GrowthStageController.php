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
        [$dayZeroEff, $transplantEff] = \App\Support\LotCalendar::effectiveAnchors($schedule);

        $rows = [];
        foreach ($schedule->lots as $lot) {
            $crop = CropStages::normalize($lot->crop);
            $age = \App\Support\LotCalendar::ageOf($lot, $on, $dayZeroEff[$lot->id] ?? null, $transplantEff[$lot->id] ?? null);
            // The counter is not decoration: rice read in DAS was direct
            // seeded and has a different calendar from transplanted rice.
            // The lot's own days to maturity when it knows them: a 105-day
            // variety and a 120-day one do not reach panicle initiation on
            // the same day, and reading both against one figure is how a
            // farmer gets told they have three weeks left when they have one.
            $maturity = $lot->maturityDays();
            $stage = $crop && $age ? CropStages::stageFor($crop, $age['day'], $age['counter'], $maturity) : null;

            $rows[] = [
                'lot' => $lot,
                'crop' => $crop,
                'cropLabel' => CropStages::label($lot->crop),
                'icon' => CropStages::icon($lot->crop),
                'age' => $age,
                'stage' => $stage,
                'isTree' => CropStages::isPerennial($crop),
                'maturity' => $maturity,
                'tips' => $stage ? CropStageTips::for($crop, $stage['index'], $age['counter'] ?? null) : ['do' => [], 'watch' => []],
                'timeline' => $crop ? CropStages::timeline($crop, $age['day'] ?? null, $age['counter'] ?? null, $maturity) : [],
                // Why a lot cannot be read, said plainly, because "no stage"
                // on its own is not a useful answer.
                'blocked' => $this->whyBlocked($lot, $crop, $age, isset($dayZeroEff[$lot->id])),
            ];
        }

        return $rows;
    }

    // The anchor + counter arithmetic lives in App\Support\LotCalendar — the
    // schedules shelf reads the same numbers, and two copies of this logic is
    // how a lot once read "no day zero" here while counting fine elsewhere.

    /** How this lot's counter works, said in a phrase. */
    public static function counterSays(?string $mode): string
    {
        return match (strtoupper((string) ($mode ?: 'DAT'))) {
            'DAS' => 'Direct seeded — one count from sowing',
            'DAP' => 'Counted from planting',
            'TREE' => 'A standing crop — read by the age of the trees',
            default => 'Sown, then transplanted — DAS until the transplant, DAT after',
        };
    }

    private function whyBlocked($lot, ?string $crop, ?array $age, bool $hasDayZero): ?string
    {
        if (! $crop) {
            return 'No crop set on this lot. Open Lots and say what is growing here.';
        }
        // A tree is not waiting for a day zero; it is waiting to be told how
        // old it is, which is a different thing and a different fix.
        if (! $age && CropStages::isPerennial($crop)) {
            return 'No age on these trees yet. Open Lots and say how old they are — that is what their guidance is read against.';
        }
        if (! $age) {
            return $hasDayZero
                ? 'This date is before the lot\'s day zero — nothing is planted yet.'
                : 'No day zero on this lot yet. Set one in Lots, or tick "this is day zero" on the activity that starts the count.';
        }

        return null;
    }
}
