<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A video sent in pieces (2026-11-26, after a worker's clips kept failing in the field).
 *
 * One request for a whole clip has three ways to lose it: the signal drops once and everything starts over, Cloudflare
 * refuses a request over 100 MB, and the front door hangs up 20 seconds after the last byte while the server is still
 * carrying the file to the bucket. So the phone cuts the clip into 5 MB pieces. Each piece is a short request that can
 * be sent again on its own, and goes straight into one multipart upload on the bucket, which joins them itself. On a
 * plain disk the pieces wait in a folder and are joined there.
 *
 * Nothing is kept in a table. The token the phone carries names the upload, sealed with the app key and good for one
 * person and a day. When the clip is whole, the phone sends the door it was going to (an observation, the gallery, a
 * note) `clipToken` where the file would have been; App\Http\Middleware\TakesClipToken puts a StoredClip there, and
 * the door asks everything it always asks: whose season, which right, which plan, how long, how much storage.
 * An upload nobody finishes is dropped by the bucket on its own.
 */
class ClipUpload
{
    /** Every piece but the last is exactly this. The bucket wants 5 MB at least, and all of them the same. */
    public const PART_BYTES = 5 * 1024 * 1024;

    private const GOOD_FOR_SECONDS = 86400;

    private const EXTENSIONS = ['mp4', 'mov', 'webm', 'mkv', 'm4v', '3gp'];

    /** @return array{token:string, parts:int, partBytes:int} */
    public static function start(int $userId, string $name, int $bytes): array
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $ext = in_array($ext, self::EXTENSIONS, true) ? $ext : 'mp4';
        $path = 'clips/' . now()->format('Y/m') . '/' . Str::uuid()->toString() . '.' . $ext;
        $upload = null;
        if (MediaStore::diskIsKept()) {
            $upload = (string) self::client()->createMultipartUpload([
                'Bucket' => self::bucket(),
                'Key' => self::key($path),
                'ContentType' => $ext === 'mov' ? 'video/quicktime' : ($ext === 'webm' ? 'video/webm' : 'video/mp4'),
            ])['UploadId'];
        }

        return [
            'token' => self::seal(['u' => $userId, 'p' => $path, 'i' => $upload, 'b' => $bytes,
                'n' => Str::limit(basename($name), 120, ''), 'm' => null, 'e' => time() + self::GOOD_FOR_SECONDS]),
            'parts' => self::partsFor($bytes),
            'partBytes' => self::PART_BYTES,
        ];
    }

    /**
     * Keep piece $number (from 1). Sending one again writes over it, so a lost answer costs nothing.
     *
     * @return array{token:string} the first piece says what the file is, and the token carries that on
     */
    public static function part(array $u, int $number, string $file): array
    {
        $size = (int) filesize($file);
        $count = self::partsFor($u['b']);
        $want = $number < $count ? self::PART_BYTES : $u['b'] - self::PART_BYTES * ($count - 1);
        if ($number < 1 || $number > $count || $size !== $want) {
            throw new \RuntimeException('A piece of the video did not arrive whole. Please try again.');
        }
        if ($number === 1) {
            $u['m'] = self::kind($file);
            if ($u['m'] === null) {
                throw new \RuntimeException('That file is not a video.');
            }
        }
        if ($u['i'] !== null) {
            self::client()->uploadPart(['Bucket' => self::bucket(), 'Key' => self::key($u['p']), 'UploadId' => $u['i'],
                'PartNumber' => $number, 'SourceFile' => $file]);
        } else {
            $dir = self::folder($u['p']);
            File::ensureDirectoryExists($dir);
            File::copy($file, $dir . '/' . $number);
        }

        return ['token' => self::seal($u)];
    }

    /** The upload a token names, or null: not ours, not this person's, or too old. */
    public static function read(string $token, int $userId): ?array
    {
        try {
            $u = json_decode(Crypt::decryptString($token), true);
        } catch (\Throwable $e) {
            return null;
        }
        if (! is_array($u) || ($u['u'] ?? 0) !== $userId || ($u['e'] ?? 0) < time() || ! str_starts_with((string) ($u['p'] ?? ''), 'clips/')) {
            return null;
        }

        return $u;
    }

    /** Join the pieces. The stored path, or a reason that can be read out. */
    public static function settle(array $u): string
    {
        $disk = Storage::disk('public');
        $count = self::partsFor($u['b']);
        if ($u['i'] === null) {
            $dir = self::folder($u['p']);
            $whole = tempnam(sys_get_temp_dir(), 'clip');
            $out = fopen($whole, 'wb');
            for ($n = 1; $n <= $count; $n++) {
                $in = @fopen($dir . '/' . $n, 'rb');
                if (! $in) {
                    fclose($out);
                    @unlink($whole);
                    throw new \RuntimeException('Part of the video is missing. Please send it again.');
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
            fclose($out);
            $in = fopen($whole, 'rb');
            $disk->put($u['p'], $in);
            if (is_resource($in)) {
                fclose($in);
            }
            @unlink($whole);
            File::deleteDirectory($dir);

            return $u['p'];
        }

        try {
            return self::join($u, $count);
        } catch (\Aws\Exception\AwsException $e) {
            // Its own words name the bucket and are read out by the door.
            \Illuminate\Support\Facades\Log::warning('ClipUpload: not joined', ['path' => $u['p'], 'error' => $e->getMessage()]);
            throw new \RuntimeException('The video could not be put together. Please try again.');
        }
    }

    private static function join(array $u, int $count): string
    {
        // What the bucket holds, not what the phone says it sent.
        $args = ['Bucket' => self::bucket(), 'Key' => self::key($u['p']), 'UploadId' => $u['i']];
        try {
            $held = [];
            $bytes = 0;
            $marker = 0;
            do {
                $page = self::client()->listParts($args + ['PartNumberMarker' => $marker]);
                foreach ($page['Parts'] ?? [] as $p) {
                    $held[(int) $p['PartNumber']] = ['PartNumber' => (int) $p['PartNumber'], 'ETag' => $p['ETag']];
                    $bytes += (int) $p['Size'];
                }
                $more = ! empty($page['IsTruncated']) && (int) $page['NextPartNumberMarker'] > $marker;
                $marker = (int) ($page['NextPartNumberMarker'] ?? 0);
            } while ($more);
        } catch (\Aws\Exception\AwsException $e) {
            // Joined already, and the answer was lost on the way back: the clip itself is there.
            if ($e->getAwsErrorCode() === 'NoSuchUpload' && Storage::disk('public')->exists($u['p'])) {
                return $u['p'];
            }
            throw $e;
        }
        if (count($held) !== $count || $bytes !== $u['b']) {
            throw new \RuntimeException('Part of the video is missing. Please send it again.');
        }
        ksort($held);
        self::client()->completeMultipartUpload($args + ['MultipartUpload' => ['Parts' => array_values($held)]]);

        return $u['p'];
    }

    /** Let an upload go: the person changed their mind. */
    public static function drop(array $u): void
    {
        try {
            if ($u['i'] !== null) {
                self::client()->abortMultipartUpload(['Bucket' => self::bucket(), 'Key' => self::key($u['p']), 'UploadId' => $u['i']]);
            } else {
                File::deleteDirectory(self::folder($u['p']));
            }
        } catch (\Throwable $e) {
            // Gone already, or the bucket drops it later on its own.
        }
    }

    /**
     * How long a joined clip runs, or null when it will not say (a browser recording often does not).
     *
     * Read from where the clip lies, a few bytes at a time: the film's own header (`moov`, then `mvhd`) names its
     * length, and stands before or after the pictures. The video tool on the live host let a 75 second clip through
     * by its address (measured 2026-11-26), and fetching the whole clip to ask it one number is what the pieces were
     * made to avoid.
     */
    public static function seconds(string $path): ?float
    {
        try {
            $at = 0;
            for ($box = 0; $box < 60; $box++) {
                $head = self::bytes($path, $at, 16);
                if (strlen($head) < 8) {
                    return null;
                }
                $size = unpack('N', substr($head, 0, 4))[1];
                $type = substr($head, 4, 4);
                $skip = 8;
                if ($size === 1 && strlen($head) === 16) {
                    $size = unpack('J', substr($head, 8, 8))[1];
                    $skip = 16;
                }
                if ($type === 'moov') {
                    $in = self::bytes($path, $at + $skip, 8192);
                    $p = strpos($in, 'mvhd');
                    if ($p === false) {
                        return null;
                    }
                    $f = substr($in, $p + 4, 32);
                    [$scale, $long] = ord($f[0]) === 1
                        ? [unpack('N', substr($f, 20, 4))[1], unpack('J', substr($f, 24, 8))[1]]
                        : [unpack('N', substr($f, 12, 4))[1], unpack('N', substr($f, 16, 4))[1]];

                    return $scale > 0 && $long > 0 && $long !== 0xFFFFFFFF ? $long / $scale : null;
                }
                if ($size < 8 || ! preg_match('~^[\x20-\x7E]{4}$~', $type)) {
                    return null;   // not this kind of file
                }
                $at += $size;
            }
        } catch (\Throwable $e) {
            // A clip that will not say how long it is goes through.
        }

        return null;
    }

    /** Some bytes of a kept clip. Short or empty past its end. */
    private static function bytes(string $path, int $from, int $count): string
    {
        if (! MediaStore::diskIsKept()) {
            return (string) file_get_contents(Storage::disk('public')->path($path), false, null, $from, $count);
        }
        try {
            return (string) self::client()->getObject(['Bucket' => self::bucket(), 'Key' => self::key($path),
                'Range' => 'bytes=' . $from . '-' . ($from + $count - 1)])['Body'];
        } catch (\Aws\Exception\AwsException $e) {
            return '';   // past the end
        }
    }

    public static function partsFor(int $bytes): int
    {
        return max(1, (int) ceil($bytes / self::PART_BYTES));
    }

    // ------------------------------------------------------------------

    private static function seal(array $u): string
    {
        return Crypt::encryptString(json_encode($u));
    }

    /** What a file that starts like this is, when it is a video. */
    private static function kind(string $file): ?string
    {
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        if (str_starts_with($mime, 'video/')) {
            return $mime;
        }
        // Cut short, some phone files read as plain data. Their first box still says what they are.
        $head = (string) file_get_contents($file, false, null, 0, 16);

        return substr($head, 4, 4) === 'ftyp' ? (substr($head, 8, 2) === 'qt' ? 'video/quicktime' : 'video/mp4')
            : (str_starts_with($head, "\x1A\x45\xDF\xA3") ? 'video/webm' : null);
    }

    private static function folder(string $path): string
    {
        return storage_path('app/private/clip-uploads/' . pathinfo($path, PATHINFO_FILENAME));
    }

    private static function client(): \Aws\S3\S3Client
    {
        return Storage::disk('public')->getClient();
    }

    private static function bucket(): string
    {
        return (string) config('filesystems.disks.public.bucket');
    }

    /** The bucket's own name for a path on the disk, under the disk's prefix. */
    private static function key(string $path): string
    {
        return ltrim(Storage::disk('public')->path($path), '/');
    }
}
