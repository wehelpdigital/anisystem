<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Things an activity points at: a drawing, a map, a note. Not copies — a tag
 * holds only what is needed to name it and open it, so editing the drawing or
 * the note is still done in one place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('imagePaths');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
