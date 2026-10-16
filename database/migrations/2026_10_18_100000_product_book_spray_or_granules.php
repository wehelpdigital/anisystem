<?php

use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The product book again (2026-10-18): the Compatibility Checker now asks
 * first whether the products go on as a spray or as granules spread
 * together, so every plain material says how it is used (on the soil, on
 * the leaves, through the drip), and the search offers what fits first.
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
