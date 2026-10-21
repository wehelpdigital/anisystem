<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The rights sheet gains an Observations row.
 *
 * What came off the season was the owner's alone (2026-08-23): no grant
 * opened it, and the Workers panel had nothing to say about it. Now it is a
 * grant level like the Inventory. Existing grants start at 'none', which is
 * what every worker had, and the owner opens it from the Workers module when
 * they mean to.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_worker_grants') || Schema::hasColumn('as_worker_grants', 'observationsAccess')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->string('observationsAccess', 8)->default('none')->after('inventoryAccess');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_worker_grants', 'observationsAccess')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->dropColumn('observationsAccess');
        });
    }
};
