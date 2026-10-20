<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Taglish help (2026-10-15, the owner): "most of the things are too
 * technical to explain since our clients are farmers". A small tag that
 * says Taglish (a Philippine flag until 2026-10-16) sits after a technical
 * label; tapping it explains the term in plain Taglish, in a tooltip on a
 * wide screen and a bottom sheet on a phone.
 *
 * The words live in one file, database/tagalog/glossary.json: a list of
 * entries, each { key, en, tl, ex?, links? } (en is the term, tl the
 * explanation, ex an optional example, links optional buttons under it as
 * [{ label, route, query? }], a route name of this app). The browser
 * fetches the whole book once, on the first tap (route tl.glossary,
 * versioned by the file's hash and cached for a year); a page carries only
 * the list of keys.
 *
 * Three ways to put a tag on a label (the methods keep their old names):
 *   - Blade:  {{ \App\Support\Tagalog::flag('dat') }} written right after the
 *             label's last word, with no space between, or
 *             {{ \App\Support\Tagalog::label('Days after transplanting', 'dat') }}
 *             for the escaped label with its flag;
 *   - markup drawn by a script: data-tl="dat" on the element, and the shared
 *             script (partials.tagalog-flags) adds the flag after its text;
 *   - in a script: window.aneeTl.flag('dat') returns the flag's HTML.
 * An unknown key draws nothing, and so does the international face.
 *
 * The tag is an EMPTY inline span with padding, drawn by its ::before: an
 * empty inline box is not a place a line can break, so the tag always stays
 * with the word before it (an inline-block would drop to a line by itself).
 * It is a span with role="button", not a <button>, because tags sit inside
 * tile links and buttons, where a nested <button> would break the HTML.
 */
final class Tagalog
{
    /** The glossary, relative to database_path(). */
    public const FILE = 'tagalog/glossary.json';

    /** @var array<string, array{en: string, tl: string, ex: ?string, links: list<array{0: string, 1: string}>}>|null */
    private static ?array $book = null;

    private static ?string $version = null;

    /**
     * Does this page carry flags at all? Signed in, on the Philippine face
     * only (the international face reads English already). The shared
     * script and styles come with layouts.app, so a flag drawn anywhere else
     * would be an empty span with nothing to open.
     */
    public static function on(): bool
    {
        try {
            return auth()->check() && Region::ph();
        } catch (\Throwable) {
            return false;
        }
    }

    /** The glossary, keyed: key => [en, tl, ex]. */
    public static function book(): array
    {
        if (self::$book !== null) {
            return self::$book;
        }
        $rows = json_decode((string) @file_get_contents(database_path(self::FILE)), true);
        $book = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $en = trim((string) ($row['en'] ?? ''));
            $tl = trim((string) ($row['tl'] ?? ''));
            if ($key === '' || $en === '' || $tl === '') {
                continue;
            }
            // No example (Halimbawa) and no why, only what the label means (2026-10-21, the owner): an `ex` is never served.
            $book[$key] = ['en' => $en, 'tl' => $tl, 'ex' => null, 'links' => self::links($row['links'] ?? [])];
        }

        return self::$book = $book;
    }

    /**
     * An entry's buttons as [label, url]: a route that does not exist is
     * left out rather than breaking the whole book. Relative URLs, so they
     * follow whatever host serves the page.
     */
    private static function links(mixed $raw): array
    {
        $out = [];
        foreach (is_array($raw) ? $raw : [] as $l) {
            $label = trim((string) ($l['label'] ?? ''));
            $name = (string) ($l['route'] ?? '');
            if ($label === '' || $name === '' || ! \Illuminate\Support\Facades\Route::has($name)) {
                continue;
            }
            try {
                $out[] = [$label, route($name, (array) ($l['query'] ?? []), false)];
            } catch (\Throwable) {
                // A route that needs more than the entry gives: skipped.
            }
        }

        return $out;
    }

    public static function has(string $key): bool
    {
        return isset(self::book()[$key]);
    }

    /** The English term of a key, or null. */
    public static function term(string $key): ?string
    {
        return self::book()[$key]['en'] ?? null;
    }

    /** Changes whenever the glossary does, so the year long cache never serves old words. */
    public static function version(): string
    {
        return self::$version ??= substr((string) @md5_file(database_path(self::FILE)), 0, 10) ?: '0';
    }

    /**
     * The Taglish tag for a key, or nothing (still named flag: every page
     * calls it so). Write it straight after the label's last word
     * ("DAT{{ Tagalog::flag('dat') }}"): a space before it is a place the
     * line may break, and the tag would sit alone below.
     */
    public static function flag(string $key, ?string $term = null): HtmlString
    {
        if (! self::on() || ! self::has($key)) {
            return new HtmlString('');
        }
        $say = 'Taglish: ' . ($term ?? self::term($key));

        return new HtmlString('<span class="tlx" role="button" tabindex="0" data-tlk="' . e($key) . '" aria-label="' . e($say) . '"></span>');
    }

    /** An escaped label with its flag after the last word. */
    public static function label(string $text, string $key): HtmlString
    {
        return new HtmlString(e($text) . self::flag($key, $text));
    }

    /** The served glossary: { v, t: { key: [en, tl, ex?, links?] } }. */
    public static function payload(): array
    {
        $t = [];
        foreach (self::book() as $key => $row) {
            $t[$key] = $row['links'] ? [$row['en'], $row['tl'], $row['ex'], $row['links']]
                : ($row['ex'] !== null ? [$row['en'], $row['tl'], $row['ex']] : [$row['en'], $row['tl']]);
        }

        return ['v' => self::version(), 't' => $t];
    }

    /** What every page carries: where the glossary is, and which keys exist. */
    public static function boot(): array
    {
        return [
            // Relative: always this origin, whatever APP_URL says.
            'url' => route('tl.glossary', ['v' => self::version()], false),
            'keys' => implode(',', array_keys(self::book())),
        ];
    }
}
