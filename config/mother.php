<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The mother app
    |--------------------------------------------------------------------------
    |
    | Dragonscale Axis: the admin app anee.io shares a database with, and
    | now its media store too. Kept in the environment rather than hard-coded
    | so the host can move without a deploy of this app's code.
    |
    */

    'url' => rtrim((string) env('MOTHER_APP_URL', ''), '/'),

    /*
     * Where the mother's files are READ from, when that is not the mother.
     *
     * On a host with a disk, the mother serves its own files at
     * <url>/storage/<path>. On Laravel Cloud they live in a bucket, and while
     * <url>/storage/<path> still works there -- the mother answers with a
     * redirect to the object -- every picture would cost the browser two
     * round trips. Naming the bucket's public address here saves the first.
     * Blank means "ask the mother", which is always right, only slower.
     */
    'media_url' => rtrim((string) env('MOTHER_MEDIA_URL', ''), '/'),

    /*
     * The shared secret the media API expects. Both apps must carry the same
     * value; with either side blank, uploads simply stay on the local disk.
     */
    'media_token' => env('ANISYSTEM_MEDIA_TOKEN', ''),

];
