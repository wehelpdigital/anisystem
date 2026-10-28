<?php

namespace App\Support;

/**
 * What a variety's own seed protects it from (2026-10-28, the owner: "you are also not taking consideration of the
 * resistance of specific crops to specific pests, or diseases ... VIP and VT2PRO's have BT traits that allows them
 * to protect the corn from FAW and other insects ... so technically in the protocols on mid to late stage the corn
 * using these is already protected and no need to spray for pre-emptive protection, targetting these specific pests").
 *
 * Read from the variety record (VarietyBook): its name, other names, kind and the seller's words for the Bt events
 * and herbicide tolerance it carries, its registration ratings (HR, R, MR, T, I, MS, S, HS) for pests and diseases,
 * and its growing environments for the stresses it was bred for (flood, salt, drought, cool). One door for every
 * module: VarietyBook::promptLines says it in each variety's line (so every prompt that reads a variety hears it),
 * the Crop Protocol and Anee Auto drop the calendar sprays the seed makes needless (skip) and lighten the ones it
 * only slows (lighter), and watch first what it is weak against (weak).
 *
 * The Bt levels are what the proteins are known to do against each caterpillar: strong (no calendar spray, scout
 * and spray only past the threshold), partial (it slows the pest: scout twice a week in its risky weeks, spray at
 * the threshold) and weak (no change). A Bt trait the seller does not name counts as Cry1Ab, the oldest and
 * narrowest, so fall armyworm is never left unguarded on a guess.
 */
final class VarietyShield
{
    /**
     * Bt events and the trade names that carry them: key => [the words, what it is, caterpillar slug => level].
     * Several in one seed stack: each pest takes its best level.
     */
    public const EVENTS = [
        'vip3a' => ['viptera|vip3a?a?(?:20)?|mir ?162|vip', 'Vip3A (Viptera)',
            ['fall-armyworm' => 'strong', 'corn-earworm' => 'strong', 'rice-armyworm' => 'strong', 'cutworm' => 'partial']],
        'cry1a105' => ['vt ?double ?pro|vtdoublepro|vt2p(?:ro)?|vt ?triple ?pro|vt3p|mon ?89034|genuity|smartstax|power ?core',
            'Cry1A.105 and Cry2Ab2 (VT Double PRO)', ['asian-corn-borer' => 'strong', 'corn-earworm' => 'strong', 'fall-armyworm' => 'partial', 'rice-armyworm' => 'partial']],
        'cry1f' => ['herculex|tc ?1507|cry1f|power ?core|smartstax', 'Cry1F (Herculex)',
            ['asian-corn-borer' => 'strong', 'fall-armyworm' => 'partial', 'cutworm' => 'partial']],
        'cry1ab' => ['yieldgard|mon ?810|bt ?11|cry1ab|agrisure|yieldgard', 'Cry1Ab (YieldGard, Bt11)',
            ['asian-corn-borer' => 'strong', 'corn-earworm' => 'partial']],
    ];

    /** Pioneer's letters after the number (P3482YR, 30T80YHR): Y YieldGard, H Herculex, R Roundup Ready. */
    private const SUFFIX = ['yhr' => ['cry1ab', 'cry1f'], 'yr' => ['cry1ab'], 'hr' => ['cry1f'], 'yh' => ['cry1ab', 'cry1f'], 'y' => ['cry1ab']];

    /** A rating's word: codes as the registration papers give them. */
    private const CODE = ['HR' => 'highly resistant', 'R' => 'resistant', 'MR' => 'moderately resistant', 'T' => 'tolerant', 'I' => 'intermediate',
        'MS' => 'moderately susceptible', 'S' => 'susceptible', 'HS' => 'highly susceptible'];

