<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The Land Prep Planner becomes Anee's (2026-10-14, the owner): its feature
 * page now says what the planner asks (method, variety, lots, crew, soil,
 * field history, place), the seed soaking by variety, the crew calendar and
 * the send to the Protocol Builder, and which plans have it. This loads it.
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
