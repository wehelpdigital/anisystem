<?php

namespace App\Console\Commands;

use App\Support\SitePages;
use Illuminate\Console\Command;

/**
 * Load the shipped guides, blog and feature pages (database/site-pages) into
 * as_site_pages. Pages edited in the mother app keep their edits.
 *
 *   php artisan site-pages:sync
 *
 * A release that changes the page files ships a migration that calls
 * SitePages::sync() too, so a deploy brings the site up to date on its own.
 */
class SyncSitePages extends Command
{
    protected $signature = 'site-pages:sync';

    protected $description = 'Load the shipped public site pages into the database';

    public function handle(): int
    {
        $n = SitePages::sync();
        $this->info("created {$n['created']}, updated {$n['updated']}, kept (edited in the mother app) {$n['kept']}");

        return self::SUCCESS;
    }
}
