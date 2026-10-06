<?php

namespace App\Http\Controllers;

use App\Services\AcumbamailService;
use App\Services\EmailVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * The email gate in front of the free tools (2026-10-07): the weed control
 * helper on /weeds and the finders on /pests and /diseases show their answer
 * blurred until the visitor gives a name and an email.
 *
 *   POST /tools/open  name, email, tool, website (a honeypot)
 *
 * The email must pass Reoon (App\Services\EmailVerifier). A good one joins
 * the anee.io subscribers list on Acumbamail as a lead, unless the list has
 * it already (AcumbamailService::addLeadIfNew), after the answer has gone
 * back, so the visitor waits on Reoon only. The page remembers the opening
 * in the browser; nothing is stored here.
 */
class ToolGateController extends Controller
{
    public const TOOLS = [
        'weeds' => 'Weed control helper',
        'pests' => 'Crop pest finder',
        'diseases' => 'Crop disease finder',
    ];

    public function open(Request $request)
    {
        if (trim((string) $request->input('website', '')) !== '') {
            return $this->json(false, 'Something went wrong. Please try again.', 422);
        }
        $data = $request->validate([
            // Letters in any language, spaces and the marks names carry: Ma. Theresa, O'Neil, Dela Cruz Jr.
            'name' => ['required', 'string', 'min:2', 'max:60', "regex:/^[\\pL\\pM][\\pL\\pM .,'\u{2019}-]*$/u"],
            'email' => 'required|string|max:190|email:rfc',
            'tool' => 'required|string|in:' . implode(',', array_keys(self::TOOLS)),
        ], [
            'name.required' => 'Please type your name.',
            'name.min' => 'Please type your name.',
            'name.max' => 'That name is too long.',
            'name.regex' => 'Please type your name using letters only.',
            'email.required' => 'Please type your email address.',
            'email.max' => 'That email address is too long.',
            'email.email' => 'That email address does not look right. Please check it.',
        ]);
        $email = mb_strtolower(trim($data['email']));
        $name = Str::limit(preg_replace('/\s+/u', ' ', trim(strip_tags($data['name']))), 60, '');

        // Each check costs a Reoon credit: a few tries an hour per visitor,
        // and a handful a day per address, are plenty for honest typing.
        $ip = (string) $request->ip();
        foreach ([['tools-h:' . $ip, 12, 3600], ['tools-d:' . $ip, 40, 86400], ['tools-e:' . sha1($email), 8, 86400]] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->json(false, 'That is a lot of tries. Please wait a while and try again.', 429);
            }
            RateLimiter::hit($key, $decay);
        }

        $verdict = app(EmailVerifier::class)->check($email);
        if (! $verdict['ok']) {
            return response()->json([
                'success' => false,
                'message' => $verdict['message'],
                'errors' => ['email' => [$verdict['message']]],
            ], 422);
        }
        // Reoon gave no answer: the address is judged on its form, plus a
        // mail server for its domain, so a plain typo still comes back.
        if (! $verdict['checked'] && ! $this->hasMailServer($email)) {
            $say = 'We could not find this email address. Please check the spelling.';

            return response()->json(['success' => false, 'message' => $say, 'errors' => ['email' => [$say]]], 422);
        }

        $tool = $data['tool'];
        app()->terminating(function () use ($email, $name, $tool) {
            [$first, $last] = $this->split($name);
            try {
                $f = config('acumbamail.fields');
                $done = app(AcumbamailService::class)->addLeadIfNew($email, [
                    (string) $f['first_name'] => $first,
                    (string) $f['last_name'] => $last,
                    // A field the list does not have is ignored on their side.
                    'Source' => self::TOOLS[$tool],
                ]);
                Log::info('Tool gate: ' . $tool . ' opened, list ' . $done);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        return response()->json(['success' => true, 'message' => 'Open', 'data' => ['firstName' => $this->split($name)[0]]]);
    }

    private function hasMailServer(string $email): bool
    {
        $domain = (string) Str::after($email, '@');
        try {
            return $domain !== '' && (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A'));
        } catch (\Throwable $e) {
            return true;
        }
    }

    /** "Juan dela Cruz" -> ["Juan", "dela Cruz"], "Ma. Theresa Santos" -> ["Ma. Theresa", "Santos"]. */
    private function split(string $name): array
    {
        $words = preg_split('/\s+/u', trim($name)) ?: [''];
        // A short first word with a period (Ma., Sta.) belongs to the next one.
        $take = count($words) > 2 && preg_match('/^\pL{1,3}\.$/u', $words[0]) ? 2 : 1;

        return [implode(' ', array_slice($words, 0, $take)), implode(' ', array_slice($words, $take))];
    }

    private function json(bool $ok, string $message, int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message], $status);
    }
}
