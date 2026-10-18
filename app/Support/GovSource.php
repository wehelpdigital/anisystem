<?php

namespace App\Support;

/**
 * No government body, and no big research body, is ever shown as anee.io's
 * source (the owner, 2026-10-17: "never mention that our source is any
 * government sources like Philrice or so. In our loaders, results, articles,
 * and libraries", and the same day: "remove any mentions of IRRI and FAO as
 * a source").
 *
 * The facts stay; whose they are does not show. Three tools:
 *  - isGov(): does a source label, title or address name one of them
 *    (PhilRice, the DA and its bureaus and offices, BSWM, PAGASA, the NSIC
 *    registry, PCAARRD, a state university, IRRI, FAO, CIMMYT, the World
 *    Vegetable Center, CABI, the Rice Knowledge Bank and the like)?
 *  - keep(): a sources list without those entries, for every screen that
 *    prints "Based on" or "Sources" (the field helpers, NPK Plus, an
 *    analysis's web pages).
 *  - scrub(): the cheap, safe rewrites for words a model wrote ("according
 *    to PhilRice, ...", "(DA Caraga)", "PhilRice recommends ..."), for AI
 *    answers and for stored text rendered again. The prompt rule (RULE)
 *    does the real work; this catches what slips.
 *
 * What stays: a name that IS the thing (a variety called NSIC Rc 222, BPI
 * Ri 1 or IR64, "the NSIC approved it in 2015"), the FPA safety line ("use
 * only products registered with the Fertilizer and Pesticide Authority
 * (FPA)") and a product's FPA registration number (the owner, 2026-10-17),
 * where to get a soil test, news whose subject is an agency, licence photo
 * credits, and The Stash (partners' own publications, shown as theirs). So
 * scrub() never touches the FPA; keep() still drops an FPA list cited as a
 * source.
 */
final class GovSource
{
    /** Said to every model that answers a farmer (AiSetting's house rules). */
    public const RULE = 'Never name a government body or a research institute as the source of what you say: not PhilRice, not the DA or '
        . 'its bureaus and offices (BPI, BSWM, ATI, PhilMech, NIA, PCA, the regional field offices), not PAGASA, not the NSIC registry, not '
        . 'PCAARRD, not a state university such as UPLB, CLSU or VSU, and not IRRI, FAO, CIMMYT, the World Vegetable Center (AVRDC), CABI, '
        . 'the Rice Knowledge Bank, Rice Crop Manager, Nutrient Manager or the PalayCheck system, not the NSIC or the variety registry, not NDRRMC, PHIVOLCS, the OCD, '
        . 'NOAA, JMA or JTWC, and not a university abroad (TNAU, UF IFAS) as where a fact comes from (a storm keeps its name). No "according to PhilRice", no "(DA)", no "IRRI recommends", '
        . 'and none of them in a list of sources or as a link. Give the advice as your own: "the usual practice", "the recommended rate", '
        . '"for your area", "registered varieties", "the leaf color chart". These stay: a variety\'s own name (NSIC Rc 222, BPI Ri 1, IR64), '
        . 'the safety line to use only products registered with the Fertilizer and Pesticide Authority (FPA) and an FPA registration number, '
        . 'telling the farmer where to get a soil test or help (the Municipal Agriculture Office, a DA soil laboratory), and a news story '
        . 'whose subject is an agency. Never name PalayCheck at all, in any spelling: not the PalayCheck system, not "PalayCheck Key Checks", '
        . 'not Sistemang PalayCheck and not the PalayCheck App, not even as a guide, a training or an app to try. Give its practices as the '
        . 'usual practice in your own words ("the nine key checks of a rice season", "Key Check 5, enough nutrients from tillering to flowering", '
        . '"a free rice calendar app").';

