<?php

namespace App\Models;

class EmailTemplate extends BaseModel
{
    protected $table = 'as_email_templates';

    protected $fillable = [
        'groupKey', 'templateKey', 'templateName', 'subject', 'bodyHtml',
        'availableTags', 'isActive', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'isActive' => 'boolean',
            'deleteStatus' => 'integer',
        ]);
    }

    public static function find_(string $groupKey, string $templateKey): ?self
    {
        return static::query()
            ->where('groupKey', $groupKey)
            ->where('templateKey', $templateKey)
            ->where('deleteStatus', 1)
            ->where('isActive', 1)
            ->first();
    }

    /**
     * Replace {{tag}} placeholders in subject and body.
     *
     * @return array{subject: string, body: string}
     */
    public function render(array $tags): array
    {
        $subject = $this->subject;
        $body = $this->bodyHtml;

        foreach ($tags as $key => $value) {
            $subject = str_replace('{{'.$key.'}}', (string) $value, $subject);
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }
}
