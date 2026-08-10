<?php

namespace App\Http\Controllers;

/**
 * Serves runtime uploads where public/storage cannot.
 *
 * On the Railway deployment public/storage is a real directory committed to
 * git (Windows checkouts cannot commit the symlink, and storage:link refuses
 * to replace a directory), so it only ever contains the files that were
 * present at deploy time. Anything uploaded while the app runs — AI photos,
 * avatars, note images — lands in storage/app/public and simply 404s.
 *
 * The web server still serves everything that exists under public/storage
 * without touching PHP; only the misses fall through to this route, which
 * streams the file from the disk the uploads actually live on. Locally the
 * symlink handles everything and this route never fires.
 */
class StorageFallbackController extends Controller
{
    public function __invoke(string $path)
    {
        $base = realpath(storage_path('app/public'));

        // realpath resolves any ../ tricks; anything that escapes the public
        // disk — or points at nothing — is a plain 404, same as a bad URL.
        $full = $base ? realpath($base . DIRECTORY_SEPARATOR . $path) : false;
        if ($full === false || ! str_starts_with($full, $base . DIRECTORY_SEPARATOR) || ! is_file($full)) {
            abort(404);
        }

        // Uploads get random names, so a URL's content never changes — cache hard.
        return response()->file($full, ['Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
