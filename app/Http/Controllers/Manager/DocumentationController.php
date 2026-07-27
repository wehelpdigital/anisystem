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
        $schedule->load(['protocol', 'docTags', 'docEntries.tag']);

        return view('sm.documentation', [
            'schedule' => $schedule,
        ]);
    }
}
