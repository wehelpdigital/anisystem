<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Irrigation is now an activity type. An irrigation activity carries a
 * `waterTask` (irrigate / drain / maintain / …). Activities can also hold
 * several reference images now, stored as a JSON list; the legacy single
 * `imagePath` stays in sync with the first one for backward compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('as_schedule_activities', 'waterTask')) {
                $table->string('waterTask', 32)->nullable()->after('activityType');
            }
            if (! Schema::hasColumn('as_schedule_activities', 'imagePaths')) {
                $table->json('imagePaths')->nullable()->after('imagePath');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_activities', function (Blueprint $table) {
            foreach (['waterTask', 'imagePaths'] as $col) {
                if (Schema::hasColumn('as_schedule_activities', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
