<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One knapsack, several chemicals.
 *
 * A task carried exactly one type, so a tank mixing a fungicide and an
 * insecticide had to be recorded as two tasks or as a half-truth. The primary
 * type stays where it is — every colour, filter and report reads it — and the
 * rest of the mix rides alongside it here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $t) {
            $t->json('extraTypes')->nullable()->after('activityType');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $t) {
            $t->dropColumn('extraTypes');
        });
    }
};
