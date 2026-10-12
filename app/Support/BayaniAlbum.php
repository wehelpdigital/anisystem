<?php

namespace App\Support;

/**
 * Bayani ng Lupa (2026-10-12): the anee.io album, in place of the single
 * song card on the homepage and the dashboard. Seven songs for the heroes of
 * the land; the first is the main song. The files are under public/audio
 * (128 kbps, tagged), the artwork under public/images/site/album.
 */
final class BayaniAlbum
{
    public const TITLE = 'Bayani ng Lupa';

    public const ART = 'images/site/album/bayani-ng-lupa-anee';

    public const ALT = 'Anee, the anee.io smart farm technician, as a hero of the land in a Philippine rice field, a green glow rising from a rice seedling while farmers cheer behind her';

    public const DESCRIPTION = 'Bayani ng Lupa, the anee.io album: seven songs for the Filipino farmers who feed the country, from the farmers who built anee.io.';

    /** [file under public/audio, title, length in seconds, the main song]. */
    public const TRACKS = [
        ['bayani-ng-lupa/01-sulong-mga-bayani-ng-lupa.mp3', 'Sulong, Mga Bayani ng Lupa', 223, true],
        ['bayani-ng-lupa/02-tuloy-ang-laban.mp3', 'Tuloy Ang Laban', 242, false],
        ['bayani-ng-lupa/03-ugat-ng-bansa.mp3', 'Ugat ng Bansa', 228, false],
        ['bayani-ng-lupa/04-laban-lang.mp3', 'Laban Lang', 268, false],
        ['bayani-ng-lupa/05-kami-ang-palay.mp3', 'Kami Ang Palay', 224, false],
        ['bayani-ng-lupa/06-aangat-din.mp3', 'Aangat Din', 196, false],
        ['anee-kay-anee-ang-ani-ay-high.mp3', 'Let\'s Plant With a Dream', 225, false],
    ];

    /** The tracks as the player and the page's structured data read them. */
    public static function tracks(): array
    {
        return array_map(fn ($t) => ['src' => asset('audio/' . $t[0]), 'title' => $t[1], 'seconds' => $t[2], 'main' => $t[3],
            'length' => intdiv($t[2], 60) . ':' . str_pad((string) ($t[2] % 60), 2, '0', STR_PAD_LEFT)], self::TRACKS);
    }

    /** schema.org MusicAlbum, for the homepage. */
    public static function schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'MusicAlbum',
            'name' => self::TITLE,
            'description' => self::DESCRIPTION,
            'image' => asset(self::ART . '-1200.jpg'),
            'byArtist' => ['@type' => 'MusicGroup', 'name' => 'anee.io'],
            'numTracks' => count(self::TRACKS),
            'track' => array_map(fn ($t, $i) => ['@type' => 'MusicRecording', 'position' => $i + 1, 'name' => $t['title'], 'url' => $t['src'],
                'duration' => 'PT' . intdiv($t['seconds'], 60) . 'M' . ($t['seconds'] % 60) . 'S'], self::tracks(), array_keys(self::TRACKS)),
        ];
    }
}
