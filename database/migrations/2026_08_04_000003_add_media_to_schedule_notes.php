<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notebook notes gain a media gallery: multiple photos and videos (both
 * auto-compressed), stored as a JSON list of {type, path, poster}. The legacy
 * single imagePath stays for older notes and is rendered alongside.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_notes', function (Blueprint $table) {
            $table->json('media')->nullable()->after('imagePath');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_notes', function (Blueprint $table) {
            $table->dropColumn('media');
        });
    }
};
