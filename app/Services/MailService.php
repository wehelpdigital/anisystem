<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\MailSmtpSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends templated email using the SMTP settings + email templates managed in
 * the mother system (btc-check → Mail Settings), group 'AniSystem' — the
 * key kept its old spelling because it names a row over there.
 * When SMTP is not configured/active, emails are written to the Laravel log so
 * flows never hard-fail in development.
 */
class MailService
{
    /**
     * Send a templated email. Returns true when the message was handed to the
     * SMTP server (or logged in fallback mode); false only on send failure.
     */
    public function sendTemplate(string $templateKey, string $toEmail, string $toName, array $tags = [], array $about = []): bool
    {
        $group = config('anisystem.mail_group', 'AniSystem');

        $template = EmailTemplate::find_($group, $templateKey);
        if (! $template) {
            Log::warning("MailService: template [{$group}/{$templateKey}] not found or inactive; email to {$toEmail} skipped.");

            return false;
        }

        $rendered = $this->fill($template, $tags);

        // What this message IS travels with it, so the mail book can say
        // which template a row came from and what it was about.
        return $this->send($toEmail, $toName, $rendered['subject'], $rendered['body'], [
            'groupKey' => $group,
            'templateKey' => $templateKey,
        ] + $about);
    }

    public function sendTemplateToUser(string $templateKey, User $user, array $tags = [], array $about = []): bool
    {
        return $this->sendTemplate($templateKey, $user->email, $user->full_name, $this->userTags($user, $tags), $about);
    }

    /**
     * What a template turns into, without sending it — the same subject and
     * body sendTemplate() would hand over. Null when the template is missing
     * or switched off.
     *
     * @return array{subject: string, body: string}|null
     */
    public function render(string $templateKey, array $tags = [], ?User $user = null): ?array
    {
        $template = EmailTemplate::find_(config('anisystem.mail_group', 'AniSystem'), $templateKey);

        return $template ? $this->fill($template, $user ? $this->userTags($user, $tags) : $tags) : null;
    }

    /** The tags every member's email can use, under the caller's own. */
    private function userTags(User $user, array $tags): array
    {
        // Their own money sign: a US member paid in dollars.
        $currency = '₱';
        try {
            $currency = \App\Support\Region::as(\App\Support\Region::of($user), fn () => \App\Support\Region::symbol());
        } catch (\Throwable $e) {
            // The peso is the right default for most members.
        }

        return array_merge([
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'email' => $user->email,
            'currency' => $currency,
        ], $tags);
    }

    /**
     * Fill a template's tags. The house tags go under the caller's, and any
     * tag left with nothing to say is emptied — a reader should never see
     * the plumbing, "{{planName}}" in an inbox least of all.
     *
     * @return array{subject: string, body: string}
     */
    private function fill(EmailTemplate $template, array $tags): array
    {
        $tags = array_merge([
            'siteName' => config('app.name', 'anee.io'),
            'loginUrl' => route('login'),
            'supportEmail' => 'support@anee.io',
        ], $tags);

        $rendered = $template->render($tags);
        $leftover = '~\{\{\s*[A-Za-z0-9_]+\s*\}\}~';

        return [
            'subject' => trim(preg_replace($leftover, '', $rendered['subject']) ?? $rendered['subject']),
            'body' => preg_replace($leftover, '', $rendered['body']) ?? $rendered['body'],
        ];
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, array $about = []): bool
    {
        /* Resend leads.
         *
         * Every message goes into the mail book first and then straight out,
         * so a reset link somebody is waiting on does not sit in a queue —
         * and if it fails there is a row saying who it was for and why, which
         * the mother app's cron picks up and tries again.
         *
         * The SMTP path below is kept for a farm that would rather use its
         * own server: fill the mail settings in the mother app and turn them
         * on, and mail goes that way instead. With neither configured, mail
         * is written to the log so a development flow never hard-fails. */
        if (app(ResendMailer::class)->configured()) {
            return app(EmailQueue::class)->queueAndSend($toEmail, $toName, $subject, $htmlBody, $about);
        }

        $settings = MailSmtpSetting::forGroup(config('anisystem.mail_group', 'AniSystem'));

        if (! $settings || ! $settings->isActive || ! $settings->isConfigured()) {
            Log::info("MailService (log fallback) → {$toEmail} | {$subject}\n".\App\Support\EmailSkin::toText($htmlBody));

            return true;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $settings->smtpHost;
            $mail->Port = (int) $settings->smtpPort;
            $mail->CharSet = 'UTF-8';

            if (filled($settings->smtpUsername)) {
                $mail->SMTPAuth = true;
                $mail->Username = $settings->smtpUsername;
                $mail->Password = (string) $settings->smtpPassword;
            }

            if ($settings->smtpEncryption !== 'none') {
                $mail->SMTPSecure = $settings->smtpEncryption === 'ssl'
                    ? PHPMailer::ENCRYPTION_SMTPS
                    : PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($settings->smtpFromEmail, $settings->smtpFromName ?: config('app.name'));
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            // The words, not the stylesheet: strip_tags() alone would read
            // the house style's <style> block out as the first paragraph.
            $mail->AltBody = \App\Support\EmailSkin::toText($htmlBody);

            $mail->send();

            return true;
        } catch (\Throwable $e) {
            Log::error("MailService: failed sending to {$toEmail}: ".$e->getMessage());

            return false;
        }
    }
}
