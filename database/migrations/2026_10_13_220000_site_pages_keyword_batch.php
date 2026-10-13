<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Twenty nine posts from the owner's 2026-10-13 contents and keywords folders:
 * the questions farmers put to ChatGPT and Gemini about fertilizer for palay
 * and farm management apps, and the new keywords (fertilizer as a head term,
 * palay drawing, the PalayCheck key checks, palayisdaan, palay drying, garden
 * fertilizers). Existing pages gained the keywords mapped to them and links
 * to the new posts. Pages edited in the mother app keep their edits; see
 * database/site-pages/README.md.
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
