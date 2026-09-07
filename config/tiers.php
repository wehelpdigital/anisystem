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
        'workersPerSchedule' => 3,
        'workerLogins'      => false,
        'videoRecording'    => false,
        'voiceFarm'         => true,    // farm-side voice notes: centavos, habit-building
        'weatherDays'       => 2,       // today + tomorrow
        'docUploads'        => false,
        'collab'            => false,
        'reportsAll'        => false,   // labor report only
        'discussionCreate'  => false,
        'discussionJoin'    => 1,
        'discussionPrivateJoin' => false,   // open rooms only — locked doors are a paid privilege
        'communityVideo'    => false,
        'communityVoice'    => false,
        'storageGb'         => 1,
        'creditsOnGrant'    => 20,      // one-time starter (≈3 text inquiries)
        'creditsMonthly'    => 0,
        'ai'                => true,    // credits gate usage, not the tier

        'features' => [
            '1 active cropping schedule (+1 archived)',
            'Activities, notes, tags, growth stages',
            'Weather today and tomorrow',
            'Drawings, photo capture, up to 3 maps',
            'Inventory, observations, labor report',
            '3 workers (planning only)',
            'Community access (photos)',
            '20 starter AI credits',
            '1 GB storage',
        ],
        'excludes' => [
            'Video recording, worker logins, collab room',
            'Full weather, all reports, document uploads',
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
        'workerLogins'      => false,   // logins are the Owner tier's story
        'videoRecording'    => true,
        'voiceFarm'         => true,
        'weatherDays'       => null,
        'docUploads'        => true,
        'collab'            => false,   // the room is the Owner tier's story
        'reportsAll'        => true,
        'discussionCreate'  => false,
        'discussionJoin'    => null,
        'discussionPrivateJoin' => true,
        'communityVideo'    => true,
        'communityVoice'    => true,
        'storageGb'         => 6,
        'creditsOnGrant'    => 30,
        'creditsMonthly'    => 30,
        'ai'                => true,

        'features' => [
            '3 active cropping schedules (+5 archived)',
            'Up to 5 lots per schedule',
            'Full weather, maps, video & voice recording',
            'All reports',
            'Add workers (no logins)',
            'Full community access',
            '30 AI credits per renewal + buy packs',
            '6 GB storage (expandable)',
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
        'workerLogins'      => true,
        'videoRecording'    => true,
        'voiceFarm'         => true,
        'weatherDays'       => null,
        'docUploads'        => true,
        'collab'            => true,
        'reportsAll'        => true,
        'discussionCreate'  => true,
        'discussionJoin'    => null,
        'discussionPrivateJoin' => true,
        'communityVideo'    => true,
        'communityVoice'    => true,
        'storageGb'         => 15,
        'creditsOnGrant'    => 100,
        'creditsMonthly'    => 100,
        'ai'                => true,

        'features' => [
            'Everything in Solo Farmer, unlimited',
            'Worker logins and access levels',
            'Collab room: team chat, whiteboard, calls',
            'Create community discussions',
            '100 AI credits per renewal + buy packs',
            '15 GB storage (expandable)',
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
        'workerLogins'      => true,
        'videoRecording'    => true,
        'voiceFarm'         => true,
        'weatherDays'       => null,
        'docUploads'        => true,
        'collab'            => true,
        'reportsAll'        => true,
        'discussionCreate'  => true,
        'discussionJoin'    => null,
        'discussionPrivateJoin' => true,
        'communityVideo'    => true,
        'communityVoice'    => true,
        'storageGb'         => null,
        'creditsOnGrant'    => 0,
        'creditsMonthly'    => 0,
        'ai'                => true,

        'features' => [],
        'excludes' => [],
    ],
];
