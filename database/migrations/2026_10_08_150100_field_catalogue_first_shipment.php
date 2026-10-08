<?php

use App\Support\FieldCatalogue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The field catalogue's first rows (2026-10-08): every crop's pests,
 * diseases, weeds and weed control plans from database/field-catalogue.
 * A later release that changes those files adds a migration like this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_crop_problems')) {
            return;
        }
        FieldCatalogue::sync();
    }

    public function down(): void
    {
        // The rows stay.
    }
};
