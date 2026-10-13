<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Five posts from the 2026-10-13 competitor gap audit and the owner's asks:
 * the plant disease identification app, an app to identify plant problems,
 * farm management software, a farm record keeping app against a notebook,
 * and anee.io's own review. Pages edited in the mother app keep their edits;
 * see database/site-pages/README.md.
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
