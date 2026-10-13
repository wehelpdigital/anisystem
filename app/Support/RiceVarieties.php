<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The rice variety catalogue (2026-10-13): every variety in PhilRice's
 * catalogue of released varieties (its data file, facts only, and its
 * public seed database at
 * https://dbmp.philrice.gov.ph/seeds), as database/rice-varieties/varieties.json. Each one has
 * a page at /rice-varieties/{slug} (database/site-pages/rice-varieties, made
 * by database/rice-varieties/build_pages.py); the finder at /rice-varieties
 * reads this list.
 */
class RiceVarieties
{
    /** Growing environments: [short label, chip label]. */
    public const ENVIRONMENTS = [
        'irrigated' => ['Irrigated lowland', 'Irrigated'],
        'rainfed-tpr' => ['Rainfed lowland, transplanted', 'Rainfed, transplanted'],
        'rainfed-dsr' => ['Rainfed lowland, direct seeded', 'Rainfed, direct seeded'],
        'upland' => ['Upland', 'Upland'],
        'saline' => ['Saline prone', 'Saline'],
        'flood' => ['Flood prone', 'Flood prone'],
        'drought' => ['Drought prone', 'Drought prone'],
        'cool' => ['Cool elevated', 'Cool elevated'],
    ];

    /** The pests and diseases PhilRice rates, with their guide pages. */
    public const PROBLEMS = [
        'blast' => ['Blast', '/diseases/rice-blast'],
        'blb' => ['Bacterial leaf blight', '/diseases/bacterial-leaf-blight'],
        'tungro' => ['Tungro', '/diseases/rice-tungro'],
        'bph' => ['Brown planthopper', '/pests/brown-planthopper'],
        'glh' => ['Green leafhopper', '/pests/green-leafhopper'],
        'sb' => ['Stem borer', '/pests/rice-stem-borer'],
        'shb' => ['Sheath blight', '/diseases/sheath-blight'],
    ];

    /** Every variety, newest first. */
    public static function all(): array
    {
        $file = database_path('rice-varieties/varieties.json');
        $stamp = @filemtime($file) ?: 0;

        return Cache::remember('rice-varieties:' . $stamp, 3600, function () use ($file) {
            $rows = json_decode((string) @file_get_contents($file), true);

            return is_array($rows) ? $rows : [];
        });
    }

    /** The first letter code of a PhilRice rating: R, MR, I, MS or S. */
    public static function code(?string $rating): ?string
    {
        return preg_match('/^\s*\(?\s*(MR|MS|R|I|S)/', (string) $rating, $m) ? $m[1] : null;
    }
}
