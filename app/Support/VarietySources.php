<?php

namespace App\Support;

/**
 * Where a variety's facts come from, shown with every variety (2026-10-21,
 * the owner: "in the variety finder to avoid issues like libel, show the
 * source for each variety"). The variety finders and pages are the one place
 * a government body is named as our source, on the owner's word: a figure
 * about a seed company's product rests on the official record or on the
 * company's own pages, and the reader sees which.
 *
 * Each source is ['name' => what a reader knows it by, 'url' => the page,
 * or null where the record has no public page]. The official records come
 * first, then the seller's own pages, then the rest. line() says the names
 * for a card; the variety page and the in-app finder's sheet link each one.
 *
 *   VarietySources::rice($riceRecord);   // App\Support\RiceVarieties::all() row
 *   VarietySources::crop($cropRecord);   // App\Support\CropVarieties::all() row
 *   VarietySources::papers($ref);        // a registration trial's papers
 */
final class VarietySources
{
    /** A host (or its tail) to the name a reader knows it by. No dashes in what a farmer reads. */
    private const HOSTS = [
        'nsic.buplant.da.gov.ph' => 'NSIC variety registry', 'biotech.buplant.da.gov.ph' => 'Bureau of Plant Industry', 'buplant.da.gov.ph' => 'Bureau of Plant Industry',
        'dbmp.philrice.gov.ph' => 'PhilRice seed database', 'philrice.gov.ph' => 'PhilRice',
        'fpa.da.gov.ph' => 'Fertilizer and Pesticide Authority', 'da.gov.ph' => 'Department of Agriculture', 'bar.gov.ph' => 'Bureau of Agricultural Research',
        'pcaarrd.dost.gov.ph' => 'DOST PCAARRD', 'dost.gov.ph' => 'DOST', 'bohol.gov.ph' => 'Bohol provincial government', 'cityofilagan.com' => 'City of Ilagan',
        'uplb.edu.ph' => 'UPLB', 'vsu.edu.ph' => 'Visayas State University', 'dmmmsu.edu.ph' => 'DMMMSU', 'mmsu.edu.ph' => 'Mariano Marcos State University',
        'bsu.edu.ph' => 'Benguet State University', 'cbsua.edu.ph' => 'CBSUA', 'debesmscat.edu.ph' => 'DEBESMSCAT',
        'cgiar.org' => 'CGIAR', 'fao.org' => 'FAO', 'atmire.com' => 'FAO', 'irri.org' => 'IRRI', 'wikipedia.org' => 'Wikipedia',
        'cropscience.bayer.com.ph' => 'Bayer', 'bayer.com' => 'Bayer', 'syngenta.com.ph' => 'Syngenta', 'syngenta.com' => 'Syngenta',
        'pioneer.com' => 'Pioneer (Corteva)', 'corteva.com' => 'Corteva', 'eastwestseed.com' => 'East West Seed', 'alliedbotanical.com' => 'Allied Botanical',
        'ramgoseeds.com' => 'Ramgo Seeds', 'harbest.com.ph' => 'Harbest', 'bioseed.com.ph' => 'Bioseed', 'evogeneseeds.com' => 'Evogene Seeds',
        'knownyou.com' => 'Known You Seed', 'goedenph.com' => 'Goeden', 'agrify.ph' => 'Agrify', 'usrfiles.com' => 'a seller flyer',
        'agriculture.com.ph' => 'Agriculture Magazine', 'philstar.com' => 'The Philippine Star', 'sunstar.com.ph' => 'SunStar', 'inquirer.net' => 'Inquirer',
        'bworldonline.com' => 'BusinessWorld', 'gmanetwork.com' => 'GMA News', 'verafiles.org' => 'VERA Files', 'freshplaza.com' => 'FreshPlaza',
        'lbtimes.ph' => 'Los Baños Times', 'dagupan.com' => 'Sunday Punch', 'lazada.com.ph' => 'a Lazada listing', 'biggo.com' => 'a BigGo listing',
        'repec.org' => 'RePEc', 'tci-thaijo.org' => 'ThaiJO', 'thaijo.org' => 'ThaiJO', 'reference-global.com' => 'Reference Global', 'coconutcommunity.org' => 'International Coconut Community',
        'stuartxchange.org' => 'StuartXchange',
    ];

