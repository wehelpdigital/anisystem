<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Market prices (2026-10-27, the owner: "analyze all these files compiled,
 * put it in the database and create a new module in the global tools ...
 * the main goal here is to show when to sell or trade the output of crops
 * based on its market price").
 *
 * The owner's collection of farmgate and wholesale prices, read by
 * database/market/build.py and loaded by `php artisan market:load`:
 *  - as_market_items   the market items, each tied to one of our crops, with
 *                      its unit and how long it keeps in storage;
 *  - as_market_series  one monthly price series per item, level (farmgate or
 *                      wholesale) and place (the country, a region, a
 *                      province), 1990 to 2026, the months as a JSON list;
 *  - as_market_local   dated local price reports by province and town
 *                      (mostly palay and corn), without the reporters' names;
 *  - as_market_events  imports, bans, tariffs and the like that moved a
 *                      crop's farmgate price, in our own words;
 *  - as_market_imports a crop's import volume by year.
 * The forecasts themselves are worked out from the series when asked
 * (App\Support\MarketBook), and saved forecasts live on the analyses shelf.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_market_items')) {
            Schema::create('as_market_items', function (Blueprint $t) {
                $t->id();
                $t->string('itemKey', 40)->unique();
                $t->string('name', 120);
                $t->string('cropKey', 40)->nullable()->index();
                $t->string('unit', 10)->default('kg');
                $t->unsignedTinyInteger('storeMonths')->default(0);
                $t->decimal('lossPct', 5, 2)->default(0);
                $t->boolean('hasFarmgate')->default(true);
                $t->boolean('hasWholesale')->default(false);
                $t->decimal('wsFactor', 5, 3)->default(1);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_market_series')) {
            Schema::create('as_market_series', function (Blueprint $t) {
                $t->id();
                $t->string('itemKey', 40);
                $t->char('level', 1);
                $t->string('geoKey', 60);
                $t->string('geoLevel', 10);
                $t->string('geoName', 80);
                $t->string('regionCode', 4)->nullable();
                $t->string('regionName', 40)->nullable();
                $t->char('startYm', 7);
                $t->char('lastYm', 7);
                $t->unsignedInteger('points');
                $t->mediumText('monthly');
                $t->timestamps();
                $t->unique(['itemKey', 'level', 'geoKey']);
                $t->index(['geoKey', 'level']);
            });
        }
        if (! Schema::hasTable('as_market_local')) {
            Schema::create('as_market_local', function (Blueprint $t) {
                $t->id();
                $t->string('itemKey', 40);
                $t->string('form', 40)->nullable();
                $t->string('buyer', 60)->nullable();
                $t->decimal('price', 8, 2);
                $t->decimal('priceLo', 8, 2)->nullable();
                $t->decimal('priceHi', 8, 2)->nullable();
                $t->unsignedSmallInteger('year');
                $t->unsignedTinyInteger('month')->nullable();
                $t->date('day')->nullable();
                $t->string('region', 60)->nullable();
                $t->string('province', 80)->nullable();
                $t->string('town', 80)->nullable();
                $t->string('kind', 12)->default('other');
                $t->boolean('fromImage')->default(false);
                $t->boolean('selfReport')->default(false);
                $t->text('url')->nullable();
                $t->timestamps();
                $t->index(['itemKey', 'province', 'year']);
            });
        }
        if (! Schema::hasTable('as_market_events')) {
            Schema::create('as_market_events', function (Blueprint $t) {
                $t->id();
                $t->string('eventKey', 20)->unique();
                $t->json('crops');
                $t->date('day');
                $t->string('precision', 5)->default('day');
                $t->date('endDay')->nullable();
                $t->string('kind', 24);
                $t->string('direction', 6);
                $t->string('title', 160);
                $t->text('summary');
                $t->decimal('importTons', 14, 0)->nullable();
                $t->decimal('tariffFrom', 6, 2)->nullable();
                $t->decimal('tariffTo', 6, 2)->nullable();
                $t->text('farmgateNote')->nullable();
                $t->string('scope', 80)->default('national');
                $t->json('sources');
                $t->text('quote')->nullable();
                $t->timestamps();
                $t->index('day');
            });
        }
        if (! Schema::hasTable('as_market_imports')) {
            Schema::create('as_market_imports', function (Blueprint $t) {
                $t->id();
                $t->string('cropKey', 40);
                $t->unsignedSmallInteger('year');
                $t->decimal('tons', 14, 0);
                $t->string('basis', 80)->nullable();
                $t->boolean('partialYear')->default(false);
                $t->json('source')->nullable();
                $t->text('quote')->nullable();
                $t->timestamps();
                $t->index(['cropKey', 'year']);
            });
        }
    }

    public function down(): void
    {
        foreach (['as_market_imports', 'as_market_events', 'as_market_local', 'as_market_series', 'as_market_items'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
