<?php

use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The product book's plain materials (2026-10-18): urea, ammosul, potash,
 * lime, borax, potassium silicate and the other plain fertilizers and
 * chemicals with their chemistry, and the spray helpers by kind, from
 * database/products/materials.json, synced with the rest of the book.
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
