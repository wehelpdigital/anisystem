<?php

use App\Support\HowToPlant;
use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant (2026-10-19, the owner: "in how to plant also include watering
 * recommendations and schedules"): each guide gains its watering, how farmers
 * here water the crop and a schedule by stage (how often, how much), the stages
 * that must never go dry, the seasons and the signs of too little and too much
 * water; in the app after the steps and as a Watering section on the public
 * pages. The first crops: rice (all three ways), the corns, kamote, cassava,
 * pechay, cabbage, banana, coconut and mango. Loaded again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_how_to_plant')) {
            HowToPlant::sync();
        }
        if (Schema::hasTable('as_site_pages')) {
            SitePages::sync();
        }
    }

    public function down(): void
    {
        // The rows stay.
    }
};
