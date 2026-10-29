<?php

namespace App\Support;

use App\Models\AsSitePage;

/**
 * Anee Knowledgebase (2026-10-30, the owner: "inside anee, add a new stash group in the stash called anee knowledgebase
 * and put in there nutrients and when opened, show a full modal that contains the nutrient knowledge"): anee.io's own
 * guides, read inside the app in a full window over the dashboard. A shelf is a section of the public site's guides:
 * the live pages as the mother app keeps them, else the shipped files (database/site-pages/{section}).
 */
final class Knowledge
{
    /** The shelves: section => label, line, icon, and the order its groups read in. */
    public const SHELVES = [
        'nutrients' => [
            'label' => 'Nutrients',
            'about' => 'What every plant nutrient does, macro and micro, and the days each crop needs it most.',
            'icon' => '🧪',
            'groups' => ['Start here', 'Primary nutrients', 'Secondary nutrients', 'Micronutrients', 'Beneficial nutrients'],
        ],
    ];

    public static function shelf(string $key): ?array
    {
        return self::SHELVES[$key] ?? null;
    }

    /** How many guides a shelf holds, for its card. */
    public static function count(string $key): int
    {
        return count(self::pages($key));
    }

    /**
     * A shelf's guides, in the order of its groups: slug, title, category, excerpt, hero and blocks.
     *
     * @return array<int, array>
     */
    public static function pages(string $key): array
    {
        static $memo = [];
        if (isset($memo[$key])) {
            return $memo[$key];
        }
        $shelf = self::shelf($key);
        if (! $shelf) {
            return [];
        }
        // Kept ten minutes: the dashboard counts the guides on every visit, and a mother app edit shows within that.
        return $memo[$key] = \Illuminate\Support\Facades\Cache::remember('knowledge:' . $key, 600, fn () => self::read($key, $shelf));
    }

    private static function read(string $key, array $shelf): array
    {
        $rows = [];
        try {
            $rows = AsSitePage::live()->where('section', $key)->orderBy('sortOrder')->orderBy('id')
                ->get(['slug', 'title', 'category', 'excerpt', 'heroImage', 'blocks', 'sortOrder'])
                ->map(fn ($p) => ['slug' => $p->slug, 'title' => $p->title, 'category' => $p->category, 'excerpt' => $p->excerpt, 'hero' => $p->heroImage, 'blocks' => $p->blocks ?: [], 'sort' => (int) $p->sortOrder])
                ->all();
        } catch (\Throwable $e) {
            report($e);
        }
        if (! $rows) {
            foreach (glob(database_path('site-pages/' . $key . '/*.json')) ?: [] as $f) {
                $p = json_decode((string) file_get_contents($f), true);
                if (is_array($p) && ! empty($p['slug'])) {
                    $rows[] = ['slug' => $p['slug'], 'title' => $p['title'] ?? $p['slug'], 'category' => $p['category'] ?? null, 'excerpt' => $p['excerpt'] ?? '',
                        'hero' => $p['heroImage'] ?? null, 'blocks' => $p['blocks'] ?? [], 'sort' => (int) ($p['sortOrder'] ?? 999)];
                }
            }
        }
        $order = array_flip($shelf['groups']);
        usort($rows, fn ($a, $b) => [($order[$a['category']] ?? 99), $a['sort'], $a['title']] <=> [($order[$b['category']] ?? 99), $b['sort'], $b['title']]);
        // The page builder's call to action asks a visitor to sign up: inside the app it is said already.
        foreach ($rows as &$r) {
            $r['blocks'] = array_values(array_filter((array) $r['blocks'], fn ($b) => is_array($b) && ($b['type'] ?? '') !== 'cta'));
        }
        unset($r);

        return $rows;
    }
}
