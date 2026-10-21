<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AsCroppingSchedule;
use App\Models\AsGlobalWorker;
use App\Models\AsScheduleWorker;
use App\Models\User;
use App\Models\WorkerGrant;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Boss-side management of worker logins (#25). A manager on the Boss/Lifetime
 * tier can give a worker a login, choose whether they get schedule view/edit
 * and community access, and (re)send the emailed set-password invite.
 *
 * Two pages call these: a season's Workers page (by the season card) and the
 * farm's roster, Global Workers (by the roster person, 2026-10-18). One grant
 * per person either way; see linkFor() and WorkerGrant::findFor().
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
        // Handing out logins is the farm owner's act, not a worker's.
        //
        // These three read $request->user() rather than the effective owner,
        // so a worker standing on the boss's Workers page was writing grants
        // — and, through setPassword, whole new accounts with a password of
        // their choosing — under their own name. Not an escalation into the
        // boss's farm, but an account-creation primitive nobody meant to
        // leave open.
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the farm owner can manage worker logins.',
            ], 403);
        }
        if (! $boss->canWorkerAccounts()) {
            \App\Support\Tier::denyFor('workerLogins', 'Worker logins and access levels come with {plan}.', \App\Support\Tier::of($boss));
        }

        $validator = Validator::make($request->all(), [
            'scheduleWorkerId' => 'nullable|integer',
            'globalWorkerId'   => 'nullable|integer',
            'email'            => 'required|email|max:191',
            'scheduleAccess'   => 'required|in:view,edit',
            'communityAccess'  => 'nullable|boolean',
            'notesAccess'      => 'nullable|in:none,view,edit',
            'reportsAccess'    => 'nullable|in:none,view,edit',
            'inventoryAccess'  => 'nullable|in:none,view,edit',
            'observationsAccess' => 'nullable|in:none,view,edit',
            'mapsAccess'       => 'nullable|in:none,view,edit',
            'drawAccess'       => 'nullable|in:none,view,edit',
            'aiAccess'         => 'nullable|boolean',
            'cameraAccess'     => 'nullable|boolean',
            'videoAccess'      => 'nullable|boolean',
            'voiceAccess'      => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please check what you entered.', 'errors' => $validator->errors()], 422);
        }

        $email = mb_strtolower(trim($request->input('email')));
        if ($no = $this->refuseOwnEmail($boss, $email)) {
            return $no;
        }

        // One grant per (boss, email). Reuse an existing row so re-granting just
        // updates the permissions and refreshes the invite.
        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('invitedEmail', $email)
            ->first() ?? new WorkerGrant(['bossUserId' => $boss->id, 'invitedEmail' => $email]);

        $link = $this->linkFor($request, $boss, $grant);
        if ($link instanceof \Illuminate\Http\JsonResponse) {
            return $link;
        }

        // Link an existing user if this email already has an account.
        $existing = User::where('deleteStatus', 1)->whereRaw('LOWER(email) = ?', [$email])->first();

        $grant->fill([
            'bossUserId'       => $boss->id,
            'invitedEmail'     => $email,
            'scheduleWorkerId' => $link[0],
            'globalWorkerId'   => $link[1],
            'scheduleAccess'   => $request->input('scheduleAccess'),
            'communityAccess'  => $request->boolean('communityAccess'),
            /* The module rights, as the owner set them. canAddNotes is
             * written from notesAccess rather than read from the form: it is
             * the same permission under an older name, and two columns that
             * can disagree is how a setting starts lying. */
            'notesAccess'      => $request->input('notesAccess', 'view'),
            'reportsAccess'    => $request->input('reportsAccess', 'view'),
            'inventoryAccess'  => $request->input('inventoryAccess', 'none'),
            'observationsAccess' => $request->input('observationsAccess', 'none'),
            'mapsAccess'       => $request->input('mapsAccess', 'none'),
            'drawAccess'       => $request->input('drawAccess', 'none'),
            'aiAccess'         => $request->boolean('aiAccess'),
            'cameraAccess'     => $request->boolean('cameraAccess'),
            'videoAccess'      => $request->boolean('videoAccess'),
            'voiceAccess'      => $request->boolean('voiceAccess'),
            'canAddNotes'      => $request->input('notesAccess', 'view') === 'edit',
            'deleteStatus'     => 1,
        ]);

        if ($existing) {
            // Account already exists → grant is active immediately.
            $grant->workerUserId = $existing->id;
            $grant->status = WorkerGrant::STATUS_ACTIVE;
            $grant->acceptedAt = now();
            $grant->inviteToken = null;
            $grant->save();
            $this->audit($request, $grant);
            $this->sendReadyEmail($existing->email, $existing->full_name ?: 'there', $boss);

            return response()->json([
                'success' => true,
                'message' => $existing->full_name . ' already has an account. Access given.',
                'data' => ['grant' => $this->grantData($grant)],
            ]);
        }

        // New invitee → pending until they set a password.
        $grant->status = WorkerGrant::STATUS_PENDING;
        $grant->inviteToken = Str::random(48);
        $grant->save();
        $this->audit($request, $grant);

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
        // Handing out logins is the farm owner's act, not a worker's.
        //
        // These three read $request->user() rather than the effective owner,
        // so a worker standing on the boss's Workers page was writing grants
        // — and, through setPassword, whole new accounts with a password of
        // their choosing — under their own name. Not an escalation into the
        // boss's farm, but an account-creation primitive nobody meant to
        // leave open.
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the farm owner can manage worker logins.',
            ], 403);
        }
        if (! $boss->canWorkerAccounts()) {
            \App\Support\Tier::denyFor('workerLogins', 'Worker logins and access levels come with {plan}.', \App\Support\Tier::of($boss));
        }

        $validator = Validator::make($request->all(), [
            'scheduleWorkerId' => 'nullable|integer',
            'globalWorkerId'   => 'nullable|integer',
            'name'             => 'nullable|string|max:191',
            'email'            => 'required|email|max:191',
            'password'         => 'required|string|min:8|max:191',
            'scheduleAccess'   => 'required|in:view,edit',
            'communityAccess'  => 'nullable|boolean',
            'notesAccess'      => 'nullable|in:none,view,edit',
            'reportsAccess'    => 'nullable|in:none,view,edit',
            'inventoryAccess'  => 'nullable|in:none,view,edit',
            'observationsAccess' => 'nullable|in:none,view,edit',
            'mapsAccess'       => 'nullable|in:none,view,edit',
            'drawAccess'       => 'nullable|in:none,view,edit',
            'aiAccess'         => 'nullable|boolean',
            'cameraAccess'     => 'nullable|boolean',
            'videoAccess'      => 'nullable|boolean',
            'voiceAccess'      => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please check what you entered.', 'errors' => $validator->errors()], 422);
        }

        $email = mb_strtolower(trim($request->input('email')));
        if ($no = $this->refuseOwnEmail($boss, $email)) {
            return $no;
        }

        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('invitedEmail', $email)
            ->first() ?? new WorkerGrant(['bossUserId' => $boss->id, 'invitedEmail' => $email]);

        $link = $this->linkFor($request, $boss, $grant);
        if ($link instanceof \Illuminate\Http\JsonResponse) {
            return $link;
        }

        $grant->fill([
            'bossUserId'       => $boss->id,
            'invitedEmail'     => $email,
            'scheduleWorkerId' => $link[0],
            'globalWorkerId'   => $link[1],
            'scheduleAccess'   => $request->input('scheduleAccess'),
            'communityAccess'  => $request->boolean('communityAccess'),
            /* The module rights, as the owner set them. canAddNotes is
             * written from notesAccess rather than read from the form: it is
             * the same permission under an older name, and two columns that
             * can disagree is how a setting starts lying. */
            'notesAccess'      => $request->input('notesAccess', 'view'),
            'reportsAccess'    => $request->input('reportsAccess', 'view'),
            'inventoryAccess'  => $request->input('inventoryAccess', 'none'),
            'observationsAccess' => $request->input('observationsAccess', 'none'),
            'mapsAccess'       => $request->input('mapsAccess', 'none'),
            'drawAccess'       => $request->input('drawAccess', 'none'),
            'aiAccess'         => $request->boolean('aiAccess'),
            'cameraAccess'     => $request->boolean('cameraAccess'),
            'videoAccess'      => $request->boolean('videoAccess'),
            'voiceAccess'      => $request->boolean('voiceAccess'),
            'canAddNotes'      => $request->input('notesAccess', 'view') === 'edit',
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
            $this->audit($request, $grant);

            return response()->json([
                'success' => true,
                'message' => 'That email already has an account. Access given, and their password stays the same.',
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
        $this->audit($request, $grant);

        return response()->json([
            'success' => true,
            'message' => 'Login created for ' . $email . '. Give them the email and password so they can log in.',
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
        return \App\Support\WorkerGrantState::withLinks($grant);
    }

    /**
     * Who a grant is about: [season card id, roster person id].
     *
     * Two pages give logins now, a season's Workers page (it sends the card)
     * and the farm's roster, Global Workers (it sends the person), and both
     * must end at the same grant. A card brings its person along (every card
     * is linked to the roster); a person brings one of their cards, so the
     * worker's own ticks and the invite's name keep working: the card the
     * grant already names when it is theirs, else their newest card on an
     * open season, else their newest card. A person on no season yet gives
     * a grant with no card, which opens no season until they are added to
     * one (WorkerContext::visibleScheduleIdsFor). Neither sent keeps what the
     * grant already says.
     *
     * Both are checked against this owner, so a grant can only point at the
     * owner's own cards and roster.
     *
     * @return array{0: ?int, 1: ?int}|\Illuminate\Http\JsonResponse
     */
    private function linkFor(Request $request, User $boss, WorkerGrant $grant)
    {
        $card = null;
        if ($request->filled('scheduleWorkerId')) {
            $card = AsScheduleWorker::active()->where('id', (int) $request->input('scheduleWorkerId'))
                ->whereIn('croppingScheduleId', AsCroppingSchedule::where('anisystemUserId', $boss->id)->select('id'))
                ->first();
            if (! $card) {
                return response()->json(['success' => false, 'message' => 'That worker could not be found.'], 422);
            }
        }
        $personId = null;
        if ($request->filled('globalWorkerId')) {
            $person = AsGlobalWorker::where('id', (int) $request->input('globalWorkerId'))
                ->where('userId', $boss->id)->where('deleteStatus', 1)->first();
            if (! $person) {
                return response()->json(['success' => false, 'message' => 'That worker could not be found.'], 422);
            }
            $personId = (int) $person->id;
        } elseif ($card && $card->globalWorkerId) {
            $personId = (int) $card->globalWorkerId;
        }

        if ($card) {
            return [(int) $card->id, $personId];
        }
        if (! $personId) {
            return [$grant->scheduleWorkerId ? (int) $grant->scheduleWorkerId : null, $grant->globalWorkerId ? (int) $grant->globalWorkerId : null];
        }

        $cardIds = AsScheduleWorker::active()
            ->join('as_cropping_schedules as s', 's.id', '=', 'as_schedule_workers.croppingScheduleId')
            ->where('as_schedule_workers.globalWorkerId', $personId)
            ->where('s.anisystemUserId', $boss->id)->where('s.deleteStatus', 1)
            ->orderByRaw('CASE WHEN s.status IN (?, ?) THEN 1 ELSE 0 END', [AsCroppingSchedule::STATUS_COMPLETED, AsCroppingSchedule::STATUS_ARCHIVED])
            ->orderByDesc('as_schedule_workers.id')
            ->pluck('as_schedule_workers.id')->map(fn ($id) => (int) $id);
        $keep = $grant->scheduleWorkerId && $cardIds->contains((int) $grant->scheduleWorkerId);

        return [$keep ? (int) $grant->scheduleWorkerId : $cardIds->first(), $personId];
    }

    /** A login is for somebody else: the owner's own address would make them a worker on their own farm. */
    private function refuseOwnEmail(User $boss, string $email): ?\Illuminate\Http\JsonResponse
    {
        if ($email !== mb_strtolower(trim((string) $boss->email))) {
            return null;
        }

        return response()->json(['success' => false, 'message' => 'That is your own email. Use the email your worker signs in with.'], 422);
    }

    /**
     * The season's Logs diary: a login given, changed or taken away is written
     * in the book of the season the grant's card is on, from either page
     * (RecordsScheduleActivity reads auditScheduleId). These routes never
     * resolve a season themselves, which is why the diary had none of them.
     */
    private function audit(Request $request, WorkerGrant $grant): void
    {
        if (! $grant->scheduleWorkerId) {
            return;
        }
        $seasonId = AsScheduleWorker::query()->where('as_schedule_workers.id', (int) $grant->scheduleWorkerId)
            ->join('as_cropping_schedules as s', 's.id', '=', 'as_schedule_workers.croppingScheduleId')
            ->where('s.anisystemUserId', (int) $grant->bossUserId)
            ->value('s.id');
        if ($seasonId) {
            $request->attributes->set('auditScheduleId', (int) $seasonId);
        }
    }

    /** The worker's name for an email: their season card, else their roster card, else what is known. */
    private function nameFor(WorkerGrant $grant, ?User $worker = null): string
    {
        $name = optional($grant->scheduleWorkerId ? AsScheduleWorker::find($grant->scheduleWorkerId) : null)->workerName
            ?: optional($grant->globalWorkerId ? AsGlobalWorker::find($grant->globalWorkerId) : null)->workerName;

        return (string) ($name ?: ($worker?->full_name ?: Str::before((string) ($worker?->email ?: $grant->invitedEmail), '@')));
    }

    /**
     * Update ONLY the rights of an existing grant — what the panel's
     * switches call the moment they change. No emails, no invites: this is
     * the quiet path, and it is why flipping a right finally sticks.
     */
    public function updateRights(Request $request)
    {
        $boss = $request->user();
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the farm owner can manage worker logins.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id'               => 'required|integer',
            'scheduleAccess'   => 'required|in:view,edit',
            'communityAccess'  => 'nullable|boolean',
            'notesAccess'      => 'nullable|in:none,view,edit',
            'reportsAccess'    => 'nullable|in:none,view,edit',
            'inventoryAccess'  => 'nullable|in:none,view,edit',
            'observationsAccess' => 'nullable|in:none,view,edit',
            'mapsAccess'       => 'nullable|in:none,view,edit',
            'drawAccess'       => 'nullable|in:none,view,edit',
            'aiAccess'         => 'nullable|boolean',
            'cameraAccess'     => 'nullable|boolean',
            'videoAccess'      => 'nullable|boolean',
            'voiceAccess'      => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please check what you entered.', 'errors' => $validator->errors()], 422);
        }

        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('id', (int) $request->input('id'))
            ->first();
        if (! $grant) {
            return response()->json(['success' => false, 'message' => 'That access was not found.'], 404);
        }

        $grant->fill([
            'scheduleAccess'   => $request->input('scheduleAccess'),
            'communityAccess'  => $request->boolean('communityAccess'),
            'notesAccess'      => $request->input('notesAccess', 'view'),
            'reportsAccess'    => $request->input('reportsAccess', 'view'),
            'inventoryAccess'  => $request->input('inventoryAccess', 'none'),
            'observationsAccess' => $request->input('observationsAccess', 'none'),
            'mapsAccess'       => $request->input('mapsAccess', 'none'),
            'drawAccess'       => $request->input('drawAccess', 'none'),
            'aiAccess'         => $request->boolean('aiAccess'),
            'cameraAccess'     => $request->boolean('cameraAccess'),
            'videoAccess'      => $request->boolean('videoAccess'),
            'voiceAccess'      => $request->boolean('voiceAccess'),
            'canAddNotes'      => $request->input('notesAccess', 'view') === 'edit',
        ])->save();
        $this->audit($request, $grant);

        return response()->json([
            'success' => true,
            'message' => 'Access updated.',
            'data' => ['grant' => $this->grantData($grant)],
        ]);
    }

    /** Revoke a worker's access. */
    public function revoke(Request $request)
    {
        $boss = $request->user();
        // Handing out logins is the farm owner's act, not a worker's.
        //
        // These three read $request->user() rather than the effective owner,
        // so a worker standing on the boss's Workers page was writing grants
        // — and, through setPassword, whole new accounts with a password of
        // their choosing — under their own name. Not an escalation into the
        // boss's farm, but an account-creation primitive nobody meant to
        // leave open.
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the farm owner can manage worker logins.',
            ], 403);
        }
        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('id', (int) $request->input('id'))
            ->first();
        if (! $grant) {
            return response()->json(['success' => false, 'message' => 'That access was not found.'], 404);
        }

        $grant->update(['status' => WorkerGrant::STATUS_REVOKED, 'deleteStatus' => 0]);
        $this->audit($request, $grant);

        return response()->json(['success' => true, 'message' => 'Access removed.']);
    }

    /**
     * Send a worker who ALREADY has a login a link to change their password.
     *
     * A registration link is no use to somebody who registered — the owner's
     * real errand at that point is "they have forgotten it, let them pick a
     * new one". The link is the app's ordinary reset link, minted by the same
     * broker the Forgot-password page uses, so the page it opens, the token's
     * life and the rules it enforces are the ones already in the app rather
     * than a second set that would have to be kept in step.
     */
    public function sendPasswordLink(Request $request)
    {
        $boss = $request->user();
        // Handing out logins is the farm owner's act, not a worker's — the
        // same guard the three writes above carry, for the same reason.
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return response()->json([
                'success' => false,
                'message' => 'Only the farm owner can manage worker logins.',
            ], 403);
        }

        $grant = WorkerGrant::active()
            ->where('bossUserId', $boss->id)
            ->where('id', (int) $request->input('id'))
            ->first();
        if (! $grant) {
            return response()->json(['success' => false, 'message' => 'That access was not found.'], 404);
        }

        $worker = $grant->workerUserId ? User::active()->find($grant->workerUserId) : null;
        if (! $worker) {
            return response()->json([
                'success' => false,
                'message' => 'This worker has no login yet. Send them a sign up link instead.',
            ], 422);
        }

        try {
            $token = Password::broker()->createToken($worker);
        } catch (\Throwable $e) {
            Log::warning('Worker password link failed: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'The link could not be made. Try again in a moment.'], 500);
        }

        $name = $this->nameFor($grant, $worker);

        $this->mail->sendTemplate('worker_password_change', $worker->email, $name, [
            'workerName' => $name,
            'bossName' => $boss->full_name ?: 'Your farm manager',
            'resetUrl' => route('password.reset', ['token' => $token, 'email' => $worker->email]),
        ], [
            'relatedType' => 'worker_grant',
            'relatedId' => $grant->id,
        ]);
        $this->audit($request, $grant);

        return response()->json([
            'success' => true,
            'message' => 'Password change link sent to '.$worker->email.'.',
        ]);
    }

    private function sendInviteEmail(WorkerGrant $grant, User $boss): void
    {
        /* Templated in the mother app like every other message this app
         * sends. It was a string literal in this file, so nobody could
         * change a word of the one email a new worker ever reads without a
         * deploy — while the welcome and the password reset had been
         * editable in the admin for months. */
        // The grant holds an address, not a name — the name is on the worker
        // row it was created from. Failing that, the part before the @ is
        // still better than opening with "Hi ,".
        $name = $this->nameFor($grant);

        $this->mail->sendTemplate('worker_invite', $grant->invitedEmail, $name, [
            'workerName' => $name,
            'bossName' => $boss->full_name ?: 'Your farm manager',
            'inviteUrl' => route('worker.invite.show', ['token' => $grant->inviteToken]),
        ], [
            'relatedType' => 'worker_grant',
            'relatedId' => $grant->id,
        ]);
    }

    private function sendReadyEmail(string $email, string $name, User $boss): void
    {
        $this->mail->sendTemplate('worker_access_ready', $email, $name, [
            'workerName' => $name,
            'bossName' => $boss->full_name ?: 'Your farm manager',
        ]);
    }
}
