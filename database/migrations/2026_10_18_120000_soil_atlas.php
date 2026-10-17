<?php

use App\Support\SoilAtlas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The soil atlas (2026-10-18, the owner): the typical soil of every surveyed
 * province, town and barangay, kept in our own structure and read by the
 * analyses instead of asking the web.
 *
 *  - as_soil_fertility: per place and crop, the soil test classes of the
 *    samples taken there (pH, nitrogen as organic matter, phosphorus,
 *    potassium, zinc where tested), measured numbers where they exist, the
 *    texture, and the usual fertilizer rates by variety type and season.
 *  - as_soil_ph: the rated rice (or corn) land by pH class, per province
 *    (with irrigated and rainfed paddy) and per town (read off the maps).
 *  - as_soil_salinity: coastal land by salinity class at three depths, per
 *    province and town, and the EC of the barangays sampled.
 *
 * Filled from database/soil-atlas/*.json by SoilAtlas::sync() (idempotent:
 * it replaces what is there, in one transaction). A later data release ships
 * new files and a migration that calls sync() again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_soil_fertility')) {
            Schema::create('as_soil_fertility', function (Blueprint $table) {
                $table->id();
                $table->string('level', 10);            // province | town | barangay
                $table->string('province', 120);
                $table->string('town', 120)->nullable();
                $table->string('barangay', 160)->nullable();
                $table->string('crop', 24)->default('rice');
                $table->unsignedInteger('samples')->default(0);
                $table->unsignedInteger('guideSamples')->default(0);
                $table->unsignedSmallInteger('year')->nullable();
                $table->json('data');                   // levels, measured, texture, rates
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['province', 'town', 'barangay'], 'soil_fert_place');
                $table->index(['level', 'crop']);
            });
        }
        if (! Schema::hasTable('as_soil_ph')) {
            Schema::create('as_soil_ph', function (Blueprint $table) {
                $table->id();
                $table->string('level', 10);            // province | town
                $table->string('province', 120);
                $table->string('town', 120)->nullable();
                $table->string('crop', 24)->default('rice');
                $table->unsignedSmallInteger('year')->nullable();
                $table->unsignedInteger('totalHa')->nullable();
                $table->json('data');                   // ha by class, irrigated/rainfed, mapped shares
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['province', 'town'], 'soil_ph_place');
            });
        }
        if (! Schema::hasTable('as_soil_salinity')) {
            Schema::create('as_soil_salinity', function (Blueprint $table) {
                $table->id();
                $table->string('level', 10);            // province | town | barangay
                $table->string('province', 120);
                $table->string('town', 120)->nullable();
                $table->string('barangay', 160)->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->unsignedInteger('samples')->default(0);
                $table->json('data');                   // areas by depth and class, or EC by depth
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->index(['province', 'town', 'barangay'], 'soil_sal_place');
            });
        }
        SoilAtlas::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists('as_soil_salinity');
        Schema::dropIfExists('as_soil_ph');
        Schema::dropIfExists('as_soil_fertility');
    }
};
