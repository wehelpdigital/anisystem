<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The field helpers are no longer free (2026-10-14, the owner): the Pest
 * Finder, the Disease Finder, the Weed Control Helper and the Variety Finder
 * come with Libre plus Anee and every plan above it, or a year's add-on on
 * Libre, so the guides and feature pages stop calling them free. The LCC
 * Checker page says the planting date it now asks for. This loads them.
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
