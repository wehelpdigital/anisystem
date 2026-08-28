<?php

/**
 * anee.io subscription tiers. Prices here are for DISPLAY only — the real,
 * chargeable price lives in the mother app's ecom products / variants
 * (/ecom-products-variants) and flows in via the Plan/Subscription records.
 *
 * `match` lists the keywords we look for in a subscription's planKey/planName
 * to decide which tier a member is on. Any active paid plan that matches none
 * of these is treated as full-featured ('boss') so existing subscribers are
 * never downgraded by accident.
 */
return [
    'basic' => [
        'name'         => 'Basic',
        'price'        => 100,
        'period'       => 'year',
        'maxSchedules' => 2,
        'ai'           => false,   // cannot use AI or buy AI credits
        'workers'      => false,   // no worker logins / notifications
        'match'        => ['basic'],
        'tagline'      => 'Plan your season, on your own.',
        'features'     => [
            'Create up to 2 cropping schedules',
            'Full community access',
            'Reports & post-harvest revenue',
        ],
        'excludes'     => [
            'No worker logins or email notifications',
            'AI Technician locked (and AI credits locked)',
        ],
    ],
    'boss' => [
        'name'         => 'Boss',
        'price'        => 300,
        'period'       => 'year',
        'maxSchedules' => null,    // unlimited
        'ai'           => true,
        'workers'      => true,
        'match'        => ['boss'],
        'tagline'      => 'Run the whole farm, with your crew.',
        'features'     => [
            'Unlimited cropping schedules',
            'Worker logins (view or edit) + community access for workers',
            'Worker email notifications for schedules',
            'AI Technician (buy AI credits)',
            'Everything in Basic',
        ],
        'excludes'     => [],
    ],
    'lifetime' => [
        'name'         => 'Lifetime',
        'price'        => 5000,
        'period'       => 'lifetime',
        'maxSchedules' => null,
        'ai'           => true,
        'workers'      => true,
        'match'        => ['lifetime', 'life time'],
        'tagline'      => 'Every feature, once, forever.',
        'features'     => [
            'Lifetime access to every feature',
            'Worker logins + email notifications',
            'AI Technician (buy AI credits)',
        ],
        'excludes'     => [
            'AI credits are still purchased separately',
        ],
    ],
];
