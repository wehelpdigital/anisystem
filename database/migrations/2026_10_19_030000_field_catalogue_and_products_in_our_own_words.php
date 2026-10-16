<?php

use App\Support\FieldCatalogue;
use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Our own words (the owner, 2026-10-17): the field catalogue's hints and weed
 * steps and the product book's notes and mixing notes that followed a
 * source's wording were said our way, the facts unchanged. Both books are
 * loaded again from their files; rows a person or Anee wrote (source other
 * than file) are left as they are by both syncs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_crop_problems')) {
            FieldCatalogue::sync();
        }
        if (Schema::hasTable('as_products')) {
            ProductLibrary::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
