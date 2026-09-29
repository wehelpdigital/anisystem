<?php

namespace App\Support;

use App\Models\AsSiteSetting;

/**
 * The ad networks' tags on the signup path (2026-09-29): the Meta Pixel and
 * the Google tag, their ids set in the mother app (AniSystem > Landing page)
 * under `landing.tracking`. Blank ids draw nothing. They are drawn on the
 * landing page, the signup form and the "check your inbox" page, where a
 * fresh signup is reported as a conversion, and nowhere behind the login.
 */
class AdTags
{
    public const KEY = 'landing.tracking';

    /** @return array{metaPixel: string, googleTag: string, googleAdsSendTo: string} */
    public static function ids(): array
    {
        static $ids = null;
        if ($ids !== null) {
            return $ids;
        }
        $s = json_decode((string) AsSiteSetting::get(self::KEY, ''), true);
        $s = is_array($s) ? $s : [];
        $pick = fn (string $k, string $re) => is_string($s[$k] ?? null) && preg_match($re, $s[$k]) ? $s[$k] : '';

        return $ids = [
            'metaPixel' => $pick('metaPixel', '/^\d{5,20}$/'),
            'googleTag' => $pick('googleTag', '/^(G|AW|GT)-[A-Z0-9]{4,20}$/'),
            'googleAdsSendTo' => $pick('googleAdsSendTo', '#^AW-\d{5,15}/[A-Za-z0-9_-]{3,40}$#'),
        ];
    }

    public static function any(): bool
    {
        $ids = self::ids();

        return $ids['metaPixel'] !== '' || $ids['googleTag'] !== '' || $ids['googleAdsSendTo'] !== '';
    }
}
