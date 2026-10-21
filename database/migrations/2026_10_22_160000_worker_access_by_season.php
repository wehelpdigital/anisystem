<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Access by cropping schedule (2026-10-22, the owner: "in the global workers,
 * the farm owner can manage what can a user access, but can specify by
 * cropping schedule, or all").
 *
 * A login (as_worker_grants) now holds only who the person is and whether
 * they can sign in; what they may open lives here, one row per scope: the
 * row with no croppingScheduleId is "all cropping schedules", and a row naming
 * a season is that season's own setting, which comes first there.
 *
 * Every login is carried over as it worked the day before: a farm wide login
 * becomes an "all" row; a login that reached the seasons its person is on
 * becomes one row per such season, with the same rights.
 */
return new class extends Migration
{
    private const RIGHTS = ['scheduleAccess', 'notesAccess', 'reportsAccess', 'inventoryAccess', 'observationsAccess', 'moneyAccess',
        'mapsAccess', 'drawAccess', 'aiAccess', 'cameraAccess', 'videoAccess', 'voiceAccess'];

    public function up(): void
    {
        if (! Schema::hasTable('as_worker_grants') || Schema::hasTable('as_worker_access')) {
            return;
        }

        Schema::create('as_worker_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grantId')->index();
            $table->unsignedBigInteger('bossUserId')->index();
            // Null is every cropping schedule of the farm.
            $table->unsignedBigInteger('croppingScheduleId')->nullable()->index();
            $table->string('scheduleAccess', 8)->default('view');
            $table->string('notesAccess', 8)->default('view');
            $table->string('reportsAccess', 8)->default('view');
            $table->string('inventoryAccess', 8)->default('none');
            $table->string('observationsAccess', 8)->default('none');
            $table->string('moneyAccess', 8)->default('none');
            $table->string('mapsAccess', 8)->default('none');
            $table->string('drawAccess', 8)->default('none');
            $table->boolean('aiAccess')->default(0);
            $table->boolean('cameraAccess')->default(0);
            $table->boolean('videoAccess')->default(0);
            $table->boolean('voiceAccess')->default(0);
            $table->tinyInteger('deleteStatus')->default(1);
            $table->timestamps();
        });

        $now = now('Asia/Manila');
        foreach (DB::table('as_worker_grants')->where('deleteStatus', 1)->get() as $g) {
            // A login that saw no season (the old community-only answer) carries no access.
            if (! in_array($g->scheduleAccess, ['view', 'edit'], true)) {
                continue;
            }
            $rights = [];
            foreach (self::RIGHTS as $col) {
                $rights[$col] = $g->{$col};
            }
            $row = fn (?int $season) => $rights + ['grantId' => $g->id, 'bossUserId' => $g->bossUserId, 'croppingScheduleId' => $season,
                'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now];

            if (! $g->scheduleWorkerId && ! $g->globalWorkerId) {
                DB::table('as_worker_access')->insert($row(null));
                continue;
            }

            $farm = DB::table('as_cropping_schedules')->where('anisystemUserId', $g->bossUserId)->where('deleteStatus', 1)->pluck('id');
            $seasons = collect();
            if ($g->scheduleWorkerId) {
                $seasons = $seasons->concat(DB::table('as_schedule_workers')->where('id', $g->scheduleWorkerId)->pluck('croppingScheduleId'));
            }
            if ($g->globalWorkerId) {
                $seasons = $seasons->concat(DB::table('as_schedule_workers')->where('globalWorkerId', $g->globalWorkerId)
                    ->where('deleteStatus', 1)->whereIn('croppingScheduleId', $farm)->pluck('croppingScheduleId'));
            }
            $email = $g->workerUserId ? DB::table('anisystem_users')->where('id', $g->workerUserId)->value('email') : $g->invitedEmail;
            if (trim((string) $email) !== '') {
                $seasons = $seasons->concat(DB::table('as_schedule_workers')->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])
                    ->whereIn('croppingScheduleId', $farm)->pluck('croppingScheduleId'));
            }
            foreach ($seasons->map(fn ($id) => (int) $id)->unique()->intersect($farm->map(fn ($id) => (int) $id)) as $season) {
                DB::table('as_worker_access')->insert($row($season));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_worker_access');
    }
};
