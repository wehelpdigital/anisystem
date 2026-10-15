<?php

namespace App\Console\Commands;

use App\Support\FarmLots;
use Illuminate\Console\Command;

/**
 * Global Lots' six month check (2026-10-16, the owner): rings the bell for
 * every lot whose answers are six months old, once a cycle
 * (App\Support\FarmLots::remind). Daily, from the scheduler.
 */
class LotsRemindCommand extends Command
{
    protected $signature = 'lots:remind';

    protected $description = 'Ask owners to check lots whose answers are six months old';

    public function handle(): int
    {
        $n = FarmLots::remind();
        $this->info($n . ' lot reminder' . ($n === 1 ? '' : 's') . ' sent.');

        return self::SUCCESS;
    }
}
