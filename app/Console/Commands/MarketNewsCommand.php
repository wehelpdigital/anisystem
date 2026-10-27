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
    protected $signature = 'market:news {--limit=60 : Stories to read at most} {--dry : Ask but keep nothing} {--check : Only send one small request on the reader\'s key and keep the answer}';

    protected $description = 'Read the new farm news for import and price policy events';

    public function handle(): int
    {
        if ($this->option('check')) {
            // One small request on the reader's key, its answer kept for the deploy check (never the key).
            $settings = \App\Models\AiSetting::newsReader() ?? \App\Models\AiSetting::current()->cheap('low');
            $r = app(\App\Services\AiClient::class)->ask($settings, [], 'Reply with the word ok.', null, 64, ['system' => 'You answer in one word.', 'timeout' => 60]);
            $out = ['at' => now('Asia/Manila')->toIso8601String(), 'via' => $settings->provider . ' ' . $settings->effectiveModel(), 'ok' => (bool) ($r['ok'] ?? false),
                'answer' => mb_substr((string) ($r['text'] ?? ''), 0, 40), 'error' => $r['error'] ?? null, 'tokensIn' => (int) ($r['tokensIn'] ?? 0), 'tokensOut' => (int) ($r['tokensOut'] ?? 0)];
            \App\Models\AsSiteSetting::put('market.news_check', json_encode($out));
            $this->line(json_encode($out));

            return ($out['ok'] ? self::SUCCESS : self::FAILURE);
        }
        $s = MarketNews::scan(max(1, (int) $this->option('limit')), (bool) $this->option('dry'));
        $this->info('read ' . $s['read'] . ', new events ' . $s['events'] . ', newsrooms added ' . $s['joined'] . ($s['failed'] ? ', failed asks ' . $s['failed'] : '') . ($s['via'] ? ' (on ' . $s['via'] . ')' : ''));
        foreach ($s['made'] as $m) {
            $this->line('  ' . $m);
        }

        return self::SUCCESS;
    }
}
