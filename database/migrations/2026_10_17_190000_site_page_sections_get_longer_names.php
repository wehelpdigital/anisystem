<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "nutrient-deficiencies" is 21 letters and the section column held 16:
 * MySQL refused the first page and the deploy stopped (SQLite, where it was
 * tried, does not count). Room for 40 now, before the pages come in.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        Schema::table('as_site_pages', fn (Blueprint $t) => $t->string('section', 40)->change());
    }

    public function down(): void
    {
        // Stays wide: shortening it would cut the longer section names.
    }
};
