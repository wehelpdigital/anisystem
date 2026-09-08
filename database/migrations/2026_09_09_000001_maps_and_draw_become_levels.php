<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Maps and Draw stop being switches and take the schedule's own
 * none/view/edit, like Notes, Reports and the Inventory before them.
 *
 * The old booleans are carried over faithfully: a switch that was on
 * becomes 'edit' (that is what it granted), a switch that was off stays
 * a closed door.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_worker_grants')) {
            return;
        }

        foreach (['mapsAccess', 'drawAccess'] as $col) {
            // tinyint → varchar first, so 0/1 become '0'/'1' instead of
            // being squeezed straight into an enum's index space.
            DB::statement("ALTER TABLE as_worker_grants MODIFY {$col} VARCHAR(10) NOT NULL DEFAULT '0'");
            DB::statement("UPDATE as_worker_grants SET {$col} = CASE WHEN {$col} IN ('1') THEN 'edit' ELSE 'none' END");
            DB::statement("ALTER TABLE as_worker_grants MODIFY {$col} ENUM('none','view','edit') NOT NULL DEFAULT 'none'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_worker_grants')) {
            return;
        }

        foreach (['mapsAccess', 'drawAccess'] as $col) {
            DB::statement("ALTER TABLE as_worker_grants MODIFY {$col} VARCHAR(10) NOT NULL DEFAULT 'none'");
            DB::statement("UPDATE as_worker_grants SET {$col} = CASE WHEN {$col} = 'none' THEN '0' ELSE '1' END");
            DB::statement("ALTER TABLE as_worker_grants MODIFY {$col} TINYINT(1) NOT NULL DEFAULT 0");
        }
    }
};
