<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Research notes kept for the next farmer (2026-10-18, the owner: "lessen
 * the amount of tokens when generating protocol ... saving more tokens in
 * the long run").
 *
 * An analysis that reads the web first (AiClient::researchThenJson) pays
 * for a grounded search and a few thousand tokens of notes. Much of what it
 * reads is not about one field: a variety's published facts, the official
 * guide for a crop in a region, the pests of a crop at a stage, the soils of
 * a province, the real harvests near it this season. When the brief is
 * written from those things alone (never the farmer's own answers), the
 * notes are good for the next farmer who asks the same: they are kept here
 * under the brief's own fingerprint, for as long as their kind stays true
 * (DAYS), and the next run with the very same brief reads them instead of
 * searching again. A brief that changes in any word is a new brief.
 *
 * Only searched notes are kept, so a reading from memory never passes for
 * research.
 */
final class ResearchShelf
{
    use Concerns\ShelfConnection;

    /** How long each kind of notes stays true, in days. */
    public const DAYS = [
        'protocol' => 30,   // the variety, the guide, pests, water, this season's outlook and harvests
        'npk' => 30,        // the province's soils, biofertilizer trials, real harvests
        'satellite' => 14,  // the crop at this age and the soils of the place
        'variety' => 30,    // varieties released, trials, this season's outlook
        'what' => 21,       // real yields and farmgate prices
        'auto' => 21,
        'wtpVariety' => 120,
    ];

    /** The shortest notes worth keeping (a failed search says little). */
    private const MIN_CHARS = 600;

    public static function ready(): bool
    {
        return (bool) Cache::remember('research-shelf:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable('as_ai_research'));
    }

    public static function key(string $kind, string $brief): string
    {
        return sha1($kind . "\n" . trim(preg_replace('/[ \t]+/', ' ', $brief)));
    }

    /**
     * The kept notes for this brief, or null: ['notes', 'sources', 'at', 'id'].
     * A hit is counted.
     */
    public static function get(string $kind, string $brief): ?array
    {
        if (! self::ready()) {
            return null;
        }
        try {
            $r = self::db()->table('as_ai_research')->where('kind', $kind)->where('cacheKey', self::key($kind, $brief))
                ->where('expiresAt', '>', now())->first();
            if (! $r) {
                return null;
            }
            self::db()->table('as_ai_research')->where('id', $r->id)->update(['hits' => DB::raw('hits + 1'), 'lastHitAt' => now()]);

            return ['id' => (int) $r->id, 'notes' => (string) $r->notes, 'sources' => json_decode((string) $r->sources, true) ?: [],
                'at' => (string) $r->created_at, 'tokensIn' => (int) $r->tokensIn, 'tokensOut' => (int) $r->tokensOut];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Keep searched notes for the next run with the same brief. */
    public static function put(string $kind, string $brief, string $scope, array $research): void
    {
        $notes = trim((string) ($research['text'] ?? ''));
        if (! self::ready() || empty($research['searched']) || mb_strlen($notes) < self::MIN_CHARS) {
            return;
        }
        $days = self::DAYS[$kind] ?? 14;
        try {
            self::db()->table('as_ai_research')->upsert([[
                'kind' => mb_substr($kind, 0, 24), 'cacheKey' => self::key($kind, $brief), 'scope' => mb_substr($scope, 0, 255),
                'notes' => $notes, 'sources' => json_encode(array_slice((array) ($research['sources'] ?? []), 0, 40)), 'searched' => true,
                'tokensIn' => (int) ($research['tokensIn'] ?? 0), 'tokensOut' => (int) ($research['tokensOut'] ?? 0), 'hits' => 0, 'lastHitAt' => null,
                'expiresAt' => now()->addDays($days), 'created_at' => now(), 'updated_at' => now(),
            ]], ['kind', 'cacheKey'], ['scope', 'notes', 'sources', 'searched', 'tokensIn', 'tokensOut', 'hits', 'lastHitAt', 'expiresAt', 'updated_at']);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Notes past their time, removed (the scheduler, daily). */
    public static function prune(): int
    {
        return self::ready() ? self::db()->table('as_ai_research')->where('expiresAt', '<', now()->subDays(30))->delete() : 0;
    }
}
