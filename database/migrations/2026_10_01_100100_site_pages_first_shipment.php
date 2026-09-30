<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The first shipment of guides, crop problems, blog and feature pages
 * (database/site-pages). A later release that changes those files ships a
 * migration like this one; pages edited in the mother app are left alone.
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
        // The pages stay: they may have been edited since.
    }
};
