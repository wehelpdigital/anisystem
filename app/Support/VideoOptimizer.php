<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Compresses an uploaded (or recorded) video to a web-friendly MP4 and extracts
 * a poster frame, using the ffmpeg binary. Videos are capped at 300 MB on input
 * and re-encoded to ≤720p H.264/AAC with faststart so they stream progressively.
 */
class VideoOptimizer
{
    /** Largest input we accept, in bytes (300 MB). */
    public const MAX_BYTES = 300 * 1024 * 1024;

    /**
     * @return array{video:string, poster:?string} relative public-disk paths
     *
     * @throws \RuntimeException on an oversized/invalid file or ffmpeg failure
     */
    public static function storeCompressed(UploadedFile $file, string $dir): array
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \RuntimeException('Video is larger than 300 MB.');
        }
        if (! str_starts_with((string) $file->getMimeType(), 'video/')) {
            throw new \RuntimeException('That file is not a video.');
        }

        $ffmpeg = self::binary();

        // No ffmpeg on this server? Keep the video anyway.
        //
        // A recording is somebody standing in a field holding a phone at a
        // broken pump. Losing that because a server tool is missing is the
        // worst trade available here — the compression is a courtesy to the
        // connection, not the reason the clip exists. It goes in at its
        // original size, with no poster frame, and the log says why.
        if (! self::usable($ffmpeg)) {
            Log::warning('VideoOptimizer: ffmpeg unavailable, storing the video as uploaded', ['bin' => $ffmpeg]);

            return ['video' => self::storeVerbatim($file, $dir), 'poster' => null];
        }

        $input = $file->getRealPath();
        $outVideo = tempnam(sys_get_temp_dir(), 'vid') . '.mp4';
        $outPoster = tempnam(sys_get_temp_dir(), 'pos') . '.webp';

        try {
            // Cap to 720p, force even dimensions (yuv420p needs them), H.264 CRF 28.
            $scale = "scale='min(1280,iw)':'min(720,ih)':force_original_aspect_ratio=decrease,"
                . 'scale=trunc(iw/2)*2:trunc(ih/2)*2';
            $encode = new Process([
                $ffmpeg, '-y', '-i', $input,
                '-vf', $scale,
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '28',
                '-c:a', 'aac', '-b:a', '96k',
                '-movflags', '+faststart', '-pix_fmt', 'yuv420p',
                $outVideo,
            ]);
            $encode->setTimeout(600);
            $encode->run();

            if (! $encode->isSuccessful() || ! is_file($outVideo) || filesize($outVideo) === 0) {
                // Surface the real reason in the log so this is debuggable.
                Log::warning('VideoOptimizer: ffmpeg failed', [
                    'bin' => $ffmpeg,
                    'exit' => $encode->getExitCode(),
                    'stderr' => Str::limit($encode->getErrorOutput(), 1500),
                ]);
                $hint = str_contains(strtolower($encode->getErrorOutput() . $ffmpeg), 'not recognized')
                    || $encode->getExitCode() === 127
                    ? 'The video tool (ffmpeg) was not found on the server.'
                    : 'Could not process the video. Please try a different file.';
                throw new \RuntimeException($hint);
            }

            // Poster ~1s in (a black first frame is common); ignore failures.
            $poster = new Process([$ffmpeg, '-y', '-ss', '1', '-i', $outVideo, '-frames:v', '1', '-q:v', '3', $outPoster]);
            $poster->setTimeout(60);
            $poster->run();
            $hasPoster = $poster->isSuccessful() && is_file($outPoster) && filesize($outPoster) > 0;

            $base = trim($dir, '/') . '/' . Str::uuid()->toString();
            $videoPath = $base . '.mp4';
            $posterPath = null;

            $stream = fopen($outVideo, 'rb');
            Storage::disk('public')->put($videoPath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($hasPoster) {
                $posterPath = $base . '.webp';
                $ps = fopen($outPoster, 'rb');
                Storage::disk('public')->put($posterPath, $ps);
                if (is_resource($ps)) {
                    fclose($ps);
                }
            }

            return ['video' => $videoPath, 'poster' => $posterPath];
        } finally {
            @unlink($outVideo);
            @unlink($outPoster);
        }
    }

    /**
     * Resolve the ffmpeg binary. Prefers the configured path (FFMPEG_BIN), then
     * common install locations, then falls back to a bare "ffmpeg" (PATH lookup).
     * The web server's PATH often differs from a shell's, so a full path is safest.
     */
    /**
     * The ffmpeg this server actually has, or null.
     *
     * Public because cutting a poster frame out of a clip that was stored
     * long ago is the same tool doing the same job — see VideoPoster — and
     * two answers to "where is ffmpeg" is one too many.
     */
    public static function usableBinary(): ?string
    {
        $bin = self::binary();

        return self::usable($bin) ? $bin : null;
    }

    /** Is this ffmpeg actually there and runnable? Asked once per request. */
    private static function usable(string $bin): bool
    {
        static $seen = [];
        if (isset($seen[$bin])) {
            return $seen[$bin];
        }

        // An absolute path we already resolved is present by definition.
        if (@is_file($bin)) {
            return $seen[$bin] = true;
        }

        // A bare name has to be found on PATH, which only running it proves.
        try {
            $probe = new Process([$bin, '-version']);
            $probe->setTimeout(10);
            $probe->run();

            return $seen[$bin] = $probe->isSuccessful();
        } catch (\Throwable $e) {
            return $seen[$bin] = false;
        }
    }

    /** Store the upload untouched, when there is no tool to process it with. */
    private static function storeVerbatim(UploadedFile $file, string $dir): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        if (! in_array($ext, ['mp4', 'mov', 'webm', 'mkv', 'm4v', '3gp'], true)) {
            $ext = 'mp4';
        }

        $rel = trim($dir, '/') . '/' . Str::uuid()->toString() . '.' . $ext;
        Storage::disk('public')->putFileAs(dirname($rel), $file, basename($rel));

        return $rel;
    }

    private static function binary(): string
    {
        $configured = (string) config('services.ffmpeg.bin', 'ffmpeg');
        $candidates = array_filter([
            $configured,
            'C:\\ffmpeg\\ffmpeg-2025\\bin\\ffmpeg.exe',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\xampp\\ffmpeg\\bin\\ffmpeg.exe',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
        ]);
        foreach ($candidates as $path) {
            if ($path !== '' && @is_file($path)) {
                return $path;
            }
        }

        return $configured !== '' ? $configured : 'ffmpeg';
    }
}
