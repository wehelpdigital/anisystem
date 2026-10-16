<?php

namespace App\Console\Commands;

use App\Models\AsNlEmail;
use App\Support\Newsletter\SpamCheck;
use Illuminate\Console\Command;

/**
 * The spam check for the emails that do not have one yet, or whose words
 * changed since (2026-10-17). The newsletter's clock does a few each pass;
 * this does as many as asked at once, for the first fill.
 */
class NewsletterSpamCheck extends Command
{
    protected $signature = 'newsletter:spamcheck {--limit=500 : How many emails to check at most}';

    protected $description = 'Give the newsletter emails without a current spam check their SpamAssassin score';

    public function handle(): int
    {
        $n = SpamCheck::fillBadges(max(1, (int) $this->option('limit')), function (AsNlEmail $e, array $r) {
            $this->line(sprintf('#%d %-8s %4s  %s', $e->id, $r['level'], $r['score'] === null ? '-' : number_format($r['score'], 1), mb_substr($e->title, 0, 70)));
        });
        $this->info($n . ' checked.');

        return self::SUCCESS;
    }
}
