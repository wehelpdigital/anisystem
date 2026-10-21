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
        return [
            'id' => (int) $grant->id,
            'status' => (string) $grant->status,
            'communityAccess' => (bool) $grant->communityAccess,
            'workerUserId' => $grant->workerUserId ? (int) $grant->workerUserId : null,
            // The home screen's tools switched off (WorkerTools keys); none is every tool.
            'toolsOff' => array_values((array) ($grant->toolsOff ?? [])),
            // Where they may work (2026-10-22): the "all cropping schedules" setting first, then one per season.
            'access' => self::access($grant),
            'reach' => $grant->reachSay(),
        ];
    }

    /** The login's access settings, each with its scope's name and its rights, for the panels. */
    public static function access(WorkerGrant $grant): array
    {
        $rows = $grant->accessRows();
        $titles = \App\Models\AsCroppingSchedule::whereIn('id', $rows->pluck('croppingScheduleId')->filter()->all() ?: [-1])
            ->get(['id', 'title', 'status', 'deleteStatus'])->keyBy('id');

        return $rows->sortBy(fn ($r) => $r->isAll() ? '' : mb_strtolower((string) optional($titles->get($r->croppingScheduleId))->title))
            ->map(function ($r) use ($titles) {
                $season = $r->isAll() ? null : $titles->get($r->croppingScheduleId);

                return ['id' => (int) $r->id, 'all' => $r->isAll(), 'scheduleId' => $r->croppingScheduleId,
                    'title' => $r->isAll() ? 'All cropping schedules' : ($season ? (string) $season->title : 'A season no longer here'),
                    'gone' => ! $r->isAll() && (! $season || (int) $season->deleteStatus !== 1),
                    'summary' => $r->summary()] + $r->rights();
            })->values()->all();
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
