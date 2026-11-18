<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The 2026-11-18 Ubersuggest keyword gaps: five new guides (soil erosion, irrigation systems, organic farming,
 * sugarcane farming, high value crops), "vegetable farming" on the vegetables guide, and "an upo plant" in the bottle
 * gourd page's harvest question. Rows edited in the mother app keep their edits (SitePages::sync).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();
        SitePages::forgetCaches();
    }

    public function down(): void
    {
        // The pages stay.
    }
};
