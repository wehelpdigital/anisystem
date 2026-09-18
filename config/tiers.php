<?php

/**
 * anee.io subscription tiers — the v5 ladder (2026-09-08).
 *
 * Prices here are for DISPLAY; the chargeable price lives in the mother
 * app's ecom products and flows in via Plan/Subscription records. `match`
 * lists the keywords looked for in a subscription's planKey/planName to
 * decide the tier. Legacy plans map UP (basic → solo, boss/lifetime →
 * owner) so nobody who ever paid is downgraded by the new ladder. A user
 * with no active subscription is 'libre' — the free floor.
 *
 * Two AI switches, read by different doors: `ai` is Anee herself -- the
 * chat, the credit shop, Realign by Anee, every run that spends a credit --
 * and `aiAnalyses` is the four analysis wizards (What to Plant, When to
 * Plant, Variety Research, Crop Protocol). Libre has neither; Libre + Anee
 * (2026-09-18) is Libre with both, for ₱70 a month: the same farm limits,
 * the same ads, and the whole of Anee. Solo and Owner have always had both.
 *
 * Every limit the gates read lives HERE and only here. null = unlimited.
 * Schedule-scoped limits (lots, workers, video on a farm…) are judged by
 * the SCHEDULE OWNER's tier — a worker inside a farm rides the owner's
 * plan. Personal limits (storage, community, discussions) are the user's
 * own. See App\Support\Tier for the single reader.
 */
