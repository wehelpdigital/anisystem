<?php

namespace App\Models;

class WorkerGrant extends BaseModel
{
    protected $table = 'as_worker_grants';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'bossUserId', 'workerUserId', 'scheduleWorkerId', 'invitedEmail',
        'inviteToken', 'scheduleAccess', 'canAddNotes', 'communityAccess', 'status',
        'acceptedAt', 'deleteStatus',
    ];

    protected $casts = [
        'canAddNotes' => 'boolean',
        'communityAccess' => 'boolean',
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
     * May this worker write the day's notes?
     *
     * Separate from editing the plan on purpose: recording what happened in
     * a field is not the same act as changing what is supposed to happen,
     * and most farms want the first without the second. Anyone who can edit
     * can obviously also write a note.
     */
    public function canAddNotes(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && ($this->canEditSchedules() || ((bool) $this->canAddNotes && $this->canViewSchedules()));
    }

    public function canViewSchedules(): bool
    {
        return $this->status === self::STATUS_ACTIVE && in_array($this->scheduleAccess, ['view', 'edit'], true);
    }
}
