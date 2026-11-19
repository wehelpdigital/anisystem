<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * The members' gate on the field guides (2026-10-14, the owner's ask): the
 * crop guides, the crop and rice varieties, land preparation, pests, diseases
 * and weeds open in full to anyone signed in to anee.io. A guest reads the
 * first paragraph of each page and the rest is blurred under an invitation
 * to register for free (split()).
 *
 * Search engines read the whole page: the full text is in the HTML for every
 * visitor (no cloaking), and the page's structured data says which part is
 * for members (isAccessibleForFree false, with the gated part's CSS
 * selector), which is how Google reads a registration wall.
 *
 * Where a guest was reading is kept for after they join: the sign up and
 * log in links carry it (?read=), and the dashboard offers it back.
 */
class ContentGate
{
    // Crop guides (/crops) stay open to everyone (the owner, 2026-10-14).
    // How to Plant (2026-10-19, the owner): gated, crawlable; it opens on registering (every plan has the tool).
    // Nutrients (2026-10-30, the owner): gated, crawlable; it opens on registering.
    public const SECTIONS = ['crop-varieties', 'rice-varieties', 'land-preparation', 'pests', 'diseases', 'weeds', 'nutrient-deficiencies', 'how-to-plant', 'nutrients'];

    /**
     * The section front pages that are gated too (2026-10-14, the owner):
     * their catalogue or finder shows a guest its first rows, the next rows
     * blurred and the rest held back, under the same invitation, and the
     * finder's answer asks for a free sign up. Every card is still in the
     * HTML. See partials/hub-gate.
     */
    public const HUBS = ['pests', 'diseases', 'weeds', 'rice-varieties', 'nutrient-deficiencies'];

    /**
     * The field helpers' sections (2026-10-17). Since 2026-11-19 a free account reads their pages in full and only
     * the solution part is locked (SOLUTION_SECTIONS); the note below is the old rule. (2026-10-17, the owner:
     * "registering does not give them those details because the feature is only available
     * for libre plus"): the guides behind the field helpers. A free account reads them as a
     * guest does and is invited to upgrade. Land preparation stays open on a free account.
     */
    public const PLUS_SECTIONS = ['crop-varieties', 'rice-varieties', 'pests', 'diseases', 'weeds', 'nutrient-deficiencies'];

    /** The plan that opens them, and its checkout item. */
    public const PLUS_PLAN = 'libreAnee';

    /**
     * Since 2026-11-19 (the owner: "after registration (free), the public gated pages should be readable. however,
     * the solution part is still gated and advertize the module of anee and say to upgrade to get it"): a free
     * account reads every guide in full, and only the solution part of these sections (how to control it, the
     * sprays, what to apply) stays locked, under a card naming the module inside anee.io that holds it. The modules
     * themselves stay locked on the free plan (FieldPackGate). The variety guides have no solution part: they open
     * in full on a free account.
     */
    public const SOLUTION_SECTIONS = ['pests', 'diseases', 'weeds', 'nutrient-deficiencies'];

    /** The module inside anee.io for each section: its name and its field helper (route fh.page). */
    public const MODULES = [
        'pests' => ['Pest Finder', 'pests'],
        'diseases' => ['Disease Finder', 'diseases'],
        'weeds' => ['Weed Control Helper', 'weeds'],
        'nutrient-deficiencies' => ['Nutrient Deficiency Finder', 'nutrients'],
        'crop-varieties' => ['Variety Finder', 'varieties'],
        'rice-varieties' => ['Variety Finder', 'varieties'],
    ];

    /** A heading that opens a solution part ("How to control", "Active ingredients", "What to apply", "Chemical control"). */
    private const SOLUTION_HEADING = '/\b(control|controlling|controls|manage|management|managing|treat|treatment|treating|cure|spray|sprays|spraying|insecticides?|fungicides?|herbicides?|pesticides?|bactericides?|nematicides?|miticides?|molluscicides?|rodenticides?|active ingredients?|what to apply|what to do at each|get rid|when to act|how to fix|fertili[sz]ers? (sold here|that supply))\b/i';

    /** A question in a page's FAQ that asks for the solution ("Which insecticide works", "What fertilizer fixes"). */
    private const SOLUTION_QUESTION = '/\b(control|controlling|treat|treatment|cure|spray|spraying|insecticides?|fungicides?|herbicides?|pesticides?|chemicals?|active ingredients?|get rid|kill|fertili[sz]ers?\s+(fix|fixes|for|cures?|to use|works?)|what to apply|what (should|do) i (apply|use|put)|fix(es)?\s+\w+\s+deficiency|how much \w+ (should|do) i (use|apply)|best (source|fertili[sz]er|product|insecticide|fungicide|herbicide)s?)\b/i';

    /** The class the gated part wears; the structured data names it. */
    public const SELECTOR = '.sp-gated';

