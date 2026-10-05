<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Every tool on /how-it-works gets its own /features page (27 new files in
 * database/site-pages/features, 2026-10-05). Pages edited in the mother app
 * keep their edits; see database/site-pages/README.md.
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