    /** A source naming one of them: names, acronyms (as written) and addresses. */
    private const WORDS = '/philrice|phil\s+rice\b|philippine rice research|bswm|bureau\s+of\s+(?:soils|plant\s+industry|agricultural|animal\s+industry|fisheries)'
        . '|pagasa|philmech|philfida|pcaarrd|agricultural\s+training\s+institute|national\s+irrigation\s+admin|philippine\s+coconut\s+authority'
        . '|fertilizer\s+and\s+pesticide\s+authority|sugar\s+regulatory|national\s+seed\s+industry\s+council|nsic\s+registry|philippine\s+statistics\s+authority'
        . '|university\s+of\s+the\s+philippines|state\s+university|regional\s+field\s+office|ndrrmc|\.gov\.ph\b|\bgov\.ph\b'
        . '|\b(?:uplb|up|clsu|vsu|cmu|isu|bsu|mmsu|usm|cvsu|cbsua|wvsu|nvsu|bpsu|tau|ssu|dmmmsu|cagsu)\.edu\.ph\b'
        . '|buplant|\bda\.gov|\bdost\b|variety\s+registry|seed\s+registry'
        // The research bodies (2026-10-17).
        . '|international\s+rice\s+research|irri\.org|\birri\b|food\s+and\s+agriculture\s+organi[sz]ation|\bfao\b|fao\.org|cimmyt|avrdc'
        . '|world\s*veg|vegetable\s+center|\bcabi\b|cabi\.org|plantwise|rice\s+knowledge\s+bank|knowledgebank|rice\s+crop\s+manager|nutrient\s+manager|palay[\s_-]?check'
        // State universities abroad the catalogues cited, and the weather and disaster agencies (2026-10-17).
        . '|\btnau\b|tnau\.ac\.in|kerala\s+agricultural\s+university|\bkau\.in|uf\s+ifas|\bifas\b|ufl\.edu|phivolcs|ndrrmc|\bnoaa\b|noaa\.gov|\bjtwc\b|jma\.go\.jp'
        // Research bodies, universities and governments abroad that the crop tables cited (2026-10-19).
        . '|\bicar\b|icar\.(?:org|gov)\.in|icrisat|embrapa|jircas|\bciat\b|cgiar|inifap|\bunesp\b|csiro|\baciar\b|\.gob\.[a-z]{2}\b|\.gov\.(?:au|in|my|th|vn|id|cn)\b|\.ac\.in\b'
        . '|\buniversit(?:y|ies|ad|ade|e)\b|\bnt\s+government/i';

    /** The acronyms, matched as capitals only (BPI Ri 1 is a variety, not a source). */
    private const CAPS = '/\b(?:DA|BPI(?!\s*-?\s*(?:[A-Z][a-z]{0,2}\s*-?\s*)?\d)|ATI|NIA|PCA|FPA|SRA|NFA|PSA|BFAR|DOST|NSIC(?!\s*(?:\d{4}\s*)?(?:[A-Z][a-z]{0,3}\s*-?\s*\d|Rice|\d))|UPLB|CLSU|VSU|CMU|ISU|BSU|MMSU|USM|CvSU|RFO|TNAU|KAU|OCD|JMA|JTWC|NOAA|PHIVOLCS|NDRRMC)\b/';

    /** "Department of Agriculture" of another country is not covered by the rule. */
    private const ABROAD = '/u\.\s?s\.|united\s+states|usda|florida|thailand|australia|india|malaysia|indonesia|vietnam|california|texas|hawai/i';

    /** Whether a label, title or address names one of them. */
    public static function isGov(?string $text): bool
    {
        $t = trim((string) $text);
        if ($t === '') {
            return false;
        }
        if (preg_match('/department\s+of\s+agriculture/i', $t) && ! preg_match(self::ABROAD, $t)) {
            return true;
        }

        return (bool) (preg_match(self::WORDS, $t) || preg_match(self::CAPS, $t));
    }

    /** An address is judged by its host: a newspaper story about the DA is not the DA. */
    public static function isGovUrl(?string $url): bool
    {
        $host = parse_url(trim((string) $url), PHP_URL_HOST);

        return self::isGov(is_string($host) && $host !== '' ? $host : (string) $url);
    }

    /**
     * A sources list without their entries: plain strings, or arrays with
     * any of label / title / name / publisher / url / uri.
     */
    public static function keep(?array $sources): array
    {
        $out = [];
        foreach ((array) $sources as $s) {
            if (is_string($s)) {
                if (! self::isGov($s)) {
                    $out[] = $s;
                }

                continue;
            }
            if (! is_array($s)) {
                continue;
            }
            $gov = false;
            foreach (['label', 'title', 'name', 'publisher', 'source', 'url', 'uri', 'href'] as $f) {
                if (isset($s[$f]) && is_string($s[$f]) && (in_array($f, ['url', 'uri', 'href'], true) ? self::isGovUrl($s[$f]) : self::isGov($s[$f]))) {
                    $gov = true;
                    break;
                }
            }
            if (! $gov) {
                $out[] = $s;
            }
        }

        return $out;
    }

