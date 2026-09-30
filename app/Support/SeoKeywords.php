<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The search keywords the site writes toward (as_seo_keywords, 2026-10-01).
 *
 * Loaded first from database/seo/anisenso_keywords.csv (No, Keyword, Volume,
 * CPC, Paid Difficulty, SEO Difficulty) and grown from the mother app's
 * AniSystem > SEO keywords, which imports the same shape. The Ask Anee
 * articles draw their focus and secondary keywords from here: the ones
 * nearest a question are offered to the writer (candidates()) and the ones
 * it used are counted (markUsed()), so the next article reaches for a
 * keyword not yet worn out.
 */
class SeoKeywords
{
    public const TABLE = 'as_seo_keywords';

    /** Words that say nothing about a topic, left out of the matching. */
    private const STOP = ['the', 'and', 'for', 'with', 'what', 'when', 'where', 'which', 'how', 'why', 'does', 'can',
        'should', 'will', 'are', 'is', 'my', 'our', 'your', 'you', 'this', 'that', 'from', 'into', 'about', 'there',
        'ang', 'ng', 'mga', 'sa', 'na', 'ba', 'ko', 'po', 'ano', 'paano', 'kung', 'para', 'may', 'ako', 'ito', 'yung',
        'bakit', 'kailan', 'saan', 'lang', 'din', 'rin', 'naman', 'pag', 'kapag'];

    public static function ready(): bool
    {
        try {
            return Schema::hasTable(self::TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Read a keyword CSV in the export shape and add or refresh every row.
     * Headers are found by name, so "Volume" and "Search Volume" both work,
     * and a CPC written "₱45.86" is read as 45.86.
     *
     * @return array{added: int, updated: int, skipped: int}
     */
    public static function importCsv(string $csv, string $source = 'import'): array
    {
        $counts = ['added' => 0, 'updated' => 0, 'skipped' => 0];
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $lines = preg_split('/\r\n|\n|\r/', trim((string) $csv)) ?: [];
        if (count($lines) < 2) {
            return $counts;
        }
        $head = array_map(fn ($h) => strtolower(trim((string) $h)), str_getcsv(array_shift($lines)));
        $col = function (array $names) use ($head): ?int {
            foreach ($names as $n) {
                $i = array_search($n, $head, true);
                if ($i !== false) {
                    return $i;
                }
            }

            return null;
        };
        $kw = $col(['keyword', 'keywords', 'key word']);
        $vol = $col(['volume', 'search volume', 'avg. monthly searches']);
        $cpc = $col(['cpc', 'cost per click']);
        $paid = $col(['paid difficulty', 'pd']);
        $seo = $col(['seo difficulty', 'sd', 'keyword difficulty']);
        if ($kw === null) {
            return $counts;
        }
        $num = fn ($v) => ($v = preg_replace('/[^0-9.]/', '', (string) $v)) === '' ? null : (float) $v;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $r = str_getcsv($line);
            $keyword = self::normalize((string) ($r[$kw] ?? ''));
            if ($keyword === '' || mb_strlen($keyword) > 190) {
                $counts['skipped']++;

                continue;
            }
            $row = [
                'volume' => $vol !== null ? (int) ($num($r[$vol] ?? '') ?? 0) : 0,
                'cpc' => $cpc !== null ? $num($r[$cpc] ?? '') : null,
                'paidDifficulty' => $paid !== null && $num($r[$paid] ?? '') !== null ? (int) $num($r[$paid]) : null,
                'seoDifficulty' => $seo !== null && $num($r[$seo] ?? '') !== null ? (int) $num($r[$seo]) : null,
                'updated_at' => now(),
            ];
            $existing = DB::table(self::TABLE)->where('keyword', $keyword)->first();
            if ($existing) {
                DB::table(self::TABLE)->where('id', $existing->id)->update($row + ['deleteStatus' => 1]);
                $counts['updated']++;
            } else {
                DB::table(self::TABLE)->insert($row + ['keyword' => $keyword, 'source' => $source, 'usedCount' => 0,
                    'deleteStatus' => 1, 'created_at' => now()]);
                $counts['added']++;
            }
        }

        return $counts;
    }

    public static function normalize(string $k): string
    {
        return trim((string) preg_replace('/\s+/', ' ', mb_strtolower(trim($k, " \t\"'"))));
    }

    /**
     * The keywords nearest a piece of text, best first: shared words count
     * most, then search volume, then how little the keyword has been used,
     * then how easy it is to rank for.
     *
     * @return Collection<int, object>
     */
    public static function candidates(string $text, int $n = 25): Collection
    {
        if (! self::ready()) {
            return collect();
        }
        $words = self::words($text);
        $rows = DB::table(self::TABLE)->where('deleteStatus', 1)->get();
        $scored = $rows->map(function ($r) use ($words) {
            $kw = self::words($r->keyword);
            $hit = count(array_intersect($kw, $words));
            // A keyword that shares nothing with the question is still worth
            // offering when nothing else is (the big palay terms), but last.
            $r->score = $hit * 1000
                + min(400, (int) round(log(max(1, (int) $r->volume)) * 40))
                - min(200, (int) $r->usedCount * 25)
                - (int) ($r->seoDifficulty ?? 30);
            $r->hit = $hit;

            return $r;
        });

        return $scored->sortByDesc('score')->take($n)->values();
    }

    /** Count the keywords a finished article actually carries. */
    public static function markUsed(string $articleText, array $keywords): void
    {
        if (! self::ready()) {
            return;
        }
        $hay = ' ' . mb_strtolower($articleText) . ' ';
        foreach (array_unique(array_map([self::class, 'normalize'], $keywords)) as $k) {
            if ($k !== '' && str_contains($hay, $k)) {
                DB::table(self::TABLE)->where('keyword', $k)->update([
                    'usedCount' => DB::raw('usedCount + 1'),
                    'lastUsedAt' => now(),
                ]);
            }
        }
    }

    /** @return list<string> */
    public static function words(string $text): array
    {
        $w = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text)) ?: [];

        return array_values(array_unique(array_filter($w, fn ($x) => mb_strlen($x) >= 3 && ! in_array($x, self::STOP, true))));
    }
}
