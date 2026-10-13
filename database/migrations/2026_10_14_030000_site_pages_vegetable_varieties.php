<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Vegetable varieties (2026-10-14): the seed farmers buy (Diamante Max, Galaxy,
 * Casino, Smooth Green and 150 more) added to the crop variety pages, and rice
 * folded into Crop varieties. Rebuilt by
 * database/crop-varieties/build_pages.py; this loads them.
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
        // The pages stay.
    }
};
