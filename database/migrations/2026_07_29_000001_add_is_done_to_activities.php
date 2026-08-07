<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-activity "done" flag. Done activities lock on the timeline (no drag,
 * no full edit — notes only) so a finished day's record stays intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_schedule_activities', 'isDone')) {
            return;
        }

        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->integer('isDone')->default(0)->after('isHidden');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_schedule_activities', 'isDone')) {
            Schema::table('as_schedule_activities', function (Blueprint $table) {
                $table->dropColumn('isDone');
            });
        }
    }
};
