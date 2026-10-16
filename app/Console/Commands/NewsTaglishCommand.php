<?php

namespace App\Console\Commands;

use App\Models\AsSitePage;
use App\Support\NewsTaglish;
use App\Support\SitePages;
use Illuminate\Console\Command;

/**
 * The farm news pages written before their "so what" cards had a Taglish tab
 * (2026-10-17, the owner: "do this for all existing ones in there"): each
 * card titled NewsRoundup::SO_WHAT without a textTl gets one, one Flash ask
 * per page (NewsTaglish::fill, ledger kind news-taglish). Safe to run again:
 * a card that has its Taglish is never asked for or changed, a page with
 * nothing missing is not touched, and no other block is changed.
 *
 *   php artisan news:taglish            every roundup page
 *   php artisan news:taglish --page=185 one page (repeat --page for more)
 *   php artisan news:taglish --dry      say what would be asked, ask nothing
 */
class NewsTaglishCommand extends Command
{
    protected $signature = 'news:taglish {--page=* : Only these page ids} {--dry : Count the cards that need it, ask nothing}';

    protected $description = 'Give every news roundup\'s "so what" cards their Taglish tab (the ones that have none)';

    public function handle(): int
    {
        $q = AsSitePage::query()->where('kind', 'roundup')->where('deleteStatus', 1)->orderBy('id');
        if ($ids = array_filter(array_map('intval', (array) $this->option('page')))) {
            $q->whereIn('id', $ids);
        }
        $dry = (bool) $this->option('dry');
        $sum = ['pages' => 0, 'cards' => 0, 'asked' => 0, 'filled' => 0, 'in' => 0, 'out' => 0];
        foreach ($q->get() as $page) {
            $blocks = is_array($page->blocks) ? $page->blocks : [];
            $cards = NewsTaglish::stories($blocks);
            $missing = array_filter(array_keys($cards), fn ($k) => trim((string) ($blocks[$k]['textTl'] ?? '')) === '');
            $sum['cards'] += count($cards);
            if (! $missing) {
                $this->line("#{$page->id} {$page->slug}: " . count($cards) . ' cards, all in Taglish already');

                continue;
            }
            if ($dry) {
                $this->line("#{$page->id} {$page->slug}: " . count($missing) . ' of ' . count($cards) . ' cards need Taglish');
                $sum['asked'] += count($missing);

                continue;
            }
            $res = NewsTaglish::fill($blocks, (int) $page->id);
            $sum['asked'] += $res['asked'];
            $sum['filled'] += $res['filled'];
            $sum['in'] += (int) ($res['result']['tokensIn'] ?? 0);
            $sum['out'] += (int) ($res['result']['tokensOut'] ?? 0);
            if ($res['filled'] > 0) {
                // Only the blocks change (updated_at with them: the Tech Blog redraws its article from it).
                $page->blocks = $res['blocks'];
                $page->save();
                $sum['pages']++;
            }
            $this->line("#{$page->id} {$page->slug}: {$res['filled']} of {$res['asked']} cards now in Taglish"
                . ' (' . (int) ($res['result']['tokensIn'] ?? 0) . ' in, ' . (int) ($res['result']['tokensOut'] ?? 0) . ' out)');
        }
        if ($sum['pages']) {
            SitePages::forgetCaches();
        }
        $this->info(($dry ? 'Would ask for ' : 'Filled ') . ($dry ? $sum['asked'] : $sum['filled'] . ' of ' . $sum['asked']) . ' cards ('
            . $sum['cards'] . ' "so what" cards in all), ' . $sum['pages'] . ' pages saved, tokens ' . $sum['in'] . ' in / ' . $sum['out'] . ' out.');

        return self::SUCCESS;
    }
}
