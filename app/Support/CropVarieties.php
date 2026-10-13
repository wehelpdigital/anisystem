<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The crop variety catalogue (2026-10-13): every crop but rice (which has
 * App\Support\RiceVarieties). Varieties from the NSIC registry of crop
 * varieties the Bureau of Plant Industry publishes, and the ones farmers
 * buy that were never registered, from research notes with their sources:
 * database/crop-varieties/varieties.json, made by build_data.py. The crops,
 * their words and photos: database/crop-varieties/crops.json.
 *
 * Each variety has a page at /crop-varieties/{slug} (site pages, section
 * crop-varieties, made by build_pages.py). A crop with three or more
 * varieties has its own finder at /crop-varieties/{crop slug}, drawn here,
 * and /crop-varieties lists the crops.
 */
class CropVarieties
{
    /** A crop needs this many varieties for a finder of its own. */
    public const HUB_MIN = 3;

    /** Crop keys whose address differs from the key. */
    public const SLUGS = [
        'stringbean' => 'sitao', 'snapbean' => 'snap-bean', 'chinesecabbage' => 'chinese-cabbage', 'limabean' => 'lima-bean',
        'wingedbean' => 'winged-bean', 'bellpepper' => 'bell-pepper', 'sweetpotato' => 'sweet-potato', 'ubi' => 'ube',
        'starapple' => 'star-apple', 'pigeonpea' => 'pigeon-pea',
    ];

    /** Every variety, by crop then newest first. */
    public static function all(): array
    {
        return self::read('varieties.json', []);
    }

    /** crops.json: groups, heroes and crops. */
    public static function book(): array
    {
        return self::read('crops.json', ['groups' => [], 'heroes' => [], 'crops' => []]);
    }

    public static function slugOf(string $key): string
    {
        return self::SLUGS[$key] ?? $key;
    }

    /** The crops with varieties, each with its count, slug and words; biggest group first. */
    public static function crops(): array
    {
        $book = self::book();
        $counts = [];
        foreach (self::all() as $v) {
            $counts[$v['crop']] = ($counts[$v['crop']] ?? 0) + 1;
        }
        $out = [];
        foreach ($counts as $key => $n) {
            $c = $book['crops'][$key] ?? ['name' => ucfirst($key), 'group' => 'fruit'];
            $out[$key] = $c + ['key' => $key, 'slug' => self::slugOf($key), 'count' => $n, 'hasHub' => $n >= self::HUB_MIN];
        }

        return $out;
    }

    /** The crop a hub address names, or null. */
    public static function cropBySlug(string $slug): ?array
    {
        foreach (self::crops() as $c) {
            if ($c['slug'] === $slug && $c['hasHub']) {
                return $c;
            }
        }

        return null;
    }

    /** One crop's varieties, newest first. */
    public static function forCrop(string $key): array
    {
        return array_values(array_filter(self::all(), fn ($v) => $v['crop'] === $key));
    }

    /**
     * The crumbs between Home and a variety page (2026-10-14): rice is one of
     * the crop varieties too, so both sections read Crop varieties, then the
     * crop's finder. Null for any other section. [[name, url], ...]
     */
    public static function trail(string $section, ?string $slug = null): ?array
    {
        $top = ['Crop varieties', url('/crop-varieties')];
        if ($section === 'rice-varieties') {
            return [$top, ['Rice', url('/rice-varieties')]];
        }
        if ($section !== 'crop-varieties') {
            return null;
        }
        $v = $slug ? collect(self::all())->firstWhere('slug', $slug) : null;
        $c = $v ? (self::crops()[$v['crop']] ?? null) : null;

        return $c && $c['hasHub'] ? [$top, [$c['name'], url('/crop-varieties/' . $c['slug'])]] : [$top];
    }

    /** The hero photo of a crop: [src, alt]. */
    public static function hero(string $key): array
    {
        $book = self::book();
        $h = $book['crops'][$key]['hero'] ?? 'orchard';

        return $book['heroes'][$h] ?? ['/images/site/land/fruit-tree-land-preparation.webp', 'Young fruit trees planted in rows'];
    }

    /** What a variety is, in a few words: "Yellow GM hybrid", "Pole sitao". */
    public static function kind(array $v): string
    {
        $k = (string) ($v['kind'] ?? '');
        if (($v['crop'] ?? '') === 'corn' && ! empty($v['color']) && ! str_contains($k, 'sweet') && ! str_contains($k, 'glutinous')) {
            $k = $v['color'] . ' ' . $k;
        }
        $k = trim(preg_replace('/(?<=\w)-(?=\w)/', ' ', $k));

        return $k === '' ? '' : ucfirst($k);
    }

    /** The finder's type chip a variety falls under, by crop. */
    public static function chip(array $v): string
    {
        $k = strtolower((string) ($v['kind'] ?? ''));
        if (($v['crop'] ?? '') === 'corn') {
            return match (true) {
                str_contains($k, 'sweet') => 'sweet',
                str_contains($k, 'glutinous') => 'glutinous',
                str_contains($k, 'gm') => 'gm',
                str_contains($k, 'hybrid') => ($v['color'] ?? '') === 'white' ? 'white-hybrid' : 'hybrid',
                default => 'opv',
            };
        }
        if (str_contains($k, 'hybrid')) {
            return 'hybrid';
        }

        return $k === '' ? '' : 'other';
    }

    /** Days (or months) to harvest, rounded, for cards and sorting. */
    public static function days(array $v): ?int
    {
        $m = $v['maturity'] ?? null;

        return $m ? (int) round($m['value']) : null;
    }

    private static function read(string $file, array $empty): array
    {
        $path = database_path('crop-varieties/' . $file);
        $stamp = @filemtime($path) ?: 0;

        return Cache::remember('crop-varieties:' . $file . ':' . $stamp, 3600, function () use ($path, $empty) {
            $rows = json_decode((string) @file_get_contents($path), true);

            return is_array($rows) ? $rows : $empty;
        });
    }
}
