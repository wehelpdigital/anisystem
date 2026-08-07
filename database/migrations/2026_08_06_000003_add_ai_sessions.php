<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Saved sessions for the Collab Room "AI Technician": each schedule can hold
 * several named AI conversations (visible to the whole team). Every message
 * belongs to a session. Existing messages are adopted into a first session.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_schedule_ai_sessions')) {
            Schema::create('as_schedule_ai_sessions', function (Blueprint $table) {
                $table->id();
                $table->integer('scheduleId')->index();
                $table->string('title', 180)->nullable();
                $table->integer('startedByUserId')->nullable();
                $table->timestamp('lastMessageAt')->nullable();
                $table->integer('deleteStatus')->default(1)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('as_schedule_ai_messages', 'sessionId')) {
            Schema::table('as_schedule_ai_messages', function (Blueprint $table) {
                $table->unsignedBigInteger('sessionId')->nullable()->index()->after('scheduleId');
            });
        }

        // Adopt any pre-existing messages into one session per schedule.
        $scheduleIds = DB::table('as_schedule_ai_messages')
            ->whereNull('sessionId')
            ->distinct()
            ->pluck('scheduleId');

        foreach ($scheduleIds as $sid) {
            $sessionId = DB::table('as_schedule_ai_sessions')->insertGetId([
                'scheduleId' => $sid,
                'title' => 'AI session',
                'startedByUserId' => null,
                'lastMessageAt' => now(),
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('as_schedule_ai_messages')
                ->where('scheduleId', $sid)
                ->whereNull('sessionId')
                ->update(['sessionId' => $sessionId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_schedule_ai_messages', 'sessionId')) {
            Schema::table('as_schedule_ai_messages', function (Blueprint $table) {
                $table->dropColumn('sessionId');
            });
        }
        Schema::dropIfExists('as_schedule_ai_sessions');
    }
};
