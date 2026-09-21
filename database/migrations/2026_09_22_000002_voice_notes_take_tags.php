<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A global note (a Quick Voice recording, a thought of the member's own)
 * belongs to no season, so the season's tags cannot reach it. It carries
 * the member's own words instead — the same JSON list the analyses and the
 * protocols wear.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_notes') && ! Schema::hasColumn('as_schedule_notes', 'tags')) {
            Schema::table('as_schedule_notes', function (Blueprint $t) {
                $t->text('tags')->nullable()->after('media');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('as_schedule_notes') && Schema::hasColumn('as_schedule_notes', 'tags')) {
            Schema::table('as_schedule_notes', function (Blueprint $t) {
                $t->dropColumn('tags');
            });
        }
    }
};
