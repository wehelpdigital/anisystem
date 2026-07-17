<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('as_cropping_schedules', 'anisystemUserId')) {
            return;
        }

        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            // NULL = created by a btc-check admin; set = owned by an AniSystem SaaS client
            $table->unsignedBigInteger('anisystemUserId')->nullable()->after('usersId')->index();
        });
    }

    public function down(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->dropColumn('anisystemUserId');
        });
    }
};
