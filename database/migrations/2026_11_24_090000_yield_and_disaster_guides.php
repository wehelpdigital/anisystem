<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The owner, 2026-11-23: "when you search in google how to increase yield or contents similar to these, anee is not in
 * the results. i want you to create contents pertaining to these keywords, make sure that you promote anee in it ...
 * also have options for filipino or ph on it as well".
 *
 * Six English guides on raising the harvest and guarding it (how to increase crop yield, vegetable yield, drought
 * resistant crops, lodging in rice, crop protection, climate resilient agriculture) and nine Filipino guides: a twin
 * of each, plus twins of the rice and corn yield guides and of "can anee.io increase my yield". SitePages::TWINS ties
 * each pair with a language switch and hreflang links. Older yield and weather guides link to the new ones.
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
