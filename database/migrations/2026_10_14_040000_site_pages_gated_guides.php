<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Gated guides (2026-10-14): the LCC Checker and Land Prep Planner feature
 * pages, five new rice blog guides (leaf color chart, fertilizer schedule,
 * alternate wetting and drying, when to spray, when to harvest), and fuller
 * rice land preparation, pest and disease guides. This loads them.
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
