<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shrinks user-uploaded media before it hits the public disk.
 *
 * Images are re-encoded to WebP (smaller than JPEG/PNG at the same quality) and
 * capped to a sane maximum dimension, so a phone's 8 MB photo lands as a ~150 KB
 * WebP. Animated GIFs are passed through untouched (a static WebP would lose the
 * animation). Anything we can't decode is stored verbatim so an upload never
 * fails just because optimisation didn't apply.
 */
class MediaOptimizer
{
    /**
     * Store an uploaded image as a compressed WebP and return its public path.
     * Falls back to a safe verbatim store if the image can't be decoded.
     */
    public static function storeImageAsWebp(UploadedFile $file, string $dir, int $maxDim = 1600, int $quality = 82): string
    {
        $mime = (string) $file->getMimeType();

        // Animated GIFs: keep as-is so the animation survives.
        if ($mime === 'image/gif') {
            return self::storeVerbatim($file, $dir, ['gif']);
        }

        if (! function_exists('imagewebp')) {
            return self::storeVerbatim($file, $dir, ['jpg', 'jpeg', 'png', 'webp']);
        }

        $img = self::decode($file->getRealPath(), $mime);
        if (! $img) {
            return self::storeVerbatim($file, $dir, ['jpg', 'jpeg', 'png', 'webp']);
        }

        $img = self::resize($img, $maxDim);

        $stem = Str::uuid()->toString();
        $rel = trim($dir, '/') . '/' . $stem . '.webp';
        /* Encoded to a temp file and handed to the disk, rather than written
           into the disk's directory by hand -- the disk is a bucket on a host
           with nothing to keep files on, and a bucket has no directory. */
        $tmp = tempnam(sys_get_temp_dir(), 'webp');
        imagepalettetotruecolor($img);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagewebp($img, $tmp, $quality);
        imagedestroy($img);
        $stream = fopen($tmp, 'r');
        Storage::disk('public')->writeStream($rel, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        @unlink($tmp);
        return $rel;
    }

    /** GD decode for the formats we accept. Returns a GdImage or null. */
    private static function decode(string $path, string $mime)
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'image/bmp', 'image/x-ms-bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false,
            default => false,
        };

        return $img ?: null;
    }

    /** Downscale so the longest side is at most $maxDim (never upscales). */
    private static function resize($img, int $maxDim)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1.0, $maxDim / max($w, $h));
        if ($scale >= 1.0) {
            return $img;
        }

        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);

        return $dst;
    }

    /** Store the file unchanged with a content-derived safe extension. */
    private static function storeVerbatim(UploadedFile $file, string $dir, array $allowed): string
    {
        $ext = UploadHelper::safeExtension($file, $allowed);
        $stem = Str::uuid()->toString();
        Storage::disk('public')->putFileAs($dir, $file, $stem . '.' . $ext);

        return trim($dir, '/') . '/' . $stem . '.' . $ext;
    }
}
