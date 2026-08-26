<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The repeat detector.
 *
 * Points made comments valuable, and the cheapest comment is the one you
 * already wrote — pasted again, or with a word nudged so it is not literally
 * identical. So before a comment or reply is kept, its words are held against
 * what the same member said in the last day: the same message, or one close
 * enough to it (85% similar), is refused out loud instead of banked.
 *
 * Only the words are judged — a member genuinely answering two people with
 * "Congrats!" is let through by the length floor, and pictures and clips are
 * not text and pass untouched.
 */
class CommunitySpam
{
    /** How alike two messages must be before the second one is a repeat. */
    private const SIMILAR_PCT = 85;

    /** Short cheers ("Nice!", "Salamat po") are not farmable enough to police. */
    private const MIN_LENGTH = 12;

    /** How far back, and how many of their own messages, to hold words against. */
    private const WINDOW_HOURS = 24;

    private const LOOKBACK = 20;

    /**
     * A refusal sentence when this member just said (almost) the same thing,
     * null when the words pass.
     */
    public static function repeats(int $userId, ?string $body, string $table, string $column = 'body'): ?string
    {
        $new = self::flatten($body);
        if ($userId <= 0 || mb_strlen($new) < self::MIN_LENGTH) {
            return null;
        }

        try {
            $recent = DB::table($table)
                ->where('userId', $userId)
                ->where('created_at', '>=', now()->subHours(self::WINDOW_HOURS))
                ->orderByDesc('id')
                ->limit(self::LOOKBACK)
                ->pluck($column);
        } catch (\Throwable $e) {
            return null;   // a guard that cannot look must not refuse
        }

        foreach ($recent as $old) {
            $old = self::flatten($old);
            if ($old === '' || abs(mb_strlen($old) - mb_strlen($new)) > mb_strlen($new)) {
                continue;
            }
            if ($old === $new) {
                return 'You just posted exactly this. Say something new — repeats do not count.';
            }
            similar_text($new, $old, $pct);
            if ($pct >= self::SIMILAR_PCT) {
                return 'That is almost word-for-word what you just posted. Say something new — repeats do not count.';
            }
        }

        return null;
    }

    /** One comparable line: lowercased, whitespace collapsed. */
    private static function flatten(?string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $text)));
    }
}
