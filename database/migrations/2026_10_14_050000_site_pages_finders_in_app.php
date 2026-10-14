<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The finders move into anee.io (2026-10-14, the owner): the Pest Finder,
 * the Disease Finder and the weed control helper leave /pests, /diseases and
 * /weeds, so the guides that linked to them there now link to the feature
 * page, and the crop problem guides say how much of a guide a guest reads.
 * This loads them.
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