    public static function gatedSection(?string $section): bool
    {
        return in_array((string) $section, self::SECTIONS, true);
    }

    /**
     * Who the members' guides open for: signed in AND the email confirmed (2026-11-19, the owner: "gated contents
     * should not be accessible if registration email is not verified"). A super admin always. Everyone else signed in
     * with an unconfirmed address (a crew login, an old account) meets the gate, asked to confirm (unconfirmed()).
     */
    public static function member(): bool
    {
        $u = Auth::user();

        return $u !== null && $u->status === 'active' && (! empty($u->emailVerifiedAt) || $u->isSuperAdmin());
    }

    /** Signed in, but the email is not confirmed yet: the gate asks for the link, not for a signup. */
    public static function unconfirmed(): bool
    {
        return Auth::check() && ! self::member();
    }

    /**
     * Whether this visitor meets the gate on a page of this section: every
     * guest, and a member who asked to see the page as a guest (?guest=1,
     * the link on the members' marker), so the owner can check it signed in.
     */
    public static function applies(?string $section): bool
    {
        return self::gatedSection($section) && (! self::member() || self::previewing());
    }

    /** Whether the section's full pages come with Libre + Anee (not with a free account). */
    public static function needsPlus(?string $section): bool
    {
        return in_array((string) $section, self::PLUS_SECTIONS, true);
    }

    /** Whether the signed in visitor's farm has Libre + Anee or above (the field helpers' plan). */
    public static function hasPlus(): bool
    {
        return self::member() && Tier::farmCan('fieldHelpers');
    }

    /** A signed in member whose plan does not open this section: invited to upgrade, not to register. */
    public static function upgradeView(?string $section): bool
    {
        return self::member() && ! self::previewing() && self::needsPlus($section) && ! self::hasPlus();
    }

    /** Whether this visitor meets the gate on a finder that is not a section front (a crop's variety finder). */
    public static function finderApplies(string $section): bool
    {
        return self::guestView();
    }

    /** Whether this visitor meets the gate on a section's front page. */
    public static function hubApplies(?string $section): bool
    {
        return in_array((string) $section, self::HUBS, true) && self::guestView();
    }

    /**
     * Whether this visitor sees the gated view of a finder that is not a
     * section front: every crop's own variety finder, /crop-varieties/corn
     * (2026-10-14, the owner: "all varieties should be gated").
     */
    public static function guestView(): bool
    {
        return ! self::member() || self::previewing();
    }

    /** A signed in member looking at the page as a guest would. */
    public static function previewing(): bool
    {
        return self::member() && request()->query('guest') === '1';
    }

    /** A signed in member reading a gated page in full: the marker shows where guests stop. */
    public static function opensFor(?string $section): bool
    {
        return self::gatedSection($section) && self::member() && ! self::previewing();
    }

    /** Whether the section's guides have a solution part that comes with Libre + Anee. */
    public static function solutionSection(?string $section): bool
    {
        return in_array((string) $section, self::SOLUTION_SECTIONS, true);
    }

    /** A member without Libre + Anee reading such a guide: everything opens but the solution part. */
    public static function solutionLocked(?string $section): bool
    {
        return self::solutionSection($section) && self::member() && ! self::previewing() && ! self::hasPlus();
    }

    /**
     * A page cut into runs, open and locked, in order (keys kept, so the headings' anchors still match the
     * contents). A locked run starts at a solution heading and holds everything under it until a heading at the same
     * level or above that is not one (a "What to apply" H3 ends at the next nutrient's H2). The questions and answers
     * stay open, except the questions that ask for the solution, which ride in a locked copy of the block. The links
     * and sources stay open. Null when the page has no solution part.
     *
     * @return list<array{locked: bool, blocks: array}>|null
     */
    public static function solutionRuns(array $blocks): ?array
    {
        $runs = [];
        $put = function (bool $locked, $key, array $block) use (&$runs) {
            $last = array_key_last($runs);
            if ($last === null || $runs[$last]['locked'] !== $locked) {
                $runs[] = ['locked' => $locked, 'blocks' => []];
                $last = array_key_last($runs);
            }
            $runs[$last]['blocks'][$key] = $block;
        };
        $inside = false;
        $level = 0;
        $any = false;
        foreach ($blocks as $k => $b) {
            $type = (string) ($b['type'] ?? '');
            if ($type === 'heading') {
                $l = (int) ($b['level'] ?? 2);
                $hit = (bool) preg_match(self::SOLUTION_HEADING, strip_tags((string) ($b['text'] ?? '')));
                if ($inside && $l <= $level && ! $hit) {
                    $inside = false;
                }
                if ($hit && (! $inside || $l < $level)) {
                    $inside = true;
                    $level = $l;
                }
            } elseif (in_array($type, ['faq', 'links', 'sources'], true)) {
                $inside = false;
            }
            if ($type === 'faq' && ! $inside) {
                $open = [];
                $shut = [];
                foreach ((array) ($b['items'] ?? []) as $item) {
                    $q = strip_tags((string) ($item['q'] ?? ''));
                    preg_match(self::SOLUTION_QUESTION, $q) ? $shut[] = $item : $open[] = $item;
                }
                if ($open) {
                    $put(false, $k, ['items' => $open] + $b);
                }
                if ($shut) {
                    $put(true, $k . ':locked', ['items' => $shut] + $b);
                    $any = true;
                }

                continue;
            }
            $any = $any || $inside;
            $put($inside, $k, $b);
        }

        return $any ? $runs : null;
    }

