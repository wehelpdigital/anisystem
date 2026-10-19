<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Bigante Plus is NSIC Rc 548H, not Rc 314H (2026-10-20, the owner: "yes,
 * fix all"). The Rc 548H registration papers name the hybrid "Bigante Plus,
 * essentially derived from Mestiso 4" (Rc 124H, Arize Bigante), the registry
 * list says the same, and Bayer's own figures for it (111 to 115 days, 114 cm)
 * are Rc 548H's. The rice book (database/rice-varieties/varieties.json, from
 * build_data.py's TRADE_FIX, DOUBTFUL_TRADE and SELLER_MOVE) moves the trade
 * names and Bayer's profile to Rc 548H; Rc 314H keeps no trade name. Its two
 * pages are synced here; a page edited in the mother app keeps its edits.
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
        // The pages keep the right names.
    }
};
