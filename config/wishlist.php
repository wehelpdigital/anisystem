<?php

/*
 * The wishlist page (/wishlist, App\Support\Wishlist). What it promises is said here, once, in both languages:
 * change a line and the page says the new thing. No name of the app anywhere on it, on purpose.
 */
return [
    // How many slots the admin panel plans for. Never shown on the page ("limited slots only").
    'limit' => 50,

    // What joining gets them. Each line is one tick on the first screen. (Never a number of places: the owner,
    // 2026-11-26, "don't mention the 50 ... limited slots only".)
    'offer' => [
        'tl' => [
            'Mauna kang makasubok, libre',
            'Para sa mismong lote at tanim mo',
            'May tutulong sa pag-set up ng bukid mo',
        ],
        'en' => [
            'Be first to try it, free',
            'Made for your own lot and crop',
            'Someone helps you set up your farm',
        ],
    ],
];
