<?php

namespace App\Support;

use Closure;

/**
 * What a saved drawing's strokes are allowed to be.
 *
 * Strokes arrive in two shapes and both are still written: the flat list of
 * objects every drawing was before the pad had pages, and a list of pages —
 * [{objects: [...], current: bool}]. A plain `array|max:4000` counts the top
 * level, so once the top level became the page list (twelve at most) the cap on
 * objects was gone: one page inside a paged save could carry any number of
 * them. These count what actually costs something, which is the objects.
 */
class DrawStrokes
{
    /** Matches the pad's own MAX_PAGES; anything past this is a document. */
    public const MAX_PAGES = 12;

    /** What one sheet was always allowed, back when a drawing was one sheet. */
    public const MAX_PER_PAGE = 4000;

    /** Pages multiply the budget, but not without limit. */
    public const MAX_TOTAL = 12000;

    /** Is this the paged shape, or the flat list a drawing used to be? */
    public static function isPaged($strokes): bool
    {
        if (! is_array($strokes) || empty($strokes)) {
            return false;
        }
        foreach ($strokes as $page) {
            if (! is_array($page) || ! isset($page['objects']) || ! is_array($page['objects'])) {
                return false;
            }
        }

        return true;
    }

    /** How many sheets a saved drawing has. Anything unrecognised is one. */
    public static function pageCount($strokes): int
    {
        return self::isPaged($strokes) ? count($strokes) : 1;
    }

    /** Objects across every page — the number that decides how big the row is. */
    public static function objectCount($strokes): int
    {
        if (! is_array($strokes)) {
            return 0;
        }
        if (! self::isPaged($strokes)) {
            return count($strokes);
        }

        return array_sum(array_map(fn ($p) => count($p['objects']), $strokes));
    }

    /**
     * The validation rule for a `strokes` field, in either shape.
     *
     * It fails the way the user can act on: a drawing this big can still be
     * kept as a picture, which is the answer they want to hear.
     */
    public static function rule(): Closure
    {
        return function ($attribute, $value, $fail) {
            if (! is_array($value) || empty($value)) {
                return;
            }
            if (self::isPaged($value)) {
                if (count($value) > self::MAX_PAGES) {
                    $fail('That drawing has more pages than one drawing keeps.');

                    return;
                }
                foreach ($value as $page) {
                    if (count($page['objects']) > self::MAX_PER_PAGE) {
                        $fail('One of those pages has too much on it to keep as an editable drawing. Saving it as an image will still work.');

                        return;
                    }
                }
            }
            if (self::objectCount($value) > self::MAX_TOTAL) {
                $fail('That drawing has too much on it to keep as an editable drawing. Saving it as an image will still work.');
            }
        };
    }
}
