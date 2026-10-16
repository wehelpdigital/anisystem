<?php

namespace App\Support\Newsletter;

use App\Models\AsSiteSetting;

/**
 * The newsletter's settings, on the shared settings shelf (as_site_settings,
 * newsletter.*), written by either app's module page:
 *   newsletter.enabled     the switch: off, the day is planned and shown but no campaign is made
 *   newsletter.send_time   when the day's email goes, Manila time (HH:MM)
 *   newsletter.from_name   the sender's name
 *   newsletter.from_email  the sender's address (one Acumbamail accepts)
 *   newsletter.theme       the one design every email wears (Theme)
 *   newsletter.lists       segment => Acumbamail list id, over Seg::DEFAULT_LISTS
 */
final class NlSettings
{
    public const DEFAULT_SEND_TIME = '08:00';

    public static function enabled(): bool
    {
        return AsSiteSetting::yes('newsletter.enabled');
    }

    public static function sendTime(): string
    {
        $t = (string) AsSiteSetting::get('newsletter.send_time', self::DEFAULT_SEND_TIME);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t) ? $t : self::DEFAULT_SEND_TIME;
    }

    public static function fromName(): string
    {
        return trim((string) AsSiteSetting::get('newsletter.from_name', '')) ?: 'John of anee.io';
    }

    public static function fromEmail(): string
    {
        $e = trim((string) AsSiteSetting::get('newsletter.from_email', ''));

        return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : 'anee@anee.io';
    }

    /** Where test sends go when no address is typed. */
    public static function testTo(): string
    {
        $e = trim((string) AsSiteSetting::get('newsletter.test_to', ''));

        return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : '';
    }

    public static function all(): array
    {
        return [
            'enabled' => self::enabled(),
            'sendTime' => self::sendTime(),
            'fromName' => self::fromName(),
            'fromEmail' => self::fromEmail(),
            'testTo' => self::testTo(),
            'lists' => Seg::lists(),
            'theme' => Theme::get(),
        ];
    }

    public static function save(array $in): void
    {
        if (array_key_exists('enabled', $in)) {
            AsSiteSetting::put('newsletter.enabled', $in['enabled'] ? '1' : '0');
        }
        if (isset($in['sendTime']) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $in['sendTime'])) {
            AsSiteSetting::put('newsletter.send_time', (string) $in['sendTime']);
        }
        if (isset($in['fromName'])) {
            AsSiteSetting::put('newsletter.from_name', mb_substr(trim(strip_tags((string) $in['fromName'])), 0, 80));
        }
        if (isset($in['fromEmail']) && filter_var(trim((string) $in['fromEmail']), FILTER_VALIDATE_EMAIL)) {
            AsSiteSetting::put('newsletter.from_email', trim((string) $in['fromEmail']));
        }
        if (array_key_exists('testTo', $in)) {
            $e = trim((string) $in['testTo']);
            AsSiteSetting::put('newsletter.test_to', filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : '');
        }
        if (isset($in['lists']) && is_array($in['lists'])) {
            $lists = [];
            foreach (Seg::DEFAULT_LISTS as $k => $_) {
                if (isset($in['lists'][$k]) && (int) $in['lists'][$k] > 0) {
                    $lists[$k] = (int) $in['lists'][$k];
                }
            }
            AsSiteSetting::put('newsletter.lists', json_encode($lists));
        }
    }
}
