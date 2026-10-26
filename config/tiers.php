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
 * The comparison table (App\Support\PlanCompare, on /pricing/compare and the
 * app's My Subscription) and the plan each How It Works tool names
 * (HowItWorks::planOf) are read off these keys. `features` and `excludes`
 * are the plan cards' lines and `sheet` the upgrade sheet's (app.js reads
 * it through Region::js()): words, so keep them true to the keys when a
 * key changes (2026-10-18: offline is on every plan, the field helpers,
 * Satellite Weather, NPK Plus and Maps come with Libre + Anee).
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
        'maps'              => false,   // the Maps module: Libre + Anee and up (2026-10-12)
        'workersPerSchedule' => 0,      // the Workers module is the Solo Farmer plan's (2026-09-19)
        'workers'           => false,
        'fieldHelpers'      => false,   // the field helpers (App\Support\FieldPack): locked on Libre, open from Libre + Anee up; no add-on since 2026-10-18
        'skyWeather'        => false,   // Satellite Weather and the NPK Plus calculator: from Libre + Anee up (2026-10-17, the owner)
        'npkPlus'           => false,
        'bentaBilad'        => true,    // the Benta o Bilad Calculator: every plan (2026-10-19)
        'hatian'            => true,    // the Hatian Calculator: every plan (2026-10-26)
        'marketPrices'      => true,    // the Market Price Forecast: every plan, no credits (2026-10-27)
        'seedCount'         => true,    // the Seed Count and Distance Calculator: every plan (2026-10-19)
        'howToPlant'        => true,    // How to Plant, the traditional way crop by crop: every plan (2026-10-19)
        'inventory'         => false,   // and so is the shed
        'workerLogins'      => false,
        'videoRecording'    => false,
        'voiceFarm'         => true,    // farm-side voice notes: centavos, habit-building
        'weatherDays'       => 2,       // today + tomorrow
        'weatherNow'        => false,   // the greeting reads the day's forecast, not the sky right now
        'docUploads'        => false,
        'collab'            => false,
        'reportsAll'        => false,   // labor report only
        'offline'           => true,    // always on, every plan (2026-10-12)
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
            '1 active season (+1 archived)',
            'Activities, notes, tags, growth stages',
            'Global Lots, contacts, drawings and photos',
            'Protocol Builder, and the LCC Checker with your own chart',
            'Weather today and tomorrow',
            'Observations, labor report',
            'Community, the farm news and offline mode',
            '1 GB storage',
            'Shows a few ads',
        ],
        'excludes' => [
            'Anee: AI chat, analyses and credits (Libre + Anee)',
            'The field helpers and the Compatibility Checker (Libre + Anee)',
            'Satellite Weather, NPK Plus and Maps (Libre + Anee)',
            'Workers and inventory (Solo Farmer)',
            'Video recording, worker logins, Collab Room',
            'Full weather, all reports, document uploads',
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
        'maps'              => true,
        'workersPerSchedule' => 0,
        'workers'           => false,
        'fieldHelpers'      => true,
        'skyWeather'        => true,
        'npkPlus'           => true,
        'bentaBilad'        => true,    // the Benta o Bilad Calculator: every plan (2026-10-19)
        'hatian'            => true,    // the Hatian Calculator: every plan (2026-10-26)
        'marketPrices'      => true,    // the Market Price Forecast: every plan, no credits (2026-10-27)
        'seedCount'         => true,    // the Seed Count and Distance Calculator: every plan (2026-10-19)
        'howToPlant'        => true,    // How to Plant, the traditional way crop by crop: every plan (2026-10-19)
        'inventory'         => false,
        'workerLogins'      => false,
        'videoRecording'    => false,
        'voiceFarm'         => true,
        'weatherDays'       => 2,
        'weatherNow'        => false,
        'docUploads'        => false,
        'collab'            => false,
        'reportsAll'        => false,
        'offline'           => true,
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
            'All AI analyses: What to Plant, When to Plant, Variety Ranking, Crop Protocol, Satellite Analysis',
            'Land Prep Planner, Anee Auto and Realign by Anee',
            'Field helpers: the pest, disease, weed, nutrient and variety finders and the Compatibility Checker',
            'Satellite Weather and the NPK Plus calculator',
            'Maps: draw and measure up to 3 fields',
            'Buy AI credit packs whenever you need more',
            'Shows a few ads',
        ],
        'sheet' => [
            'Your Libre plan exactly as it is',
            'Anee AI chat: ask anything, show a photo',
            'Every AI analysis, the Land Prep Planner and Anee Auto',
            'The field helpers, Satellite Weather, NPK Plus and Maps',
            'Buy AI credit packs whenever you need more',
        ],
        'excludes' => [
            'Workers and inventory (Solo Farmer)',
            'Video recording, worker logins, Collab Room',
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
        'maps'              => true,
        'workersPerSchedule' => null,
        'workers'           => true,
        'fieldHelpers'      => true,
        'skyWeather'        => true,
        'npkPlus'           => true,
        'bentaBilad'        => true,    // the Benta o Bilad Calculator: every plan (2026-10-19)
        'hatian'            => true,    // the Hatian Calculator: every plan (2026-10-26)
        'marketPrices'      => true,    // the Market Price Forecast: every plan, no credits (2026-10-27)
        'seedCount'         => true,    // the Seed Count and Distance Calculator: every plan (2026-10-19)
        'howToPlant'        => true,    // How to Plant, the traditional way crop by crop: every plan (2026-10-19)
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
            'Everything in Libre + Anee',
            '3 active seasons (+5 archived)',
            'Up to 5 lots per season',
            'Full weather, unlimited maps, video and voice recording',
            'All reports, with Compare Reports and the Anee Season Report',
            'Workers (without logins), Global Workers and inventory',
            'Full community access',
            '30 AI credits each renewal, and you can buy more',
            '6 GB storage (you can add more)',
            'No ads',
        ],
        'sheet' => [
            'Everything in Libre + Anee',
            '3 active seasons, 5 lots each',
            'Workers (without logins) and inventory',
            'Full weather, all reports, unlimited maps',
            'Video and voice, documents, no ads',
            '30 AI credits every renewal, 6 GB storage',
        ],
        'excludes' => [
            'Worker logins and the Collab Room (Farm Owner)',
            'Creating community discussions',
        ],
    ],

    'owner' => [
        'name'    => 'Farm Owner',
        'price'   => 600,
        'period'  => 'month',
        'priceYear' => 6480,   // a year saves at least 10% (2026-10-19, the owner): 12 x 600 less 10%
        'match'   => ['owner', 'farm owner', 'boss', 'lifetime', 'life time'],
        'tagline' => 'Run the whole farm, with your crew.',

        'schedulesActive'   => null,
        'schedulesArchived' => null,
        'lotsPerSchedule'   => null,
        'mapsTotal'         => null,
        'maps'              => true,
        'workersPerSchedule' => null,
        'workers'           => true,
        'fieldHelpers'      => true,
        'skyWeather'        => true,
        'npkPlus'           => true,
        'bentaBilad'        => true,    // the Benta o Bilad Calculator: every plan (2026-10-19)
        'hatian'            => true,    // the Hatian Calculator: every plan (2026-10-26)
        'marketPrices'      => true,    // the Market Price Forecast: every plan, no credits (2026-10-27)
        'seedCount'         => true,    // the Seed Count and Distance Calculator: every plan (2026-10-19)
        'howToPlant'        => true,    // How to Plant, the traditional way crop by crop: every plan (2026-10-19)
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
            'Collab Room: team chat, whiteboard, calls',
            'The Logs diary: every change and who made it',
            'Create community discussions',
            '100 AI credits each renewal, and you can buy more',
            '15 GB storage (you can add more)',
            'No ads',
        ],
        'sheet' => [
            'Everything in Solo Farmer, unlimited',
            'Worker logins and access levels',
            'Collab Room: team chat, whiteboard, calls',
            '100 AI credits every renewal, 15 GB storage',
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
        'maps'              => true,
        'workersPerSchedule' => null,
        'workers'           => true,
        'fieldHelpers'      => true,
        'skyWeather'        => true,
        'npkPlus'           => true,
        'bentaBilad'        => true,    // the Benta o Bilad Calculator: every plan (2026-10-19)
        'hatian'            => true,    // the Hatian Calculator: every plan (2026-10-26)
        'marketPrices'      => true,    // the Market Price Forecast: every plan, no credits (2026-10-27)
        'seedCount'         => true,    // the Seed Count and Distance Calculator: every plan (2026-10-19)
        'howToPlant'        => true,    // How to Plant, the traditional way crop by crop: every plan (2026-10-19)
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
