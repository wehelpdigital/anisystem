<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Services\WeatherService;

/**
 * The sky for a question asked in a chat (2026-10-19, the owner: "in any
 * anee analysis, you can check the enso forecast and weather forecast"):
 * the chat rules let Anee speak of the weather only from forecast data
 * attached to the question, and nothing attached it. A question about the
 * weather, a spray, fertilizer or timing now gets the ENSO line and, inside
 * a season, the next seven days at each of its places (at most three), all
 * read from what the app keeps (EnsoOutlook, WeatherService's three hour
 * readings): no outside call when they are fresh. Any other question goes
 * without, so it pays nothing for them.
 */
final class SkyLines
{
    /** Whether a question is about the weather, a spray, fertilizer or timing. */
    public static function question(string $q): bool
    {
        return (bool) preg_match('/ulan|umulan|rain|bagyo|typhoon|storm|baha|flood|init|heat|tagtuyot|drought|tuyo|el ni|la ni|enso|panahon|weather|forecast|hangin|wind|spray|wisik|abono|fertili|urea|kailan|when|harvest|\bani\b|anihin|tanim|plant|irrigat|patubig|tubig/i', $q);
    }

    /** Whether a question is about ENSO or a season long dry or wet spell (the ENSO line helps with no season). */
    public static function ensoQuestion(string $q): bool
    {
        return (bool) preg_match('/el ni|la ni|enso|tagtuyot|drought|dry spell|tag-?ulan|tag-?araw|rainy season|dry season|season/i', $q);
    }

    /** The sky lines for a question, '' when it is about something else. */
    public static function forQuestion(string $q, ?AsCroppingSchedule $s = null): string
    {
        if (! self::question($q) || (! $s && ! self::ensoQuestion($q))) {
            return '';
        }
        $lines = [];
        try {
            $e = EnsoOutlook::brief();
            if ($e !== '') {
                $lines[] = $e;
            }
            if ($s) {
                $w = app(WeatherService::class);
                $seen = [];
                foreach ($s->lots()->where('deleteStatus', 1)->orderBy('id')->limit(12)->get() as $l) {
                    if (count($seen) >= 3) {
                        break;
                    }
                    $town = trim(implode(', ', array_filter([(string) $l->locTown, (string) $l->locProvince])));
                    $at = $l->pinLat && $l->pinLng ? [(float) $l->pinLat, (float) $l->pinLng] : (($g = $town !== '' ? $w->geocode($town) : null) ? [(float) $g['lat'], (float) $g['lon']] : null);
                    $key = $at ? sprintf('%.1f,%.1f', $at[0], $at[1]) : null;
                    if (! $key || isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $next = $w->nextDaysWords($at[0], $at[1], 7);
                    if ($next !== '') {
                        $lines[] = 'The next 7 days at ' . ($town !== '' ? $town : $l->lotName) . ': ' . $next . '.';
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $lines ? "THE SKY (forecast data the app keeps, for this question):\n" . implode("\n", $lines) . "\n\n" : '';
    }

    /** The lines put before a context's closing "Question: " when it has one, else after it. */
    public static function into(string $context, string $sky): string
    {
        if ($sky === '') {
            return $context;
        }
        $tail = 'Question: ';

        return str_ends_with($context, $tail) ? substr($context, 0, -strlen($tail)) . $sky . $tail : $context . $sky;
    }
}
