<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Nutrient deficiencies, second pass (2026-10-19, the owner: "deeply research
 * as well all diseases, all nutrient defficiency products, and so on and
 * update it to the database. prioritize on local companies in ph").
 *
 * The finder reads its two files directly (DeficiencyCatalogue), so they go
 * live with the deploy:
 *  - database/field-catalogue/fertilizers.json: 1,202 registered products
 *    (was 138) that really supply a nutrient, each with the nutrients it is
 *    made for or carries, its material, use and the company's origin, ranked
 *    Philippine companies first by DeficiencyCatalogue::productsFor;
 *  - database/field-catalogue/deficiencies.json: 584 entries (silicon in rice
 *    and sugarcane, molybdenum in peanut and mungbean are new), every fix
 *    with a rate, spray rates per 16 liter sprayer, timing, what makes it
 *    worse, prevention and a soil or leaf test, no source named.
 * This migration brings the public pages along: the crop and nutrient pages
 * of /nutrient-deficiencies rebuilt from those files, and the new
 * silicon-deficiency page. Pages edited in the mother app keep their edits.
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
