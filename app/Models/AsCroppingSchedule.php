<?php

namespace App\Models;

/**
 * Client-facing cropping schedule (shared `as_cropping_schedules` table with
 * the mother btc-check app). AniSystem clients own rows via `anisystemUserId`;
 * the legacy `usersId` owner column is kept for mother-app compatibility.
 *
 * Generation/calendar/report relations from the mother app are intentionally
 * NOT ported — AniSystem exposes only the planning surface.
 */
class AsCroppingSchedule extends BaseModel
{
    protected $table = 'as_cropping_schedules';

    /** Common crop types (label => label) for the crop select. */
    public const CROP_TYPES = [
        'Rice (Palay)',
        'Corn (Mais)',
        'Vegetables (Gulay)',
        'Fruits',
        'Root Crops',
        'Sugarcane',
        'Other',
    ];

    protected $fillable = [
        'usersId',
        'anisystemUserId',
        'title',
        'description',
        'cropType',
        'cropVariety',
        'dayType',
        'defaultStaggerDays',
        'status',
        'isActive',
        'deleteStatus',
    ];

    protected $casts = [
        'defaultStaggerDays' => 'integer',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    /**
     * Mother-app owner scoping (usersId). Kept for compatibility.
     */
    public function scopeForUser($q, $userId)
    {
        return $q->where('usersId', $userId);
    }

    /**
     * AniSystem client scoping — clients only ever see their own schedules.
     */
    public function scopeForClient($q, $userId)
    {
        return $q->where('anisystemUserId', $userId);
    }

    public function lots()
    {
        return $this->hasMany(AsScheduleLot::class, 'croppingScheduleId')->where('as_schedule_lots.deleteStatus', 1);
    }

    public function workers()
    {
        return $this->hasMany(AsScheduleWorker::class, 'croppingScheduleId')
            ->where('as_schedule_workers.deleteStatus', 1)
            ->orderBy('priority', 'asc');
    }

    public function protocol()
    {
        return $this->hasOne(AsScheduleProtocol::class, 'croppingScheduleId')->where('as_schedule_protocols.deleteStatus', 1);
    }

    public function materials()
    {
        return $this->hasMany(AsScheduleMaterial::class, 'croppingScheduleId')->where('as_schedule_materials.deleteStatus', 1);
    }

    public function services()
    {
        return $this->hasMany(AsScheduleService::class, 'croppingScheduleId')->where('as_schedule_services.deleteStatus', 1);
    }

    public function versions()
    {
        return $this->hasMany(AsScheduleActivityVersion::class, 'croppingScheduleId')
            ->where('as_schedule_activity_versions.deleteStatus', 1)
            ->orderBy('versionOrder', 'asc')
            ->orderBy('id', 'asc');
    }

    public function activeVersion()
    {
        return $this->hasOne(AsScheduleActivityVersion::class, 'croppingScheduleId')
            ->where('as_schedule_activity_versions.deleteStatus', 1)
            ->where('as_schedule_activity_versions.isActive', 1);
    }

    /**
     * Activities are scoped to the schedule's currently-active version. This
     * makes every consumer ($schedule->activities) automatically reflect the
     * selected version — worker presentation, export, labor summary all
     * inherit the filter for free.
     */
    public function activities()
    {
        return $this->hasMany(AsScheduleActivity::class, 'croppingScheduleId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 0)
            ->whereIn('as_schedule_activities.versionId', function ($sub) {
                // Correlate against the activity row's own croppingScheduleId
                // so this works whether the relation is loaded as a property
                // (auto-join to parent) or invoked as a method (no parent in
                // scope). Activity rows always carry croppingScheduleId.
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_activities.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('targetDate', 'asc');
    }

    public function drafts()
    {
        return $this->hasMany(AsScheduleActivity::class, 'croppingScheduleId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 1)
            ->whereIn('as_schedule_activities.versionId', function ($sub) {
                // Correlate against the activity row's own croppingScheduleId
                // so this works whether the relation is loaded as a property
                // (auto-join to parent) or invoked as a method (no parent in
                // scope). Activity rows always carry croppingScheduleId.
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_activities.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Per-date commentary attached to the activity timeline. Scoped to the
     * schedule's active version using the same correlated-subquery trick as
     * activities() so the export view, the worker presentation, and the
     * setup screen all see the same notes for the currently-selected branch.
     */
    public function dateNotes()
    {
        return $this->hasMany(AsScheduleDateNote::class, 'croppingScheduleId')
            ->where('as_schedule_date_notes.deleteStatus', 1)
            ->whereIn('as_schedule_date_notes.versionId', function ($sub) {
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_date_notes.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('noteDate', 'asc');
    }

    /**
     * Progress markers / bookmarks the user drops into the activities
     * timeline ("where I left off yesterday"). Scoped to the active version
     * via the same correlated-subquery trick used by dateNotes() so each
     * fork carries its own markers.
     */
    public function progressMarkers()
    {
        return $this->hasMany(AsScheduleProgressMarker::class, 'croppingScheduleId')
            ->where('as_schedule_progress_markers.deleteStatus', 1)
            ->whereIn('as_schedule_progress_markers.versionId', function ($sub) {
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_progress_markers.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('markerDate', 'asc');
    }

    public function irrigations()
    {
        return $this->hasMany(AsScheduleIrrigation::class, 'croppingScheduleId')
            ->where('as_schedule_irrigations.deleteStatus', 1)
            // Manual drag-drop order wins; fall back to startDay (then id)
            // so rows without a manual order still cluster by their range.
            ->orderBy('sortOrder', 'asc')
            ->orderBy('startDay', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Reference images / files uploaded for the whole schedule. Each
     * carries a description and renders into the worker presentation
     * and the export schedule.
     */
    public function attachments()
    {
        return $this->hasMany(AsScheduleAttachment::class, 'croppingScheduleId')
            ->where('as_schedule_attachments.deleteStatus', 1)
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Season-long reminders — printed prominently on the presentation
     * and export so workers see them every time they pick up the doc.
     */
    public function criticalRules()
    {
        return $this->hasMany(AsScheduleCriticalRule::class, 'croppingScheduleId')
            ->where('as_schedule_critical_rules.deleteStatus', 1)
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc');
    }

    public function defaultGroupings()
    {
        return $this->hasMany(AsScheduleDefaultGrouping::class, 'croppingScheduleId')
            ->where('as_schedule_default_groupings.deleteStatus', 1)
            ->orderBy('groupOrder');
    }

    /**
     * Return a list of human-readable issues on schedule completeness.
     * Empty array means the schedule is fully set up.
     *
     * Uses *_count attributes when present (withCount()), otherwise queries.
     */
    public function getReadinessIssues(): array
    {
        $lotsCount       = $this->lots_count       ?? $this->lots()->count();
        $workersCount    = $this->workers_count    ?? $this->workers()->count();
        $activitiesCount = $this->activities_count ?? $this->activities()->count();

        $issues = [];
        if ($lotsCount === 0)       $issues[] = 'Add at least one lot';
        if ($workersCount === 0)    $issues[] = 'Add at least one worker';
        if ($activitiesCount === 0) $issues[] = 'Add at least one activity';
        return $issues;
    }

    public function isReadyToGenerate(): bool
    {
        return count($this->getReadinessIssues()) === 0;
    }
}
