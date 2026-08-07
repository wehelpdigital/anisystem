<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messenger reply-to-message (FB-style quoted replies): a message can point at
 * an earlier one in the same thread. Nullable — most messages are not replies.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('as_community_messages', 'replyToId')) {
            Schema::table('as_community_messages', function (Blueprint $table) {
                $table->integer('replyToId')->nullable()->after('recipientId');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_community_messages', 'replyToId')) {
            Schema::table('as_community_messages', function (Blueprint $table) {
                $table->dropColumn('replyToId');
            });
        }
    }
};
