<?php

/*
 * Where uploads actually live.
 *
 * A container's own filesystem is thrown away on every deploy, so anything a
 * user saved between deploys — a map picture, a drawing, a photo on a note —
 * went with it, leaving rows in the database pointing at files that no longer
 * exist. Mount a persistent volume and point this at it and uploads outlive
 * the container; with nothing mounted it behaves exactly as before.
 *
 * Railway names the mount itself, so a volume attached there needs no extra
 * configuration; APP_STORAGE_ROOT covers every other host.
 */
$publicRoot = env('APP_STORAGE_ROOT')
    ?: (env('RAILWAY_VOLUME_MOUNT_PATH')
        ? rtrim((string) env('RAILWAY_VOLUME_MOUNT_PATH'), '/').'/public'
        : storage_path('app/public'));

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            // The framework's serve feature claims GET /storage/{path} at boot,
            // before web.php loads, and 403s anything unsigned — which starved
            // the storage.fallback route that serves runtime uploads on
            // Railway. Nothing here issues temporaryUrl() links, so the signed
            // serving it exists for is unused anyway.
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => $publicRoot,
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    /*
     * The symlink has to follow the disk, or `storage:link` would point the
     * web root at an empty directory while the files sat on the volume.
     */
    'links' => [
        public_path('storage') => $publicRoot,
    ],

];
