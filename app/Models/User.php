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
        'city',
        'province',
        'bio',
        'headline',
        'profession',
        'yearsFarming',
        'farmSize',
        'cropsGrown',
        'farmingMethod',
        'statusBubble',
        'allowMessages',
        'avatarPath',
        'coverPath',
        'coverPos',
        'adminUserId',
        'lastSeenAt',
        'deleteStatus',
    ];

    /** A member is "online" if seen within this many minutes. */
    public const ONLINE_WINDOW_MINUTES = 5;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'deleteStatus' => 'integer',
            'adminUserId' => 'integer',
            'lastSeenAt' => 'datetime',
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    /** Whether this member was active within the online window. */
    public function isOnline(): bool
    {
        return $this->lastSeenAt !== null
            && $this->lastSeenAt->gt(now()->subMinutes(self::ONLINE_WINDOW_MINUTES));
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

    /** "Town, Province" — or whichever half is filled. Empty string if neither. */
    public function getLocationAttribute(): string
    {
        return collect([$this->city, $this->province])
            ->filter(fn ($p) => filled($p))
            ->implode(', ');
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

    /**
     * A mother-site super admin bridged into AniSystem (see SuperAdminBridge).
     * Such members get full access without an AniSystem subscription.
     */
    public function isSuperAdmin(): bool
    {
        return ! empty($this->adminUserId);
    }

    /**
     * Which subscription tier this member is on: basic | boss | lifetime | none.
     * Derived from the active plan's key/name (see config/tiers.php). Any active
     * paid plan that matches no tier keyword counts as 'boss' so existing
     * subscribers are never silently downgraded.
     */
    public function planTier(): string
    {
        // Mother-site super admins get the top tier so every feature is unlocked.
        if ($this->isSuperAdmin()) {
            return 'lifetime';
        }

        $sub = $this->activeSubscription()
            ?? $this->subscriptions()->where('status', 'active')->first();
        if (! $sub) {
            return 'none';
        }

        $hay = mb_strtolower(($sub->planKey ?? '') . ' ' . ($sub->planName ?? ''));
        foreach (config('tiers', []) as $tier => $cfg) {
            foreach (($cfg['match'] ?? []) as $needle) {
                if ($needle !== '' && str_contains($hay, $needle)) {
                    return $tier;
                }
            }
        }

        return 'boss';
    }

    /** The config block for this member's current tier (falls back to boss). */
    public function tierConfig(): array
    {
        return config('tiers.' . $this->planTier(), config('tiers.boss', []));
    }

    /** Basic tier (and no-subscription) cannot use AI or buy AI credits. */
    public function canUseAi(): bool
    {
        // Admins ride the AI free of credits, and free of the plan wall too —
        // a bridged admin account holds no subscription, and asking the house
        // to upgrade its own plan is the "asks for credits" the owner reported.
        return $this->isSuperAdmin()
            || ($this->planTier() !== 'none' && (bool) ($this->tierConfig()['ai'] ?? true));
    }

    /** Only Boss/Lifetime can create worker logins + send worker notifications. */
    public function canWorkerAccounts(): bool
    {
        return $this->planTier() !== 'none' && (bool) ($this->tierConfig()['workers'] ?? true);
    }

    /** Max schedules the tier allows (null = unlimited, 0 = none). */
    public function scheduleLimit(): ?int
    {
        if ($this->planTier() === 'none') {
            return 0;
        }

        return $this->tierConfig()['maxSchedules'] ?? null;
    }

    /** Whether this member may create another cropping schedule right now. */
    public function canCreateSchedule(): bool
    {
        $limit = $this->scheduleLimit();
        if ($limit === null) {
            return true;
        }

        return $this->schedules()->count() < $limit;
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
