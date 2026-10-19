<?php

use App\Support\HowToPlant;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant (2026-10-19): the last 14 fruit trees' watering (atis, cashew,
 * guyabano, malunggay, santol, star apple, tamarind, grape and the rare
 * highland trees), so all 97 guides carry it, in the app and on the pages.
 * Loaded again.
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
