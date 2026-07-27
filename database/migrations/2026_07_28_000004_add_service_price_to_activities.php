<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A "service" activity (activityType = 'service') is a hired job applied to a
 * lot — it carries a single price. Stored on the activity itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_activities', 'servicePrice')) {
                $table->decimal('servicePrice', 12, 2)->nullable()->after('waterTask');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (Schema::hasColumn('as_schedule_activities', 'servicePrice')) {
                $table->dropColumn('servicePrice');
            }
        });
    }
};
