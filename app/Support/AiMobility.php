<?php

namespace App\Support;

/**
 * How an active ingredient moves once it is on the plant (2026-10-15, the
 * owner): systemic (carried through the plant), translaminar (through the
 * leaf, not the whole plant) or contact (stays where it lands). One line a
 * base ingredient in database/field-catalogue/mobility.json; biologicals and
 * baits have none, since they are neither.
 *
 * A mix ("Azoxystrobin plus difenoconazole") is read part by part, so it
 * can be systemic and contact at once. The helpers show a chip per class.
 */
final class AiMobility
{
    public const ORDER = ['systemic', 'translaminar', 'contact'];

    /** A herbicide's timings, in the order a season meets them. */
    public const WHEN = ['burndown', 'pre', 'early_post', 'post'];

    private static ?array $book = null;

    public static function book(): array
    {
        if (self::$book === null) {
            $raw = json_decode((string) @file_get_contents(database_path('field-catalogue/mobility.json')), true) ?: [];
            $ai = [];
            foreach ((array) ($raw['ai'] ?? []) as $name => $row) {
                $ai[self::norm($name)] = $row;
            }
            self::$book = ['classes' => (array) ($raw['classes'] ?? []), 'herb' => (array) ($raw['herb'] ?? []), 'ai' => $ai];
        }

        return self::$book;
    }

    /** The class words, for the explainer. */
    public static function classes(): array
    {
        return self::book()['classes'];
    }

    /** One base name, as the book files it. */
    public static function norm(string $s): string
    {
        $s = mb_strtolower(preg_replace('/\(.*?\)/', '', $s));

        return trim((string) preg_replace('/[\s\-]+/', ' ', str_replace(',', '', $s)));
    }

    /**
     * The classes of an ingredient name as the helpers write it, in the
     * book's order, with the notes: ['c' => ['systemic', 'contact'], 'n' => '...'].
     * Null when nothing is known (a biological, a bait, a name not in the book).
     */
    public static function of(string $name): ?array
    {
        $book = self::book()['ai'];
        $clean = trim(preg_replace('/\(.*?\)/', '', $name));
        // "2,4-D" keeps its comma; a mix is split on "plus" or "+".
        $parts = preg_split('/\s+plus\s+|\s*\+\s*|\s+with\s+(?!safener)/i', $clean) ?: [$clean];
        $cls = [];
        $notes = [];
        $sel = null;
        $when = [];
        foreach ($parts as $p) {
            $row = $book[self::norm($p)] ?? null;
            if (! $row) {
                continue;
            }
            $cls[$row['c']] = true;
            // A herbicide: selective unless any part kills every green plant; its timings joined.
            if (isset($row['s'])) {
                $sel = ($sel === 'non_selective' || $row['s'] === 'non_selective') ? 'non_selective' : 'selective';
                foreach ((array) ($row['w'] ?? []) as $w) {
                    $when[$w] = true;
                }
            }
            if (! empty($row['n'])) {
                $notes[] = (count($parts) > 1 ? ucfirst(trim($p)) . ': ' : '') . $row['n'];
            }
        }
        if (! $cls) {
            return null;
        }

        $out = ['c' => array_values(array_filter(self::ORDER, fn ($k) => isset($cls[$k]))), 'n' => $notes ? implode('. ', $notes) . '.' : null];
        if ($sel) {
            $out['s'] = $sel;
            $out['w'] = array_values(array_filter(self::WHEN, fn ($k) => isset($when[$k])));
        }

        return $out;
    }

    /** What a page carries to read names itself: the class words, and every base ingredient (normalised) => [class, note]. */
    public static function payload(): array
    {
        $b = self::book();

        return ['classes' => $b['classes'], 'herb' => $b['herb'], 'ai' => (object) $b['ai']];
    }

    /** A product: the classes of all its active ingredients together. */
    public static function ofList(array $ais): ?array
    {
        $cls = [];
        foreach ($ais as $a) {
            foreach ((self::of((string) $a)['c'] ?? []) as $c) {
                $cls[$c] = true;
            }
        }

        return $cls ? ['c' => array_values(array_filter(self::ORDER, fn ($k) => isset($cls[$k])))] : null;
    }
}
