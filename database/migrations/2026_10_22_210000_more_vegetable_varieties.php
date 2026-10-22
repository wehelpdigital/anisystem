<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * More vegetable varieties (2026-10-22, the owner: "the variety research
 * especially in vegetables are still incomplete. I can see varieties sold in
 * Shopee that are not yet in the list"). 182 varieties that Filipino farmers
 * buy as seed today, Philippine companies first: Allied Botanical's Condor
 * line, Ramgo and Harbest's Seeds of Hope from their own catalogues, then
 * East-West Seed's best sellers, Kaneko and Known-You as Philippine farm shops
 * (goedenph.com, agrify.ph) sell them. Each carries its days to harvest,
 * fruit, yield, resistance and where it grows, as the seller gives them, in
 * our own words. Every one is a new page under /crop-varieties: chili 15,
 * eggplant 14, tomato 13, lettuce 12, squash 12, cucumber 11, sweet and
 * glutinous corn 11, ampalaya 8, bell pepper 8, carrot 8, sitao 8,
 * watermelon 8, melon 7, Chinese cabbage 7, onion 7, radish 6, cabbage 5,
 * pechay 4, snap bean 4, cauliflower 3, mustard 3, patola 3, okra 2,
 * broccoli, celery and soybean 1 each. Fortuner and Jadeite gained the
 * seller's day count. The notes live in database/crop-varieties/research and
 * fold onto varieties.json with build_data.py --extend.
 *
 * Also: the crop word after a variety's name is capitalized in every title
 * and H1 ("DK XPT 9674 Corn: Yield, Maturity and Characteristics"), and some
 * pages name the pests and diseases the field catalogue has gained since they
 * were built. A variety known only from its seller no longer says "no day
 * count is published" under the seller's own day count: it gives the seller's
 * figures and says no trial checks them yet (73 pages had nothing at all
 * and keep the old line). A page edited in the mother app keeps its edits.
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
        // The pages keep their own words.
    }
};
