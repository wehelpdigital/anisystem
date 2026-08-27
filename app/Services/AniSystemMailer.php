<?php

namespace App\Services;

use App\Models\AsMailSmtpSetting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends AniSystem's own mail (worker day-digests, share notifications) through
 * the SMTP credentials configured in the mother app under the "AniSystem" mail
 * group. When that group isn't configured/active, it quietly falls back to the
 * app's default mailer (which is `log` in local dev) so nothing breaks.
 */
class AniSystemMailer
{
    /**
     * Send one mailable to one recipient. Throws on transport failure.
     *
     * Resend leads here too. This app has two mail paths for historical
     * reasons — templated messages through MailService, mailables through
     * this — and only fixing one of them would have left the morning digest
     * still writing itself into a log file while password resets flew. A
     * mailable knows how to render itself to HTML and name its own subject,
     * which is everything the mail book needs.
     */
    public function send(string $toEmail, string $toName, Mailable $mailable, array $about = []): void
    {
        if (app(ResendMailer::class)->configured()) {
            $subject = '';
            try {
                $subject = (string) ($mailable->envelope()->subject ?? '');
            } catch (\Throwable $e) {
                $subject = (string) ($mailable->subject ?? '');
            }

            app(EmailQueue::class)->queueAndSend(
                $toEmail,
                $toName,
                $subject !== '' ? $subject : config('app.name', 'AniSystem'),
                $mailable->render(),
                $about
            );

            return;
        }

        $mailer = $this->prepareGroupMailer();

        $pending = $mailer
            ? Mail::mailer($mailer)
            : Mail::mailer(config('mail.default'));

        $pending->to($toEmail, $toName)->send($mailable);
    }

    /** True when the AniSystem group SMTP is configured and switched on. */
    public function isGroupConfigured(): bool
    {
        return (bool) optional($this->groupSetting())->isSendable();
    }

    private function groupSetting(): ?AsMailSmtpSetting
    {
        return AsMailSmtpSetting::active()
            ->forGroup(AsMailSmtpSetting::GROUP_ANISYSTEM)
            ->first();
    }

    /**
     * Register a runtime SMTP mailer from the group settings and return its
     * name, or null to signal "use the default mailer".
     */
    private function prepareGroupMailer(): ?string
    {
        $s = $this->groupSetting();
        if (! $s || ! $s->isSendable()) {
            return null;
        }

        config([
            'mail.mailers.anisystem_smtp' => [
                'transport' => 'smtp',
                'host' => $s->smtpHost,
                'port' => $s->smtpPort,
                'encryption' => $s->smtpEncryption === 'none' ? null : $s->smtpEncryption,
                'username' => $s->smtpUsername ?: null,
                'password' => $s->smtpPassword ?: null,
                'timeout' => 20,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: null,
            ],
            'mail.from.address' => $s->smtpFromEmail,
            'mail.from.name' => $s->smtpFromName ?: config('app.name'),
        ]);

        return 'anisystem_smtp';
    }
}
