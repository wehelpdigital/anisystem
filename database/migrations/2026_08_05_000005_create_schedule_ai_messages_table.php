<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One shared "AI Technician" thread per schedule for the Collab Room — any team
 * member asks, the whole team sees the Q&A. Kept separate from the per-user
 * AiConversation/AiMessage (which are hard-scoped to a single userId).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_schedule_ai_messages')) {
            return;
        }
        Schema::create('as_schedule_ai_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('scheduleId')->index();
            $table->integer('userId');            // asker (user turn) / owner (assistant turn)
            $table->string('role', 12);           // user | assistant
            $table->text('content')->nullable();
            $table->string('imagePath', 500)->nullable();
            $table->decimal('creditsCharged', 12, 4)->nullable();
            $table->integer('deleteStatus')->default(1)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_schedule_ai_messages');
    }
};