    /** The registry lists: one per crop group (the variety's own record is a row in it). */
    public const RICE_REGISTRY = 'https://nsic.buplant.da.gov.ph/downloadables/nsic-registered_rice.xlsx';

    /** The registration papers' library: one page per registered variety. */
    public const PAPERS = 'https://nsic.buplant.da.gov.ph/cvreglib/';

    /** A rice variety: its record in the seed database or the registry, then its seller's pages. */
    public static function rice(array $v): array
    {
        $out = self::fromUrls((array) ($v['sources'] ?? []));
        if (! $out) {
            // The newest varieties came from PhilRice's variety catalogue (no public page per variety) and the registry list.
            $out = [['name' => 'PhilRice rice variety catalogue', 'url' => null], ['name' => 'NSIC variety registry', 'url' => self::RICE_REGISTRY]];
        }
        $seller = (array) ($v['seller'] ?? []);

        return self::merge($out, self::fromUrls((array) ($seller['sources'] ?? []), (string) ($seller['company'] ?? '')));
    }

    /** Any other crop's variety: its registry list, then what its seller and others published. */
    public static function crop(array $v): array
    {
        $r = (array) ($v['research'] ?? []);

        return self::merge(self::fromUrls((array) ($v['sources'] ?? [])), self::fromUrls((array) ($r['sources'] ?? []), (string) ($r['company'] ?? '')));
    }

    /** A variety's registration papers (the in-app finder's trials): its page in the papers' library. */
    public static function papers(?string $ref): array
    {
        $ref = (string) $ref;

        return ['name' => 'NSIC registration papers', 'url' => $ref !== '' ? self::PAPERS . 'profile.php?r=' . rawurlencode($ref) : self::PAPERS];
    }

    /** The names for a card: "NSIC variety registry and Ramgo Seeds", "A, B and 2 more". */
    public static function line(array $sources, int $max = 2): string
    {
        $names = array_values(array_unique(array_column($sources, 'name')));
        if (! $names) {
            return '';
        }
        $more = count($names) - $max;
        $shown = array_slice($names, 0, $max);
        if ($more > 0) {
            return implode(', ', $shown) . ' and ' . $more . ' more';
        }

        return count($shown) < 2 ? $shown[0] : implode(', ', array_slice($shown, 0, -1)) . ' and ' . end($shown);
    }

    /** URLs as sources, each named by its host (a video or a social page by its company). */
    private static function fromUrls(array $urls, string $company = ''): array
    {
        $out = [];
        foreach ($urls as $u) {
            $u = trim((string) (is_array($u) ? ($u['url'] ?? '') : $u));
            if (! preg_match('#^https?://#i', $u)) {
                continue;
            }
            $out[] = ['name' => self::name($u, $company), 'url' => $u];
        }

        return $out;
    }

    private static function name(string $url, string $company): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = (string) preg_replace('/^www\./', '', $host);
        // A company's own name without its dash (no dashes in what a farmer reads): East-West Seed reads East West Seed.
        $who = trim($company) !== '' ? trim((string) preg_replace('/\s*-\s*/', ' ', trim($company))) : null;
        if (preg_match('/(^|\.)(youtube\.com|youtu\.be)$/', $host)) {
            return ($who ? $who . ' ' : '') . 'video';
        }
        if (preg_match('/(^|\.)facebook\.com$/', $host)) {
            return ($who ? $who . ' on ' : '') . 'Facebook';
        }
        $name = null;
        foreach (self::HOSTS as $tail => $n) {
            if ($host === $tail || str_ends_with($host, '.' . $tail)) {
                $name = $n;
                break;
            }
        }
        // A regional office of the Department of Agriculture keeps its region (cagayanvalley.da.gov.ph).
        if ($name === 'Department of Agriculture' && preg_match('/^([a-z0-9]+)\.da\.gov\.ph$/', $host, $m) && ! in_array($m[1], ['www', 'nsic', 'fpa', 'biotech', 'buplant'], true)) {
            $name .= ', ' . self::region($m[1]);
        }
        $name ??= $host !== '' ? $host : 'a web page';
        if (preg_match('/\.pdf(\?|$)/i', $url) && ! str_contains($name, 'flyer')) {
            $name .= ' (PDF)';
        }

