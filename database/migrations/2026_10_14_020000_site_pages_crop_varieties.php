<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Crop varieties (2026-10-14): one page per variety of every crop but rice,
 * from the NSIC registry and the varieties farmers buy
 * (database/site-pages/crop-varieties, made by
 * database/crop-varieties/build_pages.py). The finders at /crop-varieties are
 * drawn by the app from database/crop-varieties. Pages edited in the mother
 * app keep their edits; see database/site-pages/README.md.
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
