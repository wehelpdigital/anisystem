<?php

namespace App\Support;

/**
 * The house writing rules (database/site-pages/STYLE.md), applied to words a
 * model wrote before they go on the public site (2026-10-01).
 *
 * The prompt asks for all of this; a model still slips. So the mechanical
 * rules are made true here: no dashes or hyphens in prose, no semicolons,
 * ampersands, ellipses or % signs, and every banned word swapped for the
 * plain one the guide gives. Links are left alone: an address may carry a
 * hyphen, a label may not.
 */
class ArticleStyle
{
    /** Banned word or phrase => what the guide says to write instead. */
    public const SWAPS = [
        'by paying attention' => 'by watching', 'in summary' => 'in short', 'smooth experience' => 'easy time',
        'daunting task' => 'hard job', 'in this article' => 'here', 'in conclusion' => 'in the end',
        'breaking the bank' => 'costing too much', 'break the bank' => 'cost too much', "today's digital age" => 'today',
        'moreover' => 'also', 'furthermore' => 'also', 'additionally' => 'also', 'lastly' => 'finally',
        'in addition' => 'besides', 'therefore' => 'so', 'ultimately' => 'in the end', 'informed decision' => 'better choice',
        'dive into' => 'look at', 'dive in' => 'start', 'delve into' => 'look at', 'delves' => 'looks', 'delve' => 'look',
        'delving' => 'looking', 'fascinating world' => 'subject', 'performing your research' => 'checking',
        'doing your research' => 'checking', 'explore the world of' => 'learn about', 'consequently' => 'as a result',
        'utilizing' => 'using', 'utilized' => 'used', 'utilize' => 'use', 'implementing' => 'carrying out',
        'implemented' => 'carried out', 'implement' => 'carry out', 'in order to' => 'to', 'pertaining to' => 'about',
        'with regard to' => 'about', 'regarding' => 'about', 'subsequently' => 'later', 'thus' => 'so',
        'facilitates' => 'helps', 'facilitate' => 'help', 'prior to' => 'before', 'in the event of' => 'if there is',
        'owing to' => 'because of', 'in light of' => 'considering', 'on the contrary' => 'instead',
        'in the midst of' => 'during', 'despite' => 'even with', 'in accordance with' => 'following',
        'subsequent to' => 'after', 'commence' => 'begin', 'commences' => 'begins', 'endeavor' => 'try',
        'in lieu of' => 'instead of', 'notwithstanding' => 'even so', 'in conjunction with' => 'along with',
        'landscape' => 'setting', 'realm' => 'area', 'navigating' => 'handling', 'navigate' => 'handle',
        'tailored' => 'customized', 'underpins' => 'supports', 'unveils' => 'reveals', 'unveil' => 'reveal',
        'transformative' => 'big', 'encompasses' => 'covers', 'encompass' => 'cover', 'dynamic' => 'changing',
        'the world' => 'the globe', 'world' => 'globe', 'ecosystem' => 'natural system', 'confluence' => 'meeting',
        'engaging' => 'interesting', 'quest' => 'search', 'solutions' => 'answers', 'significantly' => 'greatly',
        'significant' => 'big', 'specific' => 'exact', 'numerous' => 'many', 'unsatisfied' => 'unhappy',
        'crafted' => 'made', 'craft' => 'make', 'glean' => 'gather', 'glance' => 'look', 'enhancing' => 'improving',
        'unlock' => 'open', 'seamless' => 'smooth', 'robust' => 'strong', 'leverage' => 'use', 'elevate' => 'raise',
        'harness' => 'use', 'comprehensive' => 'complete', 'game changer' => 'big help', 'boasts' => 'has',
        'a testament' => 'proof', 'treasure trove' => 'rich source', 'nestled' => 'set', 'vibrant' => 'lively',
        "whether you're" => 'if you are', 'look no further' => 'here it is', 'embarking' => 'starting',
        'embark' => 'start', 'journey' => 'path', 'tapestry' => 'mix', 'intricate' => 'detailed', 'pivotal' => 'key',
        'crucial' => 'important', 'vital' => 'important', 'paramount' => 'most important', 'meticulous' => 'careful',
        'meticulously' => 'carefully', 'bustling' => 'busy', 'foster' => 'build', 'empowers' => 'helps',
        'empower' => 'help', 'streamline' => 'simplify', 'cutting edge' => 'modern', 'state of the art' => 'modern',
        "in today's" => 'in the', "it's worth noting that" => 'note that', 'it is worth noting that' => 'note that',
        "it's worth noting" => 'note that', 'it is important to note that' => 'note that',
        'it is important to note' => 'note', 'when it comes to' => 'for', 'at the end of the day' => 'in the end',
        'plays a key role' => 'matters a lot', 'a key role' => 'a big part',
    ];

    /**
     * Keywords that read backwards in a sentence, put in the order a person
     * says them (STYLE.md: "fertilizer urea" becomes "urea fertilizer").
     */
    public const ORDER = [
        'fertilizer urea' => 'urea fertilizer', 'fertilizer yara' => 'Yara fertilizer', 'fertilizer atlas' => 'Atlas fertilizer',
        'rice variety philippines' => 'rice varieties in the Philippines', 'fertilizer price philippines' => 'fertilizer prices in the Philippines',
    ];

