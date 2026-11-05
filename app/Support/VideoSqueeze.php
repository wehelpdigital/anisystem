<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Compresses a saved clip after the person has gone (2026-11-06).
 *
 * It cannot happen during the upload: the front door hangs up at 20 seconds, and a
 * one minute clip takes about 41 seconds here (measured by videos:tool-check, on 1 CPU
 * and 512 MB shared with everyone using the site). So a clip is saved as filmed, which
 * is always quick, and listed here. The scheduler takes them one at a time, at the
 * lowest priority and on one thread, and writes the smaller file over the same address,
 * so nothing that points at the clip has to change. A clip a door moved elsewhere in
 * the meantime is simply skipped.
 *
 * Losing a clip is the one thing this must not do. Anything going wrong leaves the
 * file as it was filmed, and the old file is only replaced by a finished one.
 */
class VideoSqueeze
{
    public const TABLE = 'as_video_squeezes';

    /** The encode stops itself past this. 4K footage can get here, and it stays as filmed. */
    private const MEMORY_STOP_KB = 300 * 1024;

    /** With the machine this full, the clip waits for the next minute rather than adding to it. */
    private const MEMORY_BUSY_BYTES = 430 * 1048576;

    private const STUCK_MINUTES = 20;

    private const MAX_TRIES = 3;

