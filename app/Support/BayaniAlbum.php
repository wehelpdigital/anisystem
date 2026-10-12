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

    /**
     * Listen on (2026-10-13, the owner): the services the album is on, with
     * their marks (Simple Icons paths, CC0) and colours. Until a service's
     * own album page is set here, its link searches that service for the
     * album; put the real link in its place when it is known.
     */
    public const LISTEN = [
        'spotify' => ['Spotify', 'https://open.spotify.com/search/Bayani%20ng%20Lupa%20anee.io', '#1DB954', 'M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z'],
        'tiktok' => ['TikTok', 'https://www.tiktok.com/search?q=Bayani%20ng%20Lupa%20anee.io', '#111111', 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z'],
        'youtubemusic' => ['YouTube Music', 'https://music.youtube.com/search?q=Bayani+ng+Lupa+anee.io', '#FF0000', 'M12 0C5.376 0 0 5.376 0 12s5.376 12 12 12 12-5.376 12-12S18.624 0 12 0zm0 19.104c-3.924 0-7.104-3.18-7.104-7.104S8.076 4.896 12 4.896s7.104 3.18 7.104 7.104-3.18 7.104-7.104 7.104zm0-13.332c-3.432 0-6.228 2.796-6.228 6.228S8.568 18.228 12 18.228s6.228-2.796 6.228-6.228S15.432 5.772 12 5.772zM9.684 15.54V8.46L15.816 12l-6.132 3.54z'],
        'instagram' => ['Instagram', 'https://www.instagram.com/explore/search/keyword/?q=Bayani%20ng%20Lupa%20anee.io', '#E4405F', 'M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077'],
        'deezer' => ['Deezer', 'https://www.deezer.com/search/Bayani%20ng%20Lupa%20anee.io', '#A238FF', 'M18.81 4.16v3.03H24V4.16h-5.19zM6.27 8.38v3.027h5.189V8.38h-5.19zm12.54 0v3.027H24V8.38h-5.19zM6.27 12.594v3.027h5.189v-3.027h-5.19zm6.271 0v3.027h5.19v-3.027h-5.19zm6.27 0v3.027H24v-3.027h-5.19zM0 16.81v3.029h5.19v-3.03H0zm6.27 0v3.029h5.189v-3.03h-5.19zm6.271 0v3.029h5.19v-3.03h-5.19zm6.27 0v3.029H24v-3.03h-5.19Z'],
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
