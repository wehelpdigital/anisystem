<?php

namespace App\Console\Commands;

use App\Models\AsSiteSetting;
use App\Support\Newsletter\Audience;
use App\Support\Newsletter\NewsEmail;
use App\Support\Newsletter\Planner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * The newsletter's clock (2026-10-17), every ten minutes. Each pass:
 *   1. closes what can no longer go (yesterday's news, a passed day);
 *   2. turns this morning's farm news page into the news email, once;
 *   3. brings the Acumbamail lists in line with who is on which plan;
 *   4. reads one list's unsubscribes and spreads them to the other lists;
 *   5. sends any high priority email whose exact time has come;
 *   6. after the send time and the morning's news, makes the day's
 *      campaigns, once a day (Planner::runDaily).
 * With the switch off (newsletter.enabled), step 6 writes the day's plan
 * as a dry run and nothing is sent.
 */
class NewsletterTick extends Command
{
    protected $signature = 'newsletter:tick';

    protected $description = 'Keep the newsletter lists in step and send the day\'s emails when it is time';

    public function handle(): int
    {
        if (! Schema::hasTable('as_nl_emails')) {
            $this->warn('The newsletter tables are not there yet.');

            return self::SUCCESS;
        }
        AsSiteSetting::put('newsletter.tick', now()->toDateTimeString());
        $now = Planner::now();

        $this->step('expire', fn () => Planner::expire($now) . ' closed');
        $this->step('news', fn () => ($e = NewsEmail::ensure($now)) ? 'email #' . $e->id : 'no page today');
        $this->step('sync', function () {
            $s = Audience::sync(null, 45);

            return "added {$s['added']}, removed {$s['removed']}, failed {$s['failed']}" . ($s['errors'] ? ' (' . implode('; ', $s['errors']) . ')' : '');
        });
        $this->step('unsubscribes', function () {
            $p = Audience::pullUnsubscribes();

            return "{$p['list']}: {$p['gone']} gone, {$p['newly']} new" . ($p['error'] ? " ({$p['error']})" : '');
        });
        $this->step('timed', fn () => json_encode(Planner::runTimed()));
        $this->step('daily', function () {
            $r = Planner::runDaily();

            return $r['status'] . (isset($r['reason']) ? ': ' . $r['reason'] : '') . (isset($r['campaigns']) ? ', ' . $r['campaigns'] . ' campaigns' : '');
        });

        return self::SUCCESS;
    }

    private function step(string $name, \Closure $job): void
    {
        try {
            $this->line(str_pad($name, 13) . $job());
        } catch (\Throwable $e) {
            report($e);
            $this->error(str_pad($name, 13) . 'failed: ' . $e->getMessage());
        }
    }
}
