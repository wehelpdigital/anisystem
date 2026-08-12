<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Answers that only some kinds of observation have.
 *
 * A pest observation wants a severity and an action taken; a weather one
 * wants what happened and for how long; a lesson wants what to change. None
 * of those deserve a column of their own on a table where every other row
 * would leave them null, and all of them were being written as prose in the
 * notes box, where no report can count them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_post_harvests', function (Blueprint $table) {
            $table->json('details')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_post_harvests', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
