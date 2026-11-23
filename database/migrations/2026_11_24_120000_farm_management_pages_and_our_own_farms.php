<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-11-24: the farm management guides link the two new landing pages (best farm management software, and farm
 * management in the Philippines), and the anee.io review says what the About page now says: anee.io started on our
 * own farms to get the most yield from them, and we share it with every farmer (it used to say "client farms").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();
        SitePages::forgetCaches();
    }

    public function down(): void
    {
        // The words stay.
    }
};
