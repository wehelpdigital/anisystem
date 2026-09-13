<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * A challenge that has to be READ, for the one admin act that cannot be undone
 * from the panel.
 *
 * Deleting a client is not like suspending one: there is no button in here
 * that puts them back. A confirm dialog is one more click, and a click is
 * exactly what a tired person at the end of a support shift gives you without
 * looking. So the panel asks for something a click cannot produce — five
 * characters that exist only as shapes on a picture, tied to that one client
 * and to that one session, good once and for two minutes.
 *
 * Drawn here rather than fetched from Google: this panel has to keep working
 * on a laptop in a field office with a bad line, and a gate that fails closed
 * when a third party is unreachable is a gate that locks the admin out of
 * their own tool. Nothing to configure, no key to rotate, no request to make.
 *
 * It is not trying to stop a determined attacker — anyone who can reach this
 * endpoint is already an authenticated admin. It is trying to stop a mis-tap,
 * a stale tab and a double-click, and for that it only has to be impossible
 * to satisfy without having looked.
 */
class AdminCaptcha
{
    /** Two minutes: long enough to read and type, short enough to go stale. */
    private const TTL = 120;

    /** No O/0, no I/1/l. A captcha nobody can read is a captcha nobody passes. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LEN = 5;

    private static function key(int $userId): string
    {
        return 'admin.captcha.' . $userId;
    }

    /**
     * Mint a challenge for one client and remember it on this session.
     *
     * @return string the SVG to show
     */
    public static function issue(int $userId): string
    {
        $code = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < self::LEN; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        Session::put(self::key($userId), [
            'code' => $code,
            'until' => time() + self::TTL,
        ]);

        return self::svg($code);
    }

    /**
     * Check what was typed, and spend the challenge either way.
     *
     * Spent on a WRONG answer as well as a right one: a challenge that
     * survives being guessed at is a challenge that can be guessed at, and
     * five characters fall to a loop in an afternoon. One look, one try.
     */
    public static function check(int $userId, string $typed): bool
    {
        $held = Session::pull(self::key($userId));

        if (! is_array($held) || ! isset($held['code'], $held['until'])) {
            return false;
        }
        if (time() > (int) $held['until']) {
            return false;
        }

        return hash_equals(
            strtoupper((string) $held['code']),
            strtoupper(trim($typed))
        );
    }

    /** Forget a challenge without answering it — closing the card, say. */
    public static function forget(int $userId): void
    {
        Session::forget(self::key($userId));
    }

    /**
     * The code as a picture.
     *
     * Each character gets its own rotation, baseline and weight, and two
     * scribbles cross the whole thing, so the glyphs are shapes rather than
     * text: there is nothing here to select, copy, or read out of the DOM with
     * a script. Deliberately still legible — the rotations stay inside twenty
     * degrees and the scribbles are thin and pale.
     */
    private static function svg(string $code): string
    {
        $w = 168;
        $h = 58;
        $out = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $w . ' ' . $h . '" '
            . 'width="' . $w . '" height="' . $h . '" role="img" aria-label="Type the characters shown">'
            . '<rect width="' . $w . '" height="' . $h . '" rx="10" fill="#fffbeb" stroke="#fcd34d"/>';

        // The scribbles go under the glyphs, so they never break a stroke.
        for ($i = 0; $i < 2; $i++) {
            $y1 = random_int(12, 46);
            $y2 = random_int(12, 46);
            $out .= '<path d="M2 ' . $y1 . ' Q ' . ($w / 2) . ' ' . random_int(0, $h)
                . ' ' . ($w - 2) . ' ' . $y2 . '" fill="none" stroke="#f59e0b" stroke-opacity=".45" stroke-width="1.4"/>';
        }

        $step = ($w - 28) / self::LEN;
        for ($i = 0; $i < self::LEN; $i++) {
            $x = 18 + ($i * $step);
            $y = random_int(36, 42);
            $rot = random_int(-17, 17);
            $size = random_int(25, 30);
            $out .= '<text x="' . round($x, 1) . '" y="' . $y . '" '
                . 'transform="rotate(' . $rot . ' ' . round($x, 1) . ' ' . $y . ')" '
                . 'font-family="Georgia, serif" font-size="' . $size . '" font-weight="700" '
                . 'fill="#78350f">' . htmlspecialchars($code[$i], ENT_QUOTES, 'UTF-8') . '</text>';
        }

        return $out . '</svg>';
    }
}
