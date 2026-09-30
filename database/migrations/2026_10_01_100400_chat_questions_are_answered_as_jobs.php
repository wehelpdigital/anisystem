<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A chat question is answered as a job (2026-09-30).
 *
 * anee.io's edge gives a request about 20 seconds. A question with six
 * photos and a Realign reading takes longer than that to answer, so the
 * farmer saw "504" while the server went on, answered, and charged for an
 * answer nobody was shown. Now the question is taken, the request ends at
 * once, and the page asks after the answer (AiController::askJob), the way
 * the analyses and reports already work. One row per question.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_ai_ask_jobs')) {
            return;
        }
        Schema::create('as_ai_ask_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId')->index();
            $table->unsignedBigInteger('conversationId')->nullable();
            $table->unsignedBigInteger('messageId')->nullable();
            // pending | ready | failed
            $table->string('status', 16)->default('pending');
            // What the page is handed when it asks: the answer, or why not.
            $table->longText('payload')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_ai_ask_jobs');
    }
};
