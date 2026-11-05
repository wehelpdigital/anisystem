<?php

namespace App\Console\Commands;

use App\Models\AsSiteSetting;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * What this host can do with bin/ffmpeg, written to video.check on the settings shelf.
 *
 * The app does not use the tool yet. Compressing a clip has to happen away from a web
 * request (the front door hangs up at 20 seconds) on a machine other people are using
 * (1 CPU, 512 MB), so speed and memory decide whether it is safe. Run from the scheduler,
 * because that is where the real job would run: video.check_now on the shelf makes the
 * next minute's pass run this once. No web address reaches it.
 *
 * Low priority and one thread, as the real job would be. It stops the encode itself if
 * memory climbs past MEMORY_STOP_KB, rather than finding out what the host does at 512.
 */
class VideoToolCheck extends Command
{
    protected $signature = 'videos:tool-check';

    protected $description = 'Measure ffmpeg on this host and write the result to video.check';

    private const SAMPLE_SECONDS = 6;

    private const MEMORY_STOP_KB = 300 * 1024;

    public function handle(): int
    {
        $bin = base_path('bin/ffmpeg');
        $report = [
            'at' => now()->toDateTimeString(),
            'host' => gethostname(),
            'fetched' => is_file($bin),
            'arch' => php_uname('m'),
            'memoryMax' => $this->read('/sys/fs/cgroup/memory.max'),
            'memoryBefore' => $this->read('/sys/fs/cgroup/memory.current'),
            'cpuMax' => $this->read('/sys/fs/cgroup/cpu.max'),
            'tmpFreeMb' => (int) (@disk_free_space(sys_get_temp_dir()) / 1048576),
        ];

        if ($report['fetched']) {
            $src = tempnam(sys_get_temp_dir(), 'vtci') . '.mp4';
            $out = tempnam(sys_get_temp_dir(), 'vtco') . '.mp4';
            try {
                // Noisy 1080p, the hard case: a phone filming leaves in the wind.
                $made = $this->measure([$bin, '-y', '-hide_banner', '-loglevel', 'error',
                    '-f', 'lavfi', '-i', 'testsrc2=size=1920x1080:rate=30', '-f', 'lavfi', '-i', 'sine=frequency=440',
                    '-t', (string) self::SAMPLE_SECONDS, '-vf', 'noise=alls=18:allf=t',
                    '-c:v', 'libx264', '-preset', 'ultrafast', '-b:v', '14M', '-threads', '1', '-c:a', 'aac', '-b:a', '128k', $src], 200);
                $report['sample'] = $made + ['mb' => is_file($src) ? round(filesize($src) / 1048576, 1) : 0];

                if (($made['exit'] ?? 1) === 0) {
                    foreach (['veryfast', 'ultrafast'] as $preset) {
                        @unlink($out);
                        $scale = "scale='min(1280,iw)':'min(720,ih)':force_original_aspect_ratio=decrease,scale=trunc(iw/2)*2:trunc(ih/2)*2";
                        $done = $this->measure([$bin, '-y', '-hide_banner', '-loglevel', 'error', '-i', $src, '-vf', $scale,
                            '-c:v', 'libx264', '-preset', $preset, '-crf', '28', '-threads', '1',
                            '-c:a', 'aac', '-b:a', '96k', '-movflags', '+faststart', '-pix_fmt', 'yuv420p', $out], 300);
                        $report[$preset] = $done + [
                            'timesRealtime' => round(self::SAMPLE_SECONDS / max($done['seconds'], 0.01), 2),
                            'oneMinuteClipSeconds' => (int) round(60 * $done['seconds'] / self::SAMPLE_SECONDS),
                            'outMb' => is_file($out) ? round(filesize($out) / 1048576, 1) : 0,
                        ];
                    }
                }
            } finally {
                @unlink($src);
                @unlink($out);
            }
        }

        $report['memoryAfter'] = $this->read('/sys/fs/cgroup/memory.current');
        $report['memoryPeak'] = $this->read('/sys/fs/cgroup/memory.peak');
        $report['memoryEvents'] = $this->read('/sys/fs/cgroup/memory.events');
        $report['cpuStat'] = $this->read('/sys/fs/cgroup/cpu.stat');

        AsSiteSetting::put('video.check', json_encode($report));
        $this->line(json_encode($report));

        return self::SUCCESS;
    }

    /** One run at the lowest priority, watching its memory. */
    private function measure(array $args, int $timeout): array
    {
        if (is_file('/usr/bin/nice') || is_file('/bin/nice')) {
            array_unshift($args, 'nice', '-n', '19');
        }
        $started = microtime(true);
        $p = new Process($args);
        $p->setTimeout($timeout);
        $p->start();
        $peak = 0;
        $stopped = false;
        while ($p->isRunning()) {
            // Under nice the pid is ffmpeg itself: nice replaces itself with what it runs.
            if (preg_match('~VmHWM:\s+(\d+)~', (string) @file_get_contents('/proc/' . $p->getPid() . '/status'), $m)) {
                $peak = max($peak, (int) $m[1]);
            }
            if ($peak > self::MEMORY_STOP_KB) {
                $stopped = true;
                $p->stop(1);
                break;
            }
            usleep(100000);
        }

        return [
            'exit' => $p->getExitCode(),
            'err' => mb_substr($p->getErrorOutput(), 0, 300),
            'seconds' => round(microtime(true) - $started, 1),
            'peakMb' => (int) ($peak / 1024),
            'stoppedForMemory' => $stopped,
        ];
    }

    private function read(string $path): ?string
    {
        return is_readable($path) ? trim((string) @file_get_contents($path)) : null;
    }
}
