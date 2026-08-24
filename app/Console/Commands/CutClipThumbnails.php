<?php

namespace App\Console\Commands;

use App\Models\AsCroppingSchedule;
use App\Support\MediaStore;
use App\Support\SeasonMedia;
use App\Support\VideoOptimizer;
use App\Support\VideoPoster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give every clip already in the system a frame to show.
 *
 * A clip uploaded from now on gets one as it is stored. Everything filmed
 * before that — a season's gallery going back months, the wall, the
 * discussions — has none, and a picker full of films shows a picker full of
 * clapperboards.
 *
 * This walks what the app knows about and cuts the missing ones, once each.
 * A clip that already has a frame is not re-cut: where a row already records
 * one, or a table already carries one beside the clip, the frame is simply
 * remembered so nothing asks again.
 *
 *   php artisan clips:thumbnails                 everything missing one
 *   php artisan clips:thumbnails --limit=20      a bite at a time
 *   php artisan clips:thumbnails --dry-run       say what it would do
 */
class CutClipThumbnails extends Command
{
    protected $signature = 'clips:thumbnails
        {--limit=0 : Stop after this many frames are cut (0 = no limit)}
        {--dry-run : List what is missing without cutting anything}';

    protected $description = 'Cut and keep a thumbnail frame for every clip that has none';

    private int $cut = 0;
    private int $remembered = 0;
    private int $already = 0;
    private int $failed = 0;
    private int $seen = 0;

    public function handle(): int
    {
        if (! VideoOptimizer::usableBinary() && ! $this->option('dry-run')) {
            $this->error('No ffmpeg on this machine — a frame cannot be cut here.');
            $this->line('Run this where ffmpeg is installed, or set FFMPEG_BIN.');

            return self::FAILURE;
        }

        $this->info('Walking the clips this app knows about…');

        // The seasons' galleries: the list the picker itself offers.
        foreach (AsCroppingSchedule::query()->where('deleteStatus', 1)->cursor() as $schedule) {
            foreach (SeasonMedia::all($schedule) as $m) {
                if (($m['kind'] ?? '') !== 'video') {
                    continue;
                }
                $this->take(
                    MediaStore::pathFromUrl($m['url'] ?? null),
                    MediaStore::pathFromUrl($m['posterUrl'] ?? null)
                );
                if ($this->done()) {
                    return $this->say();
                }
            }
        }

        // The community's own tables, where a clip sits in a column.
        $pairs = [
            ['as_community_wall_posts', 'videoPath', 'videoPoster'],
            ['as_community_wall_comments', 'videoPath', 'videoPoster'],
            ['as_community_group_posts', 'videoPath', 'videoPoster'],
            ['as_community_group_replies', 'videoPath', 'videoPoster'],
            ['as_community_messages', 'videoPath', 'videoPoster'],
            ['as_community_reels', 'videoPath', 'videoPoster'],
        ];
        foreach ($pairs as [$table, $vid, $poster]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $vid)) {
                continue;
            }
            $hasPoster = Schema::hasColumn($table, $poster);
            $rows = DB::table($table)->whereNotNull($vid)->select([$vid, $hasPoster ? $poster : DB::raw('NULL as ' . $poster)]);
            foreach ($rows->cursor() as $row) {
                $this->take($row->{$vid} ?? null, $hasPoster ? ($row->{$poster} ?? null) : null);
                if ($this->done()) {
                    return $this->say();
                }
            }
        }

        return $this->say();
    }

    /** One clip: remember the frame it has, or cut the one it needs. */
    private function take(?string $video, ?string $poster): void
    {
        $video = trim((string) $video);
        if ($video === '') {
            return;
        }
        $this->seen++;

        if (VideoPoster::stored($video)) {
            $this->already++;

            return;
        }

        if ($poster) {
            if (! $this->option('dry-run')) {
                VideoPoster::remember($video, $poster);
            }
            $this->remembered++;
            $this->line('  kept  ' . $this->shorten($video));

            return;
        }

        if ($this->option('dry-run')) {
            $this->cut++;
            $this->line('  needs ' . $this->shorten($video));

            return;
        }

        $made = VideoPoster::ensure($video);
        if ($made) {
            $this->cut++;
            $this->line('  cut   ' . $this->shorten($video));
        } else {
            $this->failed++;
            $this->warn('  none  ' . $this->shorten($video) . '  (could not be read)');
        }
    }

    private function done(): bool
    {
        $limit = (int) $this->option('limit');

        return $limit > 0 && $this->cut >= $limit;
    }

    private function shorten(string $path): string
    {
        return strlen($path) > 64 ? '…' . substr($path, -63) : $path;
    }

    private function say(): int
    {
        $this->newLine();
        $this->info(sprintf(
            '%d clips seen · %d cut · %d already had one · %d remembered · %d could not be read',
            $this->seen,
            $this->cut,
            $this->already,
            $this->remembered,
            $this->failed
        ));

        return self::SUCCESS;
    }
}
