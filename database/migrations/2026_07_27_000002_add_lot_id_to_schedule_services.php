<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A service can optionally be tied to a single lot (e.g. drone spray on Lot B).
 * Null means it applies to the whole schedule / no particular lot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_services', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_services', 'lotId')) {
                $table->unsignedBigInteger('lotId')->nullable()->after('croppingScheduleId');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_services', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_services', 'lotId')) {
                $table->dropColumn('lotId');
            }
        });
    }
};
