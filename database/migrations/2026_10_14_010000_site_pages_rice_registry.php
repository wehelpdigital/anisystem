<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Rice varieties from the NSIC registry (2026-10-14): the 2026 releases
 * (Arize AZ8445DT, LP 756, PhilSCAT 21 and the rest), Golden Rice, the trial
 * days and yields of the newest varieties, and the owner's brand name fixes
 * (SL-22H for Rc 758H, LP 908 for Rc 696H). Rebuilt by
 * database/rice-varieties/build_pages.py; this loads them.
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
