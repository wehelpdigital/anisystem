<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Worker logins from the farm's roster (2026-10-18, the owner): a login can be
 * given from Global Workers as well as from a season's card. A grant now
 * names the roster PERSON (as_global_workers) besides the season card it was
 * made from, so the roster and every season card that is that person find the
 * same grant, and the seasons the person is on are the seasons the login
 * opens (App\Support\WorkerContext::visibleScheduleIdsFor).
 *
 * Every grant made from a season card learns who that card is on the roster.
 * A grant that names no card was made farm wide and is left as it was made.
 */
return new class extends Migration
{
    public function up(): void
    {
        $t = 'as_worker_grants';
        if (! Schema::hasTable($t)) {
            return;
        }
        if (! Schema::hasColumn($t, 'globalWorkerId')) {
            Schema::table($t, function (Blueprint $b) {
                $b->unsignedBigInteger('globalWorkerId')->nullable()->after('scheduleWorkerId')->index();
            });
        }
        if (! Schema::hasTable('as_schedule_workers') || ! Schema::hasTable('as_global_workers')
            || ! Schema::hasColumn('as_schedule_workers', 'globalWorkerId')) {
            return;
        }
        $rows = DB::table('as_worker_grants as g')
            ->join('as_schedule_workers as w', 'w.id', '=', 'g.scheduleWorkerId')
            ->join('as_global_workers as p', 'p.id', '=', 'w.globalWorkerId')
            ->whereNull('g.globalWorkerId')
            ->whereColumn('p.userId', 'g.bossUserId')
            ->get(['g.id', 'p.id as personId']);
        foreach ($rows as $r) {
            DB::table('as_worker_grants')->where('id', $r->id)->update(['globalWorkerId' => $r->personId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('as_worker_grants') && Schema::hasColumn('as_worker_grants', 'globalWorkerId')) {
            Schema::table('as_worker_grants', function (Blueprint $b) {
                $b->dropIndex(['globalWorkerId']);
                $b->dropColumn('globalWorkerId');
            });
        }
    }
};
