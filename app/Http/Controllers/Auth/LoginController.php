<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SuperAdminBridge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::active()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($credentials['email']))])
            ->first();

        $authenticated = $user && Hash::check($credentials['password'], $user->password);

        if (! $authenticated) {
            // Cross-app sign-in: a shared mother-site super admin may use their
            // admin credentials, bridged to a normal anisystem member (their own
            // schedules + full community access). Sessions stay isolated — the
            // apps use different cookies/tables (see config/session.php).
            $bridged = SuperAdminBridge::attempt($credentials['email'], $credentials['password']);
            if ($bridged) {
                $user = $bridged;
                $authenticated = true;
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Your account is disabled.',
            ]);
        }

        // Always issue the persistent "remember" cookie. If the database-backed
        // session is ever transiently lost (DB hiccup, connection cap), Laravel
        // re-authenticates from this cookie so the user is never bounced to login.
        Auth::login($user, true);

        $request->session()->regenerate();

        // Claim the single active-session slot for this login (see
        // EnforceSingleSession) — any other open session is superseded.
        $user->forceFill(['currentSessionId' => $request->session()->getId()])->saveQuietly();

        return redirect()->intended(route('app.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
