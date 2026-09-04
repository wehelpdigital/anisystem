<?php

namespace App\Models;

/**
 * Client-facing cropping schedule (shared `as_cropping_schedules` table with
 * the mother btc-check app). AniSystem clients own rows via `anisystemUserId`;
 * the legacy `usersId` owner column is kept for mother-app compatibility.
 *
 * Generation/calendar/report relations from the mother app are intentionally
 * NOT ported — anee.io exposes only the planning surface.
 */
class AsCroppingSchedule extends BaseModel
{
    protected $table = 'as_cropping_schedules';

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
        'isPublic',
        'publishedAt',
        'publicSummary',
        'publicRegion',
            'notifyWorkersDaily',
        'notifyOwnerDaily',
        'notifyHour',
        'notifyLastSentDate',
];

    protected $casts = [
        'defaultStaggerDays' => 'integer',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
        'isPublic' => 'boolean',
        'publishedAt' => 'datetime',
            'notifyWorkersDaily' => 'boolean',
        'notifyOwnerDaily' => 'boolean',
        'notifyHour' => 'integer',
        'notifyLastSentDate' => 'date:Y-m-d',
];

    /**
     * Lifecycle status. 'setup' = still being built, 'completed' = closed and
     * locked, 'archived' = put away on purpose. Closing and archiving are two
     * different acts now: a closed season stays on the shelf — locked, but in
     * sight, still generating its reports — and only the explicit "To the
     * Archives" button moves it out of the lists. (It used to move on close,
     * and the first thing that taught anyone was a customer hunting for the
     * season whose reports they had just come to read.)
     */
    public const STATUS_SETUP = 'setup';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    /** A closed or archived schedule is locked — read-only until reopened. */
    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_ARCHIVED], true);
    }

    /**
     * Everything that is not archived: the seasons being farmed AND the
     * closed ones the owner still wants in sight.
     */
    public function scopeOnShelf($q)
    {
        return $q->where($this->getTable() . '.status', '!=', self::STATUS_ARCHIVED);
    }

    /** The seasons put away on purpose. */
    public function scopeArchived($q)
    {
        return $q->where($this->getTable() . '.status', self::STATUS_ARCHIVED);
    }

    /**
     * Mother-app owner scoping (usersId). Kept for compatibility.
     */
    public function scopeForUser($q, $userId)
    {
        return $q->where('usersId', $userId);
    }

    /**
     * anee.io client scoping — clients only ever see their own schedules.
     */
    /**
     * The seasons this request may see of one client's farm.
     *
     * The narrowing lives here rather than in the two dozen callers because
     * it is the same question every one of them is asking, and because the
     * ones that resolve a single season by id are the authorisation gate: a
     * worker who is not on a season should not be able to open it by typing
     * its number, and that is the same sentence as not listing it.
     */
    public function scopeForClient($q, $userId)
    {
        $q->where('anisystemUserId', $userId);

        $only = \App\Support\WorkerContext::visibleScheduleIdsFor((int) $userId);
        if ($only !== null) {
            // [] is an answer: on none of them, so nothing matches.
            $q->whereIn('as_cropping_schedules.id', $only ?: [-1]);
        }

        return $q;
    }

    /** The anee.io client who owns this schedule. */
    public function owner()
    {
        return $this->belongsTo(User::class, 'anisystemUserId');
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

    public function docTags()
    {
        return $this->hasMany(AsScheduleDocTag::class, 'croppingScheduleId')
            ->where('as_schedule_doc_tags.deleteStatus', 1)
            ->orderBy('sortOrder')->orderBy('name');
    }

    public function docEntries()
    {
        return $this->hasMany(AsScheduleDocEntry::class, 'croppingScheduleId')
            ->where('as_schedule_doc_entries.deleteStatus', 1)
            ->orderBy('sortOrder')->orderBy('id');
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

    /**
     * The other half of a day's money: what it brought in. Scoped to the
     * active version the same way its twin is.
     */
    public function dayIncomes()
    {
        return $this->hasMany(AsScheduleDayIncome::class, 'croppingScheduleId')
            ->where('as_schedule_day_incomes.deleteStatus', 1)
            ->whereIn('as_schedule_day_incomes.versionId', function ($sub) {
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_day_incomes.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('incomeDate', 'asc')
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc');
    }

    /**
     * Ad-hoc extra expenses logged against a date (fuel, rentals, snacks...).
     * Scoped to the active version via the same correlated-subquery trick as
     * dateNotes()/progressMarkers() so each fork carries its own expenses.
     */
    public function dayExpenses()
    {
        return $this->hasMany(AsScheduleDayExpense::class, 'croppingScheduleId')
            ->where('as_schedule_day_expenses.deleteStatus', 1)
            ->whereIn('as_schedule_day_expenses.versionId', function ($sub) {
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_day_expenses.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('expenseDate', 'asc')
            ->orderBy('sortOrder', 'asc')
            ->orderBy('id', 'asc');
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