    /** The rating names of the papers to the catalogue's slugs (App\Support\FieldCatalogue), the first one the crop has. */
    private const RATING_SLUGS = [
        'bacterial leaf blight' => ['bacterial-leaf-blight'], 'blast' => ['rice-blast'], 'tungro' => ['rice-tungro'],
        'brown planthopper' => ['brown-planthopper'], 'green leafhopper' => ['green-leafhopper'], 'stem borer' => ['rice-stem-borer', 'sugarcane-stem-borers'],
        'sheath blight' => ['sheath-blight', 'banded-leaf-and-sheath-blight'], 'corn borer' => ['asian-corn-borer'], 'corn earworm' => ['corn-earworm'],
        'rust' => ['corn-rust', 'bean-rust', 'peanut-rust'], 'stalk rot' => ['corn-stalk-rot'], 'ear rot' => ['corn-ear-rot'], 'downy mildew' => ['corn-downy-mildew'],
        'bacterial wilt' => ['bacterial-wilt'], 'fruitworm' => ['corn-earworm'], 'head worm' => ['corn-earworm'], 'leaf miner' => ['leafminer'],
        'tomato mosaic virus' => ['tomato-mosaic'], 'fusarium wilt' => ['fusarium-wilt'], 'leafhopper' => ['eggplant-leafhopper'], 'aphid' => ['aphids', 'banana-aphid'],
        'thrips' => ['thrips'], 'cercospora leaf spot' => ['cercospora-leaf-spot', 'peanut-leaf-spot'], 'brown leaf spot' => ['cercospora-leaf-spot'],
        'powdery mildew' => ['powdery-mildew'], 'anthracnose' => ['anthracnose'], 'smut' => ['sugarcane-smut'], 'spidermite' => ['spider-mites'],
        'leaf scorch' => ['sugarcane-leaf-scald'],
    ];

    /**
     * A seller's or a researched claim ("tolerant to TYLCV", "HR: Fol:1", "resistant to downy mildew") to the catalogue:
     * the words of the claim => the slugs it may mean, the first the crop has.
     */
    private const CLAIMS = [
        'bacterial wilt|ralstonia' => ['bacterial-wilt'], 'fusarium|\\bfo[lnm]\\b' => ['fusarium-wilt'],
        'tylcv|tolcv|leaf ?curl|gemini|begomo' => ['tomato-leaf-curl'], 'tswv|tospo|spotted wilt|wsmov|mottle' => ['tospovirus'],
        'tomv|tmv|tobacco mosaic|tomato mosaic' => ['tomato-mosaic'], 'zymv|wmv|prsv|cmv|papaya ringspot|mosaic' => ['cucurbit-mosaic', 'bean-mosaic', 'tomato-mosaic'],
        'powdery mildew|podosphaera|sphaerotheca|erysiphe|\\bpx\\b' => ['powdery-mildew'], 'downy mildew|pseudoperonospora|\\bpcu\\b' => ['downy-mildew', 'corn-downy-mildew'],
        'anthracnose|colletotrichum' => ['anthracnose', 'onion-twister'], 'black rot|\\bxcc\\b' => ['black-rot'], 'club ?root|plasmodiophora' => ['clubroot'],
        'soft rot' => ['soft-rot'], 'late blight|phytophthora infestans' => ['late-blight'], 'early blight|alternaria solani' => ['early-blight'],
        'phytophthora' => ['phytophthora-blight'], 'bacterial spot|xanthomonas' => ['bacterial-spot', 'lettuce-bacterial-leaf-spot'], 'gummy stem' => ['gummy-stem-blight'],
        'purple blotch' => ['purple-blotch'], 'stemphylium' => ['stemphylium-leaf-blight'], 'white rust' => ['white-rust'], 'alternaria' => ['alternaria-leaf-spot', 'purple-blotch'],
        'nematode|meloidogyne' => ['root-knot-nematode'], 'cercospora' => ['cercospora-leaf-spot'], 'rust' => ['bean-rust', 'corn-rust', 'peanut-rust'],
        'leaf blight' => ['carrot-leaf-blight', 'northern-corn-leaf-blight'], 'damping' => ['damping-off'], 'southern blight|sclerotium' => ['southern-blight'],
    ];

