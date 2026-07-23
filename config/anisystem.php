<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mother system (btc-check) integration
    |--------------------------------------------------------------------------
    | AniSystem shares the btc-check MySQL database. Orders created at checkout
    | are inserted straight into the ecom_* tables so the super admin can
    | verify GCash payments in /ecom-orders. Payment screenshots must land in
    | btc-check's public folder so its admin UI can render them.
    */

    'btc_check_url' => env('BTC_CHECK_URL', 'http://btc-check.test'),
    'btc_check_public_path' => env('BTC_CHECK_PUBLIC_PATH', 'C:\\xampp\\htdocs\\btc-check\\public'),

    // ecom_product_stores row that marks orders as AniSystem orders
    'store_id' => (int) env('ANISYSTEM_STORE_ID', 5),
    'store_name' => env('ANISYSTEM_STORE_NAME', 'AniSystem'),

    // ecom_orders.usersId is NOT NULL; 1 = the "System" admin user (same as anisenso-course)
    'order_users_id' => (int) env('ANISYSTEM_ORDER_USERS_ID', 1),

    // Order number prefix so AniSystem orders are identifiable in /ecom-orders
    'order_prefix' => 'ANI',

    // Mail settings / email templates group key inside as_mail_smtp_settings / as_email_templates
    'mail_group' => 'AniSystem',

    /*
    | Shared secret for the AI provider key. btc-check writes the key encrypted
    | with this secret and AniSystem reads it back, so it must be the identical
    | string in both apps' .env files. Without it the AI Technician stays off.
    */
    'ai_key_secret' => env('ANISYSTEM_AI_KEY_SECRET'),

    // Days before expiry to send the "expiring soon" email
    'expiry_notice_days' => 7,

    'timezone' => 'Asia/Manila',
];