    /* ---------------------------------------------------------------- */

    /** One of them as prose names it, with a few trailing words of its office ("DA Caraga", "DA RFO 02", "BPI Davao"). */
    private static function item(): string
    {
        $names = '(?:PhilRice|Phil\s?Rice|Philippine\s+Rice\s+Research\s+Institute|BSWM|Bureau\s+of\s+Soils(?:\s+and\s+Water\s+Management)?'
            . '|Bureau\s+of\s+Plant\s+Industry|Department\s+of\s+Agriculture|PAGASA|PhilMech|PhilFIDA|DOST\s?PCAARRD|PCAARRD'
            . '|Agricultural\s+Training\s+Institute|Philippine\s+Coconut\s+Authority'
            . '|National\s+Irrigation\s+Administration|Philippine\s+Statistics\s+Authority|National\s+Seed\s+Industry\s+Council'
            . '|(?:Sistemang\s+)?Palay\s?Check(?:\s+[Ss]ystem)?|NSIC\s+registry|(?:the\s+)?(?:national\s+)?(?:variety|seed)\s+registry|NSIC(?!\s*(?:\d{4}\s*)?(?:[A-Z][a-z]{0,3}\s*-?\s*\d|Rice|\d))|UPLB|International\s+Rice\s+Research\s+Institute|Food\s+and\s+Agriculture\s+Organi[sz]ation'
            . '|(?:IRRI|PhilRice|Pinoy)\s+Rice\s+Knowledge\s+Bank|PhilRice\s+Knowledge\s+Bank|Rice\s+Knowledge\s+Bank|World\s+Vegetable\s+Center|CIMMYT|AVRDC|CABI(?:\s+Plantwise)?'
            . '|TNAU|Kerala\s+Agricultural\s+University|UF\s+IFAS|PHIVOLCS|NDRRMC|NOAA(?:\s+CPC)?|JTWC|Joint\s+Typhoon\s+Warning\s+Center|Japan\s+Meteorological\s+Agency'
            . '|(?-i:IRRI|FAO|DA|BPI(?!\s*-?\s*(?:[A-Z][a-z]{0,2}\s*-?\s*)?\d)|ATI|NIA|PCA|PSA|SRA|KAU|OCD|JMA))';

        // An office after the name: "Caraga", "RFO 02", "Region 7", "Davao", "ATI CAR" (a region or a code,
        // never any capitalized word: "BSWM Soil Data" keeps its "Soil Data").
        $office = 'RFO|RFU|Region|Regional|ATI|CAR|NCR|Caraga|Bicol|Bikol|Davao|Cagayan(?:\s+Valley)?|Ilocos|Cordillera|Calabarzon|Mimaropa'
            . '|Western\s+Visayas|Central\s+Visayas|Eastern\s+Visayas|Visayas|Northern\s+Mindanao|Mindanao|Zamboanga|Soccsksargen|SOCCSKSARGEN|BARMM|Baguio|Davao|Los\s+Ba\S+os';

        return '(?:the\s+)?' . $names . '(?-i:\s+(?:' . $office . ')\b(?:\s+(?:[IVX]+|\d+[A-Z]?|[A-Z]{2,})\b)?)?';
    }

