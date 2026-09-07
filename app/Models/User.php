<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The account the AI Technician answers community questions under.
     *
     * A sentinel address rather than a flag column: the mother app creates
     * this row by email when it first posts an answer, and both apps look it
     * up the same way. Screens ask so they can mark it — a farmer reading a
     * discussion should know at a glance which answer came from the assistant
     * and which came from a neighbour.
     */
    public const ASSISTANT_EMAIL = 'ai-technician@anisenso.system';

    /** Ids of the SYSTEM_EMAILS accounts, cached — people-lists (the
     *  suggestions strip, the connect directory) exclude the lot: there is
     *  nobody on the other end to accept a request. */
    public static function systemAccountIds(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('users.system-ids', 3600, function () {
            return self::whereIn('email', self::SYSTEM_EMAILS)->pluck('id')->map(fn ($id) => (int) $id)->all();
        });
    }

    protected $table = 'anisystem_users';

    protected $fillable = [
        'firstName',
        'lastName',
        'phone',
        'email',
        'password',
        'emailVerifiedAt',
        'googleId',
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
        'adminUserId', 'panelAdmin',
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
            'emailVerifiedAt' => 'datetime',
            'deleteStatus' => 'integer',
            'adminUserId' => 'integer',
            'lastSeenAt' => 'datetime',
            'communitySuspendedUntil' => 'datetime',
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

    public function getIsAssistantAttribute(): bool
    {
        return strcasecmp((string) $this->email, self::ASSISTANT_EMAIL) === 0;
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
     * A mother-site super admin bridged into anee.io (see SuperAdminBridge).
     * Such members get full access without an anee.io subscription.
     */
    /**
     * Accounts that post as the app itself — Anee, the AniSystem Technician.
     * Real rows (their messages need authors) but not clients, so the admin
     * panel's lists leave them out.
     */
    public const SYSTEM_EMAILS = ['ai-technician@anisenso.system', 'technician@anee.io'];

    public function isSuperAdmin(): bool
    {
        // Two doors to the same hat: linked to a mother-site admin row, or
        // granted straight from the panel.
        return ! empty($this->adminUserId) || ! empty($this->panelAdmin);
    }

    /**
     * Which subscription tier this member is on: basic | boss | lifetime | none.
     * Derived from the active plan's key/name (see config/tiers.php). Any active
     * paid plan that matches no tier keyword counts as 'boss' so existing
     * subscribers are never silently downgraded.
     */
    public function planTier(): string
    {
        // Mother-site super admins are the house account — everything,
        // admin panel included, never sold.
        if ($this->isSuperAdmin()) {
            return 'admin';
        }

        // No active subscription IS a tier now: Libre, the free floor.
        $sub = $this->activeSubscription();
        if (! $sub) {
            return 'libre';
        }

        $hay = mb_strtolower(($sub->planKey ?? '') . ' ' . ($sub->planName ?? ''));
        foreach (config('tiers', []) as $tier => $cfg) {
            foreach (($cfg['match'] ?? []) as $needle) {
                if ($needle !== '' && str_contains($hay, $needle)) {
                    return $tier;
                }
            }
        }

        // A paid plan that matches nothing maps UP, never down.
        return 'owner';
    }

    /** The config block for this member's current tier (falls back to libre). */
    public function tierConfig(): array
    {
        return config('tiers.' . $this->planTier(), config('tiers.libre', []));
    }

    /** Every tier can open the AI — credits gate its use, not the plan. */
    public function canUseAi(): bool
    {
        return $this->isSuperAdmin() || (bool) ($this->tierConfig()['ai'] ?? true);
    }

    /** Only Farm Owner (and admin) can create worker logins + notifications. */
    public function canWorkerAccounts(): bool
    {
        return (bool) ($this->tierConfig()['workerLogins'] ?? false);
    }

    /** Max ACTIVE schedules the tier allows (null = unlimited). */
    public function scheduleLimit(): ?int
    {
        $limit = $this->tierConfig()['schedulesActive'] ?? null;

        return $limit === null ? null : (int) $limit;
    }

    /**
     * Whether this member may create another cropping schedule right now.
     * Only ACTIVE seasons count against the cap — a completed or archived
     * season is history, not a slot in use.
     */
    public function canCreateSchedule(): bool
    {
        $limit = $this->scheduleLimit();
        if ($limit === null) {
            return true;
        }

        return $this->schedules()
            ->whereNotIn('status', [AsCroppingSchedule::STATUS_COMPLETED, AsCroppingSchedule::STATUS_ARCHIVED])
            ->count() < $limit;
    }

    public function schedules()
    {
        return $this->hasMany(AsCroppingSchedule::class, 'anisystemUserId')->where('deleteStatus', 1);
    }

    /**
     * Password reset email goes through the mother system's mail settings and
     * templates (group anee.io, template key password_reset).
     */
    public function sendPasswordResetNotification($token)
    {
        app(\App\Services\MailService::class)->sendTemplateToUser('password_reset', $this, [
            'resetUrl' => route('password.reset', ['token' => $token, 'email' => $this->email]),
        ]);
    }
}
