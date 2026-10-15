<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The seed companies' own profiles (2026-10-16, the owner): every Dekalb
 * hybrid on Bayer's list and every NK corn on Syngenta's, with their days,
 * yields, stands, disease packages and traits, the new NK6414 VIP page, and
 * Syngenta's and Bayer's Arize rice hybrids with what each seller says
 * (NK5017 and S6003 with their fertilizer plans, AZ 8433 DT with its dual
 * trait). This loads the pages.
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