    public static function enqueue(string $path): void
    {
        // A clip that is safely saved must not fail because of its place in a queue.
        try {
            DB::table(self::TABLE)->insert(['path' => $path, 'status' => 'waiting', 'created_at' => now(), 'updated_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('VideoSqueeze: could not list a clip', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }

    /** Whether the scheduler has anything to start this minute. */
    public static function hasWork(): bool
    {
        return DB::table(self::TABLE)->where('status', 'waiting')
            ->orWhere(fn ($q) => $q->where('status', 'working')->where('startedAt', '<', now()->subMinutes(self::STUCK_MINUTES)))
            ->exists();
    }

    /** One clip, if there is one and the machine has room. Returns its row id. */
    public static function next(): ?int
    {
        // A deploy can end a run halfway. The clip is untouched, so it simply goes round again.
        DB::table(self::TABLE)->where('status', 'working')->where('startedAt', '<', now()->subMinutes(self::STUCK_MINUTES))
            ->where('tries', '>=', self::MAX_TRIES)
            ->update(['status' => 'failed', 'note' => 'Gave up after ' . self::MAX_TRIES . ' tries', 'updated_at' => now()]);
        DB::table(self::TABLE)->where('status', 'working')->where('startedAt', '<', now()->subMinutes(self::STUCK_MINUTES))
            ->update(['status' => 'waiting', 'updated_at' => now()]);

        if (self::memoryInUse() > self::MEMORY_BUSY_BYTES) {
            return null;
        }

        $row = DB::table(self::TABLE)->where('status', 'waiting')->orderBy('id')->first();
        if (! $row) {
            return null;
        }
        $claimed = DB::table(self::TABLE)->where('id', $row->id)->where('status', 'waiting')
            ->update(['status' => 'working', 'startedAt' => now(), 'tries' => (int) $row->tries + 1, 'updated_at' => now()]);
        if (! $claimed) {
            return null;
        }

        try {
            self::finish($row->id, self::squeeze((string) $row->path));
        } catch (\Throwable $e) {
            report($e);
            self::finish($row->id, ['status' => 'kept', 'note' => mb_substr($e->getMessage(), 0, 250)]);
        }

        return (int) $row->id;
    }

    /** @return array{status:string, note?:string, bytesBefore?:int, bytesAfter?:int} */
    private static function squeeze(string $path): array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return ['status' => 'skipped', 'note' => 'The clip moved or is gone'];
        }
        $bin = VideoOptimizer::usableBinary();
        if (! $bin) {
            return ['status' => 'kept', 'note' => 'No video tool here'];
        }

        $in = tempnam(sys_get_temp_dir(), 'vsqi');
        $out = tempnam(sys_get_temp_dir(), 'vsqo') . '.mp4';
        try {
            $from = $disk->readStream($path);
            $to = fopen($in, 'wb');
            stream_copy_to_stream($from, $to);
            fclose($to);
            if (is_resource($from)) {
                fclose($from);
            }
            $before = (int) filesize($in);

            $scale = "scale='min(1280,iw)':'min(720,ih)':force_original_aspect_ratio=decrease,scale=trunc(iw/2)*2:trunc(ih/2)*2";
            $done = self::encode([$bin, '-y', '-hide_banner', '-loglevel', 'error', '-protocol_whitelist', 'file', '-i', $in,
                '-vf', $scale, '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '28', '-threads', '1',
                '-c:a', 'aac', '-b:a', '96k', '-movflags', '+faststart', '-pix_fmt', 'yuv420p', $out]);

            $after = is_file($out) ? (int) filesize($out) : 0;
            $mp4Already = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'mp4';
            $worthIt = $done === null && $after > 0 && ($after < $before || ! $mp4Already);

            if ($worthIt) {
                $stream = fopen($out, 'rb');
                // The same address, so nothing pointing at it changes. An old .mov is an MP4 now, and says so.
                $disk->put($path, $stream, ['ContentType' => 'video/mp4', 'mimetype' => 'video/mp4']);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            self::poster($bin, $path, $worthIt ? $out : $in);

            if ($done !== null) {
                return ['status' => 'kept', 'note' => $done, 'bytesBefore' => $before];
            }

            return $worthIt
                ? ['status' => 'done', 'bytesBefore' => $before, 'bytesAfter' => $after]
                : ['status' => 'kept', 'note' => 'Already as small as this would make it', 'bytesBefore' => $before, 'bytesAfter' => $after];
        } finally {
            @unlink($in);
            @unlink($out);
        }
    }

    /** Run the encode at the lowest priority, watching its memory. Null when it finished, else why not. */
    private static function encode(array $args): ?string
    {
        if (is_file('/usr/bin/nice') || is_file('/bin/nice')) {
            array_unshift($args, 'nice', '-n', '19');
        }
        $p = new Process($args);
        $p->setTimeout(900);
        $p->start();
        while ($p->isRunning()) {
            if (preg_match('~VmHWM:\s+(\d+)~', (string) @file_get_contents('/proc/' . $p->getPid() . '/status'), $m)
                && (int) $m[1] > self::MEMORY_STOP_KB) {
                $p->stop(1);

                return 'Stopped: it needed more memory than this machine can spare';
            }
            usleep(250000);
            try {
                $p->checkTimeout();
            } catch (\Throwable $e) {
                return 'Stopped: it took too long';
            }
        }

        return $p->isSuccessful() ? null : mb_substr('The video tool could not read it: ' . $p->getErrorOutput(), 0, 250);
    }

    /** A thumbnail from the finished file, if the clip has none. A failure here costs nothing. */
    private static function poster(string $bin, string $path, string $file): void
    {
        try {
            if (VideoPoster::stored($path)) {
                return;
            }
            $jpg = tempnam(sys_get_temp_dir(), 'vsqp') . '.jpg';
            try {
                foreach (['1', '0'] as $seek) {
                    $p = new Process([$bin, '-y', '-hide_banner', '-loglevel', 'error', '-threads', '1', '-protocol_whitelist', 'file',
                        '-ss', $seek, '-i', $file, '-frames:v', '1', '-update', '1', '-vf', 'scale=640:-2', '-q:v', '4', $jpg]);
                    $p->setTimeout(120);
                    $p->run();
                    if ($p->isSuccessful() && is_file($jpg) && filesize($jpg) > 0
                        && VideoPoster::keep($path, (string) file_get_contents($jpg))) {
                        return;
                    }
                }
            } finally {
                @unlink($jpg);
            }
        } catch (\Throwable $e) {
            Log::info('VideoSqueeze: no thumbnail', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }

    private static function finish(int $id, array $result): void
    {
        DB::table(self::TABLE)->where('id', $id)->update($result + ['updated_at' => now()]);
    }

    private static function memoryInUse(): int
    {
        return (int) trim((string) @file_get_contents('/sys/fs/cgroup/memory.current'));
    }
}
