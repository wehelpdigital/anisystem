<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satellite Weather as a weekly subscription (2026-10-13): a pass renews
 * itself from the farm's credits each week until it is cancelled
 * (App\Support\SkyPass::renewDue, the sky:renew command).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_sky_passes') && ! Schema::hasColumn('as_sky_passes', 'autoRenew')) {
            Schema::table('as_sky_passes', function (Blueprint $t) {
                $t->tinyInteger('autoRenew')->default(1);
                $t->unsignedBigInteger('renewedTo')->nullable();
                $t->string('renewNote', 190)->nullable();
            });
            // A pass that already ended does not come back to life as a subscription.
            \Illuminate\Support\Facades\DB::table('as_sky_passes')->where('endsAt', '<=', now())->update(['autoRenew' => 0]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_sky_passes', 'autoRenew')) {
            Schema::table('as_sky_passes', fn (Blueprint $t) => $t->dropColumn(['autoRenew', 'renewedTo', 'renewNote']));
        }
    }
};
