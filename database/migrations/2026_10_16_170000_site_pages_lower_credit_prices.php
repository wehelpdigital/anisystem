<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Credits at 4x to 6x what a run costs (2026-10-16, the owner): the feature
 * pages that say a price say the new one (Analyze So Far about 30, Realign
 * by Anee 20, the Satellite Weather week pass 45). This loads the pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();
    }

    public function down(): void
    {
        // The pages stay.
    }
};
