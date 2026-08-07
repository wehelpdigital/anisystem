<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct 1-on-1 messages between members (Messenger-style dock), plus an
 * allowMessages switch so a member can turn off their inbox.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_messages')) {
            Schema::create('as_community_messages', function (Blueprint $table) {
                $table->id();
                $table->integer('senderId');
                $table->integer('recipientId');
                $table->text('body');
                $table->boolean('isRead')->default(0);
                $table->integer('deleteStatus')->default(1);
                $table->timestamps();

                $table->index(['recipientId', 'isRead']);
                $table->index(['senderId', 'recipientId']);
            });
        }

        if (! Schema::hasColumn('anisystem_users', 'allowMessages')) {
            Schema::table('anisystem_users', function (Blueprint $table) {
                $table->boolean('allowMessages')->default(1)->after('statusBubble');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_community_messages');
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('allowMessages');
        });
    }
};
