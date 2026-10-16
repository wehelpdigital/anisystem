<?php

/*
 * The product book (App\Support\ProductLibrary, 2026-10-18): the connection
 * its two tables (as_products, as_user_products) are read through. Empty is
 * the app's own database, as on anee.io. A rehearsal on a local database
 * names 'products_local', and the 'local' block below describes it.
 */
return [
    'connection' => env('PRODUCTS_DB_CONNECTION'),

    'local' => [
        'host' => env('PRODUCTS_DB_HOST', '127.0.0.1'),
        'port' => env('PRODUCTS_DB_PORT', '3307'),
        'database' => env('PRODUCTS_DB_DATABASE', 'anee_rehearsal'),
        'username' => env('PRODUCTS_DB_USERNAME', 'root'),
        'password' => env('PRODUCTS_DB_PASSWORD', ''),
    ],
];
