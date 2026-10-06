<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Crop Pests and Crop Diseases become catalogues like the weeds (2026-10-07):
 * a profile page per pest and disease of Philippine rice, corn, vegetables
 * and fruit and plantation crops, and four guides (database/site-pages/pests,
 * diseases). Pages edited in the mother app keep their edits; see
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
