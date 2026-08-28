<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stop serving a two-megabyte portrait as a chat avatar.
 *
 * Anee's face was uploaded through the mother app's settings screen as the
 * original 2048x2048 JPEG — 2.3MB, fetched on every chat page, to be drawn at
 * thirty-two pixels. The app now ships her portrait itself, resized and
 * compressed to 65KB, and `AiSetting::faceUrl()` falls back to it.
 *
 * So the uploaded one is cleared. The column is an OVERRIDE, not the source:
 * an admin who wants a different face can still set one, and the day they do,
 * theirs wins again.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The model's own table, not a guess at it. This one is a mother-app
        // table without the `as_` prefix the app's own tables carry, and a
        // hasTable guard on a wrong name is a migration that says DONE and
        // does nothing.
        $table = (new \App\Models\AiSetting)->getTable();
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'avatarPath')) {
            return;
        }

        // Only the one this app put there. Anything else an admin has set
        // since is theirs and is left alone.
        DB::table($table)
            ->where('avatarPath', 'like', 'ai/ai-avatar-%')
            ->update(['avatarPath' => null]);
    }

    public function down(): void
    {
        // Nothing. The file is still on the mother app's disk if anybody wants
        // it back, and putting a path back that may since have been replaced
        // would overwrite somebody's choice.
    }
};
