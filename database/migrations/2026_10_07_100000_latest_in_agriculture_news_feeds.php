<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Latest in Agriculture (2026-10-07): the blog's new name, and the farm news
 * roundups that lead it (App\Services\NewsRoundup).
 *
 *   as_news_feeds    the RSS feeds the roundups read, kept in the mother app
 *   as_news_items    every story the feeds gave, once each (by its link), and
 *                    the roundup that featured it, so no story runs twice
 *   as_news_runs     each time the roundup was asked for: written, skipped
 *                    (and why) or failed
 *   as_site_pages.kind   'roundup' on a page the roundup wrote (its schema
 *                    is a NewsArticle)
 *
 * The cron address's key is made here and kept on the site settings shelf
 * (news.cron_key), where the mother app shows it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_news_feeds')) {
            Schema::create('as_news_feeds', function (Blueprint $t) {
                $t->id();
                $t->string('url', 600);
                $t->char('urlHash', 40)->unique();
                $t->string('label', 190)->nullable();
                $t->boolean('isActive')->default(true);
                $t->timestamp('lastFetchedAt')->nullable();
                $t->string('lastStatus', 190)->nullable();
                $t->unsignedInteger('lastItemCount')->default(0);
                $t->tinyInteger('deleteStatus')->default(1);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_news_items')) {
            Schema::create('as_news_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('feedId')->index();
                $t->char('linkHash', 40)->unique();
                $t->string('title', 400);
                $t->string('link', 1000);
                $t->string('source', 120)->nullable();
                $t->string('imageUrl', 1500)->nullable();
                $t->text('summary')->nullable();
                $t->boolean('isSocial')->default(false);
                $t->timestamp('publishedAt')->nullable()->index();
                $t->unsignedBigInteger('featuredPageId')->nullable()->index();
                $t->timestamp('featuredAt')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('as_news_runs')) {
            Schema::create('as_news_runs', function (Blueprint $t) {
                $t->id();
                $t->string('status', 16)->index();        // running, created, skipped, failed
                $t->string('reason', 500)->nullable();
                $t->string('trigger', 24)->nullable();    // cron, mother, schedule
                $t->unsignedBigInteger('pageId')->nullable();
                $t->date('rangeFrom')->nullable();
                $t->date('rangeTo')->nullable();
                $t->unsignedInteger('itemCount')->default(0);
                $t->unsignedInteger('tokensIn')->default(0);
                $t->unsignedInteger('tokensOut')->default(0);
                $t->timestamps();
            });
        }
        if (Schema::hasTable('as_site_pages') && ! Schema::hasColumn('as_site_pages', 'kind')) {
            Schema::table('as_site_pages', fn (Blueprint $t) => $t->string('kind', 24)->nullable()->after('section'));
        }

        $now = now();
        foreach ([
            ['https://rss.app/feeds/tuE5tz4Yo2Lx8ihp.xml', 'Weather, PAGASA and typhoon watch'],
            ['https://rss.app/feeds/ta5oEaTP2H1eiqpv.xml', 'Gasoline and diesel prices'],
            ['https://rss.app/feeds/tfQs2zJ22KfGGpem.xml', 'Department of Agriculture and PhilRice'],
            ['https://rss.app/feeds/tXh6fBFnBTb9nEzt.xml', 'Agriculture news PH'],
            ['https://rss.app/feeds/tu9BxwtG8kRXRzak.xml', 'Agriculture in the Philippines'],
        ] as [$url, $label]) {
            DB::table('as_news_feeds')->insertOrIgnore(['url' => $url, 'urlHash' => sha1($url), 'label' => $label, 'isActive' => 1,
                'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now]);
        }

        if (Schema::hasTable('as_site_settings')) {
            if (! DB::table('as_site_settings')->where('key', 'news.cron_key')->exists()) {
                DB::table('as_site_settings')->insert(['key' => 'news.cron_key', 'value' => Str::random(40), 'created_at' => $now, 'updated_at' => $now]);
            }
            if (! DB::table('as_site_settings')->where('key', 'news.every_days')->exists()) {
                DB::table('as_site_settings')->insert(['key' => 'news.every_days', 'value' => '3', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('as_news_runs');
        Schema::dropIfExists('as_news_items');
        Schema::dropIfExists('as_news_feeds');
        if (Schema::hasTable('as_site_pages') && Schema::hasColumn('as_site_pages', 'kind')) {
            Schema::table('as_site_pages', fn (Blueprint $t) => $t->dropColumn('kind'));
        }
    }
};
