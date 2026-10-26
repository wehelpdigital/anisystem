<?php

namespace App\Console\Commands;

use App\Support\MarketNews;
use Illuminate\Console\Command;

/**
 * The morning's farm news, read for the market (2026-10-27): the stories the
 * feeds brought that tell of imports, tariffs, bans and the like become
 * market events (App\Support\MarketNews). Scheduled after the news is
 * fetched; safe to run again, a story is read once.
 */
class MarketNewsCommand extends Command
{
    protected $signature = 'market:news {--limit=60 : Stories to read at most} {--dry : Ask but keep nothing}';

    protected $description = 'Read the new farm news for import and price policy events';

    public function handle(): int
    {
        $s = MarketNews::scan(max(1, (int) $this->option('limit')), (bool) $this->option('dry'));
        $this->info('read ' . $s['read'] . ', new events ' . $s['events'] . ', newsrooms added ' . $s['joined'] . ($s['failed'] ? ', failed asks ' . $s['failed'] : ''));
        foreach ($s['made'] as $m) {
            $this->line('  ' . $m);
        }

        return self::SUCCESS;
    }
}
