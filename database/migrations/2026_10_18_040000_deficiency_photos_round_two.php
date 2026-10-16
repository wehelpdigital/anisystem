<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Deficiency photos, round two (2026-10-17, the owner: "not all deficiencies
 * have images. can you deeply research online for these images?"): 50 more
 * crops' own photos from open access papers and image datasets, every licence
 * read on its source page (CC0, CC BY, CC BY SA only), and two Commons photos
 * dropped that had been copied from a company site. The 76 nutrient
 * deficiency pages that gained a photo are synced; the in-app finder reads
 * database/field-catalogue/deficiency-photos.json itself.
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
