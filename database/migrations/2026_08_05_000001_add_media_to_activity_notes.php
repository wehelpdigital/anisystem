<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give the timeline notes (inline sticky notes + the legacy per-day date note)
 * a media gallery like the notebook: photos + videos (auto-compressed), stored
 * as a JSON list of {type, path, poster}. Text/drawings/emoji stay in the body.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_inline_notes', function (Blueprint $table) {
            $table->json('media')->nullable()->after('content');
        });
        Schema::table('as_schedule_date_notes', function (Blueprint $table) {
            $table->json('media')->nullable()->after('noteContent');
        });
    }

    public function down(): void
    {
        Schema::table('as_inline_notes', function (Blueprint $table) {
            $table->dropColumn('media');
        });
        Schema::table('as_schedule_date_notes', function (Blueprint $table) {
            $table->dropColumn('media');
        });
    }
};
