<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Pests and diseases (2026-10-07): the profiles speak as "we" throughout (a
 * few said "I found no threshold"). Pages edited in the mother app keep their
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
