<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every flat-priced AI run writes what it actually cost the house -- the
 * tokens in and out, whether it searched the web, which model -- beside
 * the credits it charged, so the margin under each price on Anee's price
 * list can be seen in the mother app and the price moved when it should
 * be. The metered chat already keeps its tokens on the message row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('as_ai_usage')) {
            return;
        }
        Schema::create('as_ai_usage', function (Blueprint $table) {
            $table->id();
            // wtp | what | variety | season | sofar | compare | realign
            $table->string('kind', 24);
            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('payerId');
            // The row the run produced (an analysis, a report, a realignment).
            $table->unsignedBigInteger('refId')->nullable();
            $table->string('provider', 16)->nullable();
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('tokensIn')->default(0);
            $table->unsignedInteger('tokensOut')->default(0);
            $table->boolean('searched')->default(false);
            // What was charged (the flat price) and what the meter would have said.
            $table->decimal('credits', 10, 2)->default(0);
            $table->decimal('meteredCredits', 10, 2)->default(0);
            $table->timestamps();
            $table->index(['kind', 'created_at']);
            $table->index('payerId');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('as_ai_usage');
    }
};
