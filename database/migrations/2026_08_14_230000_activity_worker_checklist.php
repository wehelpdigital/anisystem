<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two questions any task can now ask about its roster.
 *
 * A payroll day already carried a tick-list of who turned up, because that
 * is what a payroll day is for. But the same question — who actually worked
 * this — is worth asking of a spraying day or a harvest, and the answer is
 * what a wage is calculated from. `workerChecklist` says this task keeps
 * that list.
 *
 * `workerSelfCheck` says the workers may tick themselves. That is a real
 * transfer of trust, so it is a per-task choice and not a global one: a
 * farm may want a foreman's word on the harvest and be perfectly happy for
 * people to sign themselves in for weeding.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->boolean('workerChecklist')->default(false)->after('timeRequired');
            $table->boolean('workerSelfCheck')->default(false)->after('workerChecklist');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropColumn(['workerChecklist', 'workerSelfCheck']);
        });
    }
};
