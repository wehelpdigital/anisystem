<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailService;
use App\Support\NewMemberWelcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class RegisterController extends Controller
{
    public function __construct(private MailService $mail)
    {
    }

    public function show(Request $request)
    {
        return view('auth.signup', [
            'plan' => $request->query('plan'),
        ]);
    }

    /**
     * Email signup: the account is born 'pending' and nobody is logged in —
     * the door opens from the link in the verification email. Until that
     * click the address is unproven, and an unproven address gets no
     * credits, no welcome mail, no session.
     */
    public function register(Request $request)
    {
        // Normalize phone: strip spaces and dashes before validating.
        $request->merge([
            'phone' => preg_replace('/[\s\-]+/', '', (string) $request->input('phone')),
            'email' => trim((string) $request->input('email')),
        ]);

        // A pending account holding this address is not a rival — it is this
        // same person coming back before their link arrived (or after it
        // expired). Resend instead of refusing.
        $pending = User::active()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $request->input('email'))])
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            $this->sendVerification($pending);
            $request->session()->put('signup.email', $pending->email);

            return redirect()->route('verify.notice')
                ->with('success', 'That email is already waiting on its confirmation — we sent a fresh link.');
        }

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^09\d{9}$/'],
            'email' => [
                'required',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    $exists = User::active()
                        ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $value)])
                        ->exists();
                    if ($exists) {
                        $fail('An account with this email already exists. Please log in instead.');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'phone.regex' => 'Enter a valid PH mobile number in the format 09XXXXXXXXX (11 digits).',
        ]);

        $user = User::create([
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'pending',
            'deleteStatus' => 1,
        ]);

        $this->sendVerification($user);

        $request->session()->put('signup.email', $user->email);
        if ($request->filled('plan')) {
            $request->session()->put('signup.plan', $request->input('plan'));
        }

        return redirect()->route('verify.notice');
    }

    /** The "check your inbox" page shown right after email signup. */
    public function notice(Request $request)
    {
        return view('auth.verify-notice', [
            'email' => $request->session()->get('signup.email'),
        ]);
    }

    /** Resend the confirmation link — one a minute per address, tops. */
    public function resend(Request $request)
    {
        $email = trim((string) ($request->input('email') ?: $request->session()->get('signup.email')));

        if ($email === '') {
            return redirect()->route('signup');
        }

        $user = User::active()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->where('status', 'pending')
            ->first();

        // Same words whether or not the address exists — this page must not
        // double as an address directory.
        if ($user && Cache::add('verify-resend:'.$user->id, 1, 60)) {
            $this->sendVerification($user);
        }

        $request->session()->put('signup.email', $email);

        return redirect()->route('verify.notice')
            ->with('success', 'If that address has a pending account, a fresh link is on its way.');
    }

    /**
     * The click that proves the address. Signed and time-boxed; the hash ties
     * the link to the address it was mailed to, so a link keeps working after
     * a resend but never for a different account.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::active()->find($id);

        if (! $user || ! hash_equals(sha1(mb_strtolower($user->email)), $hash)) {
            return redirect()->route('signup')
                ->with('error', 'That confirmation link is not valid. Sign up again to get a fresh one.');
        }

        if ($user->status === 'pending') {
            NewMemberWelcome::activate($user);
        } elseif ($user->status !== 'active') {
            return redirect()->route('login')->with('error', 'Your account is disabled.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['currentSessionId' => $request->session()->getId()])->saveQuietly();

        $plan = $request->session()->pull('signup.plan');
        $request->session()->forget('signup.email');

        if ($plan) {
            return redirect()->route('purchase.plans', ['plan' => $plan])
                ->with('success', 'Email confirmed — welcome to anee.io! Pick your plan to upgrade, or start free.');
        }

        return redirect()->route('app.dashboard')
            ->with('success', 'Email confirmed — welcome to anee.io! You are on the free Libre plan.');
    }

    private function sendVerification(User $user): void
    {
        $link = URL::temporarySignedRoute('verify.email', now()->addDays(3), [
            'id' => $user->id,
            'hash' => sha1(mb_strtolower($user->email)),
        ]);

        $html = view('emails.verify-email', [
            'firstName' => $user->firstName,
            'link' => $link,
        ])->render();

        $this->mail->send($user->email, $user->full_name, 'Confirm your email — anee.io', $html, [
            'templateKey' => 'email_verification',
            'userId' => $user->id,
        ]);
    }
}
