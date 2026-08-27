<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which thing in the shed an activity's line is actually spending.
 *
 * The lines on an activity are free text — a name, a price, a quantity, a
 * unit — which is right for "hired a tractor" and not enough for "80 kg of
 * Urea", because the second one has to come out of somewhere. This column is
 * the pointer: null for a line that is only a note to yourself, and an
 * inventory item for a line that will move stock when the activity is ticked
 * done.
 *
 * Note what is NOT keyed on. Every save of an activity soft-deletes all its
 * item rows and creates fresh ones with new ids, so a stock ledger keyed to
 * an item row's id would be orphaned by the next edit. The moves are keyed to
 * the ACTIVITY, and this column only says which item each line means.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_activity_items')) {
            return;
        }
        Schema::table('as_schedule_activity_items', function (Blueprint $t) {
            if (! Schema::hasColumn('as_schedule_activity_items', 'inventoryItemId')) {
                $t->integer('inventoryItemId')->nullable()->after('materialId')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_schedule_activity_items')) {
            return;
        }
        Schema::table('as_schedule_activity_items', function (Blueprint $t) {
            if (Schema::hasColumn('as_schedule_activity_items', 'inventoryItemId')) {
                $t->dropColumn('inventoryItemId');
            }
        });
    }
};
