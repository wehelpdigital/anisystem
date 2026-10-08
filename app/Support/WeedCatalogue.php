<?php

namespace App\Support;

/**
 * The weeds on /weeds (2026-10-06): one card per weed, with its profile page
 * when one is written (database/site-pages/weeds/{slug}.json, editable in
 * the mother app) and a short fact sheet when not.
 *
 * The weeds of rice came first, from PhilRice's catalogue; since 2026-10-08
 * the weeds of every other crop sit beside them, each with the crops it
 * troubles. The list itself lives in the field catalogue
 * (App\Support\FieldCatalogue), worst weeds of rice first.
 */
final class WeedCatalogue
{
    /** Every weed: slug => name, sci, group, life, local, hint, crops. */
    public static function all(): array
    {
        return FieldCatalogue::weeds();
    }

    /** One weed's facts, or null for a page that is not a weed (the guides). */
    public static function get(string $slug): ?array
    {
        return FieldCatalogue::weed($slug);
    }
}