    /** The open runs' blocks, together (what a free member's copy of the structured data may carry). */
    public static function openOf(array $runs): array
    {
        $out = [];
        foreach ($runs as $r) {
            if (! $r['locked']) {
                $out += $r['blocks'];
            }
        }

        return $out;
    }

    /**
     * Where a page opens and where its gate starts (2026-10-14, the owner:
     * "after the first paragraph"). A guest reads everything up to and
     * including the first paragraph of the page's first text block (a
     * heading before it stays with it); the rest, from that block's second
     * paragraph on, is the gated part, still in the HTML for search engines.
     * Keys are kept, so the headings' anchors match the contents; a split
     * text block's remainder rides under its own key ("5:rest"). Null when
     * there is no paragraph, or too little after it to gate.
     *
     * @return array{open: array, gated: array}|null
     */
    public static function split(array $blocks): ?array
    {
        $open = [];
        $gated = [];
        $found = false;
        foreach ($blocks as $k => $b) {
            if ($found) {
                $gated[$k] = $b;

                continue;
            }
            if (($b['type'] ?? '') === 'text' && trim((string) ($b['text'] ?? '')) !== '') {
                $found = true;
                $paras = preg_split('/\n\s*\n/', trim((string) $b['text']));
                $open[$k] = ['text' => array_shift($paras)] + $b;
                if ($paras) {
                    $gated[$k . ':rest'] = ['text' => implode("\n\n", $paras)] + $b;
                }

                continue;
            }
            $open[$k] = $b;
        }
        if (! $found || self::wordsFrom(array_values($gated), 0) < 40) {
            return null;
        }

        return ['open' => $open, 'gated' => $gated];
    }

    /**
     * The part behind the gate as a visitor gets it (2026-11-16, the owner: "user can easily read it"): the same
     * blocks with every word swapped for letters of the same length, so the blur looks the same while reader mode,
     * view source or a translation finds nothing to read. Headings and questions stay, the teaser of what is inside;
     * pictures, related links and sources stay too. Only a proven crawler (App\Support\Crawler) gets the real text.
     */
    public static function veil(array $blocks, string $seed): array
    {
        mt_srand(crc32($seed));
        $mix = fn ($s) => self::scramble(SitePages::plain((string) $s));
        $out = [];
        foreach ($blocks as $k => $b) {
            switch ($b['type'] ?? '') {
                case 'text':
                case 'quote':
                    $b['text'] = implode("\n\n", array_map($mix, preg_split('/\n\s*\n/', (string) ($b['text'] ?? ''))));
                    unset($b['cite']);
                    break;
                case 'list':
                    $b['items'] = array_map(fn ($i) => is_array($i) ? $mix($i['text'] ?? '') : $mix($i), (array) ($b['items'] ?? []));
                    break;
                case 'steps':
                    $b['items'] = array_map(fn ($i) => ['title' => $mix($i['title'] ?? ''), 'text' => $mix($i['text'] ?? '')], (array) ($b['items'] ?? []));
                    break;
                case 'table':
                    $rows = (array) ($b['rows'] ?? []);
                    $b['rows'] = array_map(fn ($row, $i) => $i === 0 ? $row : array_map($mix, (array) $row), $rows, array_keys($rows));
                    break;
                case 'callout':
                    $b['text'] = $mix($b['text'] ?? '');
                    unset($b['textTl']);
                    break;
                case 'faq':
                    $b['items'] = array_map(fn ($i) => ['q' => (string) ($i['q'] ?? ''), 'a' => $mix($i['a'] ?? '')], (array) ($b['items'] ?? []));
                    break;
            }
            $out[$k] = $b;
        }
        mt_srand();

        return $out;
    }

    /** Letters for letters and digits for digits, case and spacing kept. */
    private static function scramble(string $s): string
    {
        $lower = 'etaoinshrdlcumwfgypbvk';
        $out = '';
        foreach (mb_str_split($s) as $ch) {
            if (ctype_digit($ch)) {
                $out .= (string) mt_rand(0, 9);
            } elseif (preg_match('/^\p{L}$/u', $ch)) {
                $r = $lower[mt_rand(0, strlen($lower) - 1)];
                $out .= mb_strtoupper($ch) === $ch && mb_strtolower($ch) !== $ch ? strtoupper($r) : $r;
            } else {
                $out .= $ch;
            }
        }

        return $out;
    }

