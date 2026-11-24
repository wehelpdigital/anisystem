<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Level Up Farmers, Anee's Facebook group (2026-11-24, the owner: the bottom bar's seat is "Group", which asks
 * farmers to join www.facebook.com/groups/levelupfarmers/, "make it a persuasive page", and "inside anee app, have a
 * random popup as well asking them to join if they havent and a button to say do not show this again").
 *
 * Nobody can see from here who joined on Facebook, so a tap on the join button counts as joined. Either answer
 * (joined, or do not show this again) is kept on the account, so the popup stops on every phone, as a row in
 * as_tutorial_dismissals (the table that already holds per person "never again" answers).
 */
final class GroupInvite
{
    public const NAME = 'Level Up Farmers';

    public const URL = 'https://www.facebook.com/groups/levelupfarmers/';

    public const ANSWERS = ['joined', 'never'];

    private const KEY = 'fb-group:';

    private static array $memo = [];

    /** joined, never, or null when the person has not answered. */
    public static function answer(?int $userId = null): ?string
    {
        $userId ??= (int) Auth::id();
        if ($userId <= 0) {
            return null;
        }
        if (! array_key_exists($userId, self::$memo)) {
            try {
                $keys = DB::table('as_tutorial_dismissals')->where('userId', $userId)
                    ->whereIn('tutorialKey', array_map(fn ($a) => self::KEY . $a, self::ANSWERS))->pluck('tutorialKey')->all();
            } catch (\Throwable $e) {
                $keys = [];
            }
            self::$memo[$userId] = in_array(self::KEY . 'joined', $keys, true) ? 'joined' : (in_array(self::KEY . 'never', $keys, true) ? 'never' : null);
        }

        return self::$memo[$userId];
    }

    /** Whether the popup may ask this person (the page itself decides when, at random). */
    public static function shouldAsk(): bool
    {
        return Auth::check() && ! session()->has('admin_impersonator') && self::answer() === null;
    }

    public static function record(int $userId, string $answer): void
    {
        if (! in_array($answer, self::ANSWERS, true)) {
            return;
        }
        DB::table('as_tutorial_dismissals')->insertOrIgnore([
            'userId' => $userId, 'tutorialKey' => self::KEY . $answer, 'created_at' => now(), 'updated_at' => now(),
        ]);
        self::$memo[$userId] = $answer === 'joined' || self::answer($userId) === 'joined' ? 'joined' : $answer;
    }
}
