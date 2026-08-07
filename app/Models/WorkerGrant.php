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
        'inviteToken', 'scheduleAccess', 'communityAccess', 'status',
        'acceptedAt', 'deleteStatus',
    ];

    protected $casts = [
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

    public function canViewSchedules(): bool
    {
        return $this->status === self::STATUS_ACTIVE && in_array($this->scheduleAccess, ['view', 'edit'], true);
    }
}
