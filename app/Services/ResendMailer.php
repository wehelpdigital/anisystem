<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The one door mail leaves by.
 *
 * Resend's own SDK is installed and used when it is there, because that is
 * what their documentation hands you and it is the thing that will keep
 * working when they change something. When it is not — a deploy that has not
 * run composer yet, the mother app on an older tree — the same call goes out
 * over their REST API, which is all the SDK is doing anyway. Either way the
 * caller gets back a message id or null, and the reason for a null is kept.
 */
class ResendMailer
{
    private ?string $lastError = null;

    /** Why the last send did not happen. */
    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function configured(): bool
    {
        return filled(config('services.resend.key'));
    }

    /**
     * Send one message.
     *
     * @return string|null Resend's id for the message, or null if it did not go.
     */
    public function send(string $toEmail, string $toName, string $subject, string $html): ?string
    {
        $this->lastError = null;
        $key = (string) config('services.resend.key');

        if ($key === '') {
            $this->lastError = 'RESEND_KEY is not set, so there is nothing to send with.';

            return null;
        }

        /* The recipient goes in BARE, with no display name.
         *
         * Resend's sandbox sender only delivers to the address that owns the
         * key, and it checks that by comparing the whole `to` string — so
         * "Nena Cruz <nena@example.com>" is refused where nena@example.com is
         * accepted, and the error it returns talks about verifying a domain
         * and says nothing about a name. That cost an hour once. The
         * recipient's own mail client shows them their own name anyway; the
         * name that matters in an inbox is the one on `from`. */
        $to = $toEmail;
        $payload = [
            'from' => (string) config('services.resend.from'),
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
        ];

        try {
            if (class_exists(\Resend::class)) {
                $sent = \Resend::client($key)->emails->send($payload);
                // The SDK hands back an object on some versions and an array
                // on others; both carry the id under the same name.
                $id = is_array($sent) ? ($sent['id'] ?? null) : ($sent->id ?? null);
                if ($id) {
                    return (string) $id;
                }
                $this->lastError = 'Resend accepted the call but returned no id.';

                return null;
            }

            $res = Http::withToken($key)
                ->acceptJson()
                ->timeout(20)
                ->post('https://api.resend.com/emails', $payload);

            if ($res->successful() && $res->json('id')) {
                return (string) $res->json('id');
            }

            // Their errors are readable and worth keeping verbatim — "domain
            // is not verified" is the whole answer to why nothing arrived.
            $this->lastError = trim(($res->json('name') ?: 'HTTP ' . $res->status())
                . ': ' . ($res->json('message') ?: $res->body()));

            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('ResendMailer: ' . $e->getMessage());

            return null;
        }
    }
}
