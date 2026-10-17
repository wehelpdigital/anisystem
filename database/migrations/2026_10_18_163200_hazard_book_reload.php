<?php

use App\Support\HazardBook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/*
 * The hazard book again (2026-10-18), now with all the research in: the
 * farm losses of 167 storms, 90 floods, 208 droughts, pests, animal
 * diseases, cold and heat spells, and the eruptions, quakes, surges and
 * landslides. load() matches events by key and replaces their places; a
 * researched event the new build no longer holds is dropped; the refresh's
 * own rows of the running year stay. The cached gazetteer and "ready" are
 * cleared, so no request keeps reading an empty book. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_hazard_events') || ! is_file(database_path('hazards/events.json.gz'))) {
            return;
        }
        HazardBook::load();
        foreach (['gaz:v' . HazardBook::VERSION, 'ready'] as $k) {
            Cache::forget('hazard-book:' . config('database.default') . ':' . $k);
        }
    }

    public function down(): void
    {
        // the tables go with 2026_10_18_163000_hazard_book
    }
};