        return $name;
    }

    /** A regional office's host word as a reader says it: rfo12 → Region 12, cagayanvalley → Cagayan Valley. */
    private static function region(string $w): string
    {
        if (preg_match('/^rfo(\d+)$/', $w, $m)) {
            return 'Region ' . $m[1];
        }

        return ['cagayandeoro' => 'Northern Mindanao', 'cagayanvalley' => 'Cagayan Valley', 'caraga' => 'Caraga', 'bicol' => 'Bicol', 'ilocos' => 'Ilocos'][$w] ?? ucfirst($w);
    }

    /**
     * One list, each page once, the official records first; a name that
     * shows more than once gets the page it is ("Bayer: dk 9251s",
     * "NSIC variety registry: variety list") as its `label`, the words a
     * list shows. A card says only the names (line()).
     */
    private static function merge(array ...$lists): array
    {
        $out = [];
        $seen = [];
        foreach ($lists as $list) {
            foreach ($list as $s) {
                $k = $s['url'] ?? ('name:' . $s['name']);
                if (! isset($seen[$k])) {
                    $seen[$k] = true;
                    $out[] = $s;
                }
            }
        }
        $official = fn (array $s) => ! empty($s['url']) && GovSource::isGovUrl((string) $s['url']) ? 0 : (empty($s['url']) ? 0 : 1);
        $keyed = array_map(fn ($s, $i) => [$official($s), $i, $s], $out, array_keys($out));
        usort($keyed, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        $out = array_column($keyed, 2);
        // A name shown more than once: each by its page, else numbered (two videos, two records of one database).
        $groups = [];
        foreach ($out as $i => $s) {
            $groups[$s['name']][] = $i;
        }
        foreach ($groups as $name => $idx) {
            if (count($idx) < 2) {
                $out[$idx[0]]['label'] = $name;

                continue;
            }
            $words = array_map(fn ($i) => ! empty($out[$i]['url']) ? self::page((string) $out[$i]['url']) : '', $idx);
            $plain = count(array_unique($words)) === count($words) && ! array_intersect($words, ['', 'watch', 'another page']);
            foreach ($idx as $n => $i) {
                $out[$i]['label'] = $plain ? $name . ': ' . $words[$n] : $name . ' ' . ($n + 1);
            }
        }

        return $out;
    }

    /** The page a link is, in a few words: "variety list", "search page", "dk 9251s". */
    private static function page(string $url): string
    {
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if (preg_match('/\.xlsx?$/i', $path)) {
            return 'variety list';
        }
        if (preg_match('#/registry\.php$#i', $path)) {
            return 'search page';
        }
        if (str_contains($path, 'cvreglib')) {
            return 'registration papers';
        }
        $seg = '';
        foreach (array_reverse(array_filter(explode('/', $path))) as $p) {
            $p = (string) preg_replace('/\.(html?|php|aspx?)$/i', '', $p);
            if ($p !== '' && ! preg_match('/^(index|en|en-ph|ph|products?)$/i', $p)) {
                $seg = $p;
                break;
            }
        }
        $q = (string) parse_url($url, PHP_URL_QUERY);
        // A database's record page says its record (varietychar.php?Vchars=146: "record 146").
        if ($q !== '' && preg_match('/^(varietychar|view|record|details?)$/i', $seg)) {
            parse_str($q, $qs);
            $first = (string) (reset($qs) ?: '');
            if ($first !== '') {
                return 'record ' . mb_substr((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $first), 0, 30);
            }
        }
        if ($seg === '' && $q !== '') {
            parse_str($q, $qs);
            $seg = (string) (reset($qs) ?: '');
        }
        $w = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($seg))));

        return $w !== '' ? mb_substr($w, 0, 40) : 'another page';
    }
}
