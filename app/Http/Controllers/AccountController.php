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

    /**
     * Switch which farm a worker is viewing (#25). bossId 0 = their own account.
     */
    public function switchFarm(Request $request)
    {
        $bossId = (int) $request->input('bossId');
        if ($bossId === 0) {
            $request->session()->forget('activeBossId');
        } else {
            $ok = \App\Models\WorkerGrant::active()
                ->where('workerUserId', $request->user()->id)
                ->where('bossUserId', $bossId)
                ->where('status', \App\Models\WorkerGrant::STATUS_ACTIVE)
                ->exists();
            if ($ok) {
                $request->session()->put('activeBossId', $bossId);
            }
        }

        return redirect()->route('sm.index');
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
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'profession' => ['nullable', 'string', 'max:60'],
            'yearsFarming' => ['nullable', 'integer', 'min:0', 'max:120'],
            'farmSize' => ['nullable', 'string', 'max:60'],
            'cropsGrown' => ['nullable', 'string', 'max:255'],
            'farmingMethod' => ['nullable', 'string', 'max:60'],
            'allowMessages' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], [
            'phone.regex' => 'Enter a valid PH mobile number in the format 09XXXXXXXXX (11 digits).',
            'cover.max' => 'The cover photo must be 8 MB or smaller.',
        ]);

        // Cover is a file, not a text column — pull it out before the mass update.
        unset($data['cover']);
        $data['allowMessages'] = $request->boolean('allowMessages');
        $data['headline'] = trim((string) $request->input('headline')) ?: null;

        if ($request->hasFile('cover')) {
            $data['coverPath'] = \App\Support\MediaOptimizer::storeImageAsWebp($request->file('cover'), 'community/covers');
        }

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
