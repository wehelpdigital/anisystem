<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * reCAPTCHA Enterprise, checked on the server (config/recaptcha.php).
 *
 * An assessment is asked of Google for the token the page sent: valid, made
 * for this action, and scored at least min_score. When the project id and
 * API key are not set the check cannot be made, so the token is taken on
 * trust (a page whose script was blocked still gets through) and the fact is logged once a day: the
 * form keeps working and the rate limits carry the load.
 */
class Recaptcha
{
    public static function siteKey(): string
    {
        return (string) config('recaptcha.site_key');
    }

    public static function checkable(): bool
    {
        return config('recaptcha.project_id') !== '' && config('recaptcha.api_key') !== '' && self::siteKey() !== '';
    }

    /** @return array{ok: bool, score: ?float, why: string} */
    public static function verify(?string $token, string $action, ?string $ip = null): array
    {
        $token = trim((string) $token);
        if (self::siteKey() === '') {
            return ['ok' => true, 'score' => null, 'why' => 'no site key'];
        }
        if (! self::checkable()) {
            if (! Cache::has('recaptcha:unchecked')) {
                Cache::put('recaptcha:unchecked', 1, 86400);
                Log::warning('reCAPTCHA: RECAPTCHA_PROJECT_ID / RECAPTCHA_API_KEY not set; tokens are taken on trust.');
            }

            return ['ok' => true, 'score' => null, 'why' => 'unchecked'];
        }
        // Checkable: a page that sent no token (the script blocked, or a bot) fails.
        if ($token === '' || strlen($token) < 20) {
            return ['ok' => false, 'score' => null, 'why' => 'missing token'];
        }
        try {
            $res = Http::timeout(8)->acceptJson()->post(
                'https://recaptchaenterprise.googleapis.com/v1/projects/' . rawurlencode((string) config('recaptcha.project_id'))
                    . '/assessments?key=' . rawurlencode((string) config('recaptcha.api_key')),
                ['event' => array_filter([
                    'token' => $token,
                    'siteKey' => self::siteKey(),
                    'expectedAction' => $action,
                    'userIpAddress' => $ip,
                ])]
            );
            if (! $res->successful()) {
                // Google itself failing is not the visitor's fault.
                Log::warning('reCAPTCHA: assessment refused', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 300)]);

                return ['ok' => true, 'score' => null, 'why' => 'assessment unavailable'];
            }
            $j = $res->json();
            $valid = (bool) data_get($j, 'tokenProperties.valid');
            $act = (string) data_get($j, 'tokenProperties.action');
            $score = data_get($j, 'riskAnalysis.score');
            $score = $score === null ? null : (float) $score;
            if (! $valid) {
                return ['ok' => false, 'score' => $score, 'why' => 'invalid: ' . data_get($j, 'tokenProperties.invalidReason')];
            }
            if ($act !== '' && strcasecmp($act, $action) !== 0) {
                return ['ok' => false, 'score' => $score, 'why' => 'action ' . $act];
            }
            if ($score !== null && $score < (float) config('recaptcha.min_score', 0.5)) {
                return ['ok' => false, 'score' => $score, 'why' => 'low score'];
            }

            return ['ok' => true, 'score' => $score, 'why' => 'passed'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => true, 'score' => null, 'why' => 'assessment error'];
        }
    }
}
