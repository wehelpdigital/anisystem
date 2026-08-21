<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A grant stops being one answer about schedules and becomes an answer per
 * module.
 *
 * Until now a worker's rights were "view / edit / none" over the plan, plus a
 * single tick for the day's notes — and Maps, Draw and the AI Technician were
 * closed to every worker in every farm, by a line of code rather than by any
 * owner's decision. The owner asked for the choice to be theirs, per person,
 * per module.
 *
 * Two shapes, because the modules are two shapes. Notes and Reports are things
 * you can look at or add to, so they take the same none/view/edit answer the
 * schedule does. Maps, Draw, the AI Technician, the camera and the recorder
 * are things you either have or do not.
 *
 * Defaults are today's behaviour, not a new policy: every existing worker
 * keeps exactly the rights they had this morning. Maps, Draw and AI were shut
 * to all of them, so they stay shut until an owner opens them; the camera and
 * the recorder were only ever offered to owners, same. Notes and Reports are
 * backfilled from what the farm already said about that worker.
 *
 * House conventions: camelCase columns, integer deleteStatus, no FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_worker_grants', function (Blueprint $table) {
            // Read/append modules, in the schedule's own vocabulary.
            $table->enum('notesAccess', ['none', 'view', 'edit'])->default('view')->after('canAddNotes');
            $table->enum('reportsAccess', ['none', 'view', 'edit'])->default('view')->after('notesAccess');
            // Open/shut modules. Off by default: that is what they are today.
            $table->boolean('mapsAccess')->default(0)->after('reportsAccess');
            $table->boolean('drawAccess')->default(0)->after('mapsAccess');
            $table->boolean('aiAccess')->default(0)->after('drawAccess');
            $table->boolean('cameraAccess')->default(0)->after('aiAccess');
            $table->boolean('videoAccess')->default(0)->after('cameraAccess');
        });

        /* What each existing worker could already do, written down.
         *
         * Notes: whoever may change the plan may obviously write on it, and
         * canAddNotes was exactly this permission under an older name.
         * Reports: reading follows seeing the farm, writing followed editing
         * it. A worker with no schedule access has neither, in both. */
        DB::table('as_worker_grants')->where('scheduleAccess', 'edit')
            ->update(['notesAccess' => 'edit', 'reportsAccess' => 'edit']);
        DB::table('as_worker_grants')->where('scheduleAccess', 'view')
            ->update(['notesAccess' => 'view', 'reportsAccess' => 'view']);
        DB::table('as_worker_grants')->where('scheduleAccess', 'view')->where('canAddNotes', 1)
            ->update(['notesAccess' => 'edit']);
        DB::table('as_worker_grants')->where('scheduleAccess', 'none')
            ->update(['notesAccess' => 'none', 'reportsAccess' => 'none']);
    }

    public function down(): void
    {
        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->dropColumn([
                'notesAccess', 'reportsAccess', 'mapsAccess',
                'drawAccess', 'aiAccess', 'cameraAccess', 'videoAccess',
            ]);
        });
    }
};
