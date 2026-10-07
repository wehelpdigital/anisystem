<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The five tools of 2026-10-07 get their feature guides: Satellite
 * Analysis, Satellite Weather, NPK Plus, the pest and disease finders and
 * the Stash. Pages edited in the mother app keep their edits; see
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
