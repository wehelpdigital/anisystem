<?php

namespace App\Models;

class WorkerGrant extends BaseModel
{
    protected $table = 'as_worker_grants';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    /**
     * The modules a grant answers for, and the shape of each answer.
     *
     * 'level' modules take the schedule's own none/view/edit; 'open' modules
     * are had or not had. One list, so the form, the validator, the payload
     * and the gate cannot drift into knowing different modules — which is how
     * a permission ends up being a setting that does nothing.
     */
    public const MODULES = [
        'notes'     => ['column' => 'notesAccess',     'shape' => 'level'],
        'reports'   => ['column' => 'reportsAccess',   'shape' => 'level'],
        'inventory' => ['column' => 'inventoryAccess', 'shape' => 'level'],
        'maps'      => ['column' => 'mapsAccess',      'shape' => 'level'],
        'draw'      => ['column' => 'drawAccess',      'shape' => 'level'],
        'ai'        => ['column' => 'aiAccess',        'shape' => 'open'],
        'camera'    => ['column' => 'cameraAccess',    'shape' => 'open'],
        'video'     => ['column' => 'videoAccess',     'shape' => 'open'],
        // Speaking a note is its own errand: a farmer with their hands full
        // says a sentence, and that is not the same as being handed a camera.
        'voice'     => ['column' => 'voiceAccess',     'shape' => 'open'],
    ];

    protected $fillable = [
        'bossUserId', 'workerUserId', 'scheduleWorkerId', 'invitedEmail',
        'inviteToken', 'scheduleAccess', 'canAddNotes', 'communityAccess', 'status',
        'notesAccess', 'reportsAccess', 'inventoryAccess', 'mapsAccess', 'drawAccess', 'aiAccess',
        'cameraAccess', 'videoAccess', 'voiceAccess',
        'acceptedAt', 'deleteStatus',
    ];

    protected $casts = [
        'canAddNotes' => 'boolean',
        'communityAccess' => 'boolean',
        'aiAccess' => 'boolean',
        'cameraAccess' => 'boolean',
        'videoAccess' => 'boolean',
        'voiceAccess' => 'boolean',
        'acceptedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function boss()
    {
        return $this->belongsTo(User::class, 'bossUserId');
    }

    public function workerUser()
    {
        return $this->belongsTo(User::class, 'workerUserId');
    }

    public function scopeActive($q)
    {
        return $q->where('as_worker_grants.deleteStatus', 1);
    }

    public function canEditSchedules(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->scheduleAccess === 'edit';
    }

    /**
     * What this grant says about one module: 'none', 'view' or 'edit'.
     *
     * An open/shut module answers in the same words so a caller never has to
     * know which shape it is: shut is 'none', open is 'edit'. Everything is
     * bounded by seeing the farm at all — a community-only worker has no
     * modules, whatever the module columns happen to hold.
     */
    public function moduleAccess(string $key): string
    {
        // The board itself is not a module column: 'activities' answers with
        // the schedule access, so the gate, the hub doors and the tag picker
        // can ask about the board in the same words they ask about a module.
        if ($key === 'activities') {
            if ($this->status !== self::STATUS_ACTIVE || ! $this->canViewSchedules()) {
                return 'none';
            }

            return in_array($this->scheduleAccess, ['view', 'edit'], true) ? $this->scheduleAccess : 'none';
        }

        $spec = self::MODULES[$key] ?? null;
        if (! $spec || $this->status !== self::STATUS_ACTIVE || ! $this->canViewSchedules()) {
            return 'none';
        }

        if ($spec['shape'] === 'open') {
            return $this->{$spec['column']} ? 'edit' : 'none';
        }

        $level = (string) ($this->{$spec['column']} ?? 'none');

        return in_array($level, ['none', 'view', 'edit'], true) ? $level : 'none';
    }

    /** May they open this module at all? */
    public function mayUseModule(string $key): bool
    {
        return $this->moduleAccess($key) !== 'none';
    }

    /** May they add to it, not only look at it? */
    public function mayWriteModule(string $key): bool
    {
        return $this->moduleAccess($key) === 'edit';
    }

    /**
     * May this worker write the day's notes?
     *
     * Separate from editing the plan on purpose: recording what happened in
     * a field is not the same act as changing what is supposed to happen,
     * and most farms want the first without the second. The answer now lives
     * in notesAccess, where it sits beside the other modules; the old
     * canAddNotes column is kept in step for anything still reading it.
     */
    public function canAddNotes(): bool
    {
        return $this->mayWriteModule('notes');
    }

    public function canViewSchedules(): bool
    {
        return $this->status === self::STATUS_ACTIVE && in_array($this->scheduleAccess, ['view', 'edit'], true);
    }
}
