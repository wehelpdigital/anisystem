<?php

namespace App\Support\Concerns;

use App\Models\AsCroppingSchedule;
use App\Support\WorkerContext;

/**
 * The two questions every write to a schedule has to answer.
 *
 * They live in a trait rather than a base class because the controllers that
 * need them do not share one: the Hub predates BaseScheduleController and
 * keeps its own resolver. Fusing the two checks inside a single resolver is
 * what let them get lost — an endpoint that skipped the resolver to stay
 * usable on a completed schedule silently dropped the permission check too.
 * Asked separately, each can be waived on purpose and neither by accident.
 */
trait GuardsScheduleWrites
{
    /** Refuse a write from someone who may only look. */
    protected function assertCanEdit(): void
    {
        if (! WorkerContext::canEdit()) {
            abort(response()->json([
                'success' => false,
                'message' => 'You have view-only access to this schedule.',
            ], 403));
        }
    }

    /** Refuse a write to a schedule that has been marked completed. */
    protected function assertUnlocked(AsCroppingSchedule $schedule): void
    {
        if ($schedule->isLocked()) {
            abort(response()->json([
                'success' => false,
                'message' => 'This schedule is marked completed and locked. Reopen it in the Hub to make changes.',
            ], 423));
        }
    }
}
