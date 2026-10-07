<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Land preparation (2026-10-07): each of the fifteen guides gets a hero photo
 * of its own (freely licensed, from Wikimedia Commons, credited on the page)
 * in place of the five shared stock pictures. Pages edited in the mother app
 * keep their edits; see database/site-pages/README.md.
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
