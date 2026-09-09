<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The voice recorder stops riding on the video switch.
 *
 * Speaking a note and filming one are not the same errand — a farmer with
 * their hands full says a sentence, and an owner may want that without
 * handing over the camera. It had no column of its own, so it answered to
 * whatever video said.
 *
 * Backfilled FROM video, so every existing grant keeps exactly the answer it
 * had this morning: whoever could record a clip can still speak a note, and
 * whoever could not, still cannot.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_worker_grants') || Schema::hasColumn('as_worker_grants', 'voiceAccess')) {
            return;
        }

        Schema::table('as_worker_grants', function (Blueprint $table) {
            $table->boolean('voiceAccess')->default(0)->after('videoAccess');
        });

        DB::table('as_worker_grants')->update(['voiceAccess' => DB::raw('videoAccess')]);
    }

    public function down(): void
    {
        if (Schema::hasTable('as_worker_grants') && Schema::hasColumn('as_worker_grants', 'voiceAccess')) {
            Schema::table('as_worker_grants', function (Blueprint $table) {
                $table->dropColumn('voiceAccess');
            });
        }
    }
};
