<?php

namespace App\Support;

/**
 * A small picture of a big one.
 *
 * Every list that shows a drawing -- the pad's shelf, the Gallery, the
 * "tag a drawing" sheet, a note's card -- was loading the full canvas to
 * paint a stamp-sized frame, and a season of drawings was megabytes of
 * download for a row of thumbnails. Made once, at save time, with GD, and
 * kept beside the picture as `thumb` on the media entry. A drawing's
 * transparent ground is flattened onto white so the stamp reads the same
 * on a dark screen as on a light one.
 */
final class ImageThumb
{
    /** A PNG no wider than $maxW and no taller than $maxH, or null when GD cannot read the picture. */
    public static function png(string $binary, int $maxW = 480, int $maxH = 360): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }
        $src = @imagecreatefromstring($binary);
        if (! $src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);

            return null;
        }
        $scale = min(1.0, $maxW / $w, $maxH / $h);
        $tw = max(1, (int) round($w * $scale));
        $th = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($tw, $th);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
        imagedestroy($src);

        ob_start();
        imagepng($dst, null, 7);
        $out = (string) ob_get_clean();
        imagedestroy($dst);

        return $out !== '' ? $out : null;
    }
}
