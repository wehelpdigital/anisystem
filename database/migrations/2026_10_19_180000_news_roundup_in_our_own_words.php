<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Our own words in the news already written (the owner, 2026-10-17). The
 * seven published roundups were checked against the feed items they were
 * written from (CopyCheck): one, the hand written first roundup of
 * 2026-10-07, kept a stretch of a feed's own sentence about the weather
 * and rice data agreement, and one answer echoed another feed. Both are said
 * our way; a page someone edited in the mother app (editedAt) is left alone.
 * The page's updated_at moves, so the Tech Blog draws it again.
 */
return new class extends Migration
{
    private const PAIRS = [
        ['signed an agreement to combine weather, climate and rice data for better farm planning and rice that holds up to a changing climate.',
            'signed an agreement to pool their weather, climate and rice records, so farms can plan better and grow rice that holds up to a changing climate.'],
        ['and it is stocking up on food and farm inputs.', 'and it is building up its reserves of food and farm inputs.'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        $rows = DB::table('as_site_pages')->where('kind', 'roundup')->whereNull('editedAt')
            ->where(fn ($q) => $q->where('blocks', 'like', '%combine weather, climate and rice data%')->orWhere('blocks', 'like', '%stocking up on food and farm inputs%'))
            ->get(['id', 'blocks']);
        foreach ($rows as $p) {
            $blocks = json_decode((string) $p->blocks, true);
            if (! is_array($blocks)) {
                continue;
            }
            $hit = 0;
            $walk = function ($v) use (&$walk, &$hit) {
                if (is_array($v)) {
                    return array_map($walk, $v);
                }
                if (is_string($v)) {
                    foreach (self::PAIRS as [$old, $new]) {
                        if (str_contains($v, $old)) {
                            $v = str_replace($old, $new, $v);
                            $hit++;
                        }
                    }
                }

                return $v;
            };
            $blocks = $walk($blocks);
            if ($hit) {
                DB::table('as_site_pages')->where('id', $p->id)
                    ->update(['blocks' => json_encode($blocks), 'updated_at' => now()]);
            }
        }
        SitePages::forgetCaches();
    }

    public function down(): void
    {
        // The new words stay.
    }
};
