<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who opened an order, in Google Analytics' books (App\Support\SiteAnalytics): the purchase is reported when the order is
 * approved, later and from someone else's browser, and has to land on the visitor who came from the ad.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_orders') && ! Schema::hasColumn('as_orders', 'gaClient')) {
            Schema::table('as_orders', function (Blueprint $table) {
                $table->string('gaClient', 80)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_orders', 'gaClient')) {
            Schema::table('as_orders', function (Blueprint $table) {
                $table->dropColumn('gaClient');
            });
        }
    }
};
