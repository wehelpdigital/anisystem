<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant pages show their own crop (2026-10-21, the owner: "right now
 * the photos used are wrong"): 96 pages took their group's picture or the
 * generic planting one; each now has a free licensed photo of that crop
 * (database/how-to-plant/photos.json, public/images/site/how-to-plant),
 * credited under the picture. Rice keeps its transplanting photo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
