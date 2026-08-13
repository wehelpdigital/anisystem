<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsInlineNote;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleNote;
use App\Models\ScheduleAiMessage;
use App\Support\MediaStore;
use Illuminate\Http\Request;

/**
 * The Media Box, which is now the Gallery's "All" tab.
 *
 * There were two answers to "where are my pictures": a box that gathered
 * everything and a gallery that held albums. Nobody could be expected to
 * remember which one a picture was in — they are one module now, with a tab
 * each, and this controller only points at it so old links and bookmarks
 * still land somewhere sensible.
 */
class MediaBoxController extends BaseScheduleController
{
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');

        return redirect()->route('sm.gallery', array_filter([
            'id' => $schedule->id,
            'partial' => $request->boolean('partial') ? 1 : null,
        ]));
    }
}
