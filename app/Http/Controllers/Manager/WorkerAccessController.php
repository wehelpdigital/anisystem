<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AsScheduleWorker;
use App\Models\User;
use App\Models\WorkerGrant;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Boss-side management of worker logins (#25). A manager on the Boss/Lifetime
 * tier can give a worker a login, choose whether they get schedule view/edit
 * and community access, and (re)send the emailed set-password invite.
 */
class WorkerAccessController extends Controller
{
    public function __construct(private MailService $mail)
    {
    }

    /** Grant (or update) a worker's login access and send the invite email. */
    public function grant(Request $request)
    {
        $boss = $request->user();
        if (! $boss->canWorkerAccounts()) {
            return response()->json(['success' => false, 'message' => 'Worker logins are a Boss/Lifetime feature. Upgrade to enable them.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'scheduleWorkerId' => 'nullable|integer',
            'email'            => 'required|email|max:191',
            'scheduleAccess'   => 'required|in:none,view,edit',
            'canAddNotes'      => 'nullable|boolean',
            'communityAccess'  => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $email = mb_strtolower(trim($request->input('email')));

        // One grant per (boss, email). Reuse an existing row so re-granting just
        // updates the permissions and refreshes the invite.
        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('invitedEmail', $email)
            ->first() ?? new WorkerGrant(['bossUserId' => $boss->id, 'invitedEmail' => $email]);

        // Link an existing user if this email already has an account.
        $existing = User::where('deleteStatus', 1)->whereRaw('LOWER(email) = ?', [$email])->first();

        $grant->fill([
            'bossUserId'       => $boss->id,
            'invitedEmail'     => $email,
            'scheduleWorkerId' => $request->input('scheduleWorkerId'),
            'scheduleAccess'   => $request->input('scheduleAccess'),
            'canAddNotes'      => $request->boolean('canAddNotes'),
            'communityAccess'  => $request->boolean('communityAccess'),
            'deleteStatus'     => 1,
        ]);

        if ($existing) {
            // Account already exists → grant is active immediately.
            $grant->workerUserId = $existing->id;
            $grant->status = WorkerGrant::STATUS_ACTIVE;
            $grant->acceptedAt = now();
            $grant->inviteToken = null;
            $grant->save();
            $this->sendReadyEmail($existing->email, $existing->full_name ?: 'there', $boss);

            return response()->json([
                'success' => true,
                'message' => $existing->full_name . ' already has an account — access granted.',
                'data' => ['grant' => $this->grantData($grant)],
            ]);
        }

        // New invitee → pending until they set a password.
        $grant->status = WorkerGrant::STATUS_PENDING;
        $grant->inviteToken = Str::random(48);
        $grant->save();

        $this->sendInviteEmail($grant, $boss);

        return response()->json([
            'success' => true,
            'message' => 'Invite sent to ' . $email . '.',
            'data' => ['grant' => $this->grantData($grant)],
        ]);
    }

    /**
     * Boss-sets-password path: create the worker's login directly with a
     * password the boss chooses (so the boss can hand it over), and activate
     * the grant immediately — no emailed link needed. If the email already has
     * an account we never overwrite its password; we just grant access.
     */
    public function setPassword(Request $request)
    {
        $boss = $request->user();
        if (! $boss->canWorkerAccounts()) {
            return response()->json(['success' => false, 'message' => 'Worker logins are a Boss/Lifetime feature. Upgrade to enable them.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'scheduleWorkerId' => 'nullable|integer',
            'name'             => 'nullable|string|max:191',
            'email'            => 'required|email|max:191',
            'password'         => 'required|string|min:8|max:191',
            'scheduleAccess'   => 'required|in:none,view,edit',
            'canAddNotes'      => 'nullable|boolean',
            'communityAccess'  => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $email = mb_strtolower(trim($request->input('email')));

        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('invitedEmail', $email)
            ->first() ?? new WorkerGrant(['bossUserId' => $boss->id, 'invitedEmail' => $email]);

        $grant->fill([
            'bossUserId'       => $boss->id,
            'invitedEmail'     => $email,
            'scheduleWorkerId' => $request->input('scheduleWorkerId'),
            'scheduleAccess'   => $request->input('scheduleAccess'),
            'canAddNotes'      => $request->boolean('canAddNotes'),
            'communityAccess'  => $request->boolean('communityAccess'),
            'deleteStatus'     => 1,
        ]);

        $existing = User::where('deleteStatus', 1)->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing) {
            // Never overwrite an existing account's password — just grant access.
            $grant->workerUserId = $existing->id;
            $grant->status = WorkerGrant::STATUS_ACTIVE;
            $grant->acceptedAt = now();
            $grant->inviteToken = null;
            $grant->save();

            return response()->json([
                'success' => true,
                'message' => 'That email already has an account — access granted (their existing password is unchanged).',
                'data' => ['grant' => $this->grantData($grant)],
            ]);
        }

        [$first, $last] = $this->splitName((string) $request->input('name'), $email);

        // The User model hashes the password via its 'hashed' cast.
        $worker = User::create([
            'firstName'    => $first,
            'lastName'     => $last,
            'email'        => $email,
            'password'     => $request->input('password'),
            'status'       => 'active',
            'allowMessages' => 1,
            'deleteStatus' => 1,
        ]);

        $grant->workerUserId = $worker->id;
        $grant->status = WorkerGrant::STATUS_ACTIVE;
        $grant->acceptedAt = now();
        $grant->inviteToken = null;
        $grant->save();

        return response()->json([
            'success' => true,
            'message' => 'Login created for ' . $email . '. Share the email + password so they can sign in.',
            'data' => ['grant' => $this->grantData($grant)],
        ]);
    }

    /** Split a worker "name" into first/last, falling back to the email handle. */
    private function splitName(string $name, string $email): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            $name = ucfirst(explode('@', $email)[0] ?: 'Worker');
        }
        $pos = mb_strpos($name, ' ');

        return $pos === false ? [$name, ''] : [mb_substr($name, 0, $pos), mb_substr($name, $pos + 1)];
    }

    /** Compact grant state for the worker UI. */
    private function grantData(WorkerGrant $grant): array
    {
        return [
            'id' => $grant->id,
            'status' => $grant->status,
            'scheduleAccess' => $grant->scheduleAccess,
            'communityAccess' => (bool) $grant->communityAccess,
            'workerUserId' => $grant->workerUserId ? (int) $grant->workerUserId : null,
            'scheduleWorkerId' => $grant->scheduleWorkerId ? (int) $grant->scheduleWorkerId : null,
        ];
    }

    /** Revoke a worker's access. */
    public function revoke(Request $request)
    {
        $boss = $request->user();
        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('id', (int) $request->input('id'))
            ->first();
        if (! $grant) {
            return response()->json(['success' => false, 'message' => 'Grant not found.'], 404);
        }

        $grant->update(['status' => WorkerGrant::STATUS_REVOKED, 'deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Access revoked.']);
    }

    private function sendInviteEmail(WorkerGrant $grant, User $boss): void
    {
        $link = route('worker.invite.show', ['token' => $grant->inviteToken]);
        $bossName = $boss->full_name ?: 'Your farm manager';
        $html = '<p>Hi,</p>'
            . '<p><strong>' . e($bossName) . '</strong> has invited you to access their AniSystem cropping schedule as a worker.</p>'
            . '<p>Set your password to get started:</p>'
            . '<p><a href="' . e($link) . '" style="background:#4c8a39;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;display:inline-block;">Set my password</a></p>'
            . '<p>Or open this link:<br>' . e($link) . '</p>'
            . '<p>If you didn\'t expect this, you can ignore this email.</p>';
        $this->mail->send($grant->invitedEmail, 'New AniSystem worker', 'You\'ve been invited to AniSystem', $html);
    }

    private function sendReadyEmail(string $email, string $name, User $boss): void
    {
        $bossName = $boss->full_name ?: 'Your farm manager';
        $html = '<p>Hi ' . e($name) . ',</p>'
            . '<p><strong>' . e($bossName) . '</strong> has given your existing AniSystem account access to their farm.</p>'
            . '<p>Log in and use the farm switcher (next to your profile) to open it.</p>'
            . '<p><a href="' . e(route('login')) . '">Log in to AniSystem</a></p>';
        $this->mail->send($email, $name, 'Farm access granted on AniSystem', $html);
    }
}
