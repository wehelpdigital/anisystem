<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * The frame a clip shows before it plays.
 *
 * A clip uploaded through a composer is given one as it is stored. A clip
 * referenced out of the gallery — or uploaded before that was done — has
 * none, and then a picker full of films shows a picker full of nothing.
 *
 * One is cut here, once, and remembered: the second time anybody asks for the
 * same clip's frame it is a row, not an ffmpeg run. Where the frame is kept
 * is wherever this app keeps pictures, so the answer works the same on a
 * machine with a disk and on one where the files live somewhere else.
 */
class VideoPoster
{
    private const TABLE = 'as_video_posters';

    /** The frame already made for this clip, if there is one. */
    public static function stored(?string $video): ?string
    {
        $video = trim((string) $video);
        if ($video === '' || ! Schema::hasTable(self::TABLE)) {
            return null;
        }

        $row = DB::table(self::TABLE)->where('videoKey', sha1($video))->first();

        return $row?->posterPath ?: null;
    }

    /**
     * The frame for this clip, cutting one if it has never been cut.
     *
     * Null when there is no ffmpeg to cut with, or the clip cannot be read —
     * a caller shows its own placeholder then, which is what it was showing
     * before it asked.
     */
    public static function ensure(?string $video): ?string
    {
        $video = trim((string) $video);
        if ($video === '') {
            return null;
        }
        if ($already = self::stored($video)) {
            return $already;
        }

        $binary = self::extract($video);
        if ($binary === null) {
            return null;
        }

        // Kept where every other picture is kept, so it is served the same way
        // and swept up by the same housekeeping.
        $path = MediaStore::putBinary($binary, 'video-posters', 'jpg', null, 'poster-');
        if (! $path) {
            return null;
        }

        if (Schema::hasTable(self::TABLE)) {
            DB::table(self::TABLE)->updateOrInsert(
                ['videoKey' => sha1($video)],
                ['videoPath' => mb_substr($video, 0, 500), 'posterPath' => $path,
                 'created_at' => now(), 'updated_at' => now()]
            );
        }

        return $path;
    }

    /**
     * One frame out of the clip, as JPEG bytes.
     *
     * A second in, because the first frame of a phone video is very often
     * black; if that fails — a clip shorter than a second is a real thing —
     * the very beginning is tried instead. ffmpeg reads a URL as happily as a
     * file, which is what makes this work when the clips live elsewhere.
     */
    private static function extract(string $video): ?string
    {
        $bin = VideoOptimizer::usableBinary();
        if (! $bin) {
            return null;
        }

        $input = self::readableInput($video);
        if ($input === null) {
            return null;
        }

        $out = tempnam(sys_get_temp_dir(), 'vposter') . '.jpg';
        try {
            foreach (['1', '0'] as $seek) {
                $p = new Process([
                    $bin, '-y', '-ss', $seek, '-i', $input,
                    '-frames:v', '1', '-vf', 'scale=640:-2', '-q:v', '4', $out,
                ]);
                $p->setTimeout(45);
                $p->run();
                if ($p->isSuccessful() && is_file($out) && filesize($out) > 0) {
                    return file_get_contents($out) ?: null;
                }
            }
            Log::info('VideoPoster: no frame could be cut', ['video' => $video]);

            return null;
        } finally {
            @unlink($out);
        }
    }

    /** A path ffmpeg can open: a real file when there is one, else a URL. */
    private static function readableInput(string $video): ?string
    {
        if (! MediaStore::isRemote($video) && Storage::disk('public')->exists($video)) {
            return Storage::disk('public')->path($video);
        }

        $url = MediaStore::url($video);

        return $url && str_contains($url, '://') ? $url : null;
    }
}
