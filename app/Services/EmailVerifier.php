<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Is this email address real and worth mailing? Asked of Reoon Email
 * Verifier (https://emailverifier.reoon.com/api/v1/verify) before a visitor
 * of the free tools joins the anee.io subscribers list (2026-10-07).
 *
 * Two looks, as Reoon offers them:
 *
 *   quick  under half a second: the form, the domain's mail server, a
 *          throwaway or trap address. A typo in the domain ends here.
 *   power  a few seconds (a Yahoo inbox took 17 s in a test): whether the
 *          inbox itself exists. A made up Gmail name passes quick and fails
 *          here, so it is the look that keeps the list clean.
 *
 * The power look waits POWER_WAIT seconds at most, then the quick answer
 * stands. A clear answer is kept for a month so the same address never pays
 * twice. And Reoon being down, slow or out of credits never turns a visitor
 * away: the address is then judged on its form alone, and the log says so.
 */
class EmailVerifier
{
    private const URL = 'https://emailverifier.reoon.com/api/v1/verify';
    private const QUICK_WAIT = 6;
    private const POWER_WAIT = 12;
    private const KEEP_DAYS = 30;

    /** Power statuses that mean the mail would arrive (or cannot be told). */
    private const GOOD = ['safe', 'valid', 'catch_all', 'role_account', 'unknown'];

    /** What a visitor is told for each refusal. Plain words, no blame. */
    private const SAY = [
        'invalid' => 'We could not find this email address. Please check the spelling.',
        'disabled' => 'This email address is no longer active. Please use another one.',
        'disposable' => 'Please use your own email address, not a temporary one.',
        'spamtrap' => 'Please use another email address.',
        'inbox_full' => 'This inbox is full, so our emails would not reach you. Please use another address.',
    ];

    public function configured(): bool
    {
        return (string) config('services.reoon.key') !== '';
    }

    /**
     * @return array{ok: bool, status: string, message: ?string, checked: bool}
     *         checked = false when Reoon gave no answer and only the form was judged.
     */
    public function check(string $email): array
    {
        $email = mb_strtolower(trim($email));
        if (! $this->configured()) {
            return $this->verdict('unchecked', false);
        }
        $key = 'reoon:' . sha1($email);
        if (is_array($kept = Cache::get($key))) {
            return $kept;
        }

        $quick = $this->ask($email, 'quick', self::QUICK_WAIT);
        if ($quick === null) {
            return $this->verdict('unchecked', false);
        }
        if ($quick !== 'valid') {
            return $this->keep($key, $this->verdict($quick, true));
        }
        $power = $this->ask($email, 'power', self::POWER_WAIT);
        if ($power === null) {
            // Too slow or no answer: the quick look said valid, and it stands.
            return $this->verdict('valid', true);
        }

        return $this->keep($key, $this->verdict($power, true));
    }

    private function verdict(string $status, bool $checked): array
    {
        $ok = ! $checked || in_array($status, self::GOOD, true) || ! isset(self::SAY[$status]);

        return ['ok' => $ok, 'status' => $status, 'message' => $ok ? null : self::SAY[$status], 'checked' => $checked];
    }

    private function keep(string $key, array $verdict): array
    {
        Cache::put($key, $verdict, now()->addDays(self::KEEP_DAYS));

        return $verdict;
    }

    /** One look. The status Reoon gave, or null when it gave none. */
    private function ask(string $email, string $mode, int $wait): ?string
    {
        try {
            $res = Http::timeout($wait)->connectTimeout(4)->acceptJson()->get(self::URL, [
                'email' => $email,
                'key' => (string) config('services.reoon.key'),
                'mode' => $mode,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Reoon ' . $mode . ' check gave no answer: ' . $e->getMessage());

            return null;
        }
        $status = $res->successful() ? (string) ($res->json('status') ?? '') : '';
        if ($status === '') {
            Log::warning('Reoon ' . $mode . ' check answered ' . $res->status() . ': ' . mb_substr($res->body(), 0, 300));

            return null;
        }

        return $status;
    }
}
