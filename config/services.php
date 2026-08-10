<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Community GIF search. The key stays server-side; the app proxies searches.
    'giphy' => [
        'key' => env('GIPHY_API_KEY'),
    ],

    // ffmpeg / ffprobe binaries for compressing uploaded/recorded videos.
    'ffmpeg' => [
        'bin' => env('FFMPEG_BIN', 'ffmpeg'),
        'ffprobe' => env('FFPROBE_BIN', 'ffprobe'),
    ],

    // LiveKit — realtime audio/video calls in the Collab Room. `url` is the
    // wss:// server; `key`/`secret` sign short-lived join tokens server-side.
    // Google Maps JS API — powers the Collab Room map tab. Needs a key with
    // the Maps JavaScript API enabled (billing on, generous free tier).
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_KEY'),
    ],

    'livekit' => [
        'url' => env('LIVEKIT_URL'),
        'key' => env('LIVEKIT_API_KEY'),
        'secret' => env('LIVEKIT_API_SECRET'),
    ],

];
