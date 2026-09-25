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
| TWO CLIPS PER SCREEN, ONE PER SHAPE OF DEVICE.
|
| A tutorial recorded on a phone is portrait, and a phone is where it is
| watched -- so each screen carries a landscape clip (`video`/`poster`) for
| a desktop and a portrait one (`portrait`/`portrait_poster`) for a phone.
| The card picks by device and falls back to whichever exists: a screen
| with only a landscape recording still shows it on a phone, and only a
| screen with neither shows the placeholder (in the shape the device asks
| for). Paths are under /public.
|
| A YouTube id may stand in for either file (`youtube`, `youtube_portrait`
| -- Shorts are portrait); the library at /app/tutorials is YouTube-based
| and the finished recordings may well arrive that way. An id wins over a
| file of the same shape.
|
*/

return [

    'placeholder' => [
        'video'           => 'videos/tutorials/placeholder.mp4',
        'poster'          => 'videos/tutorials/placeholder-poster.webp',
        'portrait'        => 'videos/tutorials/placeholder-portrait.mp4',
        'portrait_poster' => 'videos/tutorials/placeholder-portrait-poster.webp',
    ],

    'pages' => [

        'dashboard' => [
            'title' => 'Your dashboard',
            'blurb' => 'Everything happening across your farm today — what is due, what the weather is doing, and where to go next. A short look around before you start.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'schedules' => [
            'title' => 'Your cropping schedules',
            'blurb' => 'Every season you farm lives here as a schedule. Open one to reach its modules, or start a new one when the next season comes round.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'hub' => [
            'title' => 'Inside a schedule',
            'blurb' => 'One season and every module that works on it — the plan, the lots, the people, the records. Pick a tile to open a module.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.activities' => [
            'title' => 'The activities board',
            'blurb' => 'Your day-by-day plan. Each card is a job on a day: tick it done, move it, price it. The whole season reads from here.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.settings' => [
            'title' => 'Schedule settings',
            'blurb' => 'The season’s name, how it counts its days, and who gets the morning email with the day’s work.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.lots' => [
            'title' => 'Lots',
            'blurb' => 'The blocks you farm, each with its crop and the day it went in. Every day count on the board starts from a lot.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.workers' => [
            'title' => 'Workers',
            'blurb' => 'Who turns up and what a half day costs. Put people on jobs and each day totals the cash to bring; give someone a login and they see only this farm.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.inventory' => [
            'title' => 'Inventory',
            'blurb' => 'What is in the shed and every move in or out. On hand is the sum of the moves, so it never drifts from the truth.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.documentation' => [
            'title' => 'Documentation',
            'blurb' => 'Certificates, receipts, protocols — anything a buyer or an inspector may ask for, kept with the season it belongs to.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.post-harvest' => [
            'title' => 'Observations',
            'blurb' => 'What the field actually did — yield, quality, what went wrong. Next season is planned from this.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.tags' => [
            'title' => 'Tags',
            'blurb' => 'Your own words on activities, expenses and notes. Tag something once and find everything wearing that word here.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.notes' => [
            'title' => 'Notes',
            'blurb' => 'Words, photos, clips and voice memos from the field — and it keeps writing when the signal drops.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.weather' => [
            'title' => 'Weather',
            'blurb' => 'The forecast for this farm, landed on each day of the board — so a spray planned into the rain is obvious before anyone drives out.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.growth' => [
            'title' => 'Growth stages',
            'blurb' => 'What each lot’s crop is doing at its day count and what it wants there — no table to memorise.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.gallery' => [
            'title' => 'Gallery',
            'blurb' => 'Every photo and clip from this season in one place, filed by where it was taken, so nothing has to be hunted for on the board.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.maps' => [
            'title' => 'Maps',
            'blurb' => 'Trace a block, drop a pin, mark where the pump is. A map can attach to a lot so it opens with that block.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.draw' => [
            'title' => 'Draw',
            'blurb' => 'A sketch is faster than words for some things — a sprayer setup, the corner that flooded. Draw it and tag it to a note.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.ai' => [
            'title' => 'Chat Anee',
            'blurb' => 'Ask Anee about this season. She reads the plan, the lots and the weather before she answers.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.reports' => [
            'title' => 'Reports',
            'blurb' => 'Costs, profit and season analysis, computed from what is on the board — every figure to the same peso.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the reports, each a page of its own (offered by route) ---- */

        'report.labor' => [
            'title' => 'Labor Report',
            'blurb' => 'Every worker day this season and what it cost, person by person. Pick a stretch of dates to see just those.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.expenses' => [
            'title' => 'Expenses Report',
            'blurb' => 'Everything spent this season, gathered from the board and sorted by what it went on. Filter it down and print it for the books.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.profit' => [
            'title' => 'Profit Report',
            'blurb' => 'What the harvest brought in against everything the season cost — expenses and labor together — so you see what was really made.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.anee-season' => [
            'title' => 'Anee Season Report',
            'blurb' => 'Anee reads your whole finished season and tells you what went wrong, what to change next time, and what you did well.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.sofar' => [
            'title' => 'Analyze So Far',
            'blurb' => 'A check-up halfway through: where the crop stands today, the risks ahead, and what to do next.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.protocol' => [
            'title' => 'View as Protocol',
            'blurb' => 'Your season written out as a protocol, stage by stage and day by day — easy to read, easy to share, easy to print.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.compare' => [
            'title' => 'Compare Reports',
            'blurb' => 'Pick two saved reports of the same kind and Anee reads them side by side, then tells you what changed and why it matters.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the analyses ---- */

        'analysis.when-to-plant' => [
            'title' => 'When to Plant',
            'blurb' => 'Tell Anee the crop and the place, and she weighs the climate to find the planting window that gives it the best chance.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'analysis.what-to-plant' => [
            'title' => 'What to Plant',
            'blurb' => 'Describe your ground and your plans, and Anee suggests the crops that suit them — with her reasons for each.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'analysis.variety' => [
            'title' => 'Variety Research',
            'blurb' => 'Anee looks up the varieties of a crop, compares them, and shows which fits your farm best.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'analysis.crop-protocol' => [
            'title' => 'Crop Protocol Analysis',
            'blurb' => 'A guide to growing a crop from start to harvest, stage by stage — what to apply, when, and what to watch for.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the tools that reach across every season ---- */

        'tool.notes' => [
            'title' => 'Global Notes',
            'blurb' => 'Every note from every season in one place. Search them, filter them, or start a new one without opening a schedule first.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.gallery' => [
            'title' => 'Global Gallery',
            'blurb' => 'Every photo and clip you have taken, from every season, on one shelf — with the albums you made along the way.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.contacts' => [
            'title' => 'Contact List',
            'blurb' => 'Your farm’s phonebook: suppliers, buyers, helpers. Tap a name to call or message them straight away.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.protocols' => [
            'title' => 'Protocol Builder',
            'blurb' => 'Write down how you grow a crop, task by task, on a day count. Build it once and use it every season after.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.protocol-editor' => [
            'title' => 'Building a protocol',
            'blurb' => 'Add the tasks in order, set the day each one falls on, and drag to rearrange. When it is ready, put it on a season.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.tags' => [
            'title' => 'Tags',
            'blurb' => 'Every tag you have used, in every season and tool. Tap one to see everything wearing it, or rename it everywhere at once.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.compare' => [
            'title' => 'Compare Reports',
            'blurb' => 'Pick two saved reports of the same kind — this season against last, or any two — and Anee lays them side by side and tells you what changed.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the quick tools: offered when their sheet opens, not by route ---- */

        'quick.capture' => [
            'title' => 'Quick Capture',
            'blurb' => 'Snap a photo, add a line about it, and file it to a season’s notes or gallery — or ask Anee what she sees.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'quick.record' => [
            'title' => 'Quick Record',
            'blurb' => 'Record a short clip in the field, give it a name, and it lands in the season’s gallery or notes.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'quick.voice' => [
            'title' => 'Quick Voice',
            'blurb' => 'Hands full? Say it instead. Record a voice note and file it to a season in two taps.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Screens that offer their tutorial by route
    |--------------------------------------------------------------------------
    |
    | Route name => tutorial key. The layout looks the current route up here
    | and, if it is listed, offers that key as the page loads -- so a screen
    | gets its card by one line here and nothing in its own view. (The older
    | screens -- dashboard, schedules, hub, the season's rooms -- still ask
    | for theirs from their views; do not list them here as well.)
    |
    | Route names hold dots, so this map is read whole and indexed
    | (config('tutorials.routes')[$name]), never through config()'s dot path.
    |
    */

    'routes' => [
        // The reports
        'sm.labor.report'    => 'report.labor',
        'sm.expenses.report' => 'report.expenses',
        'sm.profit.report'   => 'report.profit',
        'sm.anee.season'     => 'report.anee-season',
        'sm.anee.sofar'      => 'report.sofar',
        'sm.protocol.report' => 'report.protocol',
        'sm.compare.report'  => 'report.compare',

        // The analyses
        'wtp.page'   => 'analysis.when-to-plant',
        'whatp.page' => 'analysis.what-to-plant',
        'vary.page'  => 'analysis.variety',
        'proto.page' => 'analysis.crop-protocol',

        // The tools that reach across every season
        'notes.hub'     => 'tool.notes',
        'gallery.hub'   => 'tool.gallery',
        'contacts.page' => 'tool.contacts',
        'pb.page'       => 'tool.protocols',
        'pb.open'       => 'tool.protocol-editor',
        'tags.global'   => 'tool.tags',
        'compare.page'  => 'tool.compare',
    ],

    /*
    |--------------------------------------------------------------------------
    | "Don't show this again" lives in a cookie
    |--------------------------------------------------------------------------
    |
    | One cookie per key, set in the browser for five years (Chrome caps any
    | cookie at 400 days, so the card renews every one it finds on each page
    | load: in daily use it never runs out). Clear the site's cookies and the
    | cards come back.
    |
    | Until 2026-09-25 the answer was a row in as_tutorial_dismissals. Those
    | rows are still honoured -- someone who said "never" then is not asked
    | again -- and are copied into the cookie the first time they are seen,
    | but no new rows are written. Only the keys that existed then can have a
    | row, so only they are looked up; every newer key costs no query.
    |
    */

    'cookie_prefix' => 'anee_tutv_',
    'cookie_days'   => 1826,

    'account_keys' => ['dashboard', 'schedules', 'hub', 'module.*'],

];
