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
use App\Support\SignIn;

class RegisterController extends Controller
{
    public function __construct(private MailService $mail)
    {
    }

    public function show(Request $request)
    {
        // An ad may point here directly: its tags are kept for the account.
        \App\Support\SignupSource::remember($request);
        // A guide's gate sent them: the dashboard offers the page back (App\Support\ContentGate).
        \App\Support\ContentGate::remember($request->query('read'));

        return view('auth.signup', [
            'plan' => $request->query('plan'),
            // The guide they left, named on the form: they come back to it after the email link (WelcomeController).
            'reading' => \App\Support\ContentGate::waiting(),
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
        // The country first: the phone rule is the country's. Unknown or
        // missing, it is where the visitor is reading from.
        $country = \App\Support\Region::valid($request->input('country')) ?: \App\Support\Region::code();
        // While the international version is closed for maintenance, every
        // new account is a Philippine one (the form offers no other country).
        if (! \App\Support\Region::intlOpen()) {
            $country = \App\Support\Region::HOME;
        }
        $phoneRule = \App\Support\Region::phone($country);
        // Normalize phone: strip spaces, dashes and brackets before validating.
        $request->merge([
            'phone' => \App\Support\Region::cleanPhone($request->input('phone'), $country),
            'email' => trim((string) $request->input('email')),
            'country' => $country,
        ]);

        // A pending account holding this address is not a rival — it is this
        // same person coming back before their link arrived (or after it
        // expired). Resend instead of refusing.
        $pending = User::active()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $request->input('email'))])
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            // Their answer to "which best describes you" may have changed since the first try.
            if (array_key_exists((string) $request->input('occupation'), User::OCCUPATIONS)) {
                $pending->forceFill(['occupation' => (string) $request->input('occupation')])->saveQuietly();
            }
            $this->sendVerification($pending);
            WelcomeController::keep((int) $pending->id, $request);
            $request->session()->put('signup.email', $pending->email);

