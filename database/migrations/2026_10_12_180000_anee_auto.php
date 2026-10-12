<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anee Auto (2026-10-12): a season Anee plans and keeps planning. The
 * farmer answers a wizard once; Anee reads the crop, the variety, the soil,
 * the water and the weather, builds a calendar of tasks per lot, and moves
 * them as the weather, the workers and the farmer's own "not today" change.
 *
 * The season itself is an ordinary cropping schedule (aneeAuto = 1), so its
 * lots, workers and tags are the app's own; everything Anee keeps lives in
 * the four tables below. See App\Http\Controllers\AneeAutoController.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_cropping_schedules') && ! Schema::hasColumn('as_cropping_schedules', 'aneeAuto')) {
            Schema::table('as_cropping_schedules', function (Blueprint $t) {
                $t->tinyInteger('aneeAuto')->default(0)->index();
            });
        }

        if (! Schema::hasTable('as_auto_seasons')) {
            Schema::create('as_auto_seasons', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('userId')->index();
                $t->unsignedBigInteger('croppingScheduleId')->nullable()->index();
                // setting_up, active, finished (the wizard's own place is `step`)
                $t->string('status', 20)->default('setting_up');
                $t->unsignedTinyInteger('step')->default(0);
                $t->unsignedSmallInteger('lotsPaid')->default(1);
                $t->decimal('creditsCharged', 10, 2)->default(0);
                // The wizard's answers: soil signs, water, past problems, notes.
                $t->json('answers')->nullable();
                // Anee's first reading (variety, soil, season, goal, per lot).
                $t->json('analysis')->nullable();
                $t->timestamp('analysisAt')->nullable();
                $t->unsignedTinyInteger('analysisRuns')->default(0);
                // The generated plan's own words (headline, strategy, safety).
                $t->json('plan')->nullable();
                $t->timestamp('generatedAt')->nullable();
                // A running job: {kind, phase, try, error}; heartbeats touch jobAt.
                $t->string('job', 20)->nullable();
                $t->json('jobState')->nullable();
                $t->timestamp('jobAt')->nullable();
                $t->timestamp('plannedAt')->nullable();
                $t->date('briefDate')->nullable();
                $t->date('emailDate')->nullable();
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('as_auto_lots')) {
            Schema::create('as_auto_lots', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('autoId')->index();
                $t->unsignedBigInteger('lotId')->index();
                // transplanted, direct, planted, tree
                $t->string('method', 12)->nullable();
                $t->date('fieldDate')->nullable();     // DAT 0, DAS 0 or DAP 0: the day it goes in the field
                $t->date('sowDate')->nullable();       // the seedbed, for a transplanted crop
                $t->date('prepDate')->nullable();      // land preparation starts
                $t->date('harvestDate')->nullable();   // Anee's estimate
                $t->unsignedSmallInteger('treeMonths')->nullable();
                // The fertilizer plan, made in NPK Plus (as_npk_calcs) and read back.
                $t->unsignedBigInteger('calcId')->nullable();
                $t->json('fert')->nullable();
                // Going on although the plan is off balance, and why (the owner wants to know).
                $t->tinyInteger('imbalance')->default(0);
                $t->string('imbalanceReason', 40)->nullable();
                $t->text('imbalanceNote')->nullable();
                $t->timestamp('imbalanceAt')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('as_auto_tasks')) {
            Schema::create('as_auto_tasks', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('autoId')->index();
                $t->unsignedBigInteger('lotId')->nullable()->index();
                $t->string('kind', 30);
                $t->string('title', 190);
                $t->text('summary')->nullable();
                $t->text('why')->nullable();
                // Steps, products by active ingredient, rates, safety, what to bring.
                $t->json('detail')->nullable();
                // When: days from an anchor (field, sow or start), and how far it may move.
                $t->string('anchor', 10)->default('field');
                $t->integer('offsetDays')->default(0);
                $t->unsignedSmallInteger('windowBefore')->default(0);
                $t->unsignedSmallInteger('windowAfter')->default(2);
                $t->date('notBefore')->nullable();
                $t->date('plannedDate')->nullable()->index();
                $t->date('firstDate')->nullable();
                $t->string('timeOfDay', 10)->nullable();
                $t->tinyInteger('critical')->default(0);
                // dry, spray, wet, any
                $t->string('weather', 12)->default('any');
                $t->unsignedTinyInteger('crew')->default(1);
                $t->json('workerIds')->nullable();
                $t->json('planNote')->nullable();
                // planned, done, skipped
                $t->string('status', 12)->default('planned')->index();
                $t->date('doneDate')->nullable();
                $t->timestamp('doneAt')->nullable();
                $t->text('doneNote')->nullable();
                $t->json('doneData')->nullable();
                $t->unsignedSmallInteger('movedCount')->default(0);
                // plan, observation, anee, farmer
                $t->string('origin', 12)->default('plan');
                $t->integer('sortOrder')->default(0);
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('as_auto_events')) {
            Schema::create('as_auto_events', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('autoId')->index();
                $t->unsignedBigInteger('lotId')->nullable();
                $t->unsignedBigInteger('taskId')->nullable()->index();
                $t->unsignedBigInteger('userId')->nullable();
                // anee, farmer, system
                $t->string('role', 10)->default('anee');
                // briefing, done, cant, moved, observation, ask, reply, setup, email, plan
                $t->string('kind', 20);
                $t->text('body')->nullable();
                $t->json('data')->nullable();
                $t->json('media')->nullable();
                $t->timestamps();
            });
        }

        if (Schema::hasTable('as_email_templates')) {
            (new \Database\Seeders\AniSystemEmailTemplateSeeder())->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_auto_events');
        Schema::dropIfExists('as_auto_tasks');
        Schema::dropIfExists('as_auto_lots');
        Schema::dropIfExists('as_auto_seasons');
        if (Schema::hasColumn('as_cropping_schedules', 'aneeAuto')) {
            Schema::table('as_cropping_schedules', function (Blueprint $t) {
                $t->dropIndex(['aneeAuto']);
                $t->dropColumn('aneeAuto');
            });
        }
    }
};
