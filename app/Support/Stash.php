<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The Stash (2026-10-07): resources anee.io's partners share with its
 * farmers, shelved by partner and by type. The rows live in as_stash_items;
 * the partners and their shelves are named here.
 */
final class Stash
{
    public const PARTNERS = [
        'philrice' => [
            'name' => 'PhilRice',
            'full' => 'Philippine Rice Research Institute',
            'logo' => 'images/partners/philrice.webp',
            'url' => 'https://www.philrice.gov.ph',
            'about' => 'The DA agency that breeds the rice varieties and writes the PalayCheck practice most Filipino rice farmers use.',
            'types' => [
                'e-magazines' => [
                    'label' => 'E-magazines',
                    'about' => 'PhilRice Magazine, every issue: farmer stories, new varieties, and the research behind better harvests. In English and Filipino.',
                ],
            ],
        ],
    ];

    public static function partner(string $p): ?array
    {
        return self::PARTNERS[$p] ?? null;
    }

    public static function type(string $p, string $t): ?array
    {
        return self::PARTNERS[$p]['types'][$t] ?? null;
    }

    /** Partner => type => how many items are ready to read. */
    public static function counts(): array
    {
        try {
            return DB::table('as_stash_items')->where('deleteStatus', 1)
                ->selectRaw('partner, type, count(*) as n, sum(status = \'ready\') as ready')->groupBy('partner', 'type')->get()
                ->groupBy('partner')->map(fn ($g) => $g->keyBy('type')->map(fn ($r) => ['n' => (int) $r->n, 'ready' => (int) $r->ready]))->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Where an item's PDF is kept on the media disk. */
    public static function pdfPath(string $partner, string $slug): string
    {
        return 'stash/' . $partner . '/' . $slug . '.pdf';
    }
}
