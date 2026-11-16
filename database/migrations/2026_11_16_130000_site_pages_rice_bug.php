<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The rice bug, atangya and alitangya, in English and Tagalog (2026-11-16, the owner: "contents about rice bug,
 * atangya, ... alitangya are high on google ... we are not visible ... English and tagalog"). Three new pages
 * (/blog/alitangya-in-english, /blog/insecticide-for-rice-bug, /blog/atangya-sa-palay), and the rice bug, rice black
 * bug and when to spray guides brought up to date, synced here. A page edited in the mother app keeps its edits.
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
        // The words stay.
    }
};
