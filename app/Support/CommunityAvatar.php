<?php

namespace App\Support;

/**
 * Deterministic identity hues for the community ("Ang Plaza" system).
 * crc32 of the lowercased name picks one of 8 crop-named hue classes, so a
 * person or group always wears the same color — with zero stored assets.
 */
class CommunityAvatar
{
    /** av-h0 palay · h1 mais · h2 langit · h3 kamatis · h4 talong · h5 dagat · h6 kalabasa · h7 abo */
    public static function hue(?string $name): string
    {
        $key = mb_strtolower(trim((string) $name));
        if ($key === '' || $key === '?') {
            return 'av-h7';
        }

        return 'av-h' . (crc32($key) % 8);
    }

    /** Two-letter monogram for a group name ("Rice Growers PH" → "RG"). */
    public static function monogram(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = '';
        foreach ($words as $w) {
            if ($w !== '' && preg_match('/\pL|\pN/u', mb_substr($w, 0, 1))) {
                $letters .= mb_strtoupper(mb_substr($w, 0, 1));
            }
            if (mb_strlen($letters) === 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : '?';
    }
}
