<?php

use App\Support\HowToPlant;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant (2026-10-19): watering for 83 crops, every grain, legume, root,
 * vegetable and industrial crop and 18 fruit trees, in the app and on the
 * public pages. The other fruit trees follow when their research lands. Loaded
 * again.
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
