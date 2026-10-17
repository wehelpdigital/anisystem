<?php

use App\Support\ProductLibrary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Two more product book lines in our own words (Kristalon MgS's note and the
 * copper EDTA mixing note), found against every source we hold (the owner,
 * 2026-10-17). The book is loaded again; rows a person or Anee wrote stay.
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
        // The rows stay.
    }
};