    /** Names written the way their owners write them, whatever case a keyword came in. */
    public const PROPER = [
        'fertilizer and pesticide authority', 'department of agriculture', 'bureau of plant industry', 'bureau of soils and water management',
        'philippine rice research institute', 'international rice research institute', 'philippine coconut authority',
        'agricultural training institute', 'philippine statistics authority', 'philrice', 'philmech', 'pagasa', 'uplb',
        'yara', 'atlas', 'dekalb', 'syngenta', 'bayer', 'east west seed', 'ramgo', 'allied botanical',
    ];

    private const CANON = [
        'philrice' => 'PhilRice', 'philmech' => 'PhilMech', 'pagasa' => 'PAGASA', 'uplb' => 'UPLB', 'east west seed' => 'East West Seed',
    ];

    /** A line of prose made to follow the rules; [label](address) links kept whole. */
    public static function clean(?string $text): string
    {
        $text = (string) $text;
        if (trim($text) === '') {
            return '';
        }
        // Take the addresses out, fix the words, put the addresses back.
        $urls = [];
        $text = preg_replace_callback('/\]\(([^)\s]+)\)/', function ($m) use (&$urls) {
            $urls[] = $m[1];

            return '](@@' . (count($urls) - 1) . '@@)';
        }, $text);

        $text = str_replace(["\u{2014}", "\u{2013}", "\u{2012}", "\u{2015}"], ' - ', $text);
        // A dash between words is a pause: a comma.
        $text = preg_replace('/\s+-{1,2}\s+/u', ', ', $text);
        // A hyphen inside a word or a number joins them: a space ("well drained", "14 14 14").
        $text = preg_replace('/(?<=[\p{L}\p{N}])-(?=[\p{L}\p{N}])/u', ' ', $text);
        $text = str_replace(["\u{2026}", '...'], '.', $text);
        $text = str_replace([';'], '.', $text);
        $text = str_replace(' & ', ' and ', $text);
        $text = str_replace('&', ' and ', $text);
        $text = preg_replace('/(\d)\s?%/u', '$1 percent', $text);
        $text = str_replace(["\u{201C}", "\u{201D}"], '"', $text);
        $text = str_replace(["\u{2018}", "\u{2019}"], "'", $text);
        $text = str_replace(['→', '←', '⇒'], 'to', $text);

        foreach (self::SWAPS as $bad => $good) {
            $text = preg_replace_callback('/\b' . preg_quote($bad, '/') . '\b/iu', function ($m) use ($good) {
                $w = $m[0];
                if (mb_strtoupper(mb_substr($w, 0, 1)) === mb_substr($w, 0, 1) && mb_strtolower(mb_substr($w, 0, 1)) !== mb_substr($w, 0, 1)) {
                    return mb_strtoupper(mb_substr($good, 0, 1)) . mb_substr($good, 1);
                }

                return $good;
            }, $text);
        }
        foreach (self::ORDER as $bad => $good) {
            $text = preg_replace('/\b' . preg_quote($bad, '/') . '\b/iu', $good, $text);
        }
        foreach (self::PROPER as $name) {
            $canon = self::CANON[$name] ?? ucwords($name);
            $canon = str_replace([' And ', ' Of '], [' and ', ' of '], $canon);
            $text = preg_replace('/\b' . preg_quote($name, '/') . '\b/iu', $canon, $text);
        }
        // Tidy what the swaps left: ". ." and ", ," and doubled spaces.
        $text = preg_replace('/\s+([,.])/u', '$1', $text);
        $text = preg_replace('/([,.]){2,}/u', '$1', $text);
        $text = preg_replace('/ {2,}/', ' ', $text);

        return preg_replace_callback('/\]\(@@(\d+)@@\)/', fn ($m) => '](' . ($urls[(int) $m[1]] ?? '') . ')', trim($text));
    }

    /** A page's blocks, every string of prose cleaned; addresses and kinds untouched. */
    public static function cleanBlocks(array $blocks): array
    {
        $skip = ['type', 'url', 'src', 'tone', 'level', 'ordered'];
        $walk = function ($v, $k = null) use (&$walk, $skip) {
            if (is_array($v)) {
                $out = [];
                foreach ($v as $kk => $vv) {
                    $out[$kk] = is_string($kk) && in_array($kk, $skip, true) ? $vv : $walk($vv, $kk);
                }

                return $out;
            }

            return is_string($v) ? self::clean($v) : $v;
        };

        return array_map(fn ($b) => is_array($b) ? $walk($b) : $b, $blocks);
    }

    /** The banned words still present (for a check, not a fix). */
    public static function offences(string $text): array
    {
        $found = [];
        foreach (array_keys(self::SWAPS) as $bad) {
            if (preg_match('/\b' . preg_quote($bad, '/') . '\b/iu', $text)) {
                $found[] = $bad;
            }
        }

        return $found;
    }
}
