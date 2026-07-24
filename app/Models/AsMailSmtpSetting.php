<?php

namespace App\Models;

/**
 * SMTP credentials configured in the mother app (AniSenso → Mail Settings),
 * grouped by `groupKey`. AniSystem reads the row for the "AniSystem" group and
 * sends its own mail (worker digests, share notifications) through it.
 *
 * The password is stored PLAIN TEXT in the shared table on purpose — the two
 * apps have different APP_KEYs, so Laravel's Crypt cannot be shared. Never add
 * an encryption cast here.
 */
class AsMailSmtpSetting extends BaseModel
{
    public const GROUP_ANISYSTEM = 'AniSystem';

    protected $table = 'as_mail_smtp_settings';

    protected $fillable = [
        'groupKey',
        'smtpHost',
        'smtpPort',
        'smtpUsername',
        'smtpPassword',
        'smtpEncryption',
        'smtpFromEmail',
        'smtpFromName',
        'isActive',
        'isVerified',
        'lastTestedAt',
        'deleteStatus',
    ];

    protected $casts = [
        'smtpPort' => 'integer',
        'isActive' => 'boolean',
        'isVerified' => 'boolean',
        'lastTestedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function scopeForGroup($query, string $group)
    {
        return $query->where('groupKey', $group);
    }

    /** Enough fields present to actually connect. */
    public function isConfigured(): bool
    {
        return filled($this->smtpHost)
            && filled($this->smtpPort)
            && filled($this->smtpFromEmail);
    }

    /** Configured, active, and safe to send through right now. */
    public function isSendable(): bool
    {
        return $this->isConfigured() && $this->isActive;
    }
}
