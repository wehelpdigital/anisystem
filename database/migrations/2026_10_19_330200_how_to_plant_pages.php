<?php

use App\Support\HowToPlant;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant on the public site (2026-10-19, the owner): /how-to-plant and a
 * page per crop (database/site-pages/how-to-plant, gated for guests like the
 * other guides, crawlable), the crop guides linking them back, and the
 * guides again with their parts and new crops.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_how_to_plant')) {
            HowToPlant::sync();
        }
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
