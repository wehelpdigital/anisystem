<?php

namespace App\Console\Commands;

use App\Support\Trash;
use Illuminate\Console\Command;

/**
 * The global Trash's clock (2026-10-23, the owner): files the deletes a
 * mass update made (App\Support\Trash::sweep), and deletes for good what has
 * been in the Trash for 30 days (--purge). From the scheduler.
 */
class TrashCommand extends Command
{
    protected $signature = 'trash:tick {--purge : Also delete for good what has been in the Trash for 30 days}';

    protected $description = 'File new deletes in the Trash, and empty what is 30 days old';

    public function handle(): int
    {
        $n = Trash::sweep();
        $this->info($n . ' deleted ' . ($n === 1 ? 'thing' : 'things') . ' filed.');
        if ($this->option('purge')) {
            $gone = Trash::purgeDue();
            $this->info($gone . ' deleted for good.');
        }

        return self::SUCCESS;
    }
}
