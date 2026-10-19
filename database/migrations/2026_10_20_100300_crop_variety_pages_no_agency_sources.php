<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * No agency or institute as our source (2026-10-20): nine crop variety
 * pages still named one as where a fact came from. Four pechays cited an
 * institute's pechay guide "reprinted by" a regional office, two sweet
 * potatoes that office's kamote guide (one for its yield), two corns a
 * regional office's guide, and an ube an agency's planting project. The facts
 * stay, said as our own ("an old and widely used growing guide", "a
 * Mindanao kamote growing guide"), in varieties.json and the page seeds
 * alike, synced here. A page edited in the mother app keeps its edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();
    }

    public function down(): void
    {
        // The pages keep their own words.
    }
};
