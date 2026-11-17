<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A season note has a date and a lot (2026-11-18, the owner: "in the notes module, always have a taglike button for
 * the date which defaults to the current date, and a taglike button to select which lot is it applied or it
 * pertains to").
 *
 *   noteDate  the day the note is about; the notes already written take the day they were written
 *   lotId     the season lot it pertains to (as_schedule_lots.id), null for the whole season
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_notes')) {
            return;
        }
        Schema::table('as_schedule_notes', function (Blueprint $t) {
            if (! Schema::hasColumn('as_schedule_notes', 'noteDate')) {
                $t->date('noteDate')->nullable()->after('body');
            }
            if (! Schema::hasColumn('as_schedule_notes', 'lotId')) {
                $t->unsignedBigInteger('lotId')->nullable()->after('noteDate')->index();
            }
        });
        // The notes already written are about the day they were written (timestamps are Philippine time).
        DB::table('as_schedule_notes')->whereNull('noteDate')->update(['noteDate' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('as_schedule_notes', function (Blueprint $t) {
            if (Schema::hasColumn('as_schedule_notes', 'lotId')) {
                $t->dropIndex(['lotId']);
                $t->dropColumn('lotId');
            }
            if (Schema::hasColumn('as_schedule_notes', 'noteDate')) {
                $t->dropColumn('noteDate');
            }
        });
    }
};
