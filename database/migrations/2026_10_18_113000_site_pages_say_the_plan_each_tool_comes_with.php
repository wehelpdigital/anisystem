<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The guides and feature pages say the plan each tool comes with
 * (2026-10-18, the owner: "update the pricing table comparison both inside
 * anee and in public site"): NPK Plus is no longer called free (it comes
 * with Libre + Anee and up, and its count uses no credits), the field
 * helpers have no add-on any more, Satellite Weather needs Libre + Anee,
 * offline mode is on every plan, and Anee is a smart farm technician in the
 * body text. 62 pages; none had been edited in the mother app, so every one
 * takes the new words (an edited page would keep its edits, see
 * database/site-pages/README.md).
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