            return redirect()->route('verify.notice')
                ->with('success', 'Your registration is not complete yet: that email still waits to be confirmed. We sent you a new link.');
        }

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'regex:' . $phoneRule['regex']],
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
            // One box with a show button (2026-11-16): typing it twice cost signups and caught nothing the reset link does not.
            'password' => ['required', 'string', 'min:8'],
            // The End User License Agreement (2026-10-28), ticked.
            'eula' => ['accepted'],
            // Who they are (2026-11-19, the owner): farmer, student or other, for the newsletter's segments.
            'occupation' => ['required', 'in:' . implode(',', array_keys(User::OCCUPATIONS))],
        ], [
            'eula.accepted' => 'Please read and tick the agreement to create your account.',
            'occupation.required' => 'Please choose the one that fits you best.',
            'occupation.in' => 'Please choose the one that fits you best.',
            'phone.regex' => $phoneRule['error'],
            'phone.required' => 'Please enter your mobile number.',
            'firstName.required' => 'Please enter your first name.',
            'lastName.required' => 'Please enter your last name.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'That email address does not look right. Please check it.',
            'password.required' => 'Please choose a password.',
            'password.min' => 'Your password needs at least 8 characters.',
        ]);

        $user = User::create([
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'phone' => $data['phone'],
            'country' => $country,
            'email' => $data['email'],
            'password' => $data['password'],
            'occupation' => $data['occupation'],
            'status' => 'pending',
            'deleteStatus' => 1,
        ]);

        $this->sendVerification($user);
        \App\Support\SignupSource::record($user, $request);
        \App\Support\Legal::record((int) $user->id, 'eula', \App\Support\Legal::EULA_VERSION, null, $request);
        \App\Support\SiteAnalytics::signedUp((int) $user->id, $request, 'email');
        // The pages they read on the way here, now theirs (the admin panel's client sheet shows them).
        \App\Support\VisitorJourney::claim($user, $request, 'email');
        WelcomeController::keep((int) $user->id, $request);

        $request->session()->put('signup.email', $user->email);
        // One page view long: the inbox page reports the signup to the ads.
        $request->session()->flash('signup.fresh', true);
        if ($request->filled('plan')) {
            $request->session()->put('signup.plan', $request->input('plan'));
        }

        return redirect()->route('verify.notice');
    }

    /** The "check your inbox" page shown right after email signup. */
    public function notice(Request $request)
    {
        $email = $request->session()->get('signup.email');
        $owner = $email ? User::active()->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $email)])->first(['id', 'status']) : null;

        return view('auth.verify-notice', [
            'email' => $email,
            'reading' => \App\Support\ContentGate::waiting(),
            // An account that already had a login and is asked to confirm its address (2026-11-19), not a new signup.
            'existing' => $owner && $owner->status === 'active',
        ]);
    }

    /**
     * Whether the address on the "check your inbox" page was confirmed, maybe in another browser: the page asks every
     * few seconds and then offers the login. Only for the address this session signed up with, and only yes or no.
     */
    public function status(Request $request)
    {
        $email = (string) $request->session()->get('signup.email');
        $user = $email === '' ? null : User::active()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();

        // Confirmed means the address is proven now, not only that the account is active (2026-11-19).
        return response()->json(['confirmed' => (bool) ($user && $user->status === 'active' && ! \App\Support\EmailConfirm::needed($user))])
            ->header('Cache-Control', 'no-store');
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
            ->whereIn('status', ['pending', 'active'])
            ->first();
        // A new signup, or an account asked to confirm the address it already has (2026-11-19).
        if ($user && $user->status === 'active' && ! \App\Support\EmailConfirm::needed($user)) {
            $user = null;
        }

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

        $fresh = $user->status === 'pending';
        if ($fresh) {
            NewMemberWelcome::activate($user);
            \App\Support\SiteAnalytics::confirmed((int) $user->id, $request, 'email');
            \App\Support\VisitorJourney::confirmed((int) $user->id);
        } elseif ($user->status !== 'active') {
            return redirect()->route('login')->with('error', 'Your account is disabled.');
        } elseif (empty($user->emailVerifiedAt)) {
            // An account that had a login but never proved its address: proven now (2026-11-19).
            $user->forceFill(['emailVerifiedAt' => now()])->saveQuietly();
        }

        SignIn::user($user, true); // no box to tick at this door: kept for ten days
        $request->session()->regenerate();
        $user->forceFill(['currentSessionId' => $request->session()->getId()])->saveQuietly();

        // The link is often opened in another browser than the form (the mail app's own): the guide and the plan
        // the form was opened for come back from the account.
        $plan = WelcomeController::restore((int) $user->id, $request);
        $request->session()->forget('signup.email');
        // The registration is finished at this link, not at the form (2026-11-19): the next page tells the ads so.
        if ($fresh) {
            $request->session()->flash('signup.done', true);
        }
        $done = $fresh ? 'Your registration is complete. Welcome to anee.io!' : 'Email confirmed. Thank you!';

        if ($plan) {
            return redirect()->route('purchase.plans', ['plan' => $plan])
                ->with('success', $done . ' Pick a plan, or start free.');
        }

        // They signed up on a members' guide: they choose where to go, the guide first (2026-11-16).
        if ($fresh && \App\Support\ContentGate::waiting()) {
            return redirect()->route('signup.welcome');
        }
        // An account that asked for its link from a members' guide (verify.mine): straight back to that guide.
        if (! $fresh && ($w = \App\Support\ContentGate::waiting())) {
            $request->session()->forget('gate.read');

            return redirect()->to($w['url'])->with('success', $done);
        }

        return redirect()->route('app.dashboard')
            ->with('success', $fresh ? $done . ' You are on the free Libre plan.' : $done);
    }

    /**
     * A signed in account whose email is not confirmed asks for its link from a members' guide (2026-11-19: the
     * guides open to a confirmed email only). The guide is kept, so the link brings them back to it.
     */
    public function sendMine(Request $request)
    {
        $user = $request->user();
        \App\Support\ContentGate::remember((string) $request->input('read'));
        if (! empty($user->emailVerifiedAt)) {
            return back()->with('success', 'Your email is confirmed already.');
        }
        if (! \App\Support\EmailConfirm::reachable($user)) {
            return back()->with('gate.sent', 'This address cannot receive our mail. Tell us on the contact page and we fix it.');
        }
        $sent = \App\Support\EmailConfirm::send($user);

        return back()->with('gate.sent', $sent
            ? 'Sent. Look in your inbox for a mail from anee.io and tap the link.'
            : 'A link went out a minute ago. Look in your inbox, or ask again in a minute.');
    }

    private function sendVerification(User $user): void
    {
        // One helper for every door that mails the link (App\Support\EmailConfirm, 2026-11-19).
        \App\Support\EmailConfirm::send($user, true);
    }
}
