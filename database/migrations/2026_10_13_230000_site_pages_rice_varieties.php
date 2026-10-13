<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Rice varieties (2026-10-13): one page per variety in PhilRice's public seed
 * database (database/site-pages/rice-varieties, made by
 * database/rice-varieties/build_pages.py), and the finder at /rice-varieties.
 * Pages edited in the mother app keep their edits; see
 * database/site-pages/README.md.
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
