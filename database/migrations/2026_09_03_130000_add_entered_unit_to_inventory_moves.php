<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the hand actually typed, beside what the book recorded.
 *
 * A move is stored in the item's own unit — that is the ledger's spine — but
 * "−0.4 bags" is not what happened when somebody weighed out 20 kilos. The
 * typed amount and its unit ride along so the log can say both: the book's
 * figure for arithmetic, the hand's figure for memory. NULL means the move
 * was typed in the item's own unit and there is nothing extra to say.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_inventory_moves', function (Blueprint $table) {
            $table->decimal('enteredQty', 12, 3)->nullable()->after('qtyAfter');
            $table->string('enteredUnit', 16)->nullable()->after('enteredQty');
        });
    }

    public function down(): void
    {
        Schema::table('as_inventory_moves', function (Blueprint $table) {
            $table->dropColumn(['enteredQty', 'enteredUnit']);
        });
    }
};
