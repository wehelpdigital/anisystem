<?php

namespace App\Support;

/**
 * Who a public page's picture belongs to, and how it may be used (2026-11-06:
 * Search Console's Image metadata asked for creator, license and
 * acquireLicensePage). The credit under a picture is the record: "Author,
 * Licence, via Place". This turns it into those fields, so what search engines
 * read cannot disagree with what the reader sees.
 *
 * Many pages reuse a photo credited only where it first appeared, so
 * database/site-pages/picture-rights.json holds the credit by file, and the
 * original page where it is known. A picture with no name under it points at
 * /legal/pictures, and names no creator unless it is on record as ours: who
 * took it is not ours to guess.
 */
class PictureRights
{
    /** The page that says how the pictures on this site may be used. */
    private const DOOR = '/legal/pictures';

    /** A credit naming one of these is a body, not a person. */
    private const BODY = '/\b(company|corp|inc|ltd|university|college|department|division|institute|service|agency|bureau|board|ministry|museum|society|secretariat|team|laborator\w*|research|center|centre|foundation|association|extension|archives?|library|government|project|bugwood|usda|usdagov|usaid|csiro|irri|fao|nasa|copernicus)\b|\.(com|org|net)$/i';

    private static ?array $book = null;

    private static function book(): array
    {
        return self::$book ??= (array) json_decode((string) @file_get_contents(database_path('site-pages/picture-rights.json')), true);
    }

    private static function known(string $src): array
    {
        return (array) (self::book()['files'][$src] ?? []);
    }

    /** A screen of the app, or a photo on record as the owner's. */
    private static function isOurs(string $src): bool
    {
        foreach ((array) (self::book()['oursUnder'] ?? []) as $under) {
            if (str_starts_with($src, $under)) {
                return true;
            }
        }

        return in_array($src, (array) (self::book()['ours'] ?? []), true);
    }

    /** The credit to show: the page's own while it names a licence, else the one on record, else the page's own. */
    public static function credit(?string $src, ?string $credit = null): string
    {
        $own = trim((string) $credit);
        if ($own !== '' && self::licenceUrl($own) !== '') {
            return $own;
        }

        return trim((string) (self::known((string) $src)['credit'] ?? '')) ?: $own;
    }

    /** The fields an ImageObject carries for this picture. */
    public static function schema(?string $src, ?string $credit = null): array
    {
        $src = (string) $src;
        $said = self::credit($src, $credit);
        $door = url(self::DOOR);

        if ($said === '') {
            $ours = self::isOurs($src);

            return array_filter([
                'creator' => $ours ? ['@type' => 'Organization', 'name' => 'anee.io'] : null,
                'creditText' => 'anee.io',
                // As the Terms of Service put it: ours, or licensed to us.
                'copyrightNotice' => $ours ? '© anee.io' : 'anee.io or those who license it to anee.io',
                'license' => $door,
                'acquireLicensePage' => $door,
            ]);
        }

        $words = SitePages::plain($said);
        // A news picture's credit links to its report: the one place that speaks for it.
        $link = preg_match('/\]\((https?:\/\/[^)\s]+)\)/', $said, $m) ? $m[1] : null;

        return array_filter([
            'creator' => self::creator($words),
            'creditText' => $words,
            'copyrightNotice' => $words,
            'license' => self::licenceUrl($words) ?: ($link ?: $door),
            'acquireLicensePage' => $link ?: (self::known($src)['page'] ?? $door),
        ]);
    }

    /**
     * The licence named in a credit, as the address of its terms; '' when none
     * is named. iNaturalist says only "CC BY": every licence it offers is 4.0.
     */
    public static function licenceUrl(string $words): string
    {
        if (preg_match('/\bCC0\b/i', $words)) {
            return 'https://creativecommons.org/publicdomain/zero/1.0/';
        }
        if (preg_match('/\bpublic domain\b/i', $words)) {
            return 'https://creativecommons.org/publicdomain/mark/1.0/';
        }
        if (preg_match('/\bUnsplash License\b/i', $words)) {
            return 'https://unsplash.com/license';
        }
        if (! preg_match('/\bCC[\s-]+BY((?:[\s-]+(?:NC|SA|ND))*)(?:[\s-]+(\d\.\d)(?:[\s-]+([a-z]{2})\b)?)?/i', $words, $m)) {
            return '';
        }
        $version = $m[2] ?? '';
        if ($version === '') {
            if (! preg_match('/\biNaturalist\b/i', $words)) {
                return '';
            }
            $version = '4.0';
        }
        $parts = array_merge(['by'], array_map('strtolower', preg_split('/[\s-]+/', trim((string) $m[1]), -1, PREG_SPLIT_NO_EMPTY)));

        return 'https://creativecommons.org/licenses/' . implode('-', $parts) . '/' . $version . '/' . (! empty($m[3]) ? strtolower($m[3]) . '/' : '');
    }

    /** Who took it: the first name in the credit, without where they are from. */
    private static function creator(string $words): ?array
    {
        $who = (string) preg_split('/,?\s*\b(?:public domain|CC0|CC[\s-]+BY|Unsplash License)\b/i', $words, 2)[0];
        $isNews = str_starts_with(trim($who), '©');
        $who = trim((string) preg_replace('/^[©\s]+/u', '', $who));
        $who = trim(explode(',', $who, 2)[0]);
        // "Scot Nelson from Honolulu", "Vinayaraj at Malayalam Wikipedia", "Preston Keres for the USDA".
        $who = trim((string) preg_replace('/\s+(?:from\s.+|for the\s.+|at\s\S+ Wikipedia|www\.\S+|\(.*\))$/u', '', $who));
        $who = trim((string) preg_replace('/^From left:\s*/iu', '', $who));
        if ($who === '' || preg_match('/^unknown( author)?$/i', $who)) {
            return null;
        }

        return ['@type' => $isNews || preg_match(self::BODY, $who) ? 'Organization' : 'Person', 'name' => $who];
    }
}
