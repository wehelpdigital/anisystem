<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satellite Weather's week pass (2026-10-12): a farm pays credits for seven
 * days of the page (App\Support\SkyPass), up to a fair number of openings.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_sky_passes')) {
            Schema::create('as_sky_passes', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('userId')->index();
                $t->timestamp('startsAt')->nullable();
                $t->timestamp('endsAt')->nullable()->index();
                $t->decimal('credits', 10, 2)->default(0);
                $t->unsignedInteger('opens')->default(0);
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_sky_passes');
    }
};
