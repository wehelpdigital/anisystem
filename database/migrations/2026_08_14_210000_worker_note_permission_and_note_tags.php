<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two small permissions and one small link.
 *
 * `canAddNotes` lets an owner say that a particular worker may write on the
 * days they work — which is a different thing from editing the plan. A
 * view-only worker who can record what actually happened in the field is
 * exactly the arrangement most farms want, and until now the choice was
 * "change everything" or "change nothing".
 *
 * The note columns say what a note is ABOUT: which lot, and which task on
 * that day. Pointing at the lot beats writing its name into the words,
 * because a pointer can be filtered and a sentence cannot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->boolean('canAddNotes')->default(false)->after('scheduleAccess');
        });

        foreach (['as_schedule_date_notes', 'as_inline_notes'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->integer('lotId')->nullable()->index()->after('media');
                $table->integer('activityId')->nullable()->index()->after('lotId');
            });
        }
    }

    public function down(): void
    {
        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->dropColumn('canAddNotes');
        });
        foreach (['as_schedule_date_notes', 'as_inline_notes'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['lotId', 'activityId']);
            });
        }
    }
};
