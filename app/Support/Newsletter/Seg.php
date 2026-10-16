<?php

namespace App\Support\Newsletter;

use App\Models\AsSiteSetting;

/**
 * THE SEGMENTS (2026-10-17, the owner): the eight Acumbamail lists of
 * anee.io, by key. Five of them split everyone into one group each, by
 * where they stand with anee.io (TIERS): a person is on exactly one. Two
 * cross them (FLAGS): anyone who paid anything, and anyone who bought
 * credits. "all" holds everyone.
 *
 * Why it matters: Acumbamail sends a campaign to whole lists, and a person
 * on two lists that get two campaigns the same day gets two emails. So the
 * day's emails are dealt to the five TIERS, which never overlap; an email
 * for a FLAG list takes the day of every member tier it crosses (Planner).
 */
final class Seg
{
    public const ALL = 'all';
    public const NONUSERS = 'nonusers';
    public const FREE = 'free';
    public const PLUS = 'libreplus';
    public const SOLO = 'solo';
    public const OWNER = 'owner';
    public const PAID = 'paid';
    public const CREDITS = 'credits';

    /** Everyone, split once: each person sits on exactly one of these. */
    public const TIERS = [self::NONUSERS, self::FREE, self::PLUS, self::SOLO, self::OWNER];

    /** The members among them (a flag list can only hold members). */
    public const MEMBER_TIERS = [self::FREE, self::PLUS, self::SOLO, self::OWNER];

    /** The lists that cross the tiers. */
    public const FLAGS = [self::PAID, self::CREDITS];

    /** key => [name, the line under it] */
    public const LABELS = [
        self::ALL => ['Everyone', 'Every subscriber, members and not'],
        self::NONUSERS => ['Not members yet', 'Asked or tried Anee on the public site, no account'],
        self::FREE => ['Libre', 'Members on the free plan'],
        self::PLUS => ['Libre + Anee', 'Members on Libre + Anee'],
        self::SOLO => ['Solo Farmer', 'Members on Solo Farmer'],
        self::OWNER => ['Farm Owner', 'Members on Farm Owner'],
        self::PAID => ['Paid anything', 'Paid for a plan, a tool pack or credits, ever'],
        self::CREDITS => ['Bought credits', 'Bought AI credits, ever'],
    ];

    /** The owner's lists at Acumbamail (2026-10-17); newsletter.lists on the settings shelf overrides any of them. */
    public const DEFAULT_LISTS = [
        self::ALL => 1548022, self::NONUSERS => 1548023, self::FREE => 1548024, self::PAID => 1548025,
        self::PLUS => 1548026, self::SOLO => 1548027, self::OWNER => 1548028, self::CREDITS => 1548029,
    ];

    /** @return array<string, int> segment => Acumbamail list id */
    public static function lists(): array
    {
        $set = AsSiteSetting::get('newsletter.lists');
        $set = is_string($set) ? json_decode($set, true) : $set;
        $out = self::DEFAULT_LISTS;
        foreach ((array) $set as $k => $v) {
            if (isset($out[$k]) && (int) $v > 0) {
                $out[$k] = (int) $v;
            }
        }

        return $out;
    }

    public static function listId(string $seg): int
    {
        return (int) (self::lists()[$seg] ?? 0);
    }

    /** The tier list for a plan key (App\Support\Tier): the admin's own accounts count as owners. */
    public static function tierCell(string $tier): string
    {
        return match ($tier) {
            'libreAnee' => self::PLUS,
            'solo' => self::SOLO,
            'owner', 'admin' => self::OWNER,
            default => self::FREE,
        };
    }

    /** Known keys only, once each; "all" stands for every tier, so the flags beside it say nothing more. */
    public static function normalize(?array $aud): array
    {
        $aud = array_values(array_unique(array_filter((array) $aud, fn ($k) => is_string($k) && isset(self::LABELS[$k]))));
        if (in_array(self::ALL, $aud, true) || ! array_diff(self::TIERS, $aud)) {
            return [self::ALL];
        }

        return $aud ?: [self::ALL];
    }

    /**
     * An audience as the planner reads it.
     *
     * @return array{tiers: list<string>, flags: list<string>}
     */
    public static function expand(?array $aud): array
    {
        $aud = self::normalize($aud);
        if ($aud === [self::ALL]) {
            return ['tiers' => self::TIERS, 'flags' => []];
        }
        $flags = array_values(array_intersect(self::FLAGS, $aud));
        // Everyone who bought credits paid: "paid" covers "credits".
        if (in_array(self::PAID, $flags, true)) {
            $flags = [self::PAID];
        }

        return ['tiers' => array_values(array_intersect(self::TIERS, $aud)), 'flags' => $flags];
    }

    /** "Libre, Solo Farmer" for a list of keys. */
    public static function words(array $keys): string
    {
        return implode(', ', array_map(fn ($k) => self::LABELS[$k][0] ?? $k, $keys));
    }
}
