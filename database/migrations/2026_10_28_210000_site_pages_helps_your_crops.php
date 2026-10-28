<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The feature pages' new section says how each tool helps your crops and your farm, the same words as the tool windows
 * (the owner, 2026-10-28: "it should also explain how it helps your crops and your farm"). Synced here; a page edited
 * in the mother app keeps its edits.
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
