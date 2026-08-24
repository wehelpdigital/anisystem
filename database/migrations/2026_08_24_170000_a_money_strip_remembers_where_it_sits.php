<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a day's money strip sits among that day's activities.
 *
 * The rows inside a strip have always had an order of their own (sortOrder);
 * the strip itself had none, because it could only ever be in one place —
 * pinned above the day's cards. Now that it can be carried and put down
 * between two activities, the day has to remember where it was put, and the
 * only thing a day has to remember it with is its own rows.
 *
 * Every row of one day carries the same value, and null means the strip is
 * still where it has always been: at the top, before the first card. The
 * numbers share the scale the cards and the inline notes use — a card's
 * sequenceOrder, a note's sortKey — so a strip dropped between two of them
 * simply takes a number between theirs.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['as_schedule_day_expenses', 'as_schedule_day_incomes'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'blockSort')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->integer('blockSort')->nullable()->after('sortOrder');
            });
        }
    }

    public function down(): void
    {
        foreach (['as_schedule_day_expenses', 'as_schedule_day_incomes'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'blockSort')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('blockSort');
                });
            }
        }
    }
};
