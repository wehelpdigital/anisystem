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

        $chain = self::frameChain($opts['frame'] ?? []);

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

        /* The sheet of everything drawn on top, if the studio sent one.
         *
         * One transparent picture at the reel's own size, holding the words,
         * the shapes and the arrows exactly as they were arranged. */
        $sheet = $opts['overlaySheet'] ?? null;
        $sheetPath = ($sheet instanceof UploadedFile) ? $sheet->getRealPath() : null;
        if ($sheetPath !== null) {
            $args[] = '-i';
            $args[] = $sheetPath;
        }
        $sheetIndex = $audio !== null ? 2 : 1;

        array_push($args, '-t', (string) $duration);

        /* One graph rather than -vf, because there are up to three inputs to
         * join: the picture, a sheet laid over it, and two sounds mixed. */
        $graph = '[0:v]' . $chain . '[vbase]';
        $videoOut = '[vbase]';
        if ($sheetPath !== null) {
            $graph .= ';[' . $sheetIndex . ':v]scale=' . self::OUT_W . ':' . self::OUT_H . '[sheet]'
                . ';[vbase][sheet]overlay=0:0[vout]';
            $videoOut = '[vout]';
        }

        /* The sound.
         *
         * Music used to replace what the camera heard outright. A farmer
         * showing a pump running wants both, at a balance they chose — so
         * the two are mixed, and either can be turned to nothing. */
        $audioOut = null;
        if ($audio !== null) {
            $musicVol = self::volume($opts['musicVolume'] ?? 1.0);
            $ownVol = self::volume($opts['originalVolume'] ?? 0.0);
            $keepsOwn = $ownVol > 0.01 && self::hasAudio($ffmpeg, $input);
            if ($keepsOwn) {
                $graph .= ';[0:a]volume=' . $ownVol . '[a0]'
                    . ';[1:a]volume=' . $musicVol . '[a1]'
                    . ';[a0][a1]amix=inputs=2:duration=first:dropout_transition=0:normalize=0[aout]';
            } else {
                $graph .= ';[1:a]volume=' . $musicVol . '[aout]';
            }
            $audioOut = '[aout]';
        }

        array_push($args, '-filter_complex', $graph, '-map', $videoOut);
        if ($audioOut !== null) {
            array_push($args, '-map', $audioOut, '-shortest');
        } else {
            // Whatever the camera heard, if it heard anything.
            array_push($args, '-map', '0:a?');
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
     * Where the picture sits inside the reel.
     *
     * Untouched, this is what it always was: fill the frame and keep the
     * middle, because a reel with bars down the side is a landscape video
     * pretending. Once a farmer has pinched or turned it, the fit becomes
     * theirs, and whatever is left over is the backdrop they chose.
     *
     * @param  array<string,mixed>  $frame
     */
    private static function frameChain(array $frame): string
    {
        $scale = max(0.3, min(3.0, (float) ($frame['scale'] ?? 1.0)));
        $rot = fmod((float) ($frame['rotate'] ?? 0.0), 360.0);
        $bg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($frame['bg'] ?? '')) ? $frame['bg'] : '#000000';
        $bg = '0x' . ltrim(strtolower($bg), '#');

        if (abs($scale - 1.0) < 0.01 && abs($rot) < 0.5) {
            return 'scale=' . self::OUT_W . ':' . self::OUT_H . ':force_original_aspect_ratio=increase'
                . ',crop=' . self::OUT_W . ':' . self::OUT_H
                . ',setsar=1';
        }

        $w = (int) round(self::OUT_W * $scale);
        $h = (int) round(self::OUT_H * $scale);

        $chain = 'scale=' . $w . ':' . $h . ':force_original_aspect_ratio=increase'
            . ',crop=' . $w . ':' . $h;

        if (abs($rot) >= 0.5) {
            // The frame keeps its size; the corners the turn opens up take
            // the backdrop rather than black.
            $chain .= ',rotate=' . round(deg2rad($rot), 5) . ':fillcolor=' . $bg . '@1';
        }

        /* Bigger than the reel: crop to it. Smaller: pad out to it. Both, in
         * one pass, using max() so neither has to know which happened. */
        $chain .= ',pad=w=max(iw\\,' . self::OUT_W . '):h=max(ih\\,' . self::OUT_H . ')'
            . ':x=(ow-iw)/2:y=(oh-ih)/2:color=' . $bg
            . ',crop=' . self::OUT_W . ':' . self::OUT_H
            . ',setsar=1';

        return $chain;
    }

    /** A loudness the farmer set, kept inside what ffmpeg will accept. */
    private static function volume($v): string
    {
        return (string) round(max(0.0, min(2.0, (float) $v)), 2);
    }

    /**
     * Does the clip carry any sound of its own?
     *
     * Asked because mixing against a track that does not exist makes ffmpeg
     * fail the whole encode, and a farmer who filmed silence should still get
     * their music.
     */
    private static function hasAudio(string $ffmpeg, string $input): bool
    {
        $probe = preg_replace('/ffmpeg(\.exe)?$/i', 'ffprobe$1', $ffmpeg);
        if ($probe !== $ffmpeg && is_file($probe)) {
            $p = new Process([$probe, '-v', 'error', '-select_streams', 'a:0',
                '-show_entries', 'stream=codec_type', '-of', 'csv=p=0', $input], null, null, null, 30);
            $p->run();

            return str_contains($p->getOutput(), 'audio');
        }

        // No ffprobe beside it: ffmpeg itself will say, on the way past.
        $p = new Process([$ffmpeg, '-hide_banner', '-i', $input], null, null, null, 30);
        $p->run();

        return (bool) preg_match('/Stream #\d+:\d+.*: Audio:/', $p->getErrorOutput());
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
