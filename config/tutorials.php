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
            'blurb' => 'Your home screen. Today\'s tasks, the weather on your farm, and shortcuts to your seasons and tools are all here. Watch this short tour before you start.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'schedules' => [
            'title' => 'Your cropping schedules',
            'blurb' => 'Each season you farm is saved here as a cropping schedule. Tap one to open it, or create a new one when the next planting comes.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'hub' => [
            'title' => 'Inside a schedule',
            'blurb' => 'This is one season and all of its tools. Tap a tile to open the plan, your lots, your workers, your records and the rest.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.activities' => [
            'title' => 'The activities board',
            'blurb' => 'This is your daily plan for the season. Each card is a job for that day. Mark it done, move it to another day, or add its cost, and the reports use what you put here.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.settings' => [
            'title' => 'Schedule settings',
            'blurb' => 'Change the season\'s name, how it counts the days, and who gets the morning email with the work for the day.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.lots' => [
            'title' => 'Lots',
            'blurb' => 'Add the fields or blocks you farm, with the crop and the date it was planted. The day count on your board starts from each lot\'s planting date.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.workers' => [
            'title' => 'Workers',
            'blurb' => 'List the people who work on your farm and how much you pay them. Put them on jobs and each day shows how much cash to prepare. You can also give a worker a login so they only see this farm.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.inventory' => [
            'title' => 'Inventory',
            'blurb' => 'Keep track of what is in your storage. Every item that comes in or goes out is recorded, so the count on hand always matches what you really have.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.documentation' => [
            'title' => 'Documentation',
            'blurb' => 'Keep your certificates, receipts and other papers with the season they belong to, ready for when a buyer or an inspector asks for them.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.post-harvest' => [
            'title' => 'Observations',
            'blurb' => 'Write down what really happened in the field: the yield, the quality, and what went wrong. Look back at it when you plan the next season.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.tags' => [
            'title' => 'Tags',
            'blurb' => 'Tags are your own labels for activities, expenses and notes. Tag something once, then open that tag here to see everything that has it.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.notes' => [
            'title' => 'Notes',
            'blurb' => 'Save what you see in the field as text, photos, videos or voice notes. It keeps saving even when you lose signal, and sends everything once you are back online.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.weather' => [
            'title' => 'Weather',
            'blurb' => 'See the forecast for your farm on each day of your board. If a spray falls on a rainy day, you will notice it before anyone goes out to the field.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.growth' => [
            'title' => 'Growth stages',
            'blurb' => 'See what stage each lot\'s crop is in by its day count, and what it needs at that stage, so there is no table to memorize. Anee can also check your photos and tell you if the crop is ahead or behind.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.gallery' => [
            'title' => 'Gallery',
            'blurb' => 'All the photos and videos from this season, sorted by where they were taken, so you never have to dig through the board to find them.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.maps' => [
            'title' => 'Maps',
            'blurb' => 'Draw the outline of your field, drop a pin, or mark where the water pump is. Attach a map to a lot and it opens together with that lot.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.draw' => [
            'title' => 'Draw',
            'blurb' => 'Some things are easier to draw than to explain, like how the sprayer is set up or which corner got flooded. Make a quick sketch and attach it to a note.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.ai' => [
            'title' => 'Chat Anee',
            'blurb' => 'Ask Anee anything about your crops and this season. Send her photos to check, attach a report, or turn on your season plan when you want her to read it.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'module.reports' => [
            'title' => 'Reports',
            'blurb' => 'See your costs, your profit and Anee\'s reading of the season. Every number comes from what you recorded on the board, so all the reports agree with each other.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the reports, each a page of its own (offered by route) ---- */

        'report.labor' => [
            'title' => 'Labor Report',
            'blurb' => 'See every workday this season and how much each worker was paid. Choose a date range if you only want to check a certain period.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.expenses' => [
            'title' => 'Expenses Report',
            'blurb' => 'All your spending this season, taken from the board and grouped by what it was for. Filter what you need and print it for your records.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.profit' => [
            'title' => 'Profit Report',
            'blurb' => 'See what your harvest earned against everything the season cost, labor included, so you know how much you really made.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.anee-season' => [
            'title' => 'Anee Season Report',
            'blurb' => 'When the season is over, Anee reads everything you recorded and tells you what went well, what went wrong, and what to change next time.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.sofar' => [
            'title' => 'Analyze So Far',
            'blurb' => 'A checkup in the middle of the season. Anee looks at where your crop is today, the risks coming up, and what you should do next.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.protocol' => [
            'title' => 'View as Protocol',
            'blurb' => 'Your whole season written out as a protocol, stage by stage and day by day. Easy to read, easy to share, and easy to print.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'report.compare' => [
            'title' => 'Compare Reports',
            'blurb' => 'Choose two saved reports of the same kind and see them side by side. Anee can also read both and explain what changed and why it matters.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the analyses ---- */

        'analysis.when-to-plant' => [
            'title' => 'When to Plant',
            'blurb' => 'Tell Anee your crop and where your farm is. She checks the climate and the weather history of your area and gives you the best time to plant.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'analysis.what-to-plant' => [
            'title' => 'What to Plant',
            'blurb' => 'Describe your land, your water and your plans, and Anee suggests the crops that will do well there, with her reasons for each one.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'analysis.variety' => [
            'title' => 'Variety Research',
            'blurb' => 'Anee looks up the varieties of your crop, compares them, and tells you which one fits your farm best.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'analysis.crop-protocol' => [
            'title' => 'Crop Protocol Analysis',
            'blurb' => 'A full guide for growing your crop from planting to harvest: what to apply, when to apply it, and what to watch out for at every stage.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the tools that reach across every season ---- */

        'tool.notes' => [
            'title' => 'Global Notes',
            'blurb' => 'All your notes from every season, together in one list. Search or filter them, or write a new note without opening a schedule first.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.gallery' => [
            'title' => 'Global Gallery',
            'blurb' => 'Every photo and video you have taken across all your seasons, plus the albums you made.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.contacts' => [
            'title' => 'Contact List',
            'blurb' => 'Save the numbers of your workers, suppliers, buyers and helpers. Tap a name to call or message them right away.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.protocols' => [
            'title' => 'Protocol Builder',
            'blurb' => 'Write down how you grow a crop, task by task, following a day count. Make it once and use it again every season.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.protocol-editor' => [
            'title' => 'Building a protocol',
            'blurb' => 'Add your tasks in order, set the day for each one, and drag them to change the order. When it is ready, use it on a season.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.tags' => [
            'title' => 'Tags',
            'blurb' => 'Every tag you have used in all your seasons and tools. Tap a tag to see everything under it, or rename it and it changes everywhere.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'tool.compare' => [
            'title' => 'Compare Reports',
            'blurb' => 'Choose two saved reports of the same kind, like this season and the last one, and see them side by side. Anee can also tell you what changed.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        /* ---- the quick tools: offered when their sheet opens, not by route ---- */

        'quick.capture' => [
            'title' => 'Quick Capture',
            'blurb' => 'Take a photo, add a short note, and save it to a season\'s notes or gallery. You can also ask Anee what she sees in it.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'quick.record' => [
            'title' => 'Quick Record',
            'blurb' => 'Record a short video in the field, give it a name, and save it to a season\'s gallery or notes.',
            'video' => null, 'poster' => null, 'portrait' => null, 'portrait_poster' => null,
        ],

        'quick.voice' => [
            'title' => 'Quick Voice',
            'blurb' => 'Hands busy? Just say it. Record a voice note and save it to a season in two taps.',
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
