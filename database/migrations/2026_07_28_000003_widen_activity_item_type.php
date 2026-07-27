<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Free-form activity items use itemType = 'custom', which the original
 * ENUM('material','service') column rejects. Widen it to a nullable varchar
 * so legacy values and the new 'custom' rows coexist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_schedule_activity_items', 'itemType')) {
            DB::statement("ALTER TABLE `as_schedule_activity_items` MODIFY `itemType` VARCHAR(20) NULL");
        }
    }

    public function down(): void
    {
        // Left as varchar — reverting to the ENUM could truncate 'custom' rows.
    }
};
