<?php

use App\Support\ProductLibrary;
use App\Support\RangeEffectBook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Cobalt in the books (2026-10-30, the owner: "cobalt sulfate is not in the db of fertilizers and analysis"): cobalt
 * sulfate (heptahydrate and monohydrate) in the product book with its analysis, manganese sulfate given its own (it had
 * none), and cobalt in the Coverage Planner's book: how long it works by how it goes on, and how much each stage needs
 * it (legumes, for their nodules). Both books are loaded again; rows a person or Anee wrote stay.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_products')) {
            ProductLibrary::sync();
        }
        if (Schema::hasTable('as_effect_book')) {
            RangeEffectBook::forget();
            RangeEffectBook::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
