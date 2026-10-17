<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * A year of a plan saves at least 10% against twelve months (the owner,
 * 2026-10-19). Libre + Anee (17%) and Solo Farmer (25%) already did; Farm
 * Owner's year goes from 6,500 to 6,480 (12 x 600 less 10%), and fifteen
 * blog posts that quoted 6,500 now say 6,480. Synced here; a page edited in
 * the mother app keeps its edits. The Technician's Blog articles follow.
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
        // The new price stays.
    }
};
