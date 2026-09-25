<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Support\EmailSkin as S;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Every email this app sends, in the anee.io design.
 *
 * The bodies are BUILT here and stored whole, rather than assembled at send
 * time, because the mother app's editors edit the stored row — a template
 * that only became itself on the way out could not be edited or previewed.
 * EmailSkin is what keeps them consistent while they are being made.
 *
 * WHAT IS THE OWNER'S AND WHAT IS OURS.
 * Each row also keeps the version we last shipped (`defaultSubject`,
 * `defaultBodyHtml`). A body that still equals it has never been touched by
 * hand, so a new design may replace it; a body that differs is somebody's
 * work and is left exactly as it is — only the stored default moves on, which
 * is what the mother app's "Restore the anee.io design" button offers them.
 * Rows from before those columns existed fall back to the old test: never
 * saved since it was created means nobody has edited it.
 *
 * New keys are always added. Safe to run as often as you like.
 */
class AniSystemEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $hasDefaults = Schema::hasColumn('as_email_templates', 'defaultBodyHtml');

        foreach ($this->templates() as $key => $t) {
            $row = EmailTemplate::withoutGlobalScopes()
                ->where('groupKey', 'AniSystem')
                ->where('templateKey', $key)
                ->first();

            if (! $row) {
                $row = new EmailTemplate([
                    'groupKey' => 'AniSystem',
                    'templateKey' => $key,
                    'templateName' => $t['name'],
                    'subject' => $t['subject'],
                    'bodyHtml' => $t['body'],
                    'availableTags' => $t['tags'],
                    'isActive' => 1,
                    'deleteStatus' => 1,
                ]);
                if ($hasDefaults) {
                    $row->defaultSubject = $t['subject'];
                    $row->defaultBodyHtml = $t['body'];
                }
                if (isset($t['blocks'])) {
                    $row->blocks = json_encode($t['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $row->save();

                continue;
            }

            $untouchedSince = ! $row->updated_at || ! $row->created_at || $row->updated_at->eq($row->created_at);

            $bodyIsOurs = blank($row->bodyHtml) || ($hasDefaults && $row->defaultBodyHtml !== null
                ? self::same($row->bodyHtml, $row->defaultBodyHtml)
                : $untouchedSince);
            $subjectIsOurs = blank($row->subject) || ($hasDefaults && $row->defaultSubject !== null
                ? self::same($row->subject, $row->defaultSubject)
                : $untouchedSince);

            // The tag list is documentation: always worth keeping current.
            $row->availableTags = $t['tags'];

            if ($bodyIsOurs) {
                $row->bodyHtml = $t['body'];
                // The builder's blocks are the editable form of that same
                // body; a body we replace takes its matching blocks along.
                if (isset($t['blocks'])) {
                    $row->blocks = json_encode($t['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            if ($subjectIsOurs) {
                $row->subject = $t['subject'];
            }
            if ($hasDefaults) {
                $row->defaultSubject = $t['subject'];
                $row->defaultBodyHtml = $t['body'];
            }

            // Not a person's edit, so it does not move updated_at: that
            // stamp is the old "has anybody touched this" signal.
            $row->timestamps = false;
            $row->save();
        }
    }

    private static function same(?string $a, ?string $b): bool
    {
        $n = fn ($s) => trim(str_replace("\r\n", "\n", (string) $s));

        return $n($a) === $n($b);
    }

    /**
     * @return array<string, array{name:string,subject:string,tags:string,body:string,blocks?:array}>
     */
    public function templates(): array
    {
        $contact = 'Questions? Write to <a href="mailto:support@anee.io">support@anee.io</a> and a real person will answer.';

        return [

            /* ------------------------------------------------------------
             | Joining
             * ---------------------------------------------------------- */

            'email_verification' => [
                'name' => 'Sign-up — confirm your email',
                'subject' => 'Confirm your email — {{siteName}}',
                'tags' => '{{firstName}}, {{verifyUrl}}, {{thanks}}, {{siteName}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>{{thanks}} for signing up! One tap and your free anee.io account opens — your cropping '
                    . 'schedules, the activities board, and Anee, your AI farm technician, are waiting on the other side.</p>'
                    . S::button('Confirm my email', '{{verifyUrl}}')
                    . S::note('The link works for 3 days. If you did not sign up for anee.io, you can ignore this email and nothing will happen.'),
                    'Confirm your email',
                    ['face' => 'happy', 'eyebrow' => 'One last step', 'preheader' => 'One tap and your anee.io account is open.',
                     'why' => 'You are getting this because this address was used to sign up at anee.io.']
                ),
            ],

            'registration_welcome' => [
                'name' => 'Sign-up — welcome, after confirming',
                'subject' => 'Welcome to {{siteName}}, {{firstName}}!',
                'tags' => '{{firstName}}, {{lastName}}, {{email}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Your email is confirmed and your anee.io account is open. You start on the free '
                    . '<strong>Libre</strong> plan — no card needed, and no clock running.</p>'
                    . S::panel(
                        S::label('Three good first steps')
                        . '<ol style="margin:6px 0 0 18px;padding:0;">'
                        . '<li style="margin:0 0 6px;"><strong>Start a season.</strong> Pick your crop and planting date, and the work lays itself out day by day.</li>'
                        . '<li style="margin:0 0 6px;"><strong>Add your lots and workers,</strong> so everyone sees their own jobs.</li>'
                        . '<li style="margin:0;"><strong>Ask Anee.</strong> She is your AI farm technician — ask her about pests, fertilizer or the weather.</li>'
                        . '</ol>'
                    )
                    . S::button('Open anee.io', '{{loginUrl}}', false)
                    . S::note($contact),
                    'Welcome to anee.io, {{firstName}}',
                    ['face' => 'wave', 'eyebrow' => 'You are in', 'preheader' => 'Your account is open. Here is where to start.']
                ),
            ],

            'password_reset' => [
                'name' => 'Account — reset your password',
                'subject' => 'Reset your {{siteName}} password',
                'tags' => '{{firstName}}, {{resetUrl}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Someone — we hope you — asked to reset the password on your anee.io account. '
                    . 'Tap the button to choose a new one.</p>'
                    . S::button('Choose a new password', '{{resetUrl}}')
                    . S::panel('The link works for <strong>60 minutes</strong>, and only once. Asking for another is no trouble.', 'gold')
                    . S::note('If you did not ask for this, ignore this email — your password stays as it is.'),
                    'Reset your password',
                    ['face' => 'calm', 'eyebrow' => 'Account security', 'preheader' => 'Choose a new password. The link works for 60 minutes.',
                     'why' => 'You are getting this because a password reset was asked for on your anee.io account.']
                ),
            ],

            /* ------------------------------------------------------------
             | Workers
             * ---------------------------------------------------------- */

            'worker_invite' => [
                'name' => 'Worker — invitation to set a password',
                'subject' => '{{bossName}} has invited you to {{siteName}}',
                'tags' => '{{workerName}}, {{bossName}}, {{inviteUrl}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p><strong>{{bossName}}</strong> has invited you to work on their farm in anee.io — the app they plan the season in.</p>'
                    . S::panel(
                        S::label('Once you are in, you can')
                        . '<ul style="margin:6px 0 0 18px;padding:0;">'
                        . '<li style="margin:0 0 5px;">see the days you are on,</li>'
                        . '<li style="margin:0 0 5px;">read what each job is and where,</li>'
                        . '<li style="margin:0;">tick things off as you finish them.</li>'
                        . '</ul>'
                    )
                    . '<p>Set a password to get started. The link is yours alone; please do not pass it on.</p>'
                    . S::button('Set my password', '{{inviteUrl}}')
                    . S::note('If you were not expecting this, you can ignore this email and nothing will happen.'),
                    '{{bossName}} invited you to the farm',
                    ['face' => 'wave', 'eyebrow' => 'You are invited', 'preheader' => 'Set a password and see the days you are on.',
                     'why' => 'You are getting this because a farm on anee.io added you as a worker.']
                ),
            ],

            /* The one a boss sends to a worker who ALREADY has a login.
             * A registration link is no use to them — they registered — and
             * what an owner actually reaches for at that point is "let them
             * set a new password", usually because the worker has forgotten
             * theirs and is standing in a field. The link is the ordinary
             * reset link, so the page it opens is the one this app already
             * has and the token expires the way every other one does. */
            'worker_password_change' => [
                'name' => 'Worker — link to change their password',
                'subject' => 'Change your {{siteName}} password',
                'tags' => '{{workerName}}, {{bossName}}, {{resetUrl}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p><strong>{{bossName}}</strong> has sent you a link to change your anee.io password. '
                    . 'Use it if you have forgotten the one you had, or if you would rather pick a new one.</p>'
                    . S::button('Change my password', '{{resetUrl}}')
                    . S::panel('The link is yours alone; please do not pass it on. It stops working after a while, and asking for another is no trouble.', 'gold')
                    . S::note('If you did not expect this, you can ignore this email — your password stays as it is.'),
                    'Change your password',
                    ['face' => 'calm', 'eyebrow' => 'From {{bossName}}', 'preheader' => 'A link to pick a new anee.io password.',
                     'why' => 'You are getting this because a farm you work with on anee.io sent it.']
                ),
            ],

            'worker_access_ready' => [
                'name' => 'Worker — existing account given farm access',
                'subject' => '{{bossName}} has given you access on {{siteName}}',
                'tags' => '{{workerName}}, {{bossName}}, {{loginUrl}}, {{siteName}}',
                'body' => S::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p><strong>{{bossName}}</strong> has given your anee.io account access to their farm. '
                    . 'You do not need a new password — the one you already use will do.</p>'
                    . S::panel('<strong>How to open their farm:</strong> log in, then tap the farm switcher next to your profile picture and choose theirs.')
                    . S::button('Log in to anee.io', '{{loginUrl}}', false),
                    'You are on {{bossName}}’s farm',
                    ['face' => 'thumbsup', 'eyebrow' => 'Farm access', 'preheader' => 'Log in with the password you already have.',
                     'why' => 'You are getting this because a farm on anee.io gave your account access.']
                ),
            ],

            /* ------------------------------------------------------------
             | The work
             * ---------------------------------------------------------- */

            'day_schedule' => [
                'name' => "A day's work, sent by hand",
                'subject' => '{{dateLabel}} — {{scheduleTitle}}',
                'tags' => '{{workerName}}, {{scheduleTitle}}, {{dateLabel}}, {{tasksTable}}, {{sentBy}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p>Here is what is planned for <strong>{{dateLabel}}</strong> on {{scheduleTitle}}.</p>'
                    . '{{tasksTable}}'
                    . S::note('Sent by {{sentBy}} with anee.io. If anything looks off, check with them before you head out.'),
                    '{{dateLabel}}',
                    ['face' => 'salute', 'eyebrow' => 'The day’s work · {{scheduleTitle}}', 'preheader' => 'What is planned for {{dateLabel}}.',
                     'why' => 'You are getting this because {{sentBy}} sent it to you from anee.io.']
                ),
            ],

            'activity_notice' => [
                'name' => 'One activity, sent by hand',
                'subject' => '{{activityTitle}} — {{dateLabel}}',
                'tags' => '{{workerName}}, {{scheduleTitle}}, {{activityTitle}}, {{dateLabel}}, {{activityBody}}, {{sentBy}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{workerName}},</p>'
                    . '<p>This one is for you on <strong>{{dateLabel}}</strong>, on {{scheduleTitle}}.</p>'
                    . '{{activityBody}}'
                    . S::note('Sent by {{sentBy}} with anee.io. If anything looks off, check with them before you head out.'),
                    '{{activityTitle}}',
                    ['face' => 'idea', 'eyebrow' => 'A job for you · {{scheduleTitle}}', 'preheader' => '{{activityTitle}} on {{dateLabel}}.',
                     'why' => 'You are getting this because {{sentBy}} sent it to you from anee.io.']
                ),
            ],

            /* The morning email. The mother app's builder edits it as blocks,
             * so it ships with the blocks that draw this same body. */
            'daily_digest' => [
                'name' => 'Daily Schedule Digest',
                'subject' => '{{today_date}} — {{schedule_title}}',
                'tags' => '{{recipient_name}}, {{schedule_title}}, {{today_date}}, {{tomorrow_date}}, {{today_count}}, {{tomorrow_count}}, {{app_name}}, {{activities_list}}',
                'body' => S::wrap(
                    self::p('Good morning {{recipient_name}}.')
                    . self::p('Here is what is on for {{today_date}}, and what is coming tomorrow.')
                    . '{{activities_list}}'
                    . self::callout('Before you head out', 'Check the weather on the day in the app — a forecast written last night can be wrong by sunrise.')
                    . S::note('Sent by {{app_name}} because this schedule has the daily email switched on. Turn it off in the schedule’s Settings → Notifications.'),
                    '{{today_date}}',
                    ['face' => 'salute', 'eyebrow' => 'Good morning · {{schedule_title}}', 'preheader' => 'Today and tomorrow on {{schedule_title}}.',
                     'why' => 'You are getting this because a schedule on anee.io has the daily email switched on for you.']
                ),
                'blocks' => [
                    ['kind' => 'text', 'text' => "Good morning {{recipient_name}}.\n\nHere is what is on for {{today_date}}, and what is coming tomorrow."],
                    ['kind' => 'activities'],
                    ['kind' => 'callout', 'title' => 'Before you head out', 'text' => 'Check the weather on the day in the app — a forecast written last night can be wrong by sunrise.'],
                    ['kind' => 'note', 'text' => 'Sent by {{app_name}} because this schedule has the daily email switched on. Turn it off in the schedule’s Settings → Notifications.'],
                ],
            ],

            /* ------------------------------------------------------------
             | Plans and payments
             * ---------------------------------------------------------- */

            'payment_submitted' => [
                'name' => 'Payment — details received',
                'subject' => 'We received your payment details — Order {{orderNumber}}',
                'tags' => '{{firstName}}, {{orderNumber}}, {{planName}}, {{price}}, {{currency}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Thank you! Your payment details reached us.</p>'
                    . S::facts(['Order' => '{{orderNumber}}', 'Plan' => '{{planName}}', 'Amount' => '{{currency}}{{price}}'])
                    . '<p>Our team checks every payment by hand, usually within a business day. '
                    . 'You will get another email the moment your plan is switched on.</p>'
                    . S::note($contact),
                    'We have your payment details',
                    ['face' => 'thumbsup', 'eyebrow' => 'Payment received', 'preheader' => 'Order {{orderNumber}} is with our team for checking.']
                ),
            ],

            'payment_approved' => [
                'name' => 'Payment — approved, plan active',
                'subject' => 'Your {{siteName}} subscription is now active!',
                'tags' => '{{firstName}}, {{orderNumber}}, {{planName}}, {{price}}, {{currency}}, {{expiresAt}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Great news — your payment is verified and your plan is switched on.</p>'
                    . S::facts(['Plan' => '{{planName}}', 'Order' => '{{orderNumber}}', 'Active until' => '{{expiresAt}}'])
                    . '<p>Everything in your plan is open now. Enjoy the season!</p>'
                    . S::button('Open my farm', '{{loginUrl}}', false)
                    . S::note($contact),
                    'Your {{planName}} plan is active',
                    ['face' => 'cheer', 'eyebrow' => 'You are all set', 'preheader' => 'Your plan is active until {{expiresAt}}.']
                ),
            ],

            'payment_rejected' => [
                'name' => 'Payment — could not be verified',
                'subject' => 'Payment issue on order {{orderNumber}}',
                'tags' => '{{firstName}}, {{orderNumber}}, {{planName}}, {{price}}, {{currency}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>We are sorry — we could not verify your payment for this order.</p>'
                    . S::facts(['Order' => '{{orderNumber}}', 'Plan' => '{{planName}}'], 'alert')
                    . '<p>This happens most often when the reference number or the amount does not match what arrived. '
                    . 'Check your details and send them again, or write to us and we will sort it out together.</p>'
                    . S::button('Review my account', '{{loginUrl}}', false)
                    . S::note($contact),
                    'We could not verify your payment',
                    ['face' => 'concerned', 'eyebrow' => 'Payment issue', 'preheader' => 'Order {{orderNumber}} needs another look.']
                ),
            ],

            'subscription_expiring' => [
                'name' => 'Plan — ending soon',
                'subject' => 'Your {{siteName}} subscription expires on {{expiresAt}}',
                'tags' => '{{firstName}}, {{planName}}, {{expiresAt}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>A friendly heads-up: your <strong>{{planName}}</strong> plan runs until <strong>{{expiresAt}}</strong>.</p>'
                    . '<p>Renew before then and your schedules, workers and reports carry on without a pause.</p>'
                    . S::button('Renew my plan', '{{loginUrl}}', false)
                    . S::note('Your schedules and records stay safe either way. ' . $contact),
                    'Your plan ends on {{expiresAt}}',
                    ['face' => 'idea', 'eyebrow' => 'A friendly heads-up', 'preheader' => 'Renew before {{expiresAt}} to keep everything running.']
                ),
            ],

            'subscription_expired' => [
                'name' => 'Plan — ended',
                'subject' => 'Your {{siteName}} subscription has expired',
                'tags' => '{{firstName}}, {{planName}}, {{expiresAt}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Your <strong>{{planName}}</strong> plan ended on <strong>{{expiresAt}}</strong>.</p>'
                    . S::panel('<strong>Your schedules and records are safe.</strong> Nothing has been deleted — renew and you pick up exactly where you left off.')
                    . S::button('Renew my plan', '{{loginUrl}}', false)
                    . S::note($contact),
                    'Your plan has ended',
                    ['face' => 'concerned', 'eyebrow' => 'Plan ended', 'preheader' => 'Your records are safe. Renew to pick up where you left off.']
                ),
            ],

            'subscription_suspended' => [
                'name' => 'Plan — suspended',
                'subject' => 'Your {{siteName}} subscription has been suspended',
                'tags' => '{{firstName}}, {{planName}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Your anee.io subscription has been put on hold.</p>'
                    . S::panel('Write to us at <a href="mailto:support@anee.io"><strong>support@anee.io</strong></a> and we will help you restore access. Your schedules and records are safe while it is sorted out.', 'gold')
                    . S::button('Write to support', 'mailto:support@anee.io', false),
                    'Your plan is on hold',
                    ['face' => 'serious', 'eyebrow' => 'Account notice', 'preheader' => 'Write to support and we will help you restore access.']
                ),
            ],

            'subscription_cancelled' => [
                'name' => 'Plan — cancelled',
                'subject' => 'Your {{siteName}} subscription has been cancelled',
                'tags' => '{{firstName}}, {{planName}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Your <strong>{{planName}}</strong> plan has been cancelled.</p>'
                    . '<p>Your account stays open, and you can choose a plan again any time from your account page.</p>'
                    . S::button('Open my account', '{{loginUrl}}', false)
                    . S::note($contact),
                    'Your plan is cancelled',
                    ['face' => 'sad', 'eyebrow' => 'Plan cancelled', 'preheader' => 'Your account stays open. Come back any time.']
                ),
            ],

            'subscription_renewed' => [
                'name' => 'Plan — renewed',
                'subject' => 'Your {{siteName}} subscription has been renewed',
                'tags' => '{{firstName}}, {{orderNumber}}, {{planName}}, {{price}}, {{currency}}, {{expiresAt}}, {{siteName}}, {{loginUrl}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Your plan is renewed. Thank you for staying with us!</p>'
                    . S::facts(['Plan' => '{{planName}}', 'Order' => '{{orderNumber}}', 'Now runs until' => '{{expiresAt}}'])
                    . S::button('Open my farm', '{{loginUrl}}', false)
                    . S::note($contact),
                    'Thank you for staying with us',
                    ['face' => 'heart', 'eyebrow' => 'Plan renewed', 'preheader' => 'Your plan now runs until {{expiresAt}}.']
                ),
            ],

            /* ------------------------------------------------------------
             | Talking to us
             * ---------------------------------------------------------- */

            'contact_received' => [
                'name' => 'Contact form — message received',
                'subject' => 'We received your message — {{siteName}}',
                'tags' => '{{firstName}}, {{siteName}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>Your message reached the anee.io team. A real person reads every one and replies, '
                    . 'usually within a business day.</p>'
                    . S::note('Something urgent? Write to <a href="mailto:support@anee.io">support@anee.io</a>.'),
                    'Thanks for writing to us',
                    ['face' => 'smile', 'eyebrow' => 'Message received', 'preheader' => 'A real person will reply, usually within a business day.',
                     'why' => 'You are getting this because this address was used on the anee.io contact form.']
                ),
            ],

            /* An answer from the admin's support desk. {{replyBody}} is the
             * answer itself, already made safe by the sender. */
            'support_reply' => [
                'name' => 'Support — a reply to a ticket',
                'subject' => '[{{ticketNumber}}] Re: {{ticketSubject}}',
                'tags' => '{{firstName}}, {{ticketNumber}}, {{ticketSubject}}, {{replyBody}}, {{adminName}}, {{ticketUrl}}, {{siteName}}',
                'body' => S::wrap(
                    '<p>Hi {{firstName}},</p>'
                    . '<p>{{adminName}} from the anee.io team answered your ticket “{{ticketSubject}}”:</p>'
                    . S::panel('{{replyBody}}')
                    . S::button('Open the ticket', '{{ticketUrl}}', false)
                    . S::note('To answer, open the ticket and reply there — it reaches the whole team, and the thread stays in one place.'),
                    'We replied to your ticket',
                    ['face' => 'smile', 'eyebrow' => 'Support · {{ticketNumber}}', 'preheader' => '{{adminName}} answered “{{ticketSubject}}”.']
                ),
            ],
        ];
    }

    /* The daily digest's pieces are drawn exactly as the mother app's block
     * renderer draws them, so the body and its blocks stay one thing. */

    private static function p(string $text): string
    {
        return '<p style="margin:0 0 16px;">' . $text . '</p>';
    }

    private static function callout(string $title, string $text): string
    {
        return S::panel('<strong style="display:block;margin-bottom:3px;">' . $title . '</strong>' . $text, 'gold');
    }
}
