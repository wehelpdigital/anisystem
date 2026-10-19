<?php

use App\Support\HowToPlant;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant (2026-10-19): more steps told part by part (soaking, curing,
 * the seedbed, pulling seedlings, paring suckers) across fifty crops, in the
 * app's guides and on their public pages. Loaded again.
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
