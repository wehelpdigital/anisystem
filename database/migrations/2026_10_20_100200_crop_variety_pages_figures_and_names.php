<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The crop variety book's own slips (2026-10-20, the owner: "yes, fix all"),
 * fixed in build_data.py and nsic.py and so in varieties.json, and the 97
 * pages they touch, synced here:
 *  - cacao clones were "approved" in 2199 and 3110: the sheet has no year
 *    column and its "Yield (kg/tree/year)" was read as one. That column is
 *    kilograms of dry beans a hectare (the papers' dry beans a tree, times
 *    the trees a hectare), now each clone's yield; its "pod index (cm)" is
 *    pods for 1 kg of dry beans;
 *  - decimal points lost or moved (LuzonFed G6's stalks 232 cm thick are
 *    2.32, mungbean fat of "51 percent" is 0.51 percent and more), and the
 *    figures that cannot be settled left out;
 *  - root crops above 40 t/ha keep their yields (PSB Cv 10's 44.8);
 *  - VC 5 is PSB Cv 10, one cassava: its page leaves the site (deleteStatus
 *    0, the row kept) and its address answers 301 with PSB Cv 10's
 *    (SitePages::MOVED);
 *  - SG 94 17 17 is UPL Sp 14, as its nomination papers name it; the list
 *    gave the name to SG 96 35 03 too, which loses it until its own papers
 *    settle it.
 * A page edited in the mother app keeps its edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        DB::table('as_site_pages')->where('section', 'crop-varieties')->where('slug', 'cassava-vc-5')->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);
        SitePages::sync();
    }

    public function down(): void
    {
        // The pages keep the right figures.
    }
};
