<?php

/*
|--------------------------------------------------------------------------
| Page tutorials
|--------------------------------------------------------------------------
|
| One short video per screen, shown in a card the first time somebody
| opens that screen and every time after until they say "don't show this
| again". The card is the same everywhere; what changes is the words and
| the clip, and both come from here so a finished recording replaces the
| placeholder by editing one line rather than a view.
|
| Keys are what a page ASKS FOR (see partials/tutorial-offer): a page
| key for a screen of its own, `module.<key>` for a room inside a season.
| The module keys are the Activities shell's own, so the modal for Lots is
| offered by the same word that opens Lots.
|
| `video` and `poster` are paths under /public; null means the placeholder.
| A page may carry `youtube` (the 11-character id) instead, since the
| tutorial library at /app/tutorials is YouTube-based and the finished
| recordings very likely will be too; it wins over `video` when set.
|
*/

return [

    'placeholder' => [
        'video'  => 'videos/tutorials/placeholder.mp4',
        'poster' => 'videos/tutorials/placeholder-poster.webp',
    ],

    'pages' => [

        'dashboard' => [
            'title' => 'Your dashboard',
            'blurb' => 'Everything happening across your farm today — what is due, what the weather is doing, and where to go next. A short look around before you start.',
            'video' => null, 'poster' => null,
        ],

        'schedules' => [
            'title' => 'Your cropping schedules',
            'blurb' => 'Every season you farm lives here as a schedule. Open one to reach its modules, or start a new one when the next season comes round.',
            'video' => null, 'poster' => null,
        ],

        'hub' => [
            'title' => 'Inside a schedule',
            'blurb' => 'One season and every module that works on it — the plan, the lots, the people, the records. Pick a tile to open a module.',
            'video' => null, 'poster' => null,
        ],

        'module.activities' => [
            'title' => 'The activities board',
            'blurb' => 'Your day-by-day plan. Each card is a job on a day: tick it done, move it, price it. The whole season reads from here.',
            'video' => null, 'poster' => null,
        ],

        'module.settings' => [
            'title' => 'Schedule settings',
            'blurb' => 'The season’s name, how it counts its days, and who gets the morning email with the day’s work.',
            'video' => null, 'poster' => null,
        ],

        'module.lots' => [
            'title' => 'Lots',
            'blurb' => 'The blocks you farm, each with its crop and the day it went in. Every day count on the board starts from a lot.',
            'video' => null, 'poster' => null,
        ],

        'module.workers' => [
            'title' => 'Workers',
            'blurb' => 'Who turns up and what a half day costs. Put people on jobs and each day totals the cash to bring; give someone a login and they see only this farm.',
            'video' => null, 'poster' => null,
        ],

        'module.inventory' => [
            'title' => 'Inventory',
            'blurb' => 'What is in the shed and every move in or out. On hand is the sum of the moves, so it never drifts from the truth.',
            'video' => null, 'poster' => null,
        ],

        'module.documentation' => [
            'title' => 'Documentation',
            'blurb' => 'Certificates, receipts, protocols — anything a buyer or an inspector may ask for, kept with the season it belongs to.',
            'video' => null, 'poster' => null,
        ],

        'module.post-harvest' => [
            'title' => 'Observations',
            'blurb' => 'What the field actually did — yield, quality, what went wrong. Next season is planned from this.',
            'video' => null, 'poster' => null,
        ],

        'module.tags' => [
            'title' => 'Tags',
            'blurb' => 'Your own words on activities, expenses and notes. Tag something once and find everything wearing that word here.',
            'video' => null, 'poster' => null,
        ],

        'module.notes' => [
            'title' => 'Notes',
            'blurb' => 'Words, photos, clips and voice memos from the field — and it keeps writing when the signal drops.',
            'video' => null, 'poster' => null,
        ],

        'module.weather' => [
            'title' => 'Weather',
            'blurb' => 'The forecast for this farm, landed on each day of the board — so a spray planned into the rain is obvious before anyone drives out.',
            'video' => null, 'poster' => null,
        ],

        'module.growth' => [
            'title' => 'Growth stages',
            'blurb' => 'What each lot’s crop is doing at its day count and what it wants there — no table to memorise.',
            'video' => null, 'poster' => null,
        ],

        'module.gallery' => [
            'title' => 'Gallery',
            'blurb' => 'Every photo and clip from this season in one place, filed by where it was taken, so nothing has to be hunted for on the board.',
            'video' => null, 'poster' => null,
        ],

        'module.maps' => [
            'title' => 'Maps',
            'blurb' => 'Trace a block, drop a pin, mark where the pump is. A map can attach to a lot so it opens with that block.',
            'video' => null, 'poster' => null,
        ],

        'module.draw' => [
            'title' => 'Draw',
            'blurb' => 'A sketch is faster than words for some things — a sprayer setup, the corner that flooded. Draw it and tag it to a note.',
            'video' => null, 'poster' => null,
        ],

        'module.ai' => [
            'title' => 'Chat Anee',
            'blurb' => 'Ask Anee about this season. She reads the plan, the lots and the weather before she answers.',
            'video' => null, 'poster' => null,
        ],

        'module.reports' => [
            'title' => 'Reports',
            'blurb' => 'Costs, profit and season analysis, computed from what is on the board — every figure to the same peso.',
            'video' => null, 'poster' => null,
        ],

    ],

];
