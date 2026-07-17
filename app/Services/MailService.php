<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\MailSmtpSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends templated email using the SMTP settings + email templates managed in
 * the mother system (btc-check → Ani-Senso → Mail Settings), group 'AniSystem'.
 * When SMTP is not configured/active, emails are written to the Laravel log so
 * flows never hard-fail in development.
 */
class MailService
{
    /**
     * Send a templated email. Returns true when the message was handed to the
     * SMTP server (or logged in fallback mode); false only on send failure.
     */
    public function sendTemplate(string $templateKey, string $toEmail, string $toName, array $tags = []): bool
    {
        $group = config('anisystem.mail_group', 'AniSystem');

        $template = EmailTemplate::find_($group, $templateKey);
        if (! $template) {
            Log::warning("MailService: template [{$group}/{$templateKey}] not found or inactive; email to {$toEmail} skipped.");

            return false;
        }

        $tags = array_merge([
            'siteName' => config('app.name', 'AniSystem'),
            'loginUrl' => route('login'),
        ], $tags);

        $rendered = $template->render($tags);

        return $this->send($toEmail, $toName, $rendered['subject'], $rendered['body']);
    }

    public function sendTemplateToUser(string $templateKey, User $user, array $tags = []): bool
    {
        $tags = array_merge([
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'email' => $user->email,
        ], $tags);

        return $this->sendTemplate($templateKey, $user->email, $user->full_name, $tags);
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $settings = MailSmtpSetting::forGroup(config('anisystem.mail_group', 'AniSystem'));

        if (! $settings || ! $settings->isActive || ! $settings->isConfigured()) {
            Log::info("MailService (log fallback) → {$toEmail} | {$subject}\n".strip_tags($htmlBody));

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
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();

            return true;
        } catch (\Throwable $e) {
            Log::error("MailService: failed sending to {$toEmail}: ".$e->getMessage());

            return false;
        }
    }
}