    /**
     * What the gate's card calls the page's subject: a variety by the name on
     * its seed bag and its registry number, a pest, disease or weed by its
     * plain name ("Kangkong Weed in Rice Fields" is Kangkong). Null for a
     * guide that is about many things (Rice Diseases in the Philippines).
     */
    public static function subject(object $page): ?string
    {
        $section = (string) $page->section;
        $slug = (string) $page->slug;
        if ($section === 'rice-varieties' || $section === 'crop-varieties') {
            $all = $section === 'rice-varieties' ? RiceVarieties::all() : CropVarieties::all();
            $v = collect($all)->firstWhere('slug', $slug);
            if (! $v) {
                return null;
            }
            $trade = $v['tradeNames'][0] ?? null;

            return $trade && stripos((string) $v['name'], (string) $trade) === false ? $trade . ' (' . $v['name'] . ')' : (string) $v['name'];
        }
        $known = match ($section) {
            'pests', 'diseases' => (bool) ProblemCatalogue::get($section, $slug),
            'weeds' => (bool) WeedCatalogue::get($slug),
            default => false,
        };
        if (! $known) {
            return null;
        }
        $name = SitePages::shortTitle($page);
        $name = preg_replace('/\s+(in|of)\s+(Rice Fields|Rice|Your Palay|Palay|Corn|Vegetables|the Philippines)$/i', '', $name);

        return trim((string) preg_replace('/\s+Weed$/', '', (string) $name));
    }

    /** The section headings behind the gate: what a guest is invited to. */
    public static function behind(array $blocks, int $cut): array
    {
        // The sections a guest would see in the contents: the H2 headings.
        // A questions block counts only when no heading of its own names it
        // (it usually sits under "Questions farmers ask about ...", which
        // made the card say one section more than the page has).
        $out = [];
        $headed = false;
        foreach (array_slice($blocks, $cut, null, true) as $b) {
            $type = $b['type'] ?? '';
            if ($type === 'heading' && trim((string) ($b['text'] ?? '')) !== '' && (int) ($b['level'] ?? 2) === 2) {
                $out[] = (string) $b['text'];
                $headed = true;
            } elseif ($type === 'faq' && ! $headed) {
                $out[] = 'Questions farmers ask, answered';
            }
        }

        return array_values(array_unique($out));
    }

    /** Words behind the gate, for the minutes a guest is missing. */
    public static function wordsFrom(array $blocks, int $cut): int
    {
        return array_sum(array_map(fn ($b) => self::words($b), array_slice($blocks, $cut)));
    }

    /** A page path a gate link may bring a new member back to, or null. */
    public static function readPath(?string $path): ?string
    {
        $path = '/' . ltrim((string) parse_url((string) $path, PHP_URL_PATH), '/');
        $sections = implode('|', array_map(fn ($s) => preg_quote($s, '#'), self::SECTIONS));

        $hubs = implode('|', array_map(fn ($s) => preg_quote($s, '#'), self::HUBS));

        return preg_match('#^/(' . $sections . ')/[a-z0-9\-]+$#', $path) || preg_match('#^/(' . $hubs . ')$#', $path) ? $path : null;
    }

    /** Keeps the page a guest left for the sign up or log in form. */
    public static function remember(?string $path): void
    {
        if ($p = self::readPath($path)) {
            Session::put('gate.read', $p);
        }
    }

    /** The page a new member was reading, with its title, or null. */
    public static function waiting(): ?array
    {
        $p = self::readPath(Session::get('gate.read'));
        if (! $p) {
            return null;
        }
        $parts = explode('/', $p);
        if (count($parts) === 2) {
            $title = SitePages::SECTIONS[$parts[1]]['hubTitle'] ?? null;

            return $title ? ['url' => url($p), 'title' => (string) $title] : null;
        }
        [, $section, $slug] = $parts;
        // A crop's variety finder (/crop-varieties/corn) is not a page of its own.
        if ($section === 'crop-varieties' && ($crop = CropVarieties::cropBySlug($slug))) {
            return ['url' => url($p), 'title' => $crop['name'] . ' varieties'];
        }
        $page = SitePages::find($section, $slug);

        return $page ? ['url' => url($p), 'title' => (string) $page->title] : null;
    }

    private static function words(array $b): int
    {
        $text = '';
        array_walk_recursive($b, function ($v, $k) use (&$text) {
            if (is_string($v) && ! in_array($k, ['type', 'url', 'src', 'tone', 'level'], true)) {
                $text .= ' ' . $v;
            }
        });

        return str_word_count(strip_tags($text));
    }
}
