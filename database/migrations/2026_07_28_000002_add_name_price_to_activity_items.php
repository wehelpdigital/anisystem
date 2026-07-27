<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activity items are now free-form: a name, price, quantity and unit typed
 * straight into the activity (the standalone Materials/Services catalogs are
 * gone). Item names + the prices used for them are remembered per schedule so
 * they can be re-selected. Legacy materialId/serviceId rows still resolve
 * their name via the relations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activity_items', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_activity_items', 'itemName')) {
                $table->string('itemName', 255)->nullable()->after('serviceId');
            }
            if (! Schema::hasColumn('as_schedule_activity_items', 'unitPrice')) {
                $table->decimal('unitPrice', 12, 2)->nullable()->after('itemName');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activity_items', function (Blueprint $table) {
            foreach (['itemName', 'unitPrice'] as $col) {
                if (Schema::hasColumn('as_schedule_activity_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
