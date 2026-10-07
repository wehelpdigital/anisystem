<?php

namespace App\Console\Commands;

use App\Support\Stash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * The Stash's files, brought home (2026-10-07): each pending item's PDF is
 * downloaded from its partner's site and kept on the media disk, a few per
 * run. The scheduler runs it every ten minutes; with nothing pending it
 * ends at once. On a machine whose media disk is not the bucket it refuses,
 * so a laptop never marks a row ready with a file only it has.
 */
class StashFetchCommand extends Command
{
    protected $signature = 'stash:fetch {--limit=6 : files per run} {--local : allow a local media disk}';

    protected $description = 'Download pending Stash PDFs into the media disk';

    public function handle(): int
    {
        if (config('filesystems.disks.public.driver') !== 's3' && ! $this->option('local')) {
            $this->warn('The media disk is not the bucket here; run it on the server (or pass --local).');

            return self::SUCCESS;
        }
        $rows = DB::table('as_stash_items')->where('deleteStatus', 1)->whereIn('status', ['pending', 'failed'])->where('tries', '<', 5)
            ->whereNotNull('sourcePdf')->orderBy('tries')->orderBy('sortOrder')->limit(max(1, (int) $this->option('limit')))->get();
        if ($rows->isEmpty()) {
            $this->info('Nothing to fetch.');

            return self::SUCCESS;
        }
        $disk = Storage::disk('public');
        foreach ($rows as $r) {
            DB::table('as_stash_items')->where('id', $r->id)->update(['tries' => $r->tries + 1, 'updated_at' => now()]);
            $tmp = tempnam(sys_get_temp_dir(), 'stash');
            try {
                $res = Http::timeout(900)->connectTimeout(30)->retry(2, 2000, throw: false)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; anee.io Stash; +https://anee.io)', 'Accept' => 'application/pdf,*/*'])
                    ->withOptions(['sink' => $tmp])->get($r->sourcePdf);
                $size = @filesize($tmp) ?: 0;
                $head = (string) @file_get_contents($tmp, false, null, 0, 5);
                if (! $res->successful() || $size < 1000 || $head !== '%PDF-') {
                    throw new \RuntimeException('HTTP ' . $res->status() . ', ' . $size . ' bytes' . ($head !== '%PDF-' ? ', not a PDF' : ''));
                }
                $path = Stash::pdfPath($r->partner, $r->slug);
                $in = fopen($tmp, 'rb');
                $ok = $disk->writeStream($path, $in, ['ContentType' => 'application/pdf', 'visibility' => 'public']);
                if (is_resource($in)) {
                    fclose($in);
                }
                if (! $ok) {
                    throw new \RuntimeException('The media disk refused the file.');
                }
                DB::table('as_stash_items')->where('id', $r->id)->update(['pdfPath' => $path, 'bytes' => $size, 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
                $this->info('ready  ' . $r->slug . ' (' . round($size / 1048576, 1) . ' MB)');
            } catch (\Throwable $e) {
                DB::table('as_stash_items')->where('id', $r->id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
                $this->warn('failed ' . $r->slug . ': ' . $e->getMessage());
            } finally {
                @unlink($tmp);
            }
        }

        return self::SUCCESS;
    }
}
