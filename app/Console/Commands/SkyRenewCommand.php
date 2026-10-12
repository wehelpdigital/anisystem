<?php

namespace App\Console\Commands;

use App\Support\SkyPass;
use Illuminate\Console\Command;

/** Satellite Weather's weekly renewals (2026-10-13): every few minutes, the passes about to end. */
class SkyRenewCommand extends Command
{
    protected $signature = 'sky:renew';

    protected $description = 'Satellite Weather: renew the weekly passes that are about to end, from the farm credits';

    public function handle(): int
    {
        $r = SkyPass::renewDue();
        $this->info($r['renewed'] . ' renewed, ' . $r['lapsed'] . ' ended for lack of credits.');

        return self::SUCCESS;
    }
}
