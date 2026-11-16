<?php

use App\Support\FieldCatalogue;
use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The product book's labels whole again (2026-11-16, the owner: "rice bug usually gets popular insecticide which is
 * gold rush however it is not in the recommended products. why? ... research on possible issues like this").
 *
 * The import of the registered product list had cut 1,989 label lines short, at about 80 characters or at a line
 * break in the list's cell: Goldrush 370 SC lost "rice leaf folder, rice stemborer", a long row of cypermethrin
 * labels lost "rice bug". database/products/pesticides.json has the official words back (and a few misspellings
 * mended, "semborer" to stemborer); the field catalogue's products name the 402 pests and diseases those words
 * restore, and each entry's ingredient lists are recounted. Both books are loaded again; rows a person or Anee wrote
 * stay.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_products')) {
            ProductLibrary::sync();
        }
        if (Schema::hasTable('as_crop_problems')) {
            FieldCatalogue::sync();
        }
    }

    public function down(): void
    {
        // The words stay.
    }
};
