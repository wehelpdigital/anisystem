<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The free plan carries advertising.
 *
 * Two tables, managed from the mother app (AniSystem > Ads) and read by
 * anee.io: the units -- a Google AdSense slot, a picture that links
 * somewhere, or a script an ad network handed over -- each with the
 * surfaces it may appear on and a weight for the draw; and one row of
 * settings: the master switch, the AdSense publisher id, and the words
 * the slot wears. Only a Libre account on its own farm sees any of it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_ad_units')) {
            Schema::create('as_ad_units', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                // adsense | image | script
                $table->string('kind', 16)->default('image');
                // Where it may appear: a list of surface keys, empty = anywhere.
                $table->json('placements')->nullable();
                $table->unsignedSmallInteger('weight')->default(1);
                $table->boolean('isActive')->default(true);
                $table->dateTime('startsAt')->nullable();
                $table->dateTime('endsAt')->nullable();
                // AdSense
                $table->string('adClient', 64)->nullable();
                $table->string('adSlot', 64)->nullable();
                $table->string('adFormat', 24)->nullable();
                // A picture that links somewhere
                $table->string('imagePath', 255)->nullable();
                $table->string('imageAlt', 191)->nullable();
                $table->string('linkUrl', 500)->nullable();
                // A network's own tag
                $table->text('scriptHtml')->nullable();
                $table->unsignedInteger('impressions')->default(0);
                $table->unsignedInteger('clicks')->default(0);
                $table->integer('sortOrder')->default(0);
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamps();
                $table->index(['isActive', 'deleteStatus']);
            });
        }

        if (! Schema::hasTable('as_ad_settings')) {
            Schema::create('as_ad_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('isEnabled')->default(false);
                $table->string('adsenseClient', 64)->nullable();
                // Also load AdSense's own auto ads on the pages that carry slots.
                $table->boolean('autoAds')->default(false);
                $table->string('label', 60)->default('Sponsored');
                $table->string('upsell', 191)->default('Go ad-free with a paid plan');
                // In the community feed: one slot every this many posts.
                $table->unsignedTinyInteger('feedEvery')->default(6);
                $table->tinyInteger('deleteStatus')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_ad_units');
        Schema::dropIfExists('as_ad_settings');
    }
};
