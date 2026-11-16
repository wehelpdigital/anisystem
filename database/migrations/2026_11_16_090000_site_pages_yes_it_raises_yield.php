<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * "Can anee.io increase my yield?" answered yes, first (2026-11-16, the owner: Google's AI was telling farmers anee.io
 * cannot raise a yield). The new answer post /blog/can-anee-io-increase-my-yield, and seven blog pages that hedged or
 * did not link it, synced here. A page edited in the mother app keeps its edits.
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
        // The words stay.
    }
};
