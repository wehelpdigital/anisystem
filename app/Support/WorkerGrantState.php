<?php

namespace App\Support;

use App\Models\WorkerGrant;

/**
 * One shape for "what does this grant say", used by every screen that shows
 * or sets it.
 *
 * The Workers page was handed four fields; the sheet that edits it read six.
 * The two it never got — the note permission among them — were drawn as their
 * defaults, so a right an owner had given appeared not to exist, and saving
 * the sheet wrote that appearance back. A payload and a form that disagree is
 * a permission that quietly resets itself.
 */
class WorkerGrantState
{
    public static function of(WorkerGrant $grant): array
    {
        $state = [
            'id' => (int) $grant->id,
            'status' => (string) $grant->status,
            'scheduleAccess' => (string) $grant->scheduleAccess,
            'communityAccess' => (bool) $grant->communityAccess,
            'workerUserId' => $grant->workerUserId ? (int) $grant->workerUserId : null,
        ];

        foreach (WorkerGrant::MODULES as $key => $spec) {
            $state[$spec['column']] = $spec['shape'] === 'open'
                ? (bool) $grant->{$spec['column']}
                : (string) ($grant->{$spec['column']} ?? 'none');
        }

        return $state;
    }

    /**
     * The state plus who it is about: the season card, the roster person,
     * the address and whether it opens the whole farm. What the login
     * controls on both worker pages are handed (Global Workers since
     * 2026-10-18), so a grant they paint is a grant they can act on.
     */
    public static function withLinks(WorkerGrant $grant): array
    {
        return self::of($grant) + [
            'scheduleWorkerId' => $grant->scheduleWorkerId ? (int) $grant->scheduleWorkerId : null,
            'globalWorkerId' => $grant->globalWorkerId ? (int) $grant->globalWorkerId : null,
            'email' => (string) $grant->invitedEmail,
            'farmWide' => $grant->isFarmWide(),
        ];
    }
}
