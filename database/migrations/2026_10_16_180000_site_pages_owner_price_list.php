<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The owner's price list (2026-10-16): the feature
 * pages that say a price say the owner's (Analyze So Far 50, Realign
 * by Anee 30, the Satellite Weather week pass 50). This loads the pages.
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
