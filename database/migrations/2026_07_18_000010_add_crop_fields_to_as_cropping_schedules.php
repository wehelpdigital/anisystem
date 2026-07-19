<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('as_cropping_schedules', 'cropType')) {
                $table->string('cropType', 100)->nullable()->after('description');
            }
            if (! Schema::hasColumn('as_cropping_schedules', 'cropVariety')) {
                $table->string('cropVariety', 150)->nullable()->after('cropType');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            foreach (['cropType', 'cropVariety'] as $col) {
                if (Schema::hasColumn('as_cropping_schedules', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
