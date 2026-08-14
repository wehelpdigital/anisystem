<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which band of the cover photo shows.
 *
 * A banner is a wide slot and a phone photo is a tall picture, so most of
 * what someone uploads is cropped away by the shape of the slot. Centre is
 * a guess, and it is usually the wrong one — faces and horizons are rarely
 * in the middle. This is the vertical percentage the banner is anchored to,
 * dragged into place by the person who knows what the photo is of.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->unsignedTinyInteger('coverPos')->default(50)->after('coverPath');
        });
    }

    public function down(): void
    {
        Schema::table('anisystem_users', function (Blueprint $table) {
            $table->dropColumn('coverPos');
        });
    }
};
