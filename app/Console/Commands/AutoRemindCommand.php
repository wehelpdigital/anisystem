<?php

namespace App\Console\Commands;

use App\Support\AutoNudge;
use Illuminate\Console\Command;

/**
 * Anee asks a quiet farmer back (2026-10-28, the owner): the email (auto_inactive) and the bell for every running
 * Anee Auto season quiet for a few days with work waiting (App\Support\AutoNudge::run). Daily, from the scheduler.
 */
class AutoRemindCommand extends Command
{
    protected $signature = 'auto:remind';

    protected $description = 'Remind farmers whose Anee Auto season has been quiet for a few days';

    public function handle(): int
    {
        $n = AutoNudge::run();
        $this->info($n . ' reminder' . ($n === 1 ? '' : 's') . ' sent.');

        return self::SUCCESS;
    }
}
