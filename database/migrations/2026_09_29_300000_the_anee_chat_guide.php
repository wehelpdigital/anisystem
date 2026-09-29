<?php

use App\Support\AneeChatGuide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "How to chat with Anee" (2026-09-29): the full guide behind the how-to-ask
 * card in every chat, written as a How-to Guide page (moduleKey "anee-chat")
 * so the mother app's block builder edits it. Seeded once as the phone page,
 * which every device falls back to until one of its own is written.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_tutorial_pages')) {
            return;
        }
        if (DB::table('as_tutorial_pages')->where('moduleKey', AneeChatGuide::MODULE)->exists()) {
            return;
        }
        DB::table('as_tutorial_pages')->insert([
            'moduleKey' => AneeChatGuide::MODULE,
            'device' => 'mobile',
            'title' => AneeChatGuide::TITLE,
            'summary' => AneeChatGuide::SUMMARY,
            'blocks' => json_encode(AneeChatGuide::BLOCKS, JSON_UNESCAPED_UNICODE),
            'updatedByUserId' => null,
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // The page may have been rewritten in the builder since; left alone.
    }
};
