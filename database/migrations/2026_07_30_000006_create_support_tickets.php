<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support tickets raised by AniSystem clients and answered by admins in the
 * mother app. One ticket has a thread of messages (client + admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_support_tickets')) {
            Schema::create('as_support_tickets', function (Blueprint $table) {
                $table->id();
                $table->integer('userId');                 // anisystem client
                $table->string('subject', 200);
                $table->string('category', 40)->default('general');
                $table->string('status', 20)->default('open'); // open | answered | closed
                $table->timestamp('lastReplyAt')->nullable();
                $table->integer('deleteStatus')->default(1);
                $table->timestamps();

                $table->index(['userId', 'status']);
                $table->index('status');
            });
        }

        if (! Schema::hasTable('as_support_messages')) {
            Schema::create('as_support_messages', function (Blueprint $table) {
                $table->id();
                $table->integer('ticketId');
                $table->string('authorType', 10);          // client | admin
                $table->integer('authorId')->nullable();
                $table->string('authorName', 120)->nullable();
                $table->text('body');
                $table->integer('deleteStatus')->default(1);
                $table->timestamps();

                $table->index('ticketId');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_support_messages');
        Schema::dropIfExists('as_support_tickets');
    }
};
