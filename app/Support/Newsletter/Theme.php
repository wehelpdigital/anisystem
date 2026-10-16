<?php

namespace App\Support\Newsletter;

use App\Models\AsSiteSetting;
use App\Support\EmailSkin;

/**
 * THE ONE DESIGN every newsletter email wears (2026-10-17, the owner: "one
 * template editor, so if you update the template feel, colors, everything,
 * it is applied to all future mails"). An email keeps only its words and
 * blocks; the header, the colors, the type, the buttons and the footer come
 * from here when it is drawn, so a change here reaches every email not yet
 * sent. Its defaults are anee.io's own mail (App\Support\EmailSkin).
 */
final class Theme
{
    public const FONTS = [
        'nunito' => ['Nunito Sans', "'Nunito Sans','Segoe UI',Helvetica,Arial,sans-serif"],
        'system' => ['System', "-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif"],
        'georgia' => ['Georgia', "Georgia,'Times New Roman',serif"],
        'verdana' => ['Verdana', 'Verdana,Geneva,sans-serif'],
    ];

    public const DEFAULTS = [
        'header' => EmailSkin::DARK,          // the header's color
        'headerTo' => '#44632a',               // where its gradient ends ('' for a flat header)
        'accent' => EmailSkin::GOLD,           // the stripe under the header, the buttons
        'accentTo' => EmailSkin::LEAF,         // where the stripe's gradient ends
        'buttonText' => EmailSkin::DARK,
        'link' => EmailSkin::GREEN,
        'text' => EmailSkin::INK,
        'muted' => EmailSkin::MUTED,
        'paper' => EmailSkin::PAPER,           // around the card
        'card' => '#ffffff',
        'line' => EmailSkin::LINE,
        'panel' => '#f3f8ec',                  // callouts and story cards
        'font' => 'nunito',
        'radius' => 18,
        'buttonRadius' => 999,
        'logo' => 'https://anee.io/images/logo-white.png',
        'logoWidth' => 143,
        'tagline' => 'your season, planned with Anee',
        'signature' => '',                     // under every email's last block, e.g. "John, kasama ni Anee"
        'footer' => 'You get these emails because you joined anee.io or asked Anee on our site.',
        'facebook' => '',
        'youtube' => '',
        'tiktok' => '',
        'website' => 'https://anee.io',
    ];

    public static function get(): array
    {
        $set = AsSiteSetting::get('newsletter.theme');
        $set = is_string($set) ? json_decode($set, true) : $set;

        return self::clean(array_merge(self::DEFAULTS, is_array($set) ? $set : []));
    }

    public static function save(array $in): array
    {
        $t = self::clean(array_merge(self::get(), $in));
        AsSiteSetting::put('newsletter.theme', json_encode($t, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $t;
    }

    public static function reset(): array
    {
        AsSiteSetting::put('newsletter.theme', json_encode(self::DEFAULTS, JSON_UNESCAPED_SLASHES));

        return self::DEFAULTS;
    }

    /** Known keys, colors as #rrggbb, sane numbers, https links. */
    public static function clean(array $t): array
    {
        $out = [];
        foreach (self::DEFAULTS as $k => $def) {
            $v = $t[$k] ?? $def;
            if (in_array($k, ['header', 'accent', 'accentTo', 'buttonText', 'link', 'text', 'muted', 'paper', 'card', 'line', 'panel'], true)) {
                $v = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? strtolower((string) $v) : $def;
            } elseif ($k === 'headerTo') {
                $v = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $v) ? strtolower((string) $v) : '';
            } elseif ($k === 'font') {
                $v = isset(self::FONTS[$v]) ? $v : $def;
            } elseif ($k === 'radius') {
                $v = max(0, min(32, (int) $v));
            } elseif ($k === 'buttonRadius') {
                $v = max(0, min(999, (int) $v));
            } elseif ($k === 'logoWidth') {
                $v = max(60, min(260, (int) $v));
            } elseif (in_array($k, ['logo', 'facebook', 'youtube', 'tiktok', 'website'], true)) {
                $v = trim((string) $v);
                $v = $v === '' || preg_match('#^https?://#i', $v) ? mb_substr($v, 0, 500) : $def;
            } else {
                $v = mb_substr(trim(strip_tags((string) $v)), 0, 400);
            }
            $out[$k] = $v;
        }

        return $out;
    }

    public static function fontStack(array $t): string
    {
        return self::FONTS[$t['font']][1] ?? self::FONTS['nunito'][1];
    }
}
