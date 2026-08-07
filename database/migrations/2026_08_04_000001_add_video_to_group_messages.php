<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Group chat messages can carry a compressed video (with a poster frame). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_community_group_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('as_community_group_messages', 'videoPath')) {
                $table->string('videoPath', 500)->nullable()->after('imagePath');
            }
            if (! Schema::hasColumn('as_community_group_messages', 'videoPoster')) {
                $table->string('videoPoster', 500)->nullable()->after('videoPath');
            }
        });
    }

    public function down(): void
    {
        Schema::table('as_community_group_messages', function (Blueprint $table) {
            foreach (['videoPath', 'videoPoster'] as $col) {
                if (Schema::hasColumn('as_community_group_messages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
