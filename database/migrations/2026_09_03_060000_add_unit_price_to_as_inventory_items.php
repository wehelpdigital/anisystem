<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What one unit of a shed item costs.
 *
 * Optional, because a price is not what the inventory is for — the count is.
 * It is stored per item rather than per move: the farm buys the same bag at
 * the same price for a season, and the expense report this feeds later
 * multiplies the price by what the moves say was used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_inventory_items', function (Blueprint $table) {
            $table->decimal('unitPrice', 12, 2)->nullable()->after('lowAt');
        });
    }

    public function down(): void
    {
        Schema::table('as_inventory_items', function (Blueprint $table) {
            $table->dropColumn('unitPrice');
        });
    }
};
