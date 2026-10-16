<?php

namespace App\Support\Newsletter;

/**
 * THE FIELDS ACUMBAMAIL FILLS IN (2026-10-16, the owner: "add merge fields in
 * the email editor that can be used or translated correctly by acumbamail
 * when sent"). Acumbamail writes them as *|TAG|*: a list's own fields by
 * their tag (getListFields: email is EMAIL, the name field we keep on every
 * list is NAME, and GREET holds the first name or "kaibigan", so a greeting
 * never comes out blank), and its global tags for the links every campaign
 * has. A live send leaves them for Acumbamail; a test send and the builder's
 * preview fill them with a sample, so nothing shows as *|NAME|* there.
 */
final class MergeTags
{
    /** tag => [what the builder calls it, text or link, the sample a test shows] */
    public const TAGS = [
        'GREET' => ['First name, or "kaibigan"', 'text', 'Juan'],
        'NAME' => ['First name (empty when we do not know it)', 'text', 'Juan'],
        'EMAIL' => ['Their email address', 'text', 'juan@example.com'],
        'UNSUBSCRIBE_URL' => ['Unsubscribe link', 'link', 'https://anee.io/'],
        'BROWSER_VIEW' => ['Open in the browser link', 'link', 'https://anee.io/'],
        'MODIFY_SUB_URL' => ['Update their details link', 'link', 'https://anee.io/'],
    ];

    /** What GREET holds for someone whose first name is not known. */
    public const GREET_FALLBACK = 'kaibigan';

    public static function tag(string $key): string
    {
        return '*|' . $key . '|*';
    }

    public static function forKit(): array
    {
        $out = [];
        foreach (self::TAGS as $k => [$label, $kind, $sample]) {
            $out[] = ['tag' => self::tag($k), 'label' => $label, 'kind' => $kind, 'sample' => $sample];
        }

        return $out;
    }

    /**
     * Fill the tags for a test or a preview. Links become working addresses;
     * a text field becomes its sample (in a preview, marked so the eye sees
     * it is a field). Inside a tag's attributes only the plain sample goes.
     *
     * @param  array<string, string>  $sample  tag => value (a tester's own name, their address)
     */
    public static function fill(string $html, bool $mark, array $sample = []): string
    {
        $value = function (string $k) use ($sample): string {
            return (string) ($sample[$k] ?? (self::TAGS[$k][2] ?? ''));
        };
        $known = implode('|', array_keys(self::TAGS));
        // In attributes (href, alt, title): plain values only.
        $html = preg_replace_callback('~<[^>]+>~', function ($m) use ($known, $value) {
            return preg_replace_callback('~\*(?:\||%7C)(' . $known . ')(?:\||%7C)\*~i', fn ($t) => e($value(strtoupper($t[1]))), $m[0]) ?? $m[0];
        }, $html) ?? $html;
        // In the words: the sample, marked in a preview.
        return preg_replace_callback('~\*\|(' . $known . ')\|\*~i', function ($t) use ($mark, $value) {
            $k = strtoupper($t[1]);
            $v = e($value($k));
            if (! $mark) {
                return $v;
            }

            return '<span title="' . e(self::TAGS[$k][0]) . ': Acumbamail fills this in for each person" style="background:#fdf6dc;border-bottom:2px dotted #c9a227;border-radius:3px;padding:0 2px;">' . $v . '</span>';
        }, $html) ?? $html;
    }

    /** The same, for a subject line (no markup). */
    public static function fillText(string $text, array $sample = []): string
    {
        $known = implode('|', array_keys(self::TAGS));

        return preg_replace_callback('~\*\|(' . $known . ')\|\*~i', fn ($t) => (string) ($sample[strtoupper($t[1])] ?? self::TAGS[strtoupper($t[1])][2]), $text) ?? $text;
    }
}
