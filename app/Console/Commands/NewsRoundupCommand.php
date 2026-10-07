<?php

namespace App\Console\Commands;

use App\Services\NewsRoundup;
use Illuminate\Console\Command;

/**
 * The farm news roundup, asked by the scheduler every morning (7 AM Manila).
 * NewsRoundup decides: it writes only every few days (news.every_days) and
 * only when the feeds hold enough new stories, and logs each ask in
 * as_news_runs. --force writes one now, as the mother's "Write one now" does.
 * The same run can be asked over the web: GET /cron/news-roundup?key=...
 */
class NewsRoundupCommand extends Command
{
    protected $signature = 'news:roundup {--force : Write one now, whatever the days since the last}';

    protected $description = 'Write the Latest in Agriculture news roundup when one is due';

    public function handle(NewsRoundup $news): int
    {
        [$run, $why] = $news->open('schedule', (bool) $this->option('force'));
        if (! $run) {
            $this->info('Skipped: ' . $why);

            return self::SUCCESS;
        }
        $this->info('Run ' . $run . ' started.');
        $news->work($run);
        $this->info('Run ' . $run . ' finished.');

        return self::SUCCESS;
    }
}
