<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Three varieties the owner asked for (2026-10-15): the new page for
 * Syngenta's NK313 VIP corn, and the brand names on two rice hybrids,
 * LAV 777 on NSIC Rc 656H and Nasiglat on NSIC Rc 750H. This loads them.
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
