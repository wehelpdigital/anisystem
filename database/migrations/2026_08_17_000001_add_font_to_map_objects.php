<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which lettering a text label is written in.
 *
 * A key — sans | serif | cond | mono — not a font stack: the stack is a list
 * of families that differ per device, it will want improving as phones change,
 * and none of that is worth a data migration. Twelve characters is plenty.
 *
 * It also doubles as the flag that says a label's `width` means something. For
 * every other kind `width` is stroke thickness, and text objects were being
 * given whatever the pen happened to be set to — so a row with no font is a
 * row from before this existed, and it keeps the old hardcoded 11px rather
 * than suddenly rendering a fence line's thickness as a type size.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_schedule_map_objects', function (Blueprint $table) {
            $table->string('font', 12)->nullable()->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('as_schedule_map_objects', function (Blueprint $table) {
            $table->dropColumn('font');
        });
    }
};
