<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Pests and diseases follow up (2026-10-07): six profiles with more sources,
 * and the older pest and disease pages linking to the new catalogue. Pages
 * edited in the mother app keep their edits; see database/site-pages/README.md.
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
