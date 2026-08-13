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
        [$dayZeroEff, $transplantEff] = $this->effectiveAnchors($schedule);

        $rows = [];
        foreach ($schedule->lots as $lot) {
            $crop = CropStages::normalize($lot->crop);
            $age = $this->ageOf($lot, $crop, $on, $dayZeroEff[$lot->id] ?? null, $transplantEff[$lot->id] ?? null);
            // The counter is not decoration: rice read in DAS was direct
            // seeded and has a different calendar from transplanted rice.
            $stage = $crop && $age ? CropStages::stageFor($crop, $age['day'], $age['counter']) : null;

            $rows[] = [
                'lot' => $lot,
                'crop' => $crop,
                'cropLabel' => CropStages::label($lot->crop),
                'icon' => CropStages::icon($lot->crop),
                'age' => $age,
                'stage' => $stage,
                'tips' => $stage ? CropStageTips::for($crop, $stage['index'], $age['counter'] ?? null) : ['do' => [], 'watch' => []],
                'timeline' => $crop ? CropStages::timeline($crop, $age['day'] ?? null, $age['counter'] ?? null) : [],
                // Why a lot cannot be read, said plainly, because "no stage"
                // on its own is not a useful answer.
                'blocked' => $this->whyBlocked($lot, $crop, $age, isset($dayZeroEff[$lot->id])),
            ];
        }

        return $rows;
    }

    /**
     * The anchors as the activities board reads them: the lot's own dates,
     * overridden by the EARLIEST day-zero / transplant activity covering it.
     * Reading only the lot's columns here meant a count started by ticking
     * "this is day zero" on an activity existed everywhere but on this page.
     *
     * @return array{0: array<int, \Carbon\Carbon>, 1: array<int, \Carbon\Carbon>}
     */
    private function effectiveAnchors($schedule): array
    {
        $dayZero = [];
        $transplant = [];
        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $dayZero[$lot->id] = \Carbon\Carbon::parse($lot->dayZeroDate);
            }
            if ($lot->transplantDate) {
                $transplant[$lot->id] = \Carbon\Carbon::parse($lot->transplantDate);
            }
        }
        foreach ($schedule->activities as $a) {
            if (! $a->targetDate || (! $a->isDayZero && ! $a->isTransplant)) {
                continue;
            }
            $aDate = \Carbon\Carbon::parse($a->targetDate);
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
     * The lot says how it was established, and that is the whole answer: a
     * DAT lot flips to a fresh count on its transplant date and reads against
     * the transplanted calendar from then on; a DAS lot was direct seeded and
     * never flips, whatever dates it carries; a DAP lot counts from planting.
     * Reading a direct-seeded field against a transplanted calendar is how a
     * stage ends up a fortnight out.
     *
     * The anchors arrive resolved (lot date or day-zero/transplant activity,
     * whichever is earlier) so this page counts from the same day the
     * activities board does.
     *
     * @return array{day:int, counter:string}|null
     */
    private function ageOf($lot, ?string $crop, \Carbon\Carbon $on, ?\Carbon\Carbon $dayZero, ?\Carbon\Carbon $transplant): ?array
    {
        $mode = strtoupper((string) ($lot->dayType ?: 'DAT'));

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

    /** How this lot's counter works, said in a phrase. */
    public static function counterSays(?string $mode): string
    {
        return match (strtoupper((string) ($mode ?: 'DAT'))) {
            'DAS' => 'Direct seeded — one count from sowing',
            'DAP' => 'Counted from planting',
            default => 'Sown, then transplanted — DAS until the transplant, DAT after',
        };
    }

    private function whyBlocked($lot, ?string $crop, ?array $age, bool $hasDayZero): ?string
    {
        if (! $crop) {
            return 'No crop set on this lot. Open Lots and say what is growing here.';
        }
        if (! $age) {
            return $hasDayZero
                ? 'This date is before the lot\'s day zero — nothing is planted yet.'
                : 'No day zero on this lot yet. Set one in Lots, or tick "this is day zero" on the activity that starts the count.';
        }

        return null;
    }
}
