<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let direct messages carry a photo, like the community wall/comments do.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_community_messages', 'imagePath')) {
            return;
        }
        Schema::table('as_community_messages', function (Blueprint $table) {
            $table->string('imagePath', 255)->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('as_community_messages', function (Blueprint $table) {
            $table->dropColumn('imagePath');
        });
    }
};
