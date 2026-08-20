<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Turns whatever a phone hands over into a reel: sixty seconds at most,
 * vertical, filtered, captioned, optionally scored, and small enough to open
 * on a signal that comes and goes.
 *
 * The editing decisions are made on the phone, where a farmer can see what
 * they are doing, and applied here, where there is an encoder — a browser can
 * preview a filter cheaply but re-encoding video in one costs a battery. Every
 * step is one ffmpeg pass so the video is decoded once.
 *
 * ffmpeg is not assumed. Without it the clip is still kept and still plays;
 * it simply arrives as it was filmed, because a reel nobody can post is worse
 * than a reel that was not trimmed.
 */
class ReelEncoder
{
    /** The whole point of the format: a reel is short. */
    public const MAX_SECONDS = 60;

    /** Portrait, at a size phones actually record and networks can carry. */
    public const OUT_W = 1080;
    public const OUT_H = 1920;

    /**
     * The looks on offer, as ffmpeg filter fragments.
     *
     * Named the same as the photo editor's, so "Warm" means the same thing to
     * a farmer whichever thing they are making.
     *
     * @return array<string,string>
     */
    public static function looks(): array
    {
        return [
            'none' => '',
            'warm' => 'eq=saturation=1.25:contrast=1.05,colorbalance=rs=.06:gs=.02:bs=-.05',
            'cool' => 'eq=saturation=1.10:brightness=0.02,colorbalance=rs=-.05:bs=.07',
            'bright' => 'eq=brightness=0.08:contrast=1.06',
            'punch' => 'eq=saturation=1.55:contrast=1.18',
            'mono' => 'hue=s=0,eq=contrast=1.08',
            'faded' => 'eq=saturation=0.75:brightness=0.06:contrast=0.92',
        ];
    }