    /**
     * Words a model wrote, with those names taken out where it is safe to
     * do without breaking the sentence. Addresses are left whole, except a
     * link to one of their pages, which keeps only its words.
     * A rewrite leaves \x01 where it began, so a sentence that now starts
     * with a small letter gets its capital back, and nothing else changes.
     */
    public static function scrub(?string $text): string
    {
        $text = (string) $text;
        if ($text === '' || ! self::mightName($text)) {
            return $text;
        }
        // A link to one of their pages keeps its words and loses the address.
        $text = preg_replace_callback('/\[([^\]\n]+)\]\(([^)\s]+)\)/u', fn ($m) => self::isGovUrl($m[2]) ? $m[1] : $m[0], $text);
        // Keep the other addresses as they are.
        $keep = [];
        $text = preg_replace_callback('/\]\([^)\s]+\)|https?:\/\/[^\s"\'<>)\]]+/u', function ($m) use (&$keep) {
            $keep[] = $m[0];

            return "\x02" . (count($keep) - 1) . "\x03";
        }, $text);

        $one = self::item();
        $many = '(?:' . $one . ')(?:\s*(?:\(\s*[A-Z]{2,8}\s*\))?(?:,\s*(?:and|or)?|\band\b|\bor\b|\/|&)\s*(?:' . $one . ')(?:\s*\(\s*[A-Z]{2,8}\s*\))?)*';
        // A typographic apostrophe counts as one ("PAGASA’s").
        $text = str_replace("\u{2019}s ", "'s ", $text);
        $text = self::palayCheckFirst($text);

        // "(PhilRice)", "(DA Caraga)", "(source: BSWM)", "(PhilRice and IRRI, 2019)".
        $text = preg_replace('/\s?\((?:(?:source|sources|from|per|based\s+on|see|ayon\s+sa)\s*:?\s+)?' . $many . '(?:,?\s*\d{4})?\)/iu', '', $text);
        // "According to PhilRice, ..." and "Ayon sa PhilRice, ...": the clause goes, the advice stays.
        $text = preg_replace('/\b(?:according\s+to|as\s+per|based\s+on|ayon\s+sa|sabi\s+ng|batay\s+sa)\s+' . $many . '(?:\'s\s+(?:guide|guidelines|recommendations?|data|figures|advice|practice))?\s*,\s*/iu', "\x01", $text);
        // "PhilRice recommends 40 kg" -> "Agriculturists recommend 40 kg".
        $text = preg_replace_callback('/\b' . $many . '(\s+also)?\s+(recommends?|advises?|suggests?|urges?|encourages?|says|recommended|advised|suggested|urged|encouraged|said)\b/iu', function ($m) {
            $verb = strtolower($m[2]);
            $plural = ['recommends' => 'recommend', 'advises' => 'advise', 'suggests' => 'suggest', 'urges' => 'urge', 'encourages' => 'encourage', 'says' => 'say'];

            return "\x01agriculturists" . $m[1] . ' ' . ($plural[$verb] ?? $verb);
        }, $text);
        // "as recommended by the DA" -> "as recommended".
        $text = preg_replace('/\b(recommended|advised|suggested|set|given|developed|published|released|bred|listed|rated|tested|cited|quoted|counted|measured|reported|recorded|estimated)\s+by\s+' . $many . '/iu', '$1', $text);
        // "a figure taken from the DA corn derby" -> "from the corn derby" (2026-10-19: a seller's note in the variety
        // records); "a DA soil laboratory" and its office stay, being where to get help.
        $text = preg_replace('/\b(from|in|at|of|on|during)\s+' . $one . '\s+(?-i:(?=(?:corn|rice|palay|yield|derb\w*|techno\w*|demo\w*|trial\w*|contests?|harvests?|stations?|seed|national|regional|wet|dry|multi\w*)\b))/iu', '$1 the ', $text);
        // "using good management and the PalayCheck system" -> "using good management".
        $text = preg_replace('/\s+(?:and|with|following|under|through|from)\s+the\s+Palay\s?Check(?:\s+[Ss]ystem)?\b(?!\s+(?:mobile\s+)?app)/iu', '', $text);
        // ", declared by PAGASA," -> "".
        $text = preg_replace('/,?\s+(?:as\s+)?(?:declared|issued|announced|reported)\s+by\s+' . $many . '\s*,?(?=\s)/iu', '', $text);
        // "PAGASA and climate models project" -> "Climate models project".
        $text = preg_replace('/\b(?:the\s+)?(?:PAGASA|NOAA|JMA)\s+and\s+(?=(?:climate|global|international)\s+models)/iu', "\x01", $text);
        // "PhilRice's recommendation", "the general PhilRice practice" -> "the usual recommendation", "the general practice".
        $text = preg_replace_callback('/\b(?:the\s+(general|usual|standard)\s+)?' . $one . '(?:\'s)?\s+(recommendations?|advice|practice|guidelines?|rates?)\b/iu',
            fn ($m) => "\x01the " . ($m[1] !== '' ? $m[1] : 'usual') . ' ' . $m[2], $text);
        // The weather service as the one who says it: "PAGASA forecasts" -> "Forecasters forecast".
        $plural = fn (string $v) => ['has' => 'have', 'is' => 'are', 'was' => 'were', 'says' => 'say', 'does' => 'do', 'sees' => 'see'][strtolower($v)] ?? (preg_match('/[^s]s$/i', $v) ? substr($v, 0, -1) : $v);
        $text = preg_replace_callback('/\b(?:the\s+)?(?:PAGASA|NOAA(?:\s+CPC)?|JMA|JTWC|NDRRMC|PHIVOLCS)(?:\s*\(\d{4}\))?\s+(?:(usually|typically|also|officially|now|still|often)\s+)?(has|is|was|forecasts?|expects?|predicts?|projects?|warns?|warned|declares?|declared|records?|recorded|flags?|flagged|says|said|reports?|reported|notes?|noted|sees|saw|issues?|issued)\b/iu',
            fn ($m) => "\x01forecasters " . ($m[1] !== '' ? $m[1] . ' ' : '') . $plural($m[2]), $text);
        // An office as the one that counted it: "the DA Regional Field Office 1 (2024) reported" -> "Reports showed".
        $text = preg_replace_callback('/\b' . $many . '(?:\s+(?:Regional\s+)?(?:Field\s+)?Office(?:\s+\d+)?)?(?:\s*\(\d{4}\))?\s+(reported|recorded|estimated|counted|found|showed|reports|records|estimates|finds|shows)\b/iu',
            fn ($m) => "\x01" . (preg_match('/ed$|wn$|nd$/', $m[1]) || in_array(strtolower($m[1]), ['found', 'showed'], true) ? 'reports showed' : 'records show'), $text);
        // "PAGASA data", "PhilRice's catalogue", "BSWM soil maps" -> "the data", "the catalogue", "the soil maps".
        $text = preg_replace('/\b(?:the\s+)?' . $one . '(?:\'s)?\s+((?:climatological\s+|climate\s+|soil\s+|seasonal\s+|weather\s+|official\s+)?(?:data|records?|figures|normals|numbers|tables?|forecasts?|outlooks?|advisories|catalogue|catalog|database|lists?|maps?|surveys?|tests|trials|studies|reports?)\b)/iu', "\x01the $1", $text);
        // "Data from PhilRice show 5 t/ha" -> "Data show 5 t/ha" (2026-10-19): only where the clause ends or a
        // verb follows, so "results of the DA corn derby" is left to the rule above it.
        $text = preg_replace('/\b((?:climate\s+|soil\s+|yield\s+|trial\s+|official\s+)?(?:data|records|figures|numbers|statistics|surveys?|studies|trials|results|research))\s+(?:from|by|of)\s+' . $many
            . '(?=\s*(?:[.,;:!?)]|$|(?:show|shows|showed|say|says|said|indicate|indicates|indicated|suggest|suggests|put|puts|place|places|give|gives|gave)\b))/iu', '$1', $text);
        // "PAGASA's Dagupan station" -> "the Dagupan station".
        $text = preg_replace('/\b(?:the\s+)?' . $one . '\'s\s+/iu', "\x01the ", $text);
        // "PAGASA Type I climate" -> "Type I climate".
        $text = preg_replace('/\b(?:the\s+)?PAGASA\s+(?=Type\b)/u', '', $text);
        // A trailing ", according to PAGASA (2022)." -> ".".
        $text = preg_replace('/,?\s+(?:according\s+to|as\s+per|per)\s+' . $many . '(?:\s*\(\d{4}(?:[-\x{2013}]\d{4})?\))?(?=[.;:!?\n]|$)/iu', '', $text);
        // A citation in brackets keeps only the others: "(Philstar, 2025; PAGASA, 2026)" -> "(Philstar, 2025)".
        $text = preg_replace_callback('/\s?\(([^()\n]{2,240})\)/u', function ($m) {
            $parts = preg_split('/\s*;\s*/u', $m[1]);
            $cite = fn ($p) => (bool) preg_match('/,?\s*(?:\d{4}(?:[-\x{2013}]\d{2,4})?|n\.d\.)$/u', trim($p));
            // The FPA in brackets is the safety line's own word, never a citation to take out.
            $gov = fn ($p) => self::isGov($p) && ! preg_match('/\bFPA\b|fertilizer\s+and\s+pesticide/i', $p);
            if (! array_filter($parts, $gov) || array_filter($parts, fn ($p) => ! $cite($p) && ! $gov($p))) {
                return $m[0];
            }
            $left = array_values(array_filter($parts, fn ($p) => ! $gov($p)));

            return $left ? ' (' . implode('; ', $left) . ')' : '';
        }, $text);
        // A short title that opens with one of them ("BSWM Soil Suitability for Munoz") keeps the rest.
        if (mb_strlen($text) < 100 && ! preg_match('/[.!?](?:\s|$)/u', $text)) {
            $text = preg_replace('/^(?:the\s+)?' . $one . '[\s,:]+(?=\p{Lu})/u', '', $text);
        }
        // A "Sources:" line keeps only the other sources.
        $text = preg_replace_callback('/^([ \t>*_-]*(?:\*\*)?(?:Sources?|Mga\s+pinagkunan|Pinagkunan)(?:\*\*)?\s*:(?:\*\*)?)[ \t]*(.+)$/imu', function ($m) {
            $parts = preg_split('/\s*(?:;|,(?![^()]*\))|·|\|)\s*/u', $m[2]);
            $left = array_values(array_filter($parts, fn ($p) => trim($p) !== '' && ! self::isGov(self::unkeep($p))));

            return $left ? $m[1] . ' ' . implode(', ', $left) : "\x04";
        }, $text);
        $text = preg_replace('/^[^\S\n]*\x04[^\S\n]*(?:\n|$)/mu', '', $text);
        $text = self::palayCheckLast($text);

        // Where a rewrite began a sentence, the sentence keeps its capital.
        $text = preg_replace_callback('/(^|[.!?]\s+|\n[ \t]*(?:[-*>]\s+|\d+[.)]\s+)?|"|\*\*)\x01(\p{Ll})/u', fn ($m) => $m[1] . mb_strtoupper($m[2]), $text);
        $text = str_replace("\x01", '', $text);
        $text = preg_replace('/(?<=\S) {2,}(?=\S)/', ' ', $text);

        return preg_replace_callback("/\x02(\d+)\x03/", fn ($m) => $keep[(int) $m[1]] ?? '', $text);
    }

