<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * GET /storage/{path}: a file on the public disk, from wherever that disk
 * lives now.
 *
 * On a host with a volume the disk is a directory and the file is streamed
 * from it (the framework's own /storage serving is switched off in
 * config/filesystems.php; see the note there). On a bucket (MEDIA_DISK=s3)
 * the browser is sent on to the object -- a 301 to its public address, or a
 * signed two-hour link while the bucket has no public address -- so any
 * /storage/<path> ever handed out keeps working.
 */
class StorageFallbackController extends Controller
{
    public function __invoke(string $path)
    {
        if (config('filesystems.disks.public.driver') === 's3') {
            $path = ltrim(str_replace(chr(92), '/', $path), '/');
            if ($path === '' || str_contains($path, '..')) {
                abort(404);
            }
            $disk = Storage::disk('public');
            if (config('filesystems.disks.public.url')) {
                return redirect()->away($disk->url($path), 301, ['Cache-Control' => 'public, max-age=31536000, immutable']);
            }
            if (! $disk->exists($path)) {
                abort(404);
            }

            return redirect()->away($disk->temporaryUrl($path, now()->addHours(2)), 302, ['Cache-Control' => 'private, max-age=3600']);
        }

        $base = realpath(config('filesystems.disks.public.root', storage_path('app/public')));

        // realpath resolves any ../ tricks; anything that escapes the public
        // disk -- or points at nothing -- is a plain 404, same as a bad URL.
        $full = $base ? realpath($base . DIRECTORY_SEPARATOR . $path) : false;
        if ($full === false || ! str_starts_with($full, $base . DIRECTORY_SEPARATOR) || ! is_file($full)) {
            abort(404);
        }

        // Uploads get random names, so a URL's content never changes: cache hard.
        return response()->file($full, ['Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
