<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The mobile audit (2026-10-07): Anee called the smart farm technician in
 * the guides, and two feature guides filed under Weather and Crop care.
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
