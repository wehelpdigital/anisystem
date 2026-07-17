<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions)
    {
    }

    public function index(Request $request)
    {
        return view('account.index', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->merge([
            'phone' => preg_replace('/[\s\-]+/', '', (string) $request->input('phone')),
        ]);

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'phone.regex' => 'Enter a valid PH mobile number in the format 09XXXXXXXXX (11 digits).',
        ]);

        $request->user()->update($data);

        return redirect()->route('account.index')->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->update(['password' => $data['password']]);

        return redirect()->route('account.index')->with('success', 'Password updated.');
    }

    public function subscription(Request $request)
    {
        $user = $request->user();

        // Fresh sync against the mother system's order decisions so the page
        // always reflects the latest verify/reject state.
        $this->subscriptions->syncUser($user, force: true);

        $user->refresh();

        $subscription = $user->currentSubscription();
        $history = $user->subscriptions()->get();

        return view('account.subscription', [
            'user' => $user,
            'subscription' => $subscription,
            'history' => $history,
            'locked' => (bool) session('locked'),
        ]);
    }

    public function refreshSubscription(Request $request)
    {
        $this->subscriptions->syncUser($request->user(), force: true);

        return redirect()->route('account.subscription')->with('success', 'Status refreshed.');
    }
}
