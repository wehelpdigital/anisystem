<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A question can now carry several photos. The full set is kept as a JSON
 * list in `imagePaths`; the first one is still written into the legacy
 * `imagePath` column so every old renderer keeps showing something.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anisystem_ai_messages', function (Blueprint $table) {
            $table->text('imagePaths')->nullable()->after('imagePath');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_ai_messages', function (Blueprint $table) {
            $table->dropColumn('imagePaths');
        });
    }
};
