<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A lot can carry a typed address (barangay, zone, town/municipality, province).
 * Town + province are what we geocode for the local weather forecast; barangay
 * and zone are kept for display.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_lots', 'locBarangay')) {
                $table->string('locBarangay', 120)->nullable()->after('variety');
            }
            if (! Schema::hasColumn('as_schedule_lots', 'locZone')) {
                $table->string('locZone', 60)->nullable()->after('locBarangay');
            }
            if (! Schema::hasColumn('as_schedule_lots', 'locTown')) {
                $table->string('locTown', 120)->nullable()->after('locZone');
            }
            if (! Schema::hasColumn('as_schedule_lots', 'locProvince')) {
                $table->string('locProvince', 120)->nullable()->after('locTown');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_lots', function (Blueprint $table) {
            foreach (['locBarangay', 'locZone', 'locTown', 'locProvince'] as $col) {
                if (Schema::hasColumn('as_schedule_lots', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
