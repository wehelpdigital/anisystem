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
