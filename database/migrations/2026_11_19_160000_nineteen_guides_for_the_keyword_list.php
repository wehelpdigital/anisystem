<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The owner's keyword list of 2026-11-19 ("create an seo optimized blog post as well for the following, make sure that
 * it is highly optimized"): the searches that had no page of their own get one, nineteen blog guides, each promoting
 * Anee: fertilizer for flowering plants, why fertilizer is important, crop planting, corn scientific name, top dressing
 * fertilizer, Osmocote fertilizer, chicken dung as fertilizer, eggshell as fertilizer, organic fertilizer benefits,
 * fertilizer in Tagalog, fertilizer high in phosphorus, ammonium nitrate fertilizer, hybrid rice, when fertilization
 * occurs, palay seedlings, crop yield, vegetable farming, soil degradation and drip irrigation system. Their nearest
 * older guides link to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();
        SitePages::forgetCaches();
    }

    public function down(): void
    {
        // The pages stay.
    }
};
