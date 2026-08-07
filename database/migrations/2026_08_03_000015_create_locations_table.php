<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A gazetteer of Philippine places (provinces, cities/municipalities and
 * barangays) used to power @location tagging suggestions — independent of which
 * members have saved a location. Seeded by `php artisan locations:import`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_locations')) {
            return;
        }

        Schema::create('as_locations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 12);                 // province | city | barangay
            $table->string('name', 160);                // place name (what you type)
            $table->string('label', 200);               // display: "Brgy X, City, Province"
            $table->string('province', 160)->nullable();
            $table->string('city', 160)->nullable();
            $table->string('slug', 190)->unique();
            $table->unsignedTinyInteger('sort')->default(2); // 0 province,1 city,2 barangay
            $table->timestamps();

            $table->index(['name', 'sort']);
            $table->index('province');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_locations');
    }
};
