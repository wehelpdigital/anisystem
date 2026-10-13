<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Rice variety sources (2026-10-13): the owner asked that PhilRice be named
 * as one of the sources, and that no page names PhilRice's variety app. The
 * pages are rebuilt by database/rice-varieties/build_pages.py; this loads them.
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
