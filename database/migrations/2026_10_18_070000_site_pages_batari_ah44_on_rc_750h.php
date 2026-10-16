<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Batari AH44 is NSIC 2023 Rc 750H (2026-10-18, the owner): Aljay's Batari
 * brand on Long Ping's LP 777, so the Rc 750H page lists it among the names
 * it is sold as, and the Rc 406H page (Batari Indra 01) no longer claims the
 * bare word "Batari". The Land Prep Planner reads its seed preparation from
 * database/land-prep/seedprep.json itself.
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
