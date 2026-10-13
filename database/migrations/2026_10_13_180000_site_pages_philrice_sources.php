<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Sources moved off pinoyrice.com (2026-10-13), whose certificate no longer
 * matches its name: 28 guides now cite PhilRice's own copies on
 * philrice.gov.ph (PalayCheck 2022, the field guides, flash cards and
 * handouts), with the claims that lost their only source re-sourced or
 * reworded. The El Nino and planting window posts now date PAGASA's outlook
 * and point to its monthly alert. Pages edited in the mother app keep their
 * edits; see database/site-pages/README.md.
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
