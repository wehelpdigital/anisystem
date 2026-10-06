<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The weeds pages credit PhilRice by name only (2026-10-06, owner's call):
 * no mention of the app the catalogue came from, and its source links make
 * way for PhilRice's weed book. Pages edited in the mother app keep their
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
