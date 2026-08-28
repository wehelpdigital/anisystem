<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A marker on an activity, and nothing more.
 *
 * Not a priority, not a status, not a tag with a meaning the app enforces —
 * those all exist already and all of them argue with the reader about what
 * they mean. This is a star somebody puts on a line for their own reasons:
 * eight colours and off, and the app never asks what any of them stands for.
 *
 * 0 is off, 1..8 are the colours. A tiny integer rather than a hex string
 * because the palette belongs to the design, not to the database — changing
 * what colour 3 is should not mean rewriting rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_activities')
            || Schema::hasColumn('as_schedule_activities', 'markerColor')) {
            return;
        }

        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->unsignedTinyInteger('markerColor')->default(0)->after('isHidden');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_schedule_activities', 'markerColor')) {
            Schema::table('as_schedule_activities', function (Blueprint $table) {
                $table->dropColumn('markerColor');
            });
        }
    }
};
