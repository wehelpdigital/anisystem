<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A reminder checklist: the things that have to happen on a day which are
 * nobody's task and nobody's wage — permits, a delivery to chase, a payment
 * to make. Each line can carry money, and ticking it is what makes that money
 * real: the tick writes an ordinary day expense or day income row, which is
 * why those two tables need to remember which reminder put them there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->json('reminders')->nullable()->after('tags');
        });

        foreach (['as_schedule_day_expenses', 'as_schedule_day_incomes'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                // "reminder:<activityId>:<index>" — the row a tick owns, so
                // unticking removes exactly that one and nothing else.
                $table->string('sourceRef', 64)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropColumn('reminders');
        });
        foreach (['as_schedule_day_expenses', 'as_schedule_day_incomes'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn('sourceRef');
            });
        }
    }
};
