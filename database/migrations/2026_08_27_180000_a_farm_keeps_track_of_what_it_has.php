<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the farm has on hand, and every time that changed.
 *
 * TWO TABLES, AND WHY THERE IS NO THIRD
 *
 * An ITEM is a thing you keep — Urea, Cartap, a drum of diesel. It carries
 * the unit it is counted in and, where it is bought in packs, what a pack
 * holds: "a bag of Urea" means nothing until somebody says a bag is 50 kg,
 * and a farmer who buys bags and applies kilos has to be able to speak both.
 * Stock is always held in the base unit; packs are a way of saying it.
 *
 * A MOVE is one change: five bags in, twelve kilos out, an opening count.
 * Every move records what the stock was before and what it was after, which
 * is what makes the log worth reading — "12 kg used" tells you less than
 * "from 84 kg down to 72 kg" when you are deciding whether to buy more.
 *
 * There is deliberately no "quantity on hand" column. On-hand is the sum of
 * the moves, worked out when asked. A running total is faster and is wrong
 * the first time anything writes one without the other — and the one thing
 * a stock figure cannot be is quietly wrong.
 *
 * A move that came from an activity remembers which one, so unticking that
 * activity can take the move back out. That is the whole reason the link
 * exists: without it, undoing "done" would leave the stock spent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_inventory_items')) {
            Schema::create('as_inventory_items', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('croppingScheduleId')->index();
                $t->string('name', 150);
                // granular | foliar | pesticide | herbicide | fungicide |
                // molluscicide | seed | fuel | tool | other — what kind of
                // thing it is, which is what decides the sensible units.
                $t->string('kind', 30)->default('other');
                // The unit stock is HELD in: kg, g, L, ml, piece, sack…
                $t->string('unit', 20)->default('kg');
                // What one pack holds, in the base unit. 50 for a 50 kg bag;
                // null for something bought loose.
                $t->decimal('packSize', 12, 3)->nullable();
                // What the pack is called: bag, sack, bottle, box.
                $t->string('packLabel', 30)->nullable();
                // Say something when it falls this low. Nullable: not every
                // item is worth nagging about.
                $t->decimal('lowAt', 14, 3)->nullable();
                $t->string('note', 500)->nullable();
                $t->integer('deleteStatus')->default(1)->index();
                $t->timestamps();
                $t->index(['croppingScheduleId', 'deleteStatus'], 'inventory_item_schedule_idx');
            });
        }

        if (! Schema::hasTable('as_inventory_moves')) {
            Schema::create('as_inventory_moves', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->integer('croppingScheduleId')->index();
                $t->integer('itemId')->index();
                // Signed, in the item's base unit. Out is negative.
                $t->decimal('delta', 14, 3);
                // What the stock stood at either side of this move. Kept
                // rather than recomputed, because the log is a record of what
                // was true at the time and later corrections must not rewrite
                // what somebody read last month.
                $t->decimal('qtyBefore', 14, 3)->default(0);
                $t->decimal('qtyAfter', 14, 3)->default(0);
                // open | in | out | activity | adjust
                $t->string('reason', 16)->default('out')->index();
                // The activity that spent it, so unticking done can undo it.
                $t->integer('activityId')->nullable()->index();
                // The day it happened, which is not always the day it was
                // typed — a move logged against an activity belongs to that
                // activity's date.
                $t->date('happenedOn')->index();
                $t->string('note', 500)->nullable();
                $t->integer('byUserId')->nullable();
                $t->integer('deleteStatus')->default(1)->index();
                $t->timestamps();
                $t->index(['croppingScheduleId', 'happenedOn'], 'inventory_move_day_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_inventory_moves');
        Schema::dropIfExists('as_inventory_items');
    }
};
