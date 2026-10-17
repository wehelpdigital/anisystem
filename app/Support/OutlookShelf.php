<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What Anee wrote about a place that is the same for the next farmer there
 * (2026-10-19, the owner: "if the enso data is somewhat still new like
 * within 1 week ... then no need to reanalyze it in any analysis and just
 * use the existing ones or if there's already a history data of the
 * typhoons, etc. this is to save any tokens possible").
 *
 * A section of a report written ONLY from the place's kept facts (its
 * hazard history, its weather record, the ENSO reading), never from the
 * farmer's crop, lot or answers, is kept here under its kind and those
 * facts' key, and the next analysis for the same key reads it instead of
 * asking Anee to write it again:
 *
 *   wtp.risk       When to Plant's riskHistory and typhoonOdds for a town
 *                  (the hazard book and the weather record): kept until the
 *                  book's next refresh (hazards:refresh on the 6th and 21st)
 *   what.risk      What to Plant's month by month risk for a town, the same
 *   satellite.sky  Satellite Analysis's climate {enso, history} for a town
 *                  and the months to harvest, on one ENSO reading
 *   sky.climate    Satellite Weather's climate line for a town, this month
 *                  and next, on one ENSO reading
 *
 * An ENSO bound section is reused only while the ENSO reading it was
 * written on is still the latest (EnsoOutlook::key) and at most
 * Freshness::OUTLOOK_DAYS; a history one only within the hazard book's
 * refresh period (period()). Read through Concerns\ShelfConnection.
 */
final class OutlookShelf
{
    use Concerns\ShelfConnection;

    public static function ready(): bool
    {
        return (bool) Cache::remember('outlook-shelf:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable('as_ai_outlooks'));
    }

    /**
     * The hazard book's refresh period a date falls in: the book is read
     * again on the 6th and the 21st, so a history section written in one
     * period is the same until the next.
     */
    public static function period($at = null): string
    {
        $d = $at ? \Illuminate\Support\Carbon::parse($at)->timezone('Asia/Manila') : now('Asia/Manila');
        if ($d->day < 6) {
            $d = $d->copy()->subMonthNoOverflow();

            return $d->format('Y-m') . 'b';
        }

        return $d->format('Y-m') . ($d->day >= 21 ? 'b' : 'a');
    }

    /**
     * The parts a section's key is made of: the kind's own parts, and the
     * ENSO reading (an ENSO bound kind) or the book's period (a history
     * kind). Null when the ENSO reading is not known (nothing is reused or
     * kept then).
     */
    private static function parts(array $parts, bool $enso): ?array
    {
        if ($enso) {
            $k = EnsoOutlook::key();
            if ($k === '') {
                return null;
            }
            $parts['enso'] = $k;
        } else {
            $parts['period'] = self::period();
        }

        return $parts;
    }

    public static function key(string $kind, array $parts): string
    {
        ksort($parts);

        return sha1($kind . "\n" . json_encode($parts, JSON_UNESCAPED_UNICODE));
    }

    /**
     * The kept section for a kind and its parts, or null. A hit is counted.
     *
     * @return array|null the payload as it was kept
     */
    public static function get(string $kind, array $parts, bool $enso = true): ?array
    {
        if (! self::ready() || ! ($full = self::parts($parts, $enso))) {
            return null;
        }
        try {
            $r = self::db()->table('as_ai_outlooks')->where('outlookKey', self::key($kind, $full))->first();
            if (! $r || ($enso && ! Freshness::outlookFresh($r->writtenAt))) {
                return null;
            }
            $payload = json_decode((string) $r->payload, true);
            if (! is_array($payload) || ! $payload) {
                return null;
            }
            self::db()->table('as_ai_outlooks')->where('id', $r->id)->update(['uses' => DB::raw('uses + 1'), 'lastUsedAt' => now()]);

            return $payload;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Keep a section for the next analysis with the same parts. */
    public static function put(string $kind, array $parts, array $payload, bool $enso = true, ?string $place = null, ?string $months = null): void
    {
        if (! $payload || ! self::ready() || ! ($full = self::parts($parts, $enso))) {
            return;
        }
        try {
            self::db()->table('as_ai_outlooks')->upsert([[
                'kind' => mb_substr($kind, 0, 40), 'outlookKey' => self::key($kind, $full), 'place' => $place ? mb_substr($place, 0, 160) : null,
                'months' => $months ? mb_substr($months, 0, 64) : null, 'ensoKey' => $full['enso'] ?? null,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'uses' => 0, 'writtenAt' => now(), 'lastUsedAt' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]], ['outlookKey'], ['payload', 'uses', 'writtenAt', 'lastUsedAt', 'updated_at']);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Sections past any use, removed (the collector, daily). */
    public static function prune(int $days = 45): int
    {
        return self::ready() ? self::db()->table('as_ai_outlooks')->where('writtenAt', '<', now()->subDays($days))->delete() : 0;
    }
}