    /** Whether a text might name one at all: the quick test that spares the rest. */
    public static function mightName(string $text): bool
    {
        return (bool) (preg_match('/philrice|phil\s+rice|bswm|bureau\s+of|department\s+of\s+agriculture|pagasa|philmech|philfida|pcaarrd|uplb|nsic\s+registry|authority|institute|administration|organi[sz]ation|seed\s+industry|knowledge\s+bank|vegetable\s+center|cimmyt|avrdc|cabi|registry|\.gov\.ph|irri\.org|fao\.org|tnau|ifas|kerala|phivolcs|ndrrmc|noaa|jtwc|meteorological|palay[\s_-]?check/i', $text)
            || preg_match('/\b(?:DA|BPI|ATI|NIA|PCA|FPA|PSA|SRA|IRRI|FAO|NSIC|KAU|OCD|JMA)\b/', $text));
    }

    /** PalayCheck in any spelling, with the words that may come with it. */
    private const PALAYCHECK = '(?:Sistemang\s+)?Palay[\s_-]?Check(?:\s+System)?';

    /**
     * PalayCheck is named nowhere (the owner, 2026-10-17: "remove this don't
     * say some came from PalayCheck"), not even as a tool. The first pass,
     * before the other rules: its maker goes with it ("PhilRice's PalayCheck
     * System recommends" is left to the rules as "PalayCheck System
     * recommends", so "Agriculturists recommend"), and its Key Checks keep
     * their number without its name ("the PalayCheck system's Key Check 5",
     * "Key Check 5 of PalayCheck" -> "Key Check 5"; "the nine PalayCheck Key
     * Checks" -> "the nine key checks").
     */
    private static function palayCheckFirst(string $text): string
    {
        if (! preg_match('/palay[\s_-]?check/i', $text)) {
            return $text;
        }
        $pc = self::PALAYCHECK;
        $text = preg_replace('/\b(?:the\s+)?(?:DA[\s-]+)?(?:PhilRice|Phil\s?Rice|Philippine\s+Rice\s+Research\s+Institute)(?:\s*\(\s*PhilRice\s*\))?(?:\'s)?\s+(?=' . $pc . ')/iu', '', $text);
        $text = preg_replace('/(\bKey\s+Checks?(?:\s+\d+)?)\s+(?:of|from|in|under)\s+(?:the\s+)?' . $pc . '\b(?!\s+(?:mobile\s+)?app)/iu', '$1', $text);

        return preg_replace_callback('/\b(the\s+)?' . $pc . '(?:\'s)?\s+(Key\s+Checks?)\b(\s+\d+)?/iu', function ($m) {
            $num = $m[3] ?? '';
            if ($num !== '' && ! preg_match('/s$/i', $m[2])) {
                return "\x01" . $m[2] . $num;
            }

            return "\x01" . ($m[1] !== '' ? 'the ' : '') . 'key checks' . $num;
        }, $text);
    }

