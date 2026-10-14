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
    public const SECTIONS = ['crops', 'crop-varieties', 'rice-varieties', 'land-preparation', 'pests', 'diseases', 'weeds'];

    /**
     * The section front pages that are gated too (2026-10-14, the owner):
     * their catalogue or finder shows a guest its first rows, the next rows
     * blurred and the rest held back, under the same invitation, and the
     * finder's answer asks for a free sign up. Every card is still in the
     * HTML. See partials/hub-gate.
     */
    public const HUBS = ['pests', 'diseases', 'weeds', 'rice-varieties'];

    /** The class the gated part wears; the structured data names it. */
    public const SELECTOR = '.sp-gated';

    public static function gatedSection(?string $section): bool
    {
        return in_array((string) $section, self::SECTIONS, true);
    }

    /**
     * Whether this visitor meets the gate on a page of this section: every
     * guest, and a member who asked to see the page as a guest (?guest=1,
     * the link on the members' marker), so the owner can check it signed in.
     */
    public static function applies(?string $section): bool
    {
        return self::gatedSection($section) && (! Auth::check() || self::previewing());
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
        return ! Auth::check() || self::previewing();
    }

    /** A signed in member looking at the page as a guest would. */
    public static function previewing(): bool
    {
        return Auth::check() && request()->query('guest') === '1';
    }

    /** A signed in member reading a gated page in full: the marker shows where guests stop. */
    public static function opensFor(?string $section): bool
    {
        return self::gatedSection($section) && Auth::check() && ! self::previewing();
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
        $out = [];
        foreach (array_slice($blocks, $cut, null, true) as $b) {
            if (($b['type'] ?? '') === 'heading' && trim((string) ($b['text'] ?? '')) !== '' && (int) ($b['level'] ?? 2) === 2) {
                $out[] = (string) $b['text'];
            }
            if (($b['type'] ?? '') === 'faq') {
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
