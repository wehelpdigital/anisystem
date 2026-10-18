<?php

use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The product book names metamifop right (2026-10-18). The registration
 * list's twenty metamifop herbicides for rice and banana were filed as
 * "Metam sodium", a soil fumigant (the ingredient pattern METAM caught
 * METAMIFOP first), and the one three way mix for glyphosate tolerant corn
 * carried glyphosate only. database/products/pesticides.json is mended
 * (metamifop; mesotrione, glyphosate and S metolachlor) and the pattern in
 * ingredients.json no longer takes METAMIFOP for metam; the book is synced
 * so the Compatibility Checker and the searches read the right ingredient.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_products')) {
            ProductLibrary::sync();
        }
    }

    public function down(): void
    {
        // The rows stay as mended.
    }
};
