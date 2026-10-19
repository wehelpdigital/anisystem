<?php

use App\Support\HowToPlant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant (2026-10-19): more crops' guides as the research lanes finish,
 * the first multi part steps, and the way names fixed. Loaded again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_how_to_plant')) {
            HowToPlant::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
