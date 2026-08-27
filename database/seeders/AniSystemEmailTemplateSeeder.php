<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Support\EmailSkin;
use Illuminate\Database\Seeder;

/**
 * The emails this app sends, in the house style.
 *
 * The bodies are BUILT here and stored whole, rather than assembled at send
 * time, because the mother app's builder edits the stored row — a template
 * that only became itself on the way out could not be edited or previewed.
 * EmailSkin is what keeps them consistent while they are being made.
 *
 * Existing rows are left alone unless they have never been touched by hand:
 * somebody who has rewritten the welcome email in the admin should not have
 * it quietly replaced by a deploy. New keys are always added.
 */
class AniSystemEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $key => $t) {
            $row = EmailTemplate::withoutGlobalScopes()
                ->where('groupKey', 'AniSystem')
                ->where('templateKey', $key)
                ->first();

            if (! $row) {
                EmailTemplate::create([
                    'groupKey' => 'AniSystem',
                    'templateKey' => $key,
                    'templateName' => $t['name'],
                    'subject' => $t['subject'],
                    'bodyHtml' => $t['body'],
                    'availableTags' => $t['tags'],
                    'isActive' => 1,
                    'deleteStatus' => 1,
                ]);

                continue;
            }

            // Only refresh what the owner has not made their own: the tag list
            // is documentation and always worth keeping current, and a body
            // is replaced only while it is still the one we shipped.
            $row->availableTags = $t['tags'];
            if (empty($row->bodyHtml) || ($row->updated_at && $row->updated_at->eq($row->created_at))) {
                $row->bodyHtml = $t['body'];
                $row->subject = $t['subject'];
            }
            $row->save();
        }
    }

    /** @return array<string, array{name:string,subject:string,tags:string,body:string}> */
    private function templates(): array
    {
        return [
            'worker_invite' => [
                'name' => 'Worker — invitation to set a password',
                'subject' => '{{bossName}} has invited you to {{siteName}}',
                'tags' => '{{workerName}}, {{bossName}}, {{inviteUrl}}, {{siteName}}, {{loginUrl}}',
                'body' => EmailSkin::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p><strong>{{bossName}}</strong> has invited you to work on their farm in {{siteName}} — '
                    . 'the app they plan the season in. You will be able to see the days you are on, '
                    . 'what each job is, and tick things off as you finish them.</p>'
                    . '<p>Set a password to get started. The link is yours alone; please do not pass it on.</p>'
                    . EmailSkin::button('Set my password', '{{inviteUrl}}')
                    . '<p style="margin-top:22px;color:#6b7280;font-size:13px;">'
                    . 'If you were not expecting this, you can ignore this email and nothing will happen.</p>',
                    'You have been invited'
                ),
            ],

            'worker_access_ready' => [
                'name' => 'Worker — existing account given farm access',
                'subject' => '{{bossName}} has given you access on {{siteName}}',
                'tags' => '{{workerName}}, {{bossName}}, {{loginUrl}}, {{siteName}}',
                'body' => EmailSkin::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p><strong>{{bossName}}</strong> has given your existing {{siteName}} account access to their farm. '
                    . 'You do not need a new password — the one you already use will do.</p>'
                    . '<p>Log in, then use the farm switcher next to your profile picture to open theirs.</p>'
                    . EmailSkin::button('Log in to {{siteName}}', '{{loginUrl}}'),
                    'Farm access granted'
                ),
            ],

            'day_schedule' => [
                'name' => "A day's work, sent by hand",
                'subject' => '{{dateLabel}} — {{scheduleTitle}}',
                'tags' => '{{workerName}}, {{scheduleTitle}}, {{dateLabel}}, {{tasksTable}}, {{sentBy}}, {{siteName}}, {{loginUrl}}',
                'body' => EmailSkin::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p>Here is what is planned for <strong>{{dateLabel}}</strong> on {{scheduleTitle}}.</p>'
                    . '{{tasksTable}}'
                    . '<p style="margin-top:22px;color:#6b7280;font-size:13px;">Sent by {{sentBy}} from {{siteName}}.</p>',
                    "The day's work"
                ),
            ],

            'activity_notice' => [
                'name' => 'One activity, sent by hand',
                'subject' => '{{activityTitle}} — {{dateLabel}}',
                'tags' => '{{workerName}}, {{scheduleTitle}}, {{activityTitle}}, {{dateLabel}}, {{activityBody}}, {{sentBy}}, {{siteName}}, {{loginUrl}}',
                'body' => EmailSkin::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p>This one is for you on <strong>{{dateLabel}}</strong>, on {{scheduleTitle}}.</p>'
                    . '{{activityBody}}'
                    . '<p style="margin-top:22px;color:#6b7280;font-size:13px;">Sent by {{sentBy}} from {{siteName}}.</p>',
                    'A job for you'
                ),
            ],
        ];
    }
}
