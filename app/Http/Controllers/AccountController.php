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
     * Which hat, before the app opens.
     *
     * A person who farms their own land and also works two days a week on a
     * neighbour's saw one of those two farms — whichever the app guessed —
     * and had to find a switcher in the header to correct it. Asking once, on
     * the way in, costs a tap and removes the guess.
     */
    public function choose(Request $request)
    {
        $hats = \App\Support\UserHats::for($request->user());

        // Nothing to choose between: go where they were going. The admin
        // site is not one of the options — see UserHats::adminUrl.
        if (count($hats) < 2) {
            return redirect()->intended(route('app.dashboard'));
        }

        return view('auth.choose', [
            'hats' => $hats,
            'adminUrl' => \App\Support\UserHats::adminUrl($request->user()),
            'user' => $request->user(),
        ]);
    }

    /** Apply the chosen hat and open the app in it. */
    public function chooseApply(Request $request)
    {
        $key = (string) $request->input('hat');
        $hats = \App\Support\UserHats::for($request->user());
        $hat = collect($hats)->firstWhere('key', $key);

        if (! $hat) {
            return redirect()->route('account.choose');
        }

        if ($hat['kind'] === 'worker') {
            $request->session()->put('activeBossId', $hat['bossId']);
        } else {
            $request->session()->forget('activeBossId');
        }

        // Asked and answered — the chooser does not appear again this session
        // unless it is asked for by name.
        $request->session()->put('hatChosen', $key);

        return redirect()->route('app.dashboard');
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
            'coverPos' => ['nullable', 'integer', 'min:0', 'max:100'],
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

        // Which band of the banner shows. Only meaningful alongside a photo,
        // but harmless to keep either way — a later upload resets it to 50
        // from the page, because a new photo has a new middle.
        if ($request->filled('coverPos')) {
            $data['coverPos'] = max(0, min(100, (int) $request->input('coverPos')));
        }

        $request->user()->update($data);

        return redirect()->route('account.index')->with('success', 'Profile updated.');
    }

    /**
     * Set or clear the profile photo.
     *
     * Its own endpoint rather than a field on the profile form: a picture is
     * the one thing people change on its own, and making them scroll to a
     * Save button to see their own face is the sort of friction that stops
     * them bothering. The browser has already shrunk it; this compresses
     * again, because what a browser sends is a courtesy, not a promise.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'clear' => ['nullable', 'boolean'],
        ], [
            'avatar.max' => 'The photo must be 8 MB or smaller.',
        ]);

        $user = $request->user();

        if ($request->boolean('clear')) {
            $user->update(['avatarPath' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Photo removed — your initials will show instead.',
                'data' => ['url' => null],
            ]);
        }

        if (! $request->hasFile('avatar')) {
            return response()->json(['success' => false, 'message' => 'No photo was sent.'], 422);
        }

        // 512 is twice the biggest circle the app draws, which keeps it sharp
        // on a retina screen without storing a wall poster.
        // 1024, not 512: the avatar viewer shows a face at ~670 device pixels
        // on a phone, and a 512px master arrives there already soft.
        $path = \App\Support\MediaOptimizer::storeImageAsWebp($request->file('avatar'), 'community/avatars', 1024, 86);
        $user->update(['avatarPath' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Photo updated.',
            'data' => ['url' => \App\Support\MediaStore::url($path)],
        ]);
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

        /* The credit ledger, newest first.
         *
         * Paginated on its own page name so moving through it does not disturb
         * anything else the page is showing, and appended with the query string
         * so the tab a reader is on survives page two. */
        $credits = \App\Models\AiCreditLedger::active()
            ->where('userId', $user->id)
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'credits')
            ->withQueryString();

        return view('account.subscription', [
            'user' => $user,
            'subscription' => $subscription,
            'history' => $history,
            'locked' => (bool) session('locked'),
            'credits' => $credits,
            'creditBalance' => app(\App\Services\AiCreditService::class)->balance($user->id),
            'creditsUnlimited' => app(\App\Services\AiCreditService::class)->unlimited($user->id),
        ]);
    }

    public function refreshSubscription(Request $request)
    {
        $this->subscriptions->syncUser($request->user(), force: true);

        return redirect()->route('account.subscription')->with('success', 'Status refreshed.');
    }
}
