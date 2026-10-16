<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Batari (2026-10-17): the owner asked for Aljay's Batari as NSIC 2023
 * Rc 750H. The sources put it on NSIC Rc 406H (Mestiso 67, bred by Bayer),
 * sold by Aljay as Batari Indra 01; Rc 750H stays Long Ping's LP 777, sold
 * as Nasiglat. This loads the Rc 406H page with its brand name and what
 * Aljay says about it.
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
