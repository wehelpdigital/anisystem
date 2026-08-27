<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which band of a discussion's cover photo the room shows.
 *
 * The banner is a wide slot and a phone photo is a tall picture, so centring
 * it is a guess — a cover of a field with the sky above it comes out as sky.
 * A member's own profile has been able to say this since it got a cover
 * (users.coverPos); a room could not, and its organiser had no way to fix a
 * badly framed banner except by finding a differently shaped photo.
 *
 * 0 = the top of the picture, 100 = the bottom, 50 = the middle, which is
 * what every existing room keeps.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_community_groups')) {
            return;
        }
        Schema::table('as_community_groups', function (Blueprint $t) {
            if (! Schema::hasColumn('as_community_groups', 'bannerPos')) {
                $t->unsignedTinyInteger('bannerPos')->default(50)->after('bannerImagePath');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('as_community_groups')) {
            return;
        }
        Schema::table('as_community_groups', function (Blueprint $t) {
            if (Schema::hasColumn('as_community_groups', 'bannerPos')) {
                $t->dropColumn('bannerPos');
            }
        });
    }
};
