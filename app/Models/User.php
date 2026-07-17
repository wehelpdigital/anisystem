<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'anisystem_users';

    protected $fillable = [
        'firstName',
        'lastName',
        'phone',
        'email',
        'password',
        'clientId',
        'status',
        'deleteStatus',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'deleteStatus' => 'integer',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function freshTimestamp()
    {
        return Carbon::now('Asia/Manila');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::instance($date)->timezone('Asia/Manila')->format('Y-m-d H:i:s');
    }

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(mb_substr((string) $this->firstName, 0, 1).mb_substr((string) $this->lastName, 0, 1));
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'userId')
            ->where('deleteStatus', 1)
            ->orderByDesc('id');
    }

    /**
     * The subscription that governs access right now: the newest
     * non-deleted subscription row for this user.
     */
    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->first();
    }

    /**
     * The active (verified, unexpired, not suspended/cancelled) subscription, if any.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expiresAt', '>', Carbon::now('Asia/Manila'))
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    public function schedules()
    {
        return $this->hasMany(AsCroppingSchedule::class, 'anisystemUserId')->where('deleteStatus', 1);
    }

    /**
     * Password reset email goes through the mother system's mail settings and
     * templates (group AniSystem, template key password_reset).
     */
    public function sendPasswordResetNotification($token)
    {
        app(\App\Services\MailService::class)->sendTemplateToUser('password_reset', $this, [
            'resetUrl' => route('password.reset', ['token' => $token, 'email' => $this->email]),
        ]);
    }
}