    /**
     * Encode and store a reel.
     *
     * @param  array{start?:float,duration?:float,look?:string,caption?:string,audio?:UploadedFile|null,audioPath?:string|null}  $opts
     * @return array{video:string,poster:?string,duration:int}
     *
     * @throws \RuntimeException when the file is not a video or the encode fails outright
     */
    public static function store(UploadedFile $file, array $opts = []): array
    {
        if (! str_starts_with((string) $file->getMimeType(), 'video/')) {
            throw new \RuntimeException('That file is not a video.');
        }

        $ffmpeg = self::binary();
        $duration = (int) round(min(self::MAX_SECONDS, max(1.0, (float) ($opts['duration'] ?? self::MAX_SECONDS))));
        $start = max(0.0, (float) ($opts['start'] ?? 0));

        if (! self::usable($ffmpeg)) {
            /* No encoder here: keep what was filmed rather than refusing it —
             * but say so. Everything the studio offered is dropped on this
             * path (the trim, the crop to 9:16, the look, the burned words
             * and the music), and dropping a farmer's music without a word is
             * what made this feature look broken rather than degraded. */
            Log::warning('ReelEncoder: ffmpeg unavailable, storing the reel as filmed');
            $stored = VideoOptimizer::storeCompressed($file, 'community/reels');

            return [
                'video' => $stored['video'],
                'poster' => $stored['poster'] ?? null,
                'duration' => $duration,
                'raw' => true,
            ];
        }

        $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $input = $file->getRealPath();
        $stem = Str::random(32);
        $outFile = $tmp . $stem . '.mp4';
        $posterFile = $tmp . $stem . '.jpg';

        /* The picture chain, in one pass.
         *
         * scale+crop rather than pad: a reel with black bars down the sides is
         * a landscape video pretending, and the format's whole promise is that
         * it fills the phone. increase+crop keeps the middle, which is where
         * somebody points a camera. */
        $chain = 'scale=' . self::OUT_W . ':' . self::OUT_H . ':force_original_aspect_ratio=increase'
            . ',crop=' . self::OUT_W . ':' . self::OUT_H
            . ',setsar=1';

        $look = self::looks()[$opts['look'] ?? 'none'] ?? '';
        if ($look !== '') {
            $chain .= ',' . $look;
        }

        /* Everything stuck onto the picture, burned at the video's own size.
         *
         * The editor works on a preview a few hundred pixels tall and the
         * reel is 1080 wide, so positions arrive as percentages and sizes as
         * multiples — a number that means the same thing at both sizes. */
        foreach (self::overlayFilters($opts['overlays'] ?? []) as $filter) {
            $chain .= ',' . $filter;
        }

        // A single line, the old way, still honoured.
        $caption = trim((string) ($opts['caption'] ?? ''));
        if ($caption !== '') {
            $chain .= ',' . self::captionFilter($caption);
        }

        $args = [$ffmpeg, '-y'];
        // Seeking BEFORE the input is the fast kind; the trim is exact enough
        // for a person choosing a moment with their thumb.
        if ($start > 0) {
            $args[] = '-ss';
            $args[] = (string) $start;
        }
        $args[] = '-i';
        $args[] = $input;

        $audio = self::audioSource($opts);
        if ($audio !== null) {
            $args[] = '-i';
            $args[] = $audio;
        }

        array_push($args, '-t', (string) $duration, '-vf', $chain);

        if ($audio !== null) {
            // The chosen track replaces whatever the phone's microphone caught:
            // picking music is saying "not the wind and my own footsteps".
            array_push($args, '-map', '0:v:0', '-map', '1:a:0', '-shortest');
        }

        array_push(
            $args,
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26',
            '-profile:v', 'main', '-pix_fmt', 'yuv420p',
            // Streamable: the header goes first so playback can start before
            // the file has finished arriving.
            '-movflags', '+faststart',
            '-c:a', 'aac', '-b:a', '128k', '-ac', '2',
            $outFile,
        );

        $encode = new Process($args, null, null, null, 600);
        $encode->run();

        if (! $encode->isSuccessful() || ! is_file($outFile)) {
            Log::warning('ReelEncoder: ffmpeg failed', ['err' => Str::limit($encode->getErrorOutput(), 900)]);
            @unlink($outFile);
            $stored = VideoOptimizer::storeCompressed($file, 'community/reels');

            return [
                'video' => $stored['video'],
                'poster' => $stored['poster'] ?? null,
                'duration' => $duration,
                'raw' => true,
            ];
        }

        /* A cover frame taken a beat in, and frame zero if that beat is past
         * the end — a three-second clip trimmed to one has no 0.6s to grab
         * on some inputs, and a reel with no picture in the rail is worse
         * than a reel whose cover is its first frame. */
        foreach (['0.6', '0'] as $at) {
            $poster = new Process([
                $ffmpeg, '-y', '-ss', $at, '-i', $outFile,
                '-frames:v', '1', '-q:v', '4', $posterFile,
            ], null, null, null, 120);
            $poster->run();
            if (is_file($posterFile) && filesize($posterFile) > 0) {
                break;
            }
        }

        $videoPath = self::put($outFile, 'mp4');
        $posterPath = is_file($posterFile) ? self::put($posterFile, 'jpg') : null;

        @unlink($outFile);
        @unlink($posterFile);
        if (isset($opts['_tmpAudio']) && is_string($opts['_tmpAudio'])) {
            @unlink($opts['_tmpAudio']);
        }

        return ['video' => $videoPath, 'poster' => $posterPath, 'duration' => $duration];
    }

