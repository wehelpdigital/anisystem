<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * Temporary, 2026-11-06: what the live host can do with ffmpeg, measured before
 * the app is allowed to lean on it. Transcoding runs inside a web request on a
 * machine other people are using, so its speed, its memory and how long the
 * front door waits all decide whether it is safe. Remove with its route.
 */
class VideoToolProbe
{
    public const KEY = 'vtp-71c4e9a2d05b';

    private const SAMPLE_SECONDS = 6;

    public static function facts(): array
    {
        $bin = base_path('bin/ffmpeg');
        $read = fn (string $p) => is_readable($p) ? trim((string) @file_get_contents($p)) : null;
        preg_match('~MemTotal:\s+(\d+)~', (string) @file_get_contents('/proc/meminfo'), $total);

        $facts = [
            'arch' => php_uname('m'),
            'sapi' => PHP_SAPI,
            'php' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'fetched' => is_file($bin),
            'bytes' => is_file($bin) ? filesize($bin) : 0,
            'runnable' => is_executable($bin),
            'release' => $read(base_path('bin/ffmpeg.release')),
            'memoryMax' => $read('/sys/fs/cgroup/memory.max') ?? $read('/sys/fs/cgroup/memory/memory.limit_in_bytes'),
            'memoryNow' => $read('/sys/fs/cgroup/memory.current'),
            'memoryPeak' => $read('/sys/fs/cgroup/memory.peak'),
            'cpuMax' => $read('/sys/fs/cgroup/cpu.max'),
            'cpus' => substr_count((string) @file_get_contents('/proc/cpuinfo'), "processor\t"),
            'memTotalMb' => isset($total[1]) ? (int) ($total[1] / 1024) : null,
            'tmp' => sys_get_temp_dir(),
            'tmpFreeMb' => (int) (@disk_free_space(sys_get_temp_dir()) / 1048576),
            'phpMemory' => ini_get('memory_limit'),
            'maxExecution' => ini_get('max_execution_time'),
            'nice' => is_file('/usr/bin/nice') || is_file('/bin/nice'),
        ];

        if ($facts['fetched']) {
            $v = new Process([$bin, '-hide_banner', '-version']);
            $v->setTimeout(20);
            $v->run();
            $facts['version'] = strtok($v->getOutput(), "\n");
            $facts['versionExit'] = $v->getExitCode();
            $e = new Process([$bin, '-hide_banner', '-encoders']);
            $e->setTimeout(20);
            $e->run();
            $facts['encoders'] = array_values(array_filter(['libx264', 'libwebp', 'aac', 'libmp3lame'], fn ($n) => str_contains($e->getOutput(), $n)));
        }

        return $facts;
    }

    /** The app's real encode, on a noisy 1080p sample shaped like field footage. */
    public static function encode(int $threads, bool $nice): array
    {
        $bin = base_path('bin/ffmpeg');
        if (! is_file($bin)) {
            return ['error' => 'no ffmpeg here'];
        }
        $src = tempnam(sys_get_temp_dir(), 'vtpi') . '.mp4';
        $out = tempnam(sys_get_temp_dir(), 'vtpo') . '.mp4';

        try {
            $t0 = microtime(true);
            $make = new Process([$bin, '-y', '-hide_banner', '-loglevel', 'error',
                '-f', 'lavfi', '-i', 'testsrc2=size=1920x1080:rate=30', '-f', 'lavfi', '-i', 'sine=frequency=440',
                '-t', (string) self::SAMPLE_SECONDS, '-vf', 'noise=alls=18:allf=t',
                '-c:v', 'libx264', '-preset', 'ultrafast', '-b:v', '14M', '-threads', '1', '-c:a', 'aac', '-b:a', '128k', $src]);
            $make->setTimeout(100);
            $make->run();
            $madeIn = round(microtime(true) - $t0, 1);
            if (! $make->isSuccessful()) {
                return ['error' => 'sample failed', 'exit' => $make->getExitCode(), 'err' => mb_substr($make->getErrorOutput(), 0, 400), 'madeIn' => $madeIn];
            }

            $scale = "scale='min(1280,iw)':'min(720,ih)':force_original_aspect_ratio=decrease,scale=trunc(iw/2)*2:trunc(ih/2)*2";
            $args = [$bin, '-y', '-hide_banner', '-loglevel', 'error', '-i', $src, '-vf', $scale,
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '28'];
            if ($threads > 0) {
                array_push($args, '-threads', (string) $threads);
            }
            array_push($args, '-c:a', 'aac', '-b:a', '96k', '-movflags', '+faststart', '-pix_fmt', 'yuv420p', $out);
            if ($nice) {
                array_unshift($args, 'nice', '-n', '19');
            }

            $t1 = microtime(true);
            $p = new Process($args);
            $p->setTimeout(150);
            $p->start();
            $peak = 0;
            while ($p->isRunning()) {
                if (preg_match('~VmHWM:\s+(\d+)~', (string) @file_get_contents('/proc/' . $p->getPid() . '/status'), $m)) {
                    $peak = max($peak, (int) $m[1]);
                }
                usleep(100000);
            }
            $took = microtime(true) - $t1;

            return [
                'threads' => $threads,
                'nice' => $nice,
                'exit' => $p->getExitCode(),
                'err' => mb_substr($p->getErrorOutput(), 0, 300),
                'sampleSeconds' => self::SAMPLE_SECONDS,
                'madeIn' => $madeIn,
                'seconds' => round($took, 1),
                'timesRealtime' => round(self::SAMPLE_SECONDS / max($took, 0.01), 2),
                'oneMinuteClipWouldTake' => round(60 * $took / self::SAMPLE_SECONDS),
                'peakMb' => (int) ($peak / 1024),
                'inMb' => round(filesize($src) / 1048576, 1),
                'outMb' => is_file($out) ? round(filesize($out) / 1048576, 1) : 0,
                'cgroupPeak' => is_readable('/sys/fs/cgroup/memory.peak') ? trim((string) file_get_contents('/sys/fs/cgroup/memory.peak')) : null,
            ];
        } finally {
            @unlink($src);
            @unlink($out);
        }
    }

    /** How long the front door waits on a slow answer. */
    public static function wait(int $seconds): array
    {
        @set_time_limit(0);
        $t = microtime(true);
        sleep(min(max($seconds, 1), 130));

        return ['slept' => round(microtime(true) - $t, 1)];
    }
}
