<?php

namespace App\Support;

/**
 * Our own words, never the source's sentences (the owner, 2026-10-17: "the
 * wordings we use except for of course names that are or should be exact
 * are different from how it is used in the original source to avoid
 * plagiarism").
 *
 * runs() finds the stretches of words a text shares with its source. A
 * stretch counts when it holds RUN or more ordinary words, three of them
 * more than little words like "the" or "of". Names and numbers are free:
 * they may sit inside a stretch but do not count toward RUN, so keeping a
 * storm's name, a minister's name, a price or a date exact is never called
 * copying. A word is a name when the source writes it with a capital
 * letter in the middle of a sentence ("Secretary Francisco Tiu Laurel"),
 * or when the caller passes it in $names.
 *
 * The same rule as the scratchpad reword/scripts/shingle.py the site pages
 * were measured with (7 words there for the shipped pages; the news check
 * uses 8, the owner's line for text Anee writes from a feed).
 */
final class CopyCheck
{
    public const RUN = 8;

    /** Said to every model that writes for anee.io (AiSetting's house rules), beside GovSource::RULE. */
    public const RULE = 'Write in your own words. When you use a web page, a report, a label, a news story or any text you were '
        . 'given, take the facts and say them your way: never copy its sentences, and never repeat a stretch of eight words or '
        . 'more from it. What stays exact: names (crops, varieties, pests, diseases, weeds, products, active ingredients and '
        . 'their groups, people, places, storms, agencies when the news is about them), numbers, rates, prices, dates and '
        . 'standard terms such as tillering or panicle initiation. A quotation is the one exception: mark it as a quote and '
        . 'keep it short. Anything you store or publish (a summary, a description, a note, an article) follows the same rule.';

    private const STOP = ['a', 'an', 'the', 'of', 'to', 'in', 'on', 'at', 'by', 'for', 'and', 'or', 'but', 'if', 'then', 'so', 'as',
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'it', 'its', 'this', 'that', 'these', 'those', 'with', 'from', 'into',
        'onto', 'over', 'under', 'than', 'there', 'their', 'they', 'them', 'he', 'she', 'his', 'her', 'we', 'our', 'you', 'your',
        'i', 'me', 'my', 'not', 'no', 'do', 'does', 'did', 'can', 'could', 'will', 'would', 'should', 'may', 'might', 'must',
        'has', 'have', 'had', 'which', 'who', 'whom', 'whose', 'what', 'when', 'where', 'why', 'how', 'also', 'more', 'most',
        'less', 'very', 'up', 'down', 'out', 'off', 'about', 'after', 'before', 'while', 'during', 'through', 'each', 'every',
        'any', 'all', 'some', 'such', 'own', 'same', 'other', 'just', 'only', 'said', 'says'];

    /** Lowercase words of a text: links keep their label, addresses and markup go, a hyphen splits. */
    public static function words(string $text): array
    {
        $t = self::plain($text);
        preg_match_all("/[\\p{L}\\p{N}]+(?:'[\\p{L}]+)?/u", mb_strtolower($t), $m);

        return $m[0];
    }

    /**
     * The shared stretches, longest first: [['run' => 'words ...', 'words' => counted], ...].
     *
     * @param  string|array<int, string>  $source
     * @param  array<int, string>  $names  extra names or terms that stay exact
     */
    public static function runs(string $ours, string|array $source, int $min = self::RUN, array $names = []): array
    {
        $src = array_values(array_filter(array_map('strval', (array) $source), fn ($s) => trim($s) !== ''));
        if (! $src || trim($ours) === '') {
            return [];
        }
        $free = self::names($src, $names);
        $a = self::words($ours);
        // Each source on its own, so a stretch never runs from one into the next.
        $index = [];
        $docs = [];
        foreach ($src as $d => $s) {
            $docs[$d] = self::words($s);
            $n = count($docs[$d]);
            for ($j = 0; $j + 2 < $n; $j++) {
                $index[$docs[$d][$j] . ' ' . $docs[$d][$j + 1] . ' ' . $docs[$d][$j + 2]][] = [$d, $j];
            }
        }
        $out = [];
        $na = count($a);
        $i = 0;
        while ($i + 2 < $na) {
            $best = 0;
            foreach ($index[$a[$i] . ' ' . $a[$i + 1] . ' ' . $a[$i + 2]] ?? [] as [$d, $j]) {
                $b = $docs[$d];
                $k = 3;
                while ($i + $k < $na && $j + $k < count($b) && $a[$i + $k] === $b[$j + $k]) {
                    $k++;
                }
                $best = max($best, $k);
            }
            if ($best >= 3) {
                $run = array_slice($a, $i, $best);
                $counted = 0;
                $content = 0;
                foreach ($run as $w) {
                    if (isset($free[$w]) || preg_match('/\d/', $w)) {
                        continue;
                    }
                    $counted++;
                    if (! in_array($w, self::STOP, true)) {
                        $content++;
                    }
                }
                if ($counted >= $min && $content >= 3) {
                    $out[] = ['run' => implode(' ', $run), 'words' => $counted];
                    $i += $best;

                    continue;
                }
            }
            $i++;
        }
        usort($out, fn ($x, $y) => $y['words'] <=> $x['words']);

        return $out;
    }

    /** Does $ours copy a stretch of $source? */
    public static function copies(string $ours, string|array $source, int $min = self::RUN, array $names = []): bool
    {
        return self::runs($ours, $source, $min, $names) !== [];
    }

    /** The longest shared stretch, for a message, or null. */
    public static function first(string $ours, string|array $source, int $min = self::RUN, array $names = []): ?string
    {
        return self::runs($ours, $source, $min, $names)[0]['run'] ?? null;
    }

    /** Words the sources treat as names: capitalised in the middle of a sentence. */
    private static function names(array $src, array $extra): array
    {
        $free = [];
        foreach ($extra as $n) {
            foreach (self::words((string) $n) as $w) {
                if (! in_array($w, self::STOP, true)) {
                    $free[$w] = true;
                }
            }
        }
        foreach ($src as $s) {
            $t = self::plain($s);
            // Each sentence's first word is capitalised anyway: skip it.
            foreach (preg_split('/(?<=[.!?:])\s+|\n+/u', $t) as $sentence) {
                preg_match_all("/[\\p{L}\\p{N}]+(?:'[\\p{L}]+)?/u", $sentence, $m);
                $caps = count(array_filter($m[0], fn ($w) => preg_match('/^\p{Lu}/u', $w)));
                if (count($m[0]) >= 4 && $caps * 2 > count($m[0])) {
                    continue;   // a headline in Title Case: its capitals say nothing about names
                }
                foreach (array_slice($m[0], 1) as $w) {
                    if (preg_match('/^\p{Lu}/u', $w) && ! in_array(mb_strtolower($w), self::STOP, true)) {
                        $free[mb_strtolower($w)] = true;
                    }
                }
            }
        }

        return $free;
    }

    private static function plain(string $text): string
    {
        $t = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $t);
        $t = preg_replace('#https?://\S+#u', ' ', $t);
        $t = str_replace(["\u{2019}", "\u{2018}"], "'", $t);

        return preg_replace('/[\-\x{2010}-\x{2015}\/]/u', ' ', $t);
    }
}
