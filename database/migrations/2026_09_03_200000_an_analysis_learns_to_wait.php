<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An analysis becomes a job: created pending, finished after the response.
 *
 * The long model call was dying inside hosted gateway timeouts that a chat
 * answer slips under. The row now exists before the model is asked, the
 * page polls it, and 'failed' carries the reason instead of a bare 500.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_plant_analyses', function (Blueprint $table) {
            $table->string('status', 12)->default('ready')->after('credits');
            $table->text('error')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('as_plant_analyses', function (Blueprint $table) {
            $table->dropColumn(['status', 'error']);
        });
    }
};