    /**
     * The last pass, after the sources lines (a source naming it still goes
     * as a source): what the rules left of it, in plain words. "the
     * PalayCheck App" becomes "a rice calendar app", a bold "PalayCheck
     * System:" label "Key checks:", "a PalayCheck group" "a key check group",
     * and any other mention "the rice season checklist".
     */
    private static function palayCheckLast(string $text): string
    {
        if (! preg_match('/palay[\s_-]?check/i', $text)) {
            return $text;
        }
        $pc = self::PALAYCHECK;
        // "the PalayCheck App", "PalayCheck mobile app", "the PalayCheck App 2.0".
        $text = preg_replace('/\b(?:the\s+|a\s+)?' . $pc . '\s+(?:mobile\s+)?(?:app|application)\b(?:\s+\d+(?:\.\d+)?\b)?/iu', "\x01a rice calendar app", $text);
        $text = preg_replace('/\*\*(?:the\s+)?' . $pc . '(:?)\*\*/iu', '**Key checks$1**', $text);
        $text = preg_replace('/\b([Aa])n?\s+' . $pc . '\b/u', '$1 key check', $text);

        return preg_replace('/\b(?:the\s+)?' . $pc . '\b/iu', "\x01the rice season checklist", $text);
    }

    /** A part of a "Sources:" line with its address put back, for the test. */
    private static function unkeep(string $p): string
    {
        return preg_replace("/\x02\d+\x03/", ' link ', $p);
    }

