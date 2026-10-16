<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Batari is its own brand (2026-10-18, the owner: "batari is different from
 * arize"): the NSIC Rc 406H page says Aljay sells it under its own brand,
 * Batari Indra 01, next to the registry's breeder. The Land Prep Planner's
 * seed preparation for it reads database/land-prep/seedprep.json itself.
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
