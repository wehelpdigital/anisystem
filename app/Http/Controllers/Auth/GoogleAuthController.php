<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\NewMemberWelcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "Continue with Google", hand-rolled: redirect out with a state nonce,
 * trade the returned code for tokens, read the profile, and let the person
 * in. Google vouching for an address counts as email confirmation — a
 * pending email-signup that comes back through Google is activated on the
 * spot, and a brand-new Google account is born active.
 *
 * Renders nowhere unless services.google.client_id is configured.
 */
class GoogleAuthController extends Controller
{
    public function redirect(Request $request)
    {
        if (blank(config('services.google.client_id'))) {
            return redirect()->route('login');
        }

        $state = Str::random(40);
        $request->session()->put('google.state', $state);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request)
    {
        $state = $request->session()->pull('google.state');

        if ($request->filled('error') || blank($request->input('code'))
            || blank($state) || ! hash_equals($state, (string) $request->input('state'))) {
            return redirect()->route('login')->with('error', 'Google sign-in was cancelled or did not go through. Please try again.');
        }

        try {
            $tokens = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $request->input('code'),
                'grant_type' => 'authorization_code',
                'redirect_uri' => route('google.callback'),
            ])->throw()->json();

            $profile = Http::withToken($tokens['access_token'])
                ->get('https://www.googleapis.com/oauth2/v3/userinfo')
                ->throw()->json();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth failed: '.$e->getMessage());

            return redirect()->route('login')->with('error', 'Google sign-in did not go through. Please try again.');
        }

        $email = trim((string) ($profile['email'] ?? ''));
        $sub = (string) ($profile['sub'] ?? '');

        if ($email === '' || $sub === '' || ! ($profile['email_verified'] ?? false)) {
            return redirect()->route('login')->with('error', 'Google did not share a verified email for that account.');
        }

        $user = User::active()->where('googleId', $sub)->first()
            ?? User::active()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();

        if ($user && ! in_array($user->status, ['active', 'pending'], true)) {
            return redirect()->route('login')->with('error', 'Your account is disabled.');
        }

        if ($user) {
            $patch = [];
            if (blank($user->googleId)) {
                $patch['googleId'] = $sub;
            }
            if (blank($user->emailVerifiedAt)) {
                $patch['emailVerifiedAt'] = now();
            }
            if ($patch !== []) {
                $user->forceFill($patch)->saveQuietly();
            }
            if ($user->status === 'pending') {
                // Google just proved the address the emailed link was waiting on.
                NewMemberWelcome::activate($user);
            }
        } else {
            $user = User::create([
                'firstName' => trim((string) ($profile['given_name'] ?? '')) ?: Str::before($email, '@'),
                'lastName' => trim((string) ($profile['family_name'] ?? '')),
                'email' => $email,
                'googleId' => $sub,
                // Nobody knows this password, and that is the point — the
                // account opens with Google (or a password reset later).
                'password' => Str::random(40),
                'status' => 'pending',
                'deleteStatus' => 1,
            ]);
            NewMemberWelcome::activate($user);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['currentSessionId' => $request->session()->getId()])->saveQuietly();

        if (\App\Support\UserHats::needsChoice($user)) {
            return redirect()->route('account.choose');
        }

        return redirect()->intended(route('app.dashboard'));
    }
}
