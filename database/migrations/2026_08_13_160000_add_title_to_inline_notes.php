<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A note pinned to a day gets a name of its own.
 *
 * Without one, every note on a day was an anonymous block of text, and
 * anything attached to the day — a drawing, a saved map, a photo — was piled
 * into the same note because there was nothing to tell one from another.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_inline_notes', function (Blueprint $table) {
            $table->string('title', 191)->nullable()->after('sortKey');
        });
    }

    public function down(): void
    {
        Schema::table('as_inline_notes', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
