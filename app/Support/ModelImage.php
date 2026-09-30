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
        if (! self::roomFor((int) $size[0], (int) $size[1])) {
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

    /**
     * A phone photo shrunk as it arrives: at most $maxSide px, JPEG. The
     * browser does this before uploading; a page loaded before it learned
     * to (or a browser that cannot) still sends full size, and six of those
     * are what made a question fail. Anything unreadable is kept as sent.
     */
    public static function shrinkUpload(\Illuminate\Http\UploadedFile $file, int $maxSide = 2048): \Illuminate\Http\UploadedFile
    {
        try {
            $size = @getimagesize($file->getRealPath());
            if (! $size || ! function_exists('imagecreatefromstring')
                || ($file->getSize() <= self::MAX_BYTES && max($size[0], $size[1]) <= $maxSide)
                || ! self::roomFor((int) $size[0], (int) $size[1])) {
                return $file;
            }
            $src = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
            if (! $src) {
                return $file;
            }
            // A phone writes its photo sideways and says so in EXIF; drawn
            // upright here, since the redrawn copy carries no EXIF.
            $o = function_exists('exif_read_data') ? (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1) : 1;
            if ($o === 3) { $src = imagerotate($src, 180, 0); }
            if ($o === 6) { $src = imagerotate($src, -90, 0); }
            if ($o === 8) { $src = imagerotate($src, 90, 0); }
            [$w, $h] = [imagesx($src), imagesy($src)];
            $scale = min(1, $maxSide / max($w, $h));
            $dst = imagecreatetruecolor(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), imagesy($dst), $w, $h);
            imagedestroy($src);
            $tmp = tempnam(sys_get_temp_dir(), 'shr') . '.jpg';
            imagejpeg($dst, $tmp, 85);
            imagedestroy($dst);
            if (! is_file($tmp) || filesize($tmp) === 0 || filesize($tmp) >= $file->getSize()) {
                @unlink($tmp);

                return $file;
            }
            $name = preg_replace('/\.[^.]+$/', '', (string) $file->getClientOriginalName()) ?: 'photo';

            return new \Illuminate\Http\UploadedFile($tmp, $name . '.jpg', 'image/jpeg', null, true);
        } catch (\Throwable $e) {
            return $file;
        }
    }

    /**
     * Whether a picture of these sides can be decoded without the request
     * running out of memory: GD holds about five bytes a pixel, and a 50 MP
     * phone photo is a quarter of a gigabyte. The limit is raised for the
     * decode when it has to be (never past 2 GB); a picture too big even
     * for that is left as it is rather than taking the request down.
     */
    private static function roomFor(int $w, int $h): bool
    {
        $need = $w * $h * 5 + 32 * 1024 * 1024;
        $limit = self::bytes((string) ini_get('memory_limit'));
        if ($limit < 0) {
            return true;   // no limit
        }
        $want = memory_get_usage(true) + $need * 2;   // the source and the copy
        if ($want <= $limit) {
            return true;
        }
        if ($want > 2048 * 1024 * 1024) {
            return false;
        }

        return @ini_set('memory_limit', (string) (int) ceil($want / 1048576) . 'M') !== false;
    }

    private static function bytes(string $v): int
    {
        $v = trim($v);
        if ($v === '' || $v === '-1') {
            return -1;
        }
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1024 * 1024 * 1024,
            'm' => $n * 1024 * 1024,
            'k' => $n * 1024,
            default => $n,
        };
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
