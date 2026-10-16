<?php

use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The product book again (2026-10-18): grades in the names and notes taken
 * from the companies' pages keep their hyphens (17-0-17, not "17 to 0 to
 * 17"), and analyses written as oxides (CaO, MgO) are counted as the element.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_products')) {
            return;
        }
        ProductLibrary::sync();
    }

    public function down(): void
    {
        // The rows stay.
    }
};
