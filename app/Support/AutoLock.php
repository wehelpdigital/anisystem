<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Anee Auto is not open yet (2026-11-05, the owner: "if user is not admin, lock anee auto and say we are still building
 * it"). Admins keep it whole, to go on building it. Everyone else meets the words below at every door: the routes
 * (AutoIsOpen), the start button (auto.partials.building-door) and the public pages (public.partials.auto-building-note).
 * The morning work stops too, so a locked season neither spends credits nor mails anyone.
 *
 * To open it to everybody, set ADMINS_ONLY to false. Nothing else needs to change.
 */
final class AutoLock
{
    public const ADMINS_ONLY = true;

    public const TITLE = 'We are still building Anee Auto';

    public const SAY = 'We are still building Anee Auto. It opens here as soon as it is ready.';

    /** A member looked at through impersonation counts as that member: the admin sees what the member sees. */
    public static function open(): bool
    {
        if (! self::ADMINS_ONLY) {
            return true;
        }
        $user = auth()->user();

        return $user && $user->isSuperAdmin();
    }

    /** Only the seasons whose owner may use Anee Auto, for the jobs that run with nobody signed in. */
    public static function onlyOpen(Builder $seasons): Builder
    {
        if (! self::ADMINS_ONLY) {
            return $seasons;
        }

        return $seasons->whereIn('userId', User::query()
            ->where(fn ($q) => $q->whereNotNull('adminUserId')->orWhere('panelAdmin', 1))->select('id'));
    }
}
