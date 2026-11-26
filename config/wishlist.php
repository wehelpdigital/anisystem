<?php

/*
 * The First 50 wishlist page (/unang-50, App\Support\Wishlist). What it promises is said here, once, in both
 * languages: change a line and the page says the new thing. No name of the app anywhere on it, on purpose.
 */
return [
    // How many places the page counts down from.
    'limit' => 50,

    // What the first ones get. Each line is one tick on the first screen.
    'offer' => [
        'tl' => [
            'Mauuna kang makagamit bago ang lahat',
            'Libre ang buong unang season mo',
            'May tao kaming tutulong sa pag-set up ng bukid mo',
        ],
        'en' => [
            'You get in before everyone else',
            'Your whole first season is free',
            'A person from our team helps you set up your farm',
        ],
    ],
];
