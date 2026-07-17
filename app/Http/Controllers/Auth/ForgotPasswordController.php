<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // No user enumeration: same flash whether or not the account exists.
        try {
            $user = User::active()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($request->input('email')))])
                ->first();

            if ($user) {
                // Broker creates the token; the User model override sends the
                // templated email (password_reset) via MailService.
                Password::broker()->sendResetLink(['email' => $user->email]);
            }
        } catch (\Throwable $e) {
            Log::warning('Password reset link failed: '.$e->getMessage());
        }

        return back()->with('success', 'If that email is registered with us, a password reset link has been sent. Please check your inbox.');
    }
}
