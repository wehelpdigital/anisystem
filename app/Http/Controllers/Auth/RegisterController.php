<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function register(Request $request)
    {
        // Normalize phone: strip spaces and dashes before validating.
        $request->merge([
            'phone' => preg_replace('/[\s\-]+/', '', (string) $request->input('phone')),
            'email' => trim((string) $request->input('email')),
        ]);

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
            'status' => 'active',
            'deleteStatus' => 1,
        ]);

        // Free allowance so a new member can try the AI Technician before
        // deciding whether to buy credits.
        try {
            $freeCredits = (int) \App\Models\AiSetting::current()->freeCreditsOnSignup;
            if ($freeCredits > 0) {
                app(\App\Services\AiCreditService::class)
                    ->grant($user->id, $freeCredits, 'Welcome credits', 'signup');
            }
        } catch (\Throwable $e) {
            Log::warning('Welcome AI credits failed for user '.$user->id.': '.$e->getMessage());
        }

        try {
            $this->mail->sendTemplateToUser('registration_welcome', $user);
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed for user '.$user->id.': '.$e->getMessage());
        }

        Auth::login($user);
        $request->session()->regenerate();

        $params = $request->filled('plan') ? ['plan' => $request->input('plan')] : [];

        return redirect()->route('purchase.plans', $params)
            ->with('success', 'Welcome! Choose your plan to activate your account.');
    }
}
