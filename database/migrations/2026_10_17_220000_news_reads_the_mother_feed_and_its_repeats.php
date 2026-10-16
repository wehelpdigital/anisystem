<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Latest in Agriculture reads the mother app's own RSS (2026-10-16, the
 * owner: "use this rss instead since we created this in the mother app"):
 * its RSS Feeds module compiles the farm news sources into one feed. The five
 * rss.app feeds answered HTTP 402 since their plan ran out, so they are paused
 * (not deleted: the mother's Latest in Agriculture page can turn them back on).
 *
 * as_news_items.sameAsId: a story that tells news another story already
 * tells (the cheap model's check, NewsRoundup::sameNews): the lead story of
 * its group, or the posted story it repeats.
 */
return new class extends Migration
{
    private const MOTHER_FEED = 'https://dragonscale-axis-production-3mf0e4.laravel.cloud/rss/qfpbyOvb6rQJtjqEbjp5.xml';

    public function up(): void
    {
        if (Schema::hasTable('as_news_items') && ! Schema::hasColumn('as_news_items', 'sameAsId')) {
            Schema::table('as_news_items', function (Blueprint $t) {
                $t->unsignedBigInteger('sameAsId')->nullable()->index()->after('featuredAt');
            });
        }
        if (! Schema::hasTable('as_news_feeds')) {
            return;
        }
        $now = now();
        DB::table('as_news_feeds')->insertOrIgnore(['url' => self::MOTHER_FEED, 'urlHash' => sha1(self::MOTHER_FEED),
            'label' => 'All farm news (the mother app\'s RSS Feeds)', 'isActive' => 1, 'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('as_news_feeds')->where('url', 'like', 'https://rss.app/%')->where('isActive', 1)
            ->update(['isActive' => 0, 'lastStatus' => 'Paused on Oct 16, 2026: the mother app\'s own feed replaces it', 'updated_at' => $now]);
    }

    public function down(): void
    {
        // The feeds stay as they are; the column stays (it only remembers).
    }
};
