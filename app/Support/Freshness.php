<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * How long what the app keeps serves every analysis (the owner, 2026-10-19:
 * "if the enso data is somewhat still new like within 1 week ... then no
 * need to reanalyze it in any analysis and just use the existing ones or if
 * there's already a history data of the typhoons, etc.").
 *
 *  - ENSO, the state (NOAA's ONI) and the official forecast (the monthly
 *    advisory): seven days. NOAA writes the ONI once a month and the advisory
 *    on the second Thursday of each month; the weekly Niño 3.4 update is not
 *    read. So a reading checked within a week is never more than a week
 *    behind a release, and the daily collector (climate:collect --enso)
 *    usually has a new one the next morning. Analyses never look it up.
 *  - The weather forecast: three hours a place (WeatherService::FRESH_HOURS),
 *    shared by everyone at that place and every analysis.
 *  - History: the twenty years of weeks, the past days, the hazard book's
 *    storms, floods and droughts. A day that is over never changes, so it is
 *    read once and kept; only the scheduled refreshes add to it
 *    (climate:collect hourly, hazards:refresh on the 6th and 21st). No
 *    analysis searches it again.
 *  - Text Anee wrote that depends only on the place, the months and the ENSO
 *    reading (a season outlook): reused for the same key while the ENSO
 *    reading it was written on is the latest one, and at most seven days.
 */
final class Freshness
{
    /** Days a kept ENSO reading or advisory serves before it is read again. */
    public const ENSO_DAYS = 7;

    /** Hours a kept forecast serves its place. */
    public const FORECAST_HOURS = 3;

    /** Days a kept outlook paragraph is reused (also bounded by its ENSO reading). */
    public const OUTLOOK_DAYS = 7;

    /** Whether ENSO kept at $at is fresh. */
    public static function ensoFresh($at): bool
    {
        return $at !== null && Carbon::parse($at)->gt(now()->subDays(self::ENSO_DAYS));
    }

    /** Whether a forecast kept at $at is fresh. */
    public static function forecastFresh($at): bool
    {
        return $at !== null && Carbon::parse($at)->gt(now()->subHours(self::FORECAST_HOURS));
    }

    /** Whether an outlook written at $at may be reused. */
    public static function outlookFresh($at): bool
    {
        return $at !== null && Carbon::parse($at)->gt(now()->subDays(self::OUTLOOK_DAYS));
    }
}
