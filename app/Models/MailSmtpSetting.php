<?php

namespace App\Models;

class MailSmtpSetting extends BaseModel
{
    protected $table = 'as_mail_smtp_settings';

    protected $fillable = [
        'groupKey', 'smtpHost', 'smtpPort', 'smtpUsername', 'smtpPassword',
        'smtpEncryption', 'smtpFromEmail', 'smtpFromName',
        'isActive', 'isVerified', 'lastTestedAt', 'deleteStatus',
    ];

    protected $hidden = ['smtpPassword'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'smtpPort' => 'integer',
            'isActive' => 'boolean',
            'isVerified' => 'boolean',
            'lastTestedAt' => 'datetime:Y-m-d H:i:s',
            'deleteStatus' => 'integer',
        ]);
    }

    public static function forGroup(string $groupKey): ?self
    {
        return static::query()
            ->where('groupKey', $groupKey)
            ->where('deleteStatus', 1)
            ->first();
    }

    public function isConfigured(): bool
    {
        return filled($this->smtpHost) && filled($this->smtpPort) && filled($this->smtpFromEmail);
    }
}
