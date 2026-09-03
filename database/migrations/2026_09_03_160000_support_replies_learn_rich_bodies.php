<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A support message knows what it is written in.
 *
 * 'text' is every message so far and every client message to come; 'html' is
 * an admin reply composed in the panel's small editor — sanitized at write,
 * rendered rich in the thread and the email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_support_messages', function (Blueprint $table) {
            $table->string('bodyFormat', 8)->default('text')->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('as_support_messages', function (Blueprint $table) {
            $table->dropColumn('bodyFormat');
        });
    }
};
