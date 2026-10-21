<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The sitaw page's photo: a farmer carrying freshly picked long beans
 * instead of the busy shot of vines on poles (2026-10-21).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
