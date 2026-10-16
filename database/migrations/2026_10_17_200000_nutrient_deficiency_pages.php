<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Nutrient deficiencies (2026-10-16, the owner): one page per crop, every
 * deficiency of it a section, and one page per nutrient, under
 * /nutrient-deficiencies (database/site-pages/nutrient-deficiencies).
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
