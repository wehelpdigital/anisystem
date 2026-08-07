<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let an AI conversation be pinned to a specific day (date) or a specific
 * activity within its schedule, so the farmer can keep a thread focused on
 * "what's happening on Aug 12" or "this transplanting task".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anisystem_ai_conversations', function (Blueprint $table) {
            $table->date('linkedDate')->nullable()->after('croppingScheduleId');
            $table->unsignedBigInteger('linkedActivityId')->nullable()->after('linkedDate');
            $table->index('linkedActivityId');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_ai_conversations', function (Blueprint $table) {
            $table->dropIndex(['linkedActivityId']);
            $table->dropColumn(['linkedDate', 'linkedActivityId']);
        });
    }
};
