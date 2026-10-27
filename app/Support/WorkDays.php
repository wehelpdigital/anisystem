<?php

namespace App\Support;

/**
 * A worker's week (2026-10-28, the owner: "weekdays they do not work / work
 * should be selectable if half day am/pm or wholeday as well"): each weekday
 * is a whole day, a morning, an afternoon or a day off.
 *
 * The days off stay where they always were (a season card's off days in
 * as_schedule_worker_off_days, the roster's offWeekdays), so every reader of
 * those is unchanged: a half day is still a day they come. The half days are
 * `dayParts`, weekday => am|pm, on the roster (as_global_workers) and on each
 * season card (as_schedule_workers). The page side is partials/work-days.
 */
final class WorkDays
{
    public const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    /** The task times (AsAutoTask::timeOfDay) each half day can take. */
    public const TIME = ['morning' => 'am', 'afternoon' => 'pm'];

    /**
     * The half days as weekday => am|pm, none on a day off; null when every
     * day they come is a whole day.
     */
    public static function parts(mixed $raw, array $off = []): ?array
    {
        $off = array_map('intval', $off);
        $out = [];
        foreach ((array) $raw as $d => $p) {
            $d = (int) $d;
            if ($d >= 0 && $d <= 6 && in_array($p, ['am', 'pm'], true) && ! in_array($d, $off, true)) {
                $out[$d] = $p;
            }
        }
        ksort($out);

        return $out ?: null;
    }

    /** For a JSON answer: always an object, so a Sunday alone is {"0":"am"} and never ["am"]. */
    public static function json(mixed $raw): object
    {
        return (object) (self::parts($raw) ?? []);
    }

    /** "Mon morning only, Sat afternoon only", for a person or a prompt. */
    public static function words(mixed $raw): string
    {
        return implode(', ', array_map(fn ($d, $p) => self::DAYS[$d] . ($p === 'am' ? ' morning only' : ' afternoon only'),
            array_keys(self::parts($raw) ?? []), self::parts($raw) ?? []));
    }
}
