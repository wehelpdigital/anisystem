<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tombstone deletes: when a member removes their own wall comment or group
 * reply, we keep the row (so a thread's replies don't orphan) but flag it
 * isDeleted and blank the body — the UI then shows "This comment was deleted".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('as_community_wall_comments', 'isDeleted')) {
            Schema::table('as_community_wall_comments', function (Blueprint $table) {
                $table->boolean('isDeleted')->default(0)->after('imagePath');
            });
        }
        if (! Schema::hasColumn('as_community_group_replies', 'isDeleted')) {
            Schema::table('as_community_group_replies', function (Blueprint $table) {
                $table->boolean('isDeleted')->default(0)->after('imagePath');
            });
        }
    }

    public function down(): void
    {
        Schema::table('as_community_wall_comments', function (Blueprint $table) {
            $table->dropColumn('isDeleted');
        });
        Schema::table('as_community_group_replies', function (Blueprint $table) {
            $table->dropColumn('isDeleted');
        });
    }
};
