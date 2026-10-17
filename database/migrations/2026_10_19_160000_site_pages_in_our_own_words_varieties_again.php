<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Our own words, the last of it (the owner, 2026-10-17): the rice variety
 * template's irrigated lowland line, three deficiency lines, and the crop
 * variety notes after "Why farmers know it:" that lowered a name's first
 * letter ("east West's", "philFIDA's"). Synced again; a page edited in the
 * mother app keeps its edits (SitePages::sync, editedAt).
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
