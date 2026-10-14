<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Tagalog help flags (2026-10-15, the owner): "most of the things are too
 * technical to explain since our clients are farmers". A small Philippine
 * flag sits after a technical label; tapping it explains the term in plain
 * Tagalog, in a tooltip on a wide screen and a bottom sheet on a phone.
 *
 * The words live in one file, database/tagalog/glossary.json: a list of
 * entries, each { key, en, tl, ex? } (en is the term, tl the explanation,
 * ex an optional example). The browser fetches the whole book once, on the
 * first tap (route tl.glossary, versioned by the file's hash and cached for
 * a year); a page carries only the list of keys.
 *
 * Three ways to put a flag on a label:
 *   - Blade:  {{ \App\Support\Tagalog::flag('dat') }} written right after the
 *             label's last word, with no space between, or
 *             {{ \App\Support\Tagalog::label('Days after transplanting', 'dat') }}
 *             for the escaped label with its flag;
 *   - markup drawn by a script: data-tl="dat" on the element, and the shared
 *             script (partials.tagalog-flags) adds the flag after its text;
 *   - in a script: window.aneeTl.flag('dat') returns the flag's HTML.
 * An unknown key draws nothing, and so does the international face.
 *
 * The flag is an EMPTY inline span with padding, drawn as a background: an
 * empty inline box is not a place a line can break, so the flag always stays
 * with the word before it (an inline-block would drop to a line by itself).
 * It is a span with role="button", not a <button>, because flags sit inside
 * tile links and buttons, where a nested <button> would break the HTML.
 */
final class Tagalog
{
    /** The glossary, relative to database_path(). */
    public const FILE = 'tagalog/glossary.json';

    /** @var array<string, array{en: string, tl: string, ex: ?string}>|null */
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
            $ex = trim((string) ($row['ex'] ?? ''));
            $book[$key] = ['en' => $en, 'tl' => $tl, 'ex' => $ex !== '' ? $ex : null];
        }

        return self::$book = $book;
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
     * The flag for a key, or nothing. Write it straight after the label's
     * last word ("DAT{{ Tagalog::flag('dat') }}"): a space before it is a
     * place the line may break, and the flag would sit alone below.
     */
    public static function flag(string $key, ?string $term = null): HtmlString
    {
        if (! self::on() || ! self::has($key)) {
            return new HtmlString('');
        }
        $say = 'Ipaliwanag sa Tagalog: ' . ($term ?? self::term($key));

        return new HtmlString('<span class="tlx" role="button" tabindex="0" data-tlk="' . e($key) . '" aria-label="' . e($say) . '"></span>');
    }

    /** An escaped label with its flag after the last word. */
    public static function label(string $text, string $key): HtmlString
    {
        return new HtmlString(e($text) . self::flag($key, $text));
    }

    /** The served glossary: { v, t: { key: [en, tl, ex] } }. */
    public static function payload(): array
    {
        $t = [];
        foreach (self::book() as $key => $row) {
            $t[$key] = $row['ex'] !== null ? [$row['en'], $row['tl'], $row['ex']] : [$row['en'], $row['tl']];
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
