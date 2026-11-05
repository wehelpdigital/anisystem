<?php

namespace App\Console\Commands;

use App\Models\AsSiteSetting;
use App\Support\VideoOptimizer;
use App\Support\VideoPoster;
use App\Support\VideoSqueeze;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Compress the next saved clip. See App\Support\VideoSqueeze.
 *
 * --selftest proves the whole road on this host with a clip of its own, in a folder
 * nothing else uses: it saves one, compresses it, asks the public address what it got
 * back, deletes everything it made, and writes the answer to video.selftest. Set
 * video.selftest_now on the settings shelf to have the next minute's pass run it.
 */
class SqueezeVideos extends Command
{
    protected $signature = 'videos:squeeze {--selftest}';

    protected $description = 'Compress the next clip that was saved as filmed';

    public function handle(): int
    {
        if ($this->option('selftest')) {
            AsSiteSetting::put('video.selftest', json_encode($this->selftest()));

            return self::SUCCESS;
        }

        VideoSqueeze::next();

        return self::SUCCESS;
    }

    private function selftest(): array
    {
        $report = ['at' => now()->toDateTimeString(), 'host' => gethostname()];
        $bin = VideoOptimizer::usableBinary();
        $report['tool'] = $bin;
        if (! $bin) {
            return $report + ['ok' => false, 'why' => 'no tool'];
        }

        $disk = Storage::disk('public');
        $path = 'video-selftest/' . Str::uuid()->toString() . '.mov';
        $local = tempnam(sys_get_temp_dir(), 'vst') . '.mov';
        $rowId = null;
        try {
            // A .mov on purpose: the hard case, an iPhone's clip, whose address must keep working as an MP4.
            $make = new Process([$bin, '-y', '-hide_banner', '-loglevel', 'error',
                '-f', 'lavfi', '-i', 'testsrc2=size=1920x1080:rate=30', '-f', 'lavfi', '-i', 'sine=frequency=440',
                '-t', '4', '-c:v', 'libx264', '-preset', 'ultrafast', '-b:v', '12M', '-threads', '1', '-c:a', 'aac', $local]);
            $make->setTimeout(120);
            $make->run();
            $report['sampleBytes'] = (int) @filesize($local);

            $stream = fopen($local, 'rb');
            $disk->put($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $report['storedBytes'] = $disk->size($path);

            VideoSqueeze::enqueue($path);
            $rowId = (int) DB::table(VideoSqueeze::TABLE)->where('path', $path)->value('id');
            // Ahead of anything else waiting, since this is the only thing asked of this run.
            $t = microtime(true);
            do {
                $did = VideoSqueeze::next();
            } while ($did !== null && $did !== $rowId);
            $report['seconds'] = round(microtime(true) - $t, 1);

            $row = DB::table(VideoSqueeze::TABLE)->where('id', $rowId)->first();
            $report['status'] = $row->status ?? null;
            $report['note'] = $row->note ?? null;
            $report['bytesBefore'] = $row->bytesBefore ?? null;
            $report['bytesAfter'] = $row->bytesAfter ?? null;
            $report['diskBytesNow'] = $disk->size($path);

            $url = \App\Support\MediaStore::url($path);
            $report['url'] = $url;
            $res = Http::timeout(30)->withHeaders(['Range' => 'bytes=0-15'])->get($url);
            $report['publicStatus'] = $res->status();
            $report['publicType'] = $res->header('Content-Type');
            $report['publicRange'] = $res->header('Content-Range');
            // Something a browser can play: "ftyp" in an MP4's first bytes.
            $report['isMp4'] = str_contains(substr($res->body(), 0, 16), 'ftyp');

            $poster = VideoPoster::stored($path);
            $report['poster'] = $poster;
            $report['posterStatus'] = $poster ? Http::timeout(20)->get(\App\Support\MediaStore::url($poster))->status() : null;

            $report['ok'] = ($report['status'] === 'done') && $report['bytesAfter'] < $report['bytesBefore']
                && $report['publicStatus'] < 300 && $report['isMp4'] && str_contains((string) $report['publicType'], 'mp4') && $report['posterStatus'] === 200;

            return $report;
        } catch (\Throwable $e) {
            return $report + ['ok' => false, 'why' => get_class($e) . ' ' . mb_substr($e->getMessage(), 0, 300)];
        } finally {
            @unlink($local);
            $poster = VideoPoster::stored($path);
            if ($poster) {
                \App\Support\MediaStore::delete($poster);
            }
            DB::table('as_video_posters')->where('videoKey', sha1($path))->delete();
            $disk->delete($path);
            if ($rowId) {
                DB::table(VideoSqueeze::TABLE)->where('id', $rowId)->delete();
            }
        }
    }
}