return [
    'libre' => [
        'name'    => 'Libre',
        'price'   => 0,
        'period'  => 'forever',
        'match'   => [],   // never matched from a paid plan — it is the floor
        'tagline' => 'Prove the habit. Your farm diary, free.',

        'schedulesActive'   => 1,
        'schedulesArchived' => 1,
        'lotsPerSchedule'   => 1,
        'mapsTotal'         => 3,
        'workersPerSchedule' => 0,      // the Workers module is the Solo Farmer plan's (2026-09-19)
        'workers'           => false,
        'inventory'         => false,   // and so is the shed
        'workerLogins'      => false,
        'videoRecording'    => false,
        'voiceFarm'         => true,    // farm-side voice notes: centavos, habit-building
        'weatherDays'       => 2,       // today + tomorrow
        'weatherNow'        => false,   // the greeting reads the day's forecast, not the sky right now
        'docUploads'        => false,
        'collab'            => false,
        'reportsAll'        => false,   // labor report only
        'offline'           => false,   // the field without a signal is a paid convenience
        'auditLogs'         => false,   // the Logs diary is the Owner tier's story
        'discussionCreate'  => false,
        'discussionJoin'    => 1,
        'discussionPrivateJoin' => false,   // open rooms only — locked doors are a paid privilege
        'communityVideo'    => false,
        'communityVoice'    => false,
        'storageGb'         => 1,
        'creditsOnGrant'    => 20,      // the starter, kept for the day they add Anee
        'creditsMonthly'    => 0,
        'ai'                => false,   // Anee is the ₱70 step up: Libre + Anee
        'aiAnalyses'        => false,

        'features' => [
            '1 active cropping schedule (+1 archived)',
            'Activities, notes, tags, growth stages',
            'Weather today and tomorrow',
            'Drawings, photo capture, up to 3 maps',
            'Observations, labor report',
            'Community access (photos)',
            '1 GB storage',
            'Supported by a few ads',
        ],
        'excludes' => [
            'Anee: AI chat, analyses and credits (Libre + Anee)',
            'Workers and inventory (Solo Farmer)',
            'Video recording, worker logins, collab room',
            'Full weather, all reports, document uploads, offline mode',
        ],
    ],

    /* Libre, plus Anee. Every farm limit above, word for word -- the one
       season, the one lot, the two days of weather, the ads -- and the whole
       of Anee: the chat, the four analyses, Realign, and the credit shop. */
    'libreAnee' => [
        'name'    => 'Libre + Anee',
        'price'   => 70,
        'period'  => 'month',
        'priceYear' => 700,
        'match'   => ['libre + anee', 'libre-anee', 'libre+anee', 'libre anee', 'libreanee'],
        'tagline' => 'Your free diary, with Anee beside you.',

        'schedulesActive'   => 1,
        'schedulesArchived' => 1,
        'lotsPerSchedule'   => 1,
        'mapsTotal'         => 3,
        'workersPerSchedule' => 0,
        'workers'           => false,
        'inventory'         => false,
        'workerLogins'      => false,
        'videoRecording'    => false,
        'voiceFarm'         => true,
        'weatherDays'       => 2,
        'weatherNow'        => false,
        'docUploads'        => false,
        'collab'            => false,
        'reportsAll'        => false,
        'offline'           => false,
        'auditLogs'         => false,
        'discussionCreate'  => false,
        'discussionJoin'    => 1,
        'discussionPrivateJoin' => false,
        'communityVideo'    => false,
        'communityVoice'    => false,
        'storageGb'         => 1,
        'creditsOnGrant'    => 0,       // the 20 starter credits came with the account
        'creditsMonthly'    => 0,
        'ai'                => true,
        'aiAnalyses'        => true,

        'features' => [
            'Everything in Libre',
            'Anee AI chat: ask anything, show a photo',
            'All AI analyses: What to Plant, When to Plant, Variety Research, Crop Protocol',
            'Realign by Anee on your growth stages',
            'Buy AI credit packs whenever you need more',
            'Supported by a few ads',
        ],
        'excludes' => [
            'Workers and inventory (Solo Farmer)',
            'Video recording, worker logins, collab room',
            'Full weather, all reports, document uploads, offline mode',
        ],
    ],

    'solo' => [
        'name'    => 'Solo Farmer',
        'price'   => 200,
        'period'  => 'month',
        'priceYear' => 1800,
        'match'   => ['solo', 'basic'],
        'tagline' => 'Run your whole season, on your own.',

        'schedulesActive'   => 3,
        'schedulesArchived' => 5,
        'lotsPerSchedule'   => 5,
        'mapsTotal'         => null,
        'workersPerSchedule' => null,
        'workers'           => true,
        'inventory'         => true,
        'workerLogins'      => false,   // logins are the Owner tier's story
        'videoRecording'    => true,
        'voiceFarm'         => true,
        'weatherDays'       => null,
        'weatherNow'        => true,    // the greeting reads the sky as it is right now
        'docUploads'        => true,
        'collab'            => false,   // the room is the Owner tier's story
        'reportsAll'        => true,
        'offline'           => true,
        'auditLogs'         => false,
        'discussionCreate'  => false,
        'discussionJoin'    => null,
        'discussionPrivateJoin' => true,
        'communityVideo'    => true,
        'communityVoice'    => true,
        'storageGb'         => 6,
        'creditsOnGrant'    => 30,
        'creditsMonthly'    => 30,
        'ai'                => true,
        'aiAnalyses'        => true,

        'features' => [
            '3 active cropping schedules (+5 archived)',
            'Up to 5 lots per schedule',
            'Full weather, maps, video & voice recording',
            'All reports',
            'Offline mode for the field',
            'Workers (no logins) and the inventory',
            'Full community access',
            '30 AI credits per renewal + buy packs',
            '6 GB storage (expandable)',
            'Ad-free',
        ],
        'excludes' => [
            'Worker logins and the collab room (Farm Owner)',
            'Creating community discussions',
        ],
    ],

    'owner' => [
        'name'    => 'Farm Owner',
        'price'   => 600,
        'period'  => 'month',
        'priceYear' => 6500,
        'match'   => ['owner', 'farm owner', 'boss', 'lifetime', 'life time'],
        'tagline' => 'Run the whole farm, with your crew.',

        'schedulesActive'   => null,
        'schedulesArchived' => null,
        'lotsPerSchedule'   => null,
        'mapsTotal'         => null,
        'workersPerSchedule' => null,
        'workers'           => true,
        'inventory'         => true,
        'workerLogins'      => true,
        'videoRecording'    => true,
        'voiceFarm'         => true,
        'weatherDays'       => null,
        'weatherNow'        => true,    // the greeting reads the sky as it is right now
        'docUploads'        => true,
        'collab'            => true,
        'reportsAll'        => true,
        'offline'           => true,
        'auditLogs'         => true,
        'discussionCreate'  => true,
        'discussionJoin'    => null,
        'discussionPrivateJoin' => true,
        'communityVideo'    => true,
        'communityVoice'    => true,
        'storageGb'         => 15,
        'creditsOnGrant'    => 100,
        'creditsMonthly'    => 100,
        'ai'                => true,
        'aiAnalyses'        => true,

        'features' => [
            'Everything in Solo Farmer, unlimited',
            'Worker logins and access levels',
            'Collab room: team chat, whiteboard, calls',
            'Create community discussions',
            '100 AI credits per renewal + buy packs',
            '15 GB storage (expandable)',
            'Ad-free',
        ],
        'excludes' => [],
    ],

    /* Internal only — derived from isSuperAdmin(), never sold, never shown
       on the pricing page. Everything unlimited, admin panel included. */
    'admin' => [
        'name'    => 'Admin',
        'price'   => null,
        'period'  => 'internal',
        'match'   => [],
        'tagline' => 'The house account.',

        'schedulesActive'   => null,
        'schedulesArchived' => null,
        'lotsPerSchedule'   => null,
        'mapsTotal'         => null,
        'workersPerSchedule' => null,
        'workers'           => true,
        'inventory'         => true,
        'workerLogins'      => true,
        'videoRecording'    => true,
        'voiceFarm'         => true,
        'weatherDays'       => null,
        'weatherNow'        => true,    // the greeting reads the sky as it is right now
        'docUploads'        => true,
        'collab'            => true,
        'reportsAll'        => true,
        'offline'           => true,
        'auditLogs'         => true,
        'discussionCreate'  => true,
        'discussionJoin'    => null,
        'discussionPrivateJoin' => true,
        'communityVideo'    => true,
        'communityVoice'    => true,
        'storageGb'         => null,
        'creditsOnGrant'    => 0,
        'creditsMonthly'    => 0,
        'ai'                => true,
        'aiAnalyses'        => true,

        'features' => [],
        'excludes' => [],
    ],
];
