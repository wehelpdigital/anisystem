<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The rights sheet gains a Money row (2026-10-22, the owner).
 *
 * Until now every worker who could see the board saw its money (task costs,
 * worker pay, the day's cash, extra expenses, income, prices), and every one
 * who could edit the board could add expenses and income. Existing grants
 * keep exactly that, so nothing changes under anybody's feet: a board editor
 * gets 'edit', a board reader 'view'. A new login starts at 'none'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_worker_grants') || Schema::hasColumn('as_worker_grants', 'moneyAccess')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->string('moneyAccess', 8)->default('none')->after('observationsAccess');
        });
        DB::table('as_worker_grants')->where('scheduleAccess', 'edit')->update(['moneyAccess' => 'edit']);
        DB::table('as_worker_grants')->where('scheduleAccess', 'view')->update(['moneyAccess' => 'view']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('as_worker_grants', 'moneyAccess')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->dropColumn('moneyAccess');
        });
    }
};
