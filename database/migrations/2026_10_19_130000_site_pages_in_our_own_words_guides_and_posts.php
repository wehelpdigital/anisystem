<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Our own words (the owner, 2026-10-17): the land preparation, crop, pest and
 * disease guides and the blog posts had their lines that followed a source's
 * wording said our way, the facts, rates and names unchanged. Synced again;
 * a page edited in the mother app keeps its edits (SitePages::sync, editedAt).
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
