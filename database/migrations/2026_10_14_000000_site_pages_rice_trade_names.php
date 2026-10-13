<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Rice trade names (2026-10-14): the names hybrids are sold under (Jackpot
 * 102 for NSIC Rc 666H, SL 8H for NSIC Rc 132H) on their variety pages, from
 * database/rice-varieties/build_pages.py; this loads the rebuilt pages.
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
