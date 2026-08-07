<?php

namespace App\Http\Controllers;

use App\Models\AsScheduleWorker;
use App\Models\User;
use App\Models\WorkerGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Public worker-invite flow (#25): a worker follows the emailed link, sets a
 * password, and their login is created + the grant activated.
 */
class WorkerInviteController extends Controller
{
    public function show(string $token)
    {
        $grant = $this->resolve($token);

        return view('auth.worker-invite', [
            'token' => $token,
            'email' => $grant->invitedEmail,
            'bossName' => optional($grant->boss)->full_name ?: 'Your farm manager',
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $grant = $this->resolve($token);

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName'  => ['nullable', 'string', 'max:100'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Link an existing account or create a fresh worker login.
        $user = User::where('deleteStatus', 1)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($grant->invitedEmail)])
            ->first();

        if (! $user) {
            $user = new User();
            $user->email = $grant->invitedEmail;
            $user->firstName = $data['firstName'];
            $user->lastName = $data['lastName'] ?? '';
            $user->status = 'active';
            $user->deleteStatus = 1;
        }
        $user->password = Hash::make($data['password']);
        $user->save();

        $grant->update([
            'workerUserId' => $user->id,
            'status' => WorkerGrant::STATUS_ACTIVE,
            'acceptedAt' => now(),
            'inviteToken' => null,
        ]);

        // Log the worker in and drop them into this farm's context.
        Auth::login($user, true);
        $request->session()->regenerate();
        $user->forceFill(['currentSessionId' => $request->session()->getId()])->saveQuietly();
        session(['activeBossId' => $grant->bossUserId]);

        return redirect()->route('app.dashboard')->with('success', 'Welcome! You now have access to your farm manager\'s schedules.');
    }

    private function resolve(string $token): WorkerGrant
    {
        $grant = WorkerGrant::active()
            ->where('inviteToken', $token)
            ->where('status', WorkerGrant::STATUS_PENDING)
            ->first();
        if (! $grant) {
            throw ValidationException::withMessages(['token' => 'This invite link is no longer valid.']);
        }

        return $grant;
    }
}