    /**
     * The words a farmer put on the picture, as drawtext filters.
     *
     * @param  list<array<string,mixed>>  $overlays
     * @return list<string>
     */
    private static function overlayFilters(array $overlays): array
    {
        $out = [];
        foreach (array_slice($overlays, 0, 6) as $o) {
            $text = trim((string) ($o['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $text = self::escapeDrawText($text);

            // A size of 1 means "the size the editor showed", which is about
            // 4% of the frame's height; the rest scales from there.
            $size = max(18, min(160, (int) round(self::OUT_H * 0.042 * (float) ($o['size'] ?? 1))));
            $x = max(0, min(100, (float) ($o['x'] ?? 50)));
            $y = max(0, min(100, (float) ($o['y'] ?? 80)));
            $ink = preg_match('/^#[0-9a-f]{6}$/i', (string) ($o['ink'] ?? '')) ? $o['ink'] : '#ffffff';

            $out[] = "drawtext=text='{$text}'"
                . ':fontcolor=' . str_replace('#', '0x', strtolower($ink))
                . ':fontsize=' . $size
                // A shadow rather than a box: the editor draws a shadow, and
                // what is posted should look like what was chosen.
                . ':shadowcolor=black@0.6:shadowx=2:shadowy=2'
                . ':x=(w*' . ($x / 100) . ')-(text_w/2)'
                . ':y=(h*' . ($y / 100) . ')-(text_h/2)';
        }

        return $out;
    }

    /** drawtext reads colons and backslashes as syntax; a farmer does not. */
    private static function escapeDrawText(string $text): string
    {
        $text = Str::limit($text, 120, '');
        $text = str_replace(['\\', ':', "'", '%'], ['\\\\', '\\:', "\u{2019}", '\\%'], $text);

        return preg_replace('/[\r\n]+/', ' ', $text);
    }

    /**
     * The caption, burned into the picture.
     *
     * Burned rather than overlaid in the page because a reel travels: shared
     * outward or saved to a phone, the words have to go with it. Escaped
     * carefully — drawtext reads colons and backslashes as syntax, and a
     * farmer writing "6:00 am" should not break an encode.
     */
    private static function captionFilter(string $caption): string
    {
        $text = Str::limit($caption, 120, '');
        $text = str_replace(['\\', ':', "'", '%'], ['\\\\', '\\:', "\u{2019}", '\\%'], $text);
        $text = preg_replace('/[\r\n]+/', ' ', $text);

        return "drawtext=text='" . $text . "'"
            . ':fontcolor=white:fontsize=52:box=1:boxcolor=black@0.45:boxborderw=18'
            . ':x=(w-text_w)/2:y=h-320';
    }

    /** An uploaded track, or one of the library files, or nothing. */
    private static function audioSource(array &$opts): ?string
    {
        if (isset($opts['audio']) && $opts['audio'] instanceof UploadedFile) {
            return $opts['audio']->getRealPath();
        }

        $name = trim((string) ($opts['audioPath'] ?? ''));
        if ($name === '') {
            return null;
        }
        // Library tracks live in one folder and are named, never pathed: a
        // caller cannot walk out of it into the rest of the disk.
        $name = basename($name);
        $full = storage_path('app/public/reel-music/' . $name);

        return is_file($full) ? $full : null;
    }

    /**
     * Store the encoded file where every other piece of media in this app
     * lives, so a reel survives a redeploy like the rest of them.
     */
    private static function put(string $localFile, string $ext): string
    {
        $stored = MediaStore::putBinary(file_get_contents($localFile), 'community/reels', $ext);
        if ($stored) {
            return $stored;
        }

        // MediaStore not configured: the public disk still works.
        $path = 'community/reels/' . Str::random(40) . '.' . $ext;
        Storage::disk('public')->put($path, file_get_contents($localFile));

        return $path;
    }

    /**
     * The same resolver VideoOptimizer uses, for the same reason: the binary
     * lives somewhere different on a Windows workstation and a Linux box, and
     * one of them is where this actually runs.
     */
    private static function binary(): string
    {
        $configured = (string) config('services.ffmpeg.bin', 'ffmpeg');
        $candidates = array_filter([
            $configured,
            'C:\ffmpeg\ffmpeg-2025\bin\ffmpeg.exe',
            'C:\ffmpeg\bin\ffmpeg.exe',
            'C:\xampp\ffmpeg\bin\ffmpeg.exe',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
        ]);
        /* PATH first, because that is where a package manager puts it.
         *
         * A Nix host keeps ffmpeg in the store and links it onto PATH; none
         * of the absolute guesses below exist there, so a list of guesses
         * found nothing and every reel fell back to being stored raw. */
        foreach (['where ffmpeg', 'which ffmpeg'] as $ask) {
            $found = @shell_exec($ask . ' 2>' . (DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null'));
            $first = trim(strtok((string) $found, "\r\n"));
            if ($first !== '' && @is_file($first)) {
                return $first;
            }
        }

        foreach ($candidates as $path) {
            if ($path !== '' && @is_file($path)) {
                return $path;
            }
        }

        return $configured !== '' ? $configured : 'ffmpeg';
    }

    private static function usable(string $bin): bool
    {
        try {
            $p = new Process([$bin, '-version'], null, null, null, 20);
            $p->run();

            return $p->isSuccessful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
