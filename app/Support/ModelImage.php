<?php

namespace App\Support;

/**
 * A picture as a model gets it (2026-09-30).
 *
 * No longer than 1568 px on its long side, which is what the providers read
 * anyway, and re-encoded as JPEG when it arrived big. A phone's 8 MB photo,
 * six of them on one question, was a request the provider refused and a
 * memory spike the server did not survive, and the farmer saw "Request
 * failed". Every AI call that carries a picture passes through here
 * (AiClient::pictures), so a chat photo, a gallery pick and a receipt are
 * all the same safe size. A PDF, or anything GD cannot read, goes as it is.
 */
class ModelImage
{
    public const MAX_SIDE = 1568;

    /** Above this many bytes a picture is redrawn even when its sides are small. */
    public const MAX_BYTES = 1_500_000;

    /** @return array{mime:string,data:string} from raw bytes */
    public static function fit(string $binary, string $mime): array
    {
        return self::fitRaw($binary, $mime);
    }

    /**
     * The same, for a picture already in the ['mime', 'data' => base64] shape.
     *
     * @param  array{mime:string,data:string}  $picture
     * @return array{mime:string,data:string}
     */
    public static function fitEncoded(array $picture): array
    {
        $mime = (string) ($picture['mime'] ?? '');
        $data = (string) ($picture['data'] ?? '');
        // Only images, and only big ones: base64 is four bytes for three.
        if (! str_starts_with($mime, 'image/') || strlen($data) * 3 / 4 <= self::MAX_BYTES && ! self::tooWide($data)) {
            return $picture;
        }
        $binary = base64_decode($data, true);

        return $binary === false ? $picture : self::fitRaw($binary, $mime);
    }

    /** @return array{mime:string,data:string} */
    private static function fitRaw(string $binary, string $mime): array
    {
        $as = ['mime' => $mime, 'data' => base64_encode($binary)];
        if (! str_starts_with($mime, 'image/') || ! function_exists('imagecreatefromstring')) {
            return $as;
        }
        $size = @getimagesizefromstring($binary);
        if (! $size || (strlen($binary) <= self::MAX_BYTES && max($size[0], $size[1]) <= self::MAX_SIDE)) {
            return $as;
        }
        $src = @imagecreatefromstring($binary);
        if (! $src) {
            return $as;
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        // A transparent picture goes onto white, as it would be seen on paper.
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        ob_start();
        imagejpeg($dst, null, 85);
        $out = (string) ob_get_clean();
        imagedestroy($dst);

        return $out !== '' ? ['mime' => 'image/jpeg', 'data' => base64_encode($out)] : $as;
    }

    /** Whether an encoded picture is wider than a model reads, judged from its header alone. */
    private static function tooWide(string $data): bool
    {
        $head = base64_decode(substr($data, 0, 64 * 1024), false);
        if ($head === false || $head === '') {
            return false;
        }
        $size = @getimagesizefromstring($head);

        return $size && max($size[0], $size[1]) > self::MAX_SIDE;
    }
}
