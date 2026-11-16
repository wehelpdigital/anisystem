<?php

namespace App\Http\Controllers;

use App\Models\AsScheduleWorker;
use App\Models\User;
use App\Models\WorkerGrant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Support\SignIn;

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

        $isNew = ! $user;
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
        SignIn::user($user, true); // no box to tick at this door: kept for ten days
        $request->session()->regenerate();
        $user->forceFill(['currentSessionId' => $request->session()->getId()])->saveQuietly();
        session(['activeBossId' => $grant->bossUserId]);

        // A login this invite just made gets its welcome (2026-11-16, the owner: "after registration, there's no
        // welcome to anee email being sent"). The address is proven: the invite reached it.
        if ($isNew) {
            try {
                app(\App\Services\MailService::class)->sendTemplateToUser('worker_welcome', $user, [
                    // "Isinama ka ni Ariel": the first name reads as people say it.
                    'bossName' => trim((string) optional($grant->boss)->firstName)
                        ?: (trim((string) optional($grant->boss)->full_name) ?: (string) strtok((string) optional($grant->boss)->email, '@')),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Worker welcome email failed for user ' . $user->id . ': ' . $e->getMessage());
            }
        }

        return redirect()->route('app.dashboard')->with('success', 'Welcome! You can now open your farm manager\'s seasons.');
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
