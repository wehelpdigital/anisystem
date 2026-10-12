<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Global Workers (2026-10-12): a farm's own roster, kept above the seasons,
 * so the same people are added to any season in a tap instead of typed in
 * again. A season's worker card (as_schedule_workers) links to its global
 * worker (globalWorkerId); the activities, pay and logins stay on the card.
 *
 * The existing cards are gathered into global workers here: one per owner
 * and person (the same email, or else the same name and phone number).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_global_workers')) {
            Schema::create('as_global_workers', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('userId')->index();
                $t->string('workerName');
                $t->string('email')->nullable();
                $t->string('phone', 32)->nullable();
                $t->decimal('costPerHalfDay', 10, 2)->default(0);
                $t->json('skills')->nullable();
                // Weekdays they do not work (0 Sunday to 6 Saturday), copied to a season's card.
                $t->json('offWeekdays')->nullable();
                $t->text('notes')->nullable();
                $t->string('personKey', 191)->nullable()->index();
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
        if (Schema::hasTable('as_schedule_workers') && ! Schema::hasColumn('as_schedule_workers', 'globalWorkerId')) {
            Schema::table('as_schedule_workers', function (Blueprint $t) {
                $t->unsignedBigInteger('globalWorkerId')->nullable()->index();
            });
        }
        if (! Schema::hasTable('as_schedule_workers') || ! Schema::hasTable('as_cropping_schedules')) {
            return;
        }
        // Gather the existing cards.
        $rows = DB::table('as_schedule_workers as w')->join('as_cropping_schedules as s', 's.id', '=', 'w.croppingScheduleId')
            ->where('w.deleteStatus', 1)->whereNull('w.globalWorkerId')
            ->orderBy('w.id')->get(['w.*', 's.anisystemUserId as ownerId']);
        $made = [];
        foreach ($rows as $w) {
            if (! $w->ownerId) {
                continue;
            }
            $key = \App\Models\AsGlobalWorker::personKey((string) $w->workerName, $w->email, $w->phone);
            $gid = $made[$w->ownerId . '|' . $key] ?? null;
            if (! $gid) {
                $off = DB::table('as_schedule_worker_off_days')->where('workerId', $w->id)->pluck('dayOfWeek')->map(fn ($d) => (int) $d)->unique()->values()->all();
                $gid = DB::table('as_global_workers')->insertGetId([
                    'userId' => $w->ownerId, 'workerName' => $w->workerName, 'email' => $w->email, 'phone' => $w->phone,
                    'costPerHalfDay' => $w->costPerHalfDay ?? 0, 'skills' => $w->skills, 'offWeekdays' => $off ? json_encode($off) : null,
                    'notes' => $w->notes, 'personKey' => $key, 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $made[$w->ownerId . '|' . $key] = $gid;
            }
            DB::table('as_schedule_workers')->where('id', $w->id)->update(['globalWorkerId' => $gid]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_schedule_workers', 'globalWorkerId')) {
            Schema::table('as_schedule_workers', function (Blueprint $t) {
                $t->dropIndex(['globalWorkerId']);
                $t->dropColumn('globalWorkerId');
            });
        }
        Schema::dropIfExists('as_global_workers');
    }
};
