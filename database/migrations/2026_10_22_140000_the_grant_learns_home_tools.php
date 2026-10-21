<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The tools on a worker's home screen (2026-10-22, the owner: "in the workers
 * add also management switches for the tools in the home or dashboard").
 *
 * One column, the tools the owner switched OFF for this login, as a JSON list
 * of App\Support\WorkerTools keys. Empty means every tool, which is what each
 * login already had, so no row is touched; a tool added to the home screen
 * later is on until the owner turns it off.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_worker_grants') || Schema::hasColumn('as_worker_grants', 'toolsOff')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->text('toolsOff')->nullable()->after('voiceAccess');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_worker_grants', 'toolsOff')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->dropColumn('toolsOff');
        });
    }
};
