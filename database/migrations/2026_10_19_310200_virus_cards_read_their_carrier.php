<?php

use App\Support\FieldCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * A virus's card reads its carrier's own list (2026-10-19): rice tungro no
 * longer warns against pymetrozine and buprofezin, nor bunchy top against
 * dimethoate and acephate, since those lines could contradict the green
 * leafhopper's and the banana aphid's own lists shown beside them. The
 * catalogue is loaded again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_crop_problems')) {
            FieldCatalogue::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
