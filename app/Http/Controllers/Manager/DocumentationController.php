<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;

class DocumentationController extends BaseScheduleController
{
    /**
     * Documentation module page (?id={scheduleId}) — protocol document,
     * introduction (active version's global note), attachments and
     * critical rules in four sub-tabs.
     */
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        $schedule->load(['protocol', 'attachments', 'criticalRules', 'versions']);

        // Active version resolution mirrors the mother app's fallback
        // chain: isActive -> isOriginal -> first (may be null when the
        // schedule has no versions yet).
        $activeVersion = $schedule->versions->first(fn ($v) => $v->isActive)
            ?? $schedule->versions->first(fn ($v) => $v->isOriginal)
            ?? $schedule->versions->first();

        return view('sm.documentation', [
            'schedule' => $schedule,
            'activeVersion' => $activeVersion,
        ]);
    }
}
