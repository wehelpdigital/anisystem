<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Five blog pictures' alt texts placed the photo "at PhilRice", "at IRRI"
 * or at "an IRRI demonstration": the alt text is ours, so it names the place,
 * not the body (the owner, 2026-10-17). The photo credit lines are left as
 * they are. Synced again; a page edited in the mother app keeps its edits.
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
