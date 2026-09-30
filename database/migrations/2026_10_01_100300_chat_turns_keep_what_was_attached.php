<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A question asked with a report, an analysis, a Realign reading or a
 * protocol review attached keeps what was attached (2026-09-30).
 *
 * The composer clears its chips once a question is sent; the chat has to
 * go on knowing what the farmer showed Anee, or the answer to her own
 * clarifying question ("which lot did you mean?") arrives with the report
 * it is about already gone. The newest attachment rides the history of
 * the questions after it (AiController::ask).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('anisystem_ai_messages') && ! Schema::hasColumn('anisystem_ai_messages', 'attachedContext')) {
            Schema::table('anisystem_ai_messages', function (Blueprint $table) {
                $table->longText('attachedContext')->nullable()->after('imagePaths');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('anisystem_ai_messages', 'attachedContext')) {
            Schema::table('anisystem_ai_messages', function (Blueprint $table) {
                $table->dropColumn('attachedContext');
            });
        }
    }
};
