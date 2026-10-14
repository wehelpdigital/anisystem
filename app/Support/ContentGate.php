<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * The members' gate on the field guides (2026-10-14, the owner's ask): the
 * crop guides, the crop and rice varieties, land preparation, pests, diseases
 * and weeds open in full to anyone signed in to anee.io. A guest reads the
 * opening of each page (enough to see what the page answers) and the rest is
 * blurred under an invitation to sign up free.
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
     * Where a page's blocks are cut: the opening a guest reads is the
     * blocks before the cut. The cut falls on a section heading, after at
     * least about 110 words, and leaves at least two sections behind it.
     * Returns null when the page is too short to gate.
     */
    public static function cut(array $blocks): ?int
    {
        $blocks = array_values($blocks);
        $h2 = [];
        $words = [];
        $run = 0;
        foreach ($blocks as $i => $b) {
            $words[$i] = $run;
            if (($b['type'] ?? '') === 'heading' && (int) ($b['level'] ?? 2) === 2) {
                $h2[] = $i;
            }
            $run += self::words($b);
        }
        $total = max(1, $run);
        if (count($h2) < 3) {
            return null;
        }
        foreach (array_slice($h2, 1) as $k => $i) {
            $left = count($h2) - ($k + 1);
            if ($words[$i] >= 110 && $left >= 2) {
                return $i;
            }
        }

        // Every section short: the cut after the second, if two are left.
        return count($h2) >= 4 ? $h2[1] : null;
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

        return preg_match('#^/(' . $sections . ')/[a-z0-9\-]+$#', $path) ? $path : null;
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
        [, $section, $slug] = explode('/', $p);
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
