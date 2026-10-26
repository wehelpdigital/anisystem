<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The farm news the market shelf has read (2026-10-27, the owner: "since we
 * are now getting agri news daily for the newsletter from the rss, if there
 * are updates about the import, better if to save it as well and include it
 * in the analysis"). App\Support\MarketNews reads each new story in
 * as_news_items once: a story about imports, tariffs, bans or a crop's
 * farmgate price becomes a market event (as_market_events, key "n" + the
 * story's id), and every story read is noted here so none is asked twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_market_news_seen')) {
            Schema::create('as_market_news_seen', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('newsItemId')->unique();
                $t->boolean('hit')->default(false);
                $t->string('eventKey', 20)->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_market_news_seen');
    }
};
