<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An activity can now DECLARE a purchase: a material line that brings stock
 * into the shed (at its own price) before the work spends it.
 *
 * - as_inventory_moves.unitPrice: what one unit cost on THIS move, so a new
 *   bag bought dearer than the shelf's old stock keeps its own price and a
 *   future report can cost old and new stock separately.
 * - as_schedule_activity_items.newBuy: the line's own word that it is a
 *   purchase. A flag, not an inference from "has a price" — plenty of old
 *   lines carry prices that were only ever notes, and a re-save must not
 *   turn them into deliveries nobody received.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_inventory_moves') && ! Schema::hasColumn('as_inventory_moves', 'unitPrice')) {
            Schema::table('as_inventory_moves', function (Blueprint $t) {
                $t->decimal('unitPrice', 12, 2)->nullable()->after('note');
            });
        }
        if (Schema::hasTable('as_schedule_activity_items') && ! Schema::hasColumn('as_schedule_activity_items', 'newBuy')) {
            Schema::table('as_schedule_activity_items', function (Blueprint $t) {
                $t->tinyInteger('newBuy')->default(0)->after('inventoryItemId');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('as_inventory_moves', 'unitPrice')) {
            Schema::table('as_inventory_moves', fn (Blueprint $t) => $t->dropColumn('unitPrice'));
        }
        if (Schema::hasColumn('as_schedule_activity_items', 'newBuy')) {
            Schema::table('as_schedule_activity_items', fn (Blueprint $t) => $t->dropColumn('newBuy'));
        }
    }
};
