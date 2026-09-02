<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A member barred from the Community, until a date.
 *
 * A datetime rather than a flag, because "suspended" is a sentence with a
 * length: the admin picks the day it ends and nothing has to remember to flip
 * a switch back. NULL or a past date both mean the door is open.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dateTime('communitySuspendedUntil')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('communitySuspendedUntil');
        });
    }
};
