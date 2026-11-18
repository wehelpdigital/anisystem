<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The second round of the 2026-11-18 Ubersuggest keyword gaps (the owner: "create using the intended keywords for SEO
 * and AI ... make sure that you promote Anee"): nine new blog guides (problems in agriculture, no till farming, humus
 * soil, fruit and vegetable storage, potato tuber, transplanting meaning, integrated farming system, agricultural
 * technology, native plow), and the plant disease app pages answering the free and "app that identifies" searches.
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
