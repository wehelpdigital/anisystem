<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The rights sheet gains an Inventory row.
 *
 * The shed was an owner-only door with no lock on it: the hub hid the tile
 * for workers but the routes answered anyone. Now it is a grant level like
 * Notes and Reports. Existing grants start at 'none' — that is what the
 * hidden tile always claimed — and the owner opens it from the Workers
 * module when they mean to.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_worker_grants', 'inventoryAccess')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->string('inventoryAccess', 8)->default('none')->after('reportsAccess');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_worker_grants', 'inventoryAccess')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->dropColumn('inventoryAccess');
        });
    }
};