    /**
     * A site page's blocks as they are shown: a sources block keeps only the
     * sources that may show (and goes when none is left), every other block
     * is scrubbed. For a page edited in the mother app or written by Anee;
     * the shipped files are already clean. Never for the news roundups.
     */
    public static function pageBlocks(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $b) {
            if (! is_array($b)) {
                $out[] = $b;

                continue;
            }
            if (($b['type'] ?? '') === 'sources') {
                $b['items'] = self::keep((array) ($b['items'] ?? []));
                if (! $b['items']) {
                    continue;
                }
                $out[] = $b;

                continue;
            }
            $out[] = self::scrubAll($b);
        }

        return $out;
    }

    /**
     * Every string in a nested array (a saved report, a stored page's
     * blocks) scrubbed, and every list under a "sources" key kept.
     * Keys, addresses and photo credits are never touched.
     */
    public static function scrubAll(mixed $v, ?string $key = null): mixed
    {
        if (is_string($v)) {
            // A public breeder ("by": "DA-PhilRice", "IRRI", "UPLB-IPB") is not named; a seed company stays.
            if ($key !== null && preg_match('/^(?:by|breeder|bredBy|developer|owner)$/i', $key) && self::isGov($v)) {
                return '';
            }
            // A "source" line ("PhilRice / IRRI", "NSIC, PhilRice, IRRI") keeps only the others.
            if ($key !== null && preg_match('/^(?:source|sources|basis|from)$/i', $key) && self::isGov($v)) {
                $parts = preg_split('/\s*(?:\/|,|;|\band\b|&|\+)\s*/iu', $v);

                return implode(', ', array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== '' && ! self::isGov($p))));
            }

            return self::scrub($v);
        }
        if (! is_array($v)) {
            return $v;
        }
        if ($key !== null && preg_match('/^(?:sources|webSources|citations|refs|references)$/i', $key) && array_is_list($v)) {
            $v = self::keep($v);
        }
        foreach ($v as $k => $x) {
            if (is_string($k) && in_array(strtolower($k), ['url', 'uri', 'href', 'src', 'credit', 'slug', 'key', 'id'], true)) {
                continue;
            }
            $v[$k] = self::scrubAll($x, is_string($k) ? $k : null);
        }

        return $v;
    }
}