    /** A stress named in a claim, as the shield says it. */
    private const STRESS = ['heat' => 'heat', 'rain' => 'heavy rain', 'flood|waterlog' => 'flooding (submergence)', 'drought|dry spell' => 'dry spells', 'cold|cool' => 'cool weather at high elevation'];

    /** The growing environments a variety was bred for, as the stress it takes. */
    private const ENVS = ['flood' => 'flooding (submergence)', 'saline' => 'salty soil', 'drought' => 'dry spells', 'cool' => 'cool weather at high elevation'];

    /**
     * The shield of a variety, by crop and name (the record looked up), or of a record already in hand.
     *
     * @return array{bt: ?array{label: string, pests: array<string, string>}, herbicide: array{glyphosate: bool, glufosinate: bool},
     *     resist: array<string, array{code: string, name: string}>, weak: array<string, array{code: string, name: string}>,
     *     tolerates: list<string>, skip: array<string, string>, lighter: array<string, string>, say: string, has: bool}
     */
    public static function for(?string $crop, ?string $variety, ?array $rec = null): array
    {
        if ($rec === null && $crop && trim((string) $variety) !== '') {
            try {
                $key = VarietyBook::bookFor($crop) ? $crop : VarietyBook::cropKeyFor($crop);
                $rec = $key ? VarietyBook::find($key, (string) $variety) : null;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return self::of($rec ?? ['name' => (string) $variety], $crop);
    }

    /** The shield of a record. $crop (an app crop key) picks the catalogue's slugs; without it the names stand. */
    public static function of(array $rec, ?string $crop = null): array
    {
        $names = implode(' ', array_filter([(string) ($rec['name'] ?? ''), implode(' ', (array) ($rec['aliases'] ?? []))], 'strlen'));
        $text = implode(' ', array_filter([$names, (string) ($rec['kind'] ?? ''), (string) ($rec['seller'] ?? ''),
            implode(' ', (array) ($rec['trials']['names'] ?? [])), (string) ($rec['trials']['notes'] ?? '')], 'strlen'));
        // A trait word may follow the number ("NK6414BtGt"), never another letter.
        $has = fn (string $words, string $in) => (bool) preg_match('/(?<![a-z])(' . $words . ')(?![a-z])/i', $in);
        $corn = $crop === null || str_starts_with((string) $crop, 'corn') || str_contains(mb_strtolower($text), 'corn') || str_contains(mb_strtolower((string) ($rec['kind'] ?? '')), 'gm');

        // The Bt events: named ones, then Pioneer's letters, then a Bt trait the seller does not name.
        $events = [];
        if ($corn) {
            foreach (self::EVENTS as $k => [$words]) {
                if ($has($words, $text)) {
                    $events[$k] = true;
                }
            }
            if (preg_match('/\d(yhr|yr|hr|yh|y)\b/i', $names, $m)) {
                foreach (self::SUFFIX[strtolower($m[1])] as $k) {
                    $events[$k] = true;
                }
            }
            // A Bt trait the seller does not name; "GM" alone may be only a herbicide trait, so it is not read as Bt.
            if (! $events && $has('bt|btgt|bt ?\/ ?gt|bgt|bt proteins?|bt trait|insect protected|insect resistant', $text)) {
                $events['cry1ab'] = true;
            }
        }
        // What research found for this variety (database/varieties/variety-shield.json, 2026-10-28): its events,
        // its herbicide traits, its claimed resistances and the stresses it takes.
        $found = self::overlay($crop, (string) ($rec['name'] ?? ''));
        if ($found && $corn) {
            $ev = implode(' ', (array) ($found['events'] ?? []));
            foreach (self::EVENTS as $k => [$words]) {
                if ($ev !== '' && $has($words, $ev)) {
                    $events[$k] = true;
                }
            }
        }
        $bt = null;
        if ($events) {
            $rank = ['weak' => 0, 'partial' => 1, 'strong' => 2];
            $pests = [];
            foreach (array_keys($events) as $k) {
                foreach (self::EVENTS[$k][2] as $slug => $lv) {
                    if (! isset($pests[$slug]) || $rank[$lv] > $rank[$pests[$slug]]) {
                        $pests[$slug] = $lv;
                    }
                }
            }
            $bt = ['label' => implode(' with ', array_map(fn ($k) => self::EVENTS[$k][1], array_keys($events))), 'pests' => $pests];
        }
        $herbicide = [
            'glyphosate' => $corn && ($has('rr|rr2|rrc2|roundup ready|gt|btgt|bgt|glyphosate[ -]tolerant|tolerant to glyphosate|vt ?double ?pro|vtdoublepro|vt2p|ga ?21|nk ?603', $text)
                || in_array('glyphosate', (array) ($found['herbicide'] ?? []), true)),
            'glufosinate' => $corn && ($has('liberty ?link|glufosinate[ -]tolerant|tolerant to glufosinate|herculex|tc ?1507', $text)
                || in_array('glufosinate', (array) ($found['herbicide'] ?? []), true)),
        ];

        // The registration ratings: the record's, with its papers' where the record has none of that name.
        $ratings = array_merge((array) ($rec['trials']['ratings'] ?? []), (array) ($rec['ratings'] ?? []));
        $slugs = $crop ? self::cropSlugs($crop) : [];
        $resist = [];
        $weak = [];
        foreach ($ratings as $name => $code) {
            $code = strtoupper((string) $code);
            if (! isset(self::CODE[$code])) {
                continue;
            }
            $slug = self::slugFor((string) $name, $slugs);
            $entry = ['code' => $code, 'name' => (string) $name];
            if (in_array($code, ['HR', 'R', 'MR', 'T'], true)) {
                $resist[$slug ?? ('name:' . $name)] = $entry;
            } elseif (in_array($code, ['S', 'HS'], true)) {
                $weak[$slug ?? ('name:' . $name)] = $entry;
            }
        }

        // Claims beside the ratings: the researched ones, then the seller's words ("tolerant to bacterial wilt").
        // A claim never overrides a registration rating of the same disease.
        $claimed = (array) ($found['resist'] ?? []);
        foreach (self::sellerClaims((string) ($rec['seller'] ?? '')) as $words => $code) {
            $claimed[$words] ??= $code;
        }
        foreach ($claimed as $words => $code) {
            $code = ['HR' => 'HR', 'R' => 'R', 'IR' => 'MR', 'MR' => 'MR', 'T' => 'T'][strtoupper((string) $code)] ?? 'T';
            foreach (self::CLAIMS as $re => $cands) {
                if (! preg_match('/' . $re . '/i', (string) $words)) {
                    continue;
                }
                foreach ($cands as $c) {
                    if (($slugs === [] || in_array($c, $slugs, true)) && ! isset($resist[$c]) && ! isset($weak[$c])) {
                        $resist[$c] = ['code' => $code, 'name' => (string) $words];
                        break 2;
                    }
                }
            }
        }

        // What changes in the spraying.
        $skip = [];
        $lighter = [];
        foreach ((array) ($bt['pests'] ?? []) as $slug => $lv) {
            if ($lv === 'strong') {
                $skip[$slug] = 'bt';
            } elseif ($lv === 'partial') {
                $lighter[$slug] = 'bt';
            }
        }
        foreach ($resist as $slug => $r) {
            if (str_starts_with($slug, 'name:') || isset($skip[$slug])) {
                continue;
            }
            if (in_array($r['code'], ['HR', 'R'], true)) {
                $skip[$slug] = 'rating';
                unset($lighter[$slug]);
            } elseif (! isset($lighter[$slug])) {
                $lighter[$slug] = 'rating';
            }
        }
        $tolerates = [];
        foreach ((array) ($rec['envs'] ?? []) as $e) {
            if (isset(self::ENVS[$e])) {
                $tolerates[] = self::ENVS[$e];
            }
        }
        if (preg_match('/sub ?1|submarino/i', $names) && ! in_array(self::ENVS['flood'], $tolerates, true)) {
            $tolerates[] = self::ENVS['flood'];
        }
        foreach (array_merge((array) ($found['tolerates'] ?? []), self::sellerStress((string) ($rec['seller'] ?? ''))) as $t) {
            foreach (self::STRESS as $re => $say) {
                if (preg_match('/' . $re . '/i', (string) $t) && ! in_array($say, $tolerates, true)) {
                    $tolerates[] = $say;
                }
            }
        }

        $out = compact('bt', 'herbicide', 'resist', 'weak', 'tolerates', 'skip', 'lighter');
        $out['say'] = self::words($out);
        $out['has'] = $out['say'] !== '';

        return $out;
    }

    /**
     * The shield in plain words for a prompt or a report: what the seed guards against, what that changes in the
     * spraying, what it takes, and what it is weak against. '' when the record says nothing.
     */
    public static function words(array $s): string
    {
        $bits = [];
        $nm = fn (string $slug) => self::pestName($slug);
        if ($s['bt']) {
            $strong = array_keys(array_filter($s['bt']['pests'], fn ($l) => $l === 'strong'));
            $part = array_keys(array_filter($s['bt']['pests'], fn ($l) => $l === 'partial'));
            $bits[] = 'Bt seed, ' . $s['bt']['label'] . ($strong ? ': strong against ' . self::andList(array_map($nm, $strong)) : '')
                . ($part ? ($strong ? ', ' : ': ') . 'partial against ' . self::andList(array_map($nm, $part)) : '')
                . ($strong ? '. No calendar sprays for ' . (count($strong) > 1 ? 'those' : 'it') . '; scout every week and spray only past the threshold' : '')
                . ($part ? '. ' . (count($part) > 1 ? 'Those it only slows' : ucfirst($nm($part[0])) . ' it only slows') . ': scout twice a week in the risky weeks and spray at the threshold' : '');
        }
        if ($s['herbicide']['glyphosate'] || $s['herbicide']['glufosinate']) {
            $bits[] = 'tolerates ' . self::andList(array_keys(array_filter($s['herbicide']))) . ' over the top';
        }
        // A pest the Bt line already speaks for is not said again from its rating.
        $btSaid = (array) ($s['bt']['pests'] ?? []);
        $res = array_filter($s['resist'], fn ($r, $slug) => in_array($r['code'], ['HR', 'R'], true) && ! isset($btSaid[$slug]), ARRAY_FILTER_USE_BOTH);
        $mid = array_filter($s['resist'], fn ($r, $slug) => in_array($r['code'], ['MR', 'T'], true) && ! isset($btSaid[$slug]), ARRAY_FILTER_USE_BOTH);
        if ($res) {
            $bits[] = 'resistant to ' . self::andList(array_map(fn ($r) => $r['name'] . ' (' . $r['code'] . ')', $res)) . ': no preventive sprays for ' . (count($res) > 1 ? 'them' : 'it') . ', only if it shows past the threshold';
        }
        if ($mid) {
            $bits[] = 'moderately resistant or tolerant to ' . self::andList(array_map(fn ($r) => $r['name'] . ' (' . $r['code'] . ')', $mid)) . ': fewer sprays, scout before spraying';
        }
        if ($s['tolerates']) {
            $bits[] = 'bred to take ' . self::andList($s['tolerates']);
        }
        if ($s['weak']) {
            $bits[] = 'weak against ' . self::andList(array_map(fn ($r) => $r['name'] . ' (' . $r['code'] . ')', $s['weak'])) . ': watch these first and protect early where they are common';
        }

        return implode('; ', $bits);
    }

    /** The researched entry of a variety, by crop and name (any spacing or case), or null. */
    private static function overlay(?string $crop, string $name): ?array
    {
        static $book = null;
        if ($book === null) {
            $book = [];
            $raw = json_decode((string) @file_get_contents(database_path('varieties/variety-shield.json')), true);
            foreach ((array) $raw as $k => $v) {
                if (is_array($v)) {
                    [$c, $n] = array_pad(explode('|', (string) $k, 2), 2, '');
                    $book[self::flat($c === 'corn' ? 'corn' : $c) . '|' . self::flat($n)] = $v;
                }
            }
        }
        if ($name === '') {
            return null;
        }
        $c = (string) $crop;
        $c = str_starts_with($c, 'corn') ? 'corn' : (str_starts_with($c, 'rice') ? 'rice' : $c);

        return $book[self::flat($c) . '|' . self::flat($name)] ?? null;
    }

    private static function flat(string $s): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', mb_strtolower($s));
    }

    /**
     * The resistances a seller's words claim: "tolerant to bacterial wilt and TYLCV", "HR: Fol:1; IR: TYLCV",
     * "resistant to downy mildew". The words of each claim => its code.
     *
     * @return array<string, string>
     */
    private static function sellerClaims(string $seller): array
    {
        $out = [];
        if ($seller === '') {
            return $out;
        }
        foreach (preg_split('/[;.]/', $seller) as $part) {
            $part = trim($part);
            if (preg_match('/\b(HR|IR)\s*[:=]\s*(.+)$/i', $part, $m)) {
                foreach (preg_split('/[,\/]| and /i', $m[2]) as $w) {
                    if (trim($w) !== '') {
                        $out[trim($w)] = strtoupper($m[1]);
                    }
                }
                continue;
            }
            if (preg_match('/(highly |intermediate(?:ly)? |moderately )?(resistan(?:t|ce)|toleran(?:t|ce))\s+(?:to|against|for)\s+(.+)$/i', $part, $m)) {
                $code = stripos($m[1] ?? '', 'high') !== false ? 'HR' : (($m[1] ?? '') !== '' ? 'MR' : (stripos($m[2], 'resist') === 0 ? 'R' : 'T'));
                foreach (preg_split('/,| and /i', $m[3]) as $w) {
                    if (trim($w) !== '') {
                        $out[trim($w)] = $code;
                    }
                }
            }
        }

        return $out;
    }

    /** The stresses a seller's words say it takes ("heat tolerant", "tolerates heavy rain"). */
    private static function sellerStress(string $seller): array
    {
        preg_match_all('/\b(heat|rain|flood\w*|drought|cold|cool)[ -](?:tolerant|tolerance|resistant)|toleran(?:t|ce) (?:to|of) (heat|rain|flood\w*|drought|cold)/i', $seller, $m);

        return array_values(array_filter(array_merge($m[1], $m[2])));
    }

    /** The slugs a crop's catalogue holds. */
    private static function cropSlugs(string $crop): array
    {
        static $memo = [];
        if (isset($memo[$crop])) {
            return $memo[$crop];
        }
        try {
            $key = str_starts_with($crop, 'rice') ? 'rice' : (str_starts_with($crop, 'corn') ? 'corn' : $crop);
            $memo[$crop] = \Illuminate\Support\Facades\Schema::hasTable('as_crop_problems')
                ? \Illuminate\Support\Facades\DB::table('as_crop_problems')->where('deleteStatus', 1)->where('crops', 'like', '%' . $key . '%')->pluck('slug')->all() : [];
        } catch (\Throwable $e) {
            $memo[$crop] = [];
        }

        return $memo[$crop];
    }

    private static function slugFor(string $name, array $slugs): ?string
    {
        $cands = self::RATING_SLUGS[mb_strtolower(trim($name))] ?? [];
        foreach ($cands as $c) {
            if (! $slugs || in_array($c, $slugs, true)) {
                return $c;
            }
        }

        return null;
    }

    /** A caterpillar's name in words, for the slugs the Bt list uses. */
    private static function pestName(string $slug): string
    {
        return ['fall-armyworm' => 'fall armyworm', 'asian-corn-borer' => 'Asian corn borer', 'corn-earworm' => 'corn earworm', 'rice-armyworm' => 'armyworms',
            'cutworm' => 'cutworms'][$slug] ?? str_replace('-', ' ', $slug);
    }

    private static function andList(array $a): string
    {
        $a = array_values($a);

        return count($a) < 2 ? (string) ($a[0] ?? '') : implode(', ', array_slice($a, 0, -1)) . ' and ' . end($a);
    }
}
