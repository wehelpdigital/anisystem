<?php

use App\Support\HazardBook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/*
 * The hazard book's load (2026-10-18): every town's, province's and region's
 * twenty years of storms, floods, droughts and the rest, from the committed
 * files in database/hazards (our own structure, built from open data by our
 * own scripts), then the year lines. Rehearsed on a strict local MariaDB in
 * about 35 seconds; idempotent (events by key, their places replaced), so a
 * second run changes nothing. The schema migration's down() drops it all.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_hazard_events') || ! is_file(database_path('hazards/events.json.gz'))) {
            return;
        }
        HazardBook::load();
    }

    public function down(): void
    {
        // the tables go with 2026_10_18_163000_hazard_book
    }
};
