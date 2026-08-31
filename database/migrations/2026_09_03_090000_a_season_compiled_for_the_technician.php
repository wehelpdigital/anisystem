<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One compiled season per schedule, for the technician to read.
 *
 * Not a source of truth — every row in it is a copy of something the modules
 * own. It exists because gathering a whole season is six modules' worth of
 * queries and a question should not pay for that twice.
 *
 * The fingerprint is what keeps it honest: the counts and newest touch of
 * each table it was built from. When any module writes anything the
 * fingerprint stops matching and the next read rebuilds. No module has to
 * remember to say so, which is the only way this stays true as modules are
 * added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_ai_season_context')) {
            return;
        }

        Schema::create('as_ai_season_context', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('croppingScheduleId')->unique();
            // Short hash of the counts and newest updated_at of every table
            // this was built from.
            $t->string('fingerprint', 64)->nullable();
            $t->longText('payload')->nullable();
            $t->timestamp('builtAt')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_ai_season_context');
    }
};
