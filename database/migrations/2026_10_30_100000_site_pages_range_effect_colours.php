<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The Range Effect guide (2026-10-30): the calendar's colours (in the soil, taken up, strongest, easing, fading) and
 * granular or foliar for a try, synced here. A page edited in the mother app keeps its edits.
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
