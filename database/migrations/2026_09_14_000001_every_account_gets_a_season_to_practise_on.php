<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One flag, so a season can say it was given rather than made.
 *
 * A new member lands on an app with nineteen modules and nothing in any of
 * them, which is the worst possible first screen: every door opens onto an
 * empty room. So every account is handed a worked example — real lots, real
 * activities across real days, money on some of them — to open, poke at,
 * and take a guided walk through.
 *
 * It has to be TELLABLE from a season the farmer made, for three reasons,
 * and each of them is a bug if this column is missing:
 *
 *  - The guided walk's button belongs on this season and nowhere else.
 *  - A free account may keep one active season. A demo the app handed over
 *    must not be the one slot they are allowed, or their first act in the
 *    app is deleting something to make room.
 *  - Nobody should be given two. The flag is what "already has one" is asked
 *    of, including for a member who deleted theirs — a demo declined is a
 *    decision, not an omission to correct on the next visit.
 *
 * The table is as_-prefixed, like everything this app owns. Named wrong, the
 * hasTable guard below turns this migration into one that reports success
 * and does nothing at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_cropping_schedules')) {
            return;
        }

        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('as_cropping_schedules', 'isDemo')) {
                $table->boolean('isDemo')->default(false)->after('isActive');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_cropping_schedules')) {
            return;
        }

        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('as_cropping_schedules', 'isDemo')) {
                $table->dropColumn('isDemo');
            }
        });
    }
};
