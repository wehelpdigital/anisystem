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
    /** Send one mailable to one recipient. Throws on transport failure. */
    public function send(string $toEmail, string $toName, Mailable $mailable): void
    {
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
