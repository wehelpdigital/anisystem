<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Land preparation (2026-10-07): fifteen guides under /land-preparation, one
 * per crop group (palay transplanted and direct seeded, mais, vegetables,
 * onion and garlic, cucurbits, root crops, legumes, sugarcane, banana, fruit
 * trees) and three for problem soils (sodic and alkaline, acidic, saline),
 * following IRRI, PhilRice and DA practice. Pages edited in the mother app
 * keep their edits; see database/site-pages/README.md.
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
