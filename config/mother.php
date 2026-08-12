<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The mother app
    |--------------------------------------------------------------------------
    |
    | Dragonscale Axis: the admin app AniSystem shares a database with, and
    | now its media store too. Kept in the environment rather than hard-coded
    | so the host can move without a deploy of this app's code.
    |
    */

    'url' => rtrim((string) env('MOTHER_APP_URL', ''), '/'),

    /*
     * The shared secret the media API expects. Both apps must carry the same
     * value; with either side blank, uploads simply stay on the local disk.
     */
    'media_token' => env('ANISYSTEM_MEDIA_TOKEN', ''),

];
