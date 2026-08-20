<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * A picture the member pointed at instead of uploading.
 *
 * The gallery picker hands back a stored path — the same file the season
 * already keeps — so nothing is copied and deleting one does not take the
 * other. What arrives is still a string from a browser, though, so it is
 * checked before anything is built on it: no traversal, no remote URL
 * smuggled in as a path, an extension that is actually a picture, and, when
 * the path claims to live on this disk, a file that is really there. A
 * reference that points nowhere renders as a broken tile forever.
 *
 * Extracted from the messenger, which had this shape first and was not the
 * only place that needed it.
 */
class GalleryPick
{
    /** @var list<string> */
    public const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /** @var list<string> */
    public const VIDEO_EXTS = ['mp4', 'mov', 'webm', 'mkv', '3gp', 'm4v'];

    /**
     * The path, if it can be trusted; null if it cannot.
     *
     * @param  list<string>|null  $exts  what to accept (pictures by default)
     */
    public static function path(?string $path, ?array $exts = null): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || str_contains($path, '..') || str_contains($path, '://')) {
            return null;
        }

        $allowed = $exts ?? self::IMAGE_EXTS;
        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $allowed, true)) {
            return null;
        }

        if (MediaStore::isRemote($path)) {
            return $path;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }
}
