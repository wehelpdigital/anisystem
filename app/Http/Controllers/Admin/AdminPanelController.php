<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiCreditLedger;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkerGrant;
use App\Services\AiCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * The admin panel: the platform seen from above.
 *
 * Three pages — Dashboard, Clients, Support — each a thin shell that fetches
 * its data as JSON and pages it as the admin scrolls, because an admin on a
 * phone in a field deserves the same treatment a client gets.
 *
 * Nothing here changes what a client sees. The panel reads the same tables the
 * client app writes, and where it acts (a suspension, a credit grant, a
 * password) it acts through the same services the app itself uses, so there is
 * one set of rules however the change arrives.
 */
class AdminPanelController extends Controller
{
    /* ---------------------------------------------------------------- pages */

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function clients()
    {
        return view('admin.clients');
    }

    /* ----------------------------------------------------------- dashboard */

    /**
     * The numbers an owner actually asks for: how many, how new, and whether
     * money is coming in — with a year of months behind each so a spike has
     * something to be a spike against.
     */
    public function overview()
    {
        $now = now('Asia/Manila');
        $from = $now->copy()->startOfMonth()->subMonths(11);

        // Twelve month buckets, oldest first, present even when empty — a
        // chart with missing months reads as a chart that is lying.
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $from->copy()->addMonths($i);
            $months[$m->format('Y-m')] = ['ym' => $m->format('Y-m'), 'label' => $m->format('M'), 'value' => 0.0];
        }

        $registrations = $months;
        foreach (User::active()->where('created_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as n")
            ->groupBy('ym')->pluck('n', 'ym') as $ym => $n) {
            if (isset($registrations[$ym])) {
                $registrations[$ym]['value'] = (int) $n;
            }
        }

        /* Sales = subscriptions that were actually paid: active now, or active
         * once and since expired. Pending and rejected money is not money. */
        $sales = $months;
        foreach (Subscription::where('deleteStatus', 1)
            ->whereIn('status', ['active', 'expired'])
            ->where('created_at', '>=', $from)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(price) as total")
            ->groupBy('ym')->pluck('total', 'ym') as $ym => $total) {
            if (isset($sales[$ym])) {
                $sales[$ym]['value'] = round((float) $total, 2);
            }
        }

        $monthStart = $now->copy()->startOfMonth();

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'clients' => User::active()->count(),
            'clientsThisMonth' => User::active()->where('created_at', '>=', $monthStart)->count(),
            'activeSubscriptions' => Subscription::where('deleteStatus', 1)->where('status', 'active')
                ->where(fn ($w) => $w->whereNull('expiresAt')->orWhere('expiresAt', '>', $now))->count(),
            'salesThisMonth' => round((float) Subscription::where('deleteStatus', 1)
                ->whereIn('status', ['active', 'expired'])
                ->where('created_at', '>=', $monthStart)->sum('price'), 2),
            'openTickets' => SupportTicket::active()->whereIn('status', ['open'])->count(),
            'creditsSpentThisMonth' => round(-1 * (float) AiCreditLedger::active()
                ->where('source', 'usage')->where('created_at', '>=', $monthStart)->sum('delta'), 2),
            'registrationsByMonth' => array_values($registrations),
            'salesByMonth' => array_values($sales),
        ]]);
    }

    /* -------------------------------------------------------------- clients */

    /**
     * The client list, a page at a time.
     *
     * Cursor pagination on the id rather than page numbers: the admin scrolls,
     * the list grows, and a client registering mid-scroll cannot shift the
     * pages under their thumb.
     */
    public function clientsData(Request $request)
    {
        // The app's own voices — Anee, the Technician — are rows, not clients.
        $q = User::active()->whereNotIn('email', User::SYSTEM_EMAILS)->orderByDesc('id');

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $q->where(function ($w) use ($s) {
                $w->where('firstName', 'like', "%{$s}%")
                    ->orWhere('lastName', 'like', "%{$s}%")
                    ->orWhereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$s}%"])
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }
        if ($request->filled('cursor')) {
            $q->where('id', '<', (int) $request->input('cursor'));
        }

        $rows = $q->limit(13)->get();
        $more = $rows->count() > 12;
        $rows = $rows->take(12);
        $roles = $this->rolesFor($rows->pluck('id')->all());

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'rows' => $rows->map(fn ($u) => $this->clientRow($u, $roles[$u->id] ?? null))->values(),
            'nextCursor' => $more ? $rows->last()->id : null,
        ]]);
    }

    /**
     * Which hats a page of accounts wear, in two grouped queries — a worker
     * with a login is a client too, and the list should say which kind it is
     * looking at. Admin rides separately (its badge already exists).
     */
    private function rolesFor(array $ids): array
    {
        if (! $ids) {
            return [];
        }
        $owners = DB::table('as_cropping_schedules')->whereIn('anisystemUserId', $ids)
            ->where('deleteStatus', 1)->distinct()->pluck('anisystemUserId')->map(fn ($v) => (int) $v)->all();
        $workers = WorkerGrant::active()->whereIn('workerUserId', $ids)
            ->where('status', WorkerGrant::STATUS_ACTIVE)->distinct()->pluck('workerUserId')->map(fn ($v) => (int) $v)->all();

        $out = [];
        foreach ($ids as $id) {
            $o = in_array((int) $id, $owners, true);
            $w = in_array((int) $id, $workers, true);
            $out[$id] = $o && $w ? 'owner + worker' : ($w ? 'worker' : ($o ? 'farm owner' : null));
        }

        return $out;
    }

    /** One client, in full: the sheet's payload. */
    public function clientOne(int $id)
    {
        $u = User::active()->findOrFail($id);
        $sub = Subscription::where('deleteStatus', 1)->where('userId', $u->id)->orderByDesc('id')->first();

        return response()->json(['success' => true, 'message' => 'ok', 'data' => $this->clientRow($u, $this->rolesFor([$u->id])[$u->id] ?? null) + [
            'phone' => $u->phone,
            // The farms this account works for: each is a balance the
            // admin may credit instead, because a worker's questions bill
            // the farm owner, not the worker.
            'workerFarms' => WorkerGrant::active()->where('workerUserId', $u->id)
                ->where('status', WorkerGrant::STATUS_ACTIVE)->with('boss')->get()
                ->map(fn ($g) => [
                    'bossId' => (int) $g->bossUserId,
                    'bossName' => trim((($g->boss->firstName ?? '') . ' ' . ($g->boss->lastName ?? ''))) ?: ($g->boss->email ?? ('Farm #' . $g->bossUserId)),
                ])->values(),
            'subscription' => $sub ? [
                'planName' => $sub->planName,
                'status' => $sub->effective_status ?? $sub->status,
                'price' => (float) $sub->price,
                'startsAt' => $sub->startsAt?->format('M j, Y'),
                'expiresAt' => $sub->expiresAt?->format('M j, Y'),
            ] : null,
            'creditBalance' => round(app(AiCreditService::class)->balance($u->id), 2),
            'schedules' => DB::table('as_cropping_schedules')->where('anisystemUserId', $u->id)->where('deleteStatus', 1)->count(),
            'tickets' => SupportTicket::active()->where('userId', $u->id)->count(),
        ]]);
    }

    private function clientRow(User $u, ?string $role = null): array
    {
        $until = $u->communitySuspendedUntil;

        return [
            'id' => $u->id,
            'role' => $role,
            'name' => trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? '')) ?: $u->email,
            'firstName' => $u->firstName,
            'lastName' => $u->lastName,
            'email' => $u->email,
            'isAdmin' => $u->isSuperAdmin(),
            'registered' => $u->created_at?->format('M j, Y'),
            'online' => $u->isOnline(),
            'suspendedUntil' => ($until && now()->lt($until)) ? $until->format('Y-m-d') : null,
            'suspendedSays' => ($until && now()->lt($until)) ? $until->format('M j, Y') : null,
        ];
    }

    /** Name, phone, email — the facts a support call corrects. */
    public function updateInfo(Request $request, int $id)
    {
        $u = User::active()->findOrFail($id);
        $data = $request->validate([
            'firstName' => 'required|string|max:100',
            'lastName' => 'nullable|string|max:100',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:30',
        ]);

        // One address, one account. Said before the save, not by a 500.
        $clash = User::active()->whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])
            ->where('id', '!=', $u->id)->exists();
        if ($clash) {
            return response()->json(['success' => false, 'message' => 'Another account already uses that email.'], 422);
        }

        $u->forceFill([
            'firstName' => trim($data['firstName']),
            'lastName' => trim((string) ($data['lastName'] ?? '')),
            'email' => trim($data['email']),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
        ])->save();

        return response()->json(['success' => true, 'message' => 'Client updated.', 'data' => $this->clientRow($u->fresh())]);
    }

    /**
     * Password, the polite way: the same reset link the forgot-password page
     * sends, so the client sets their own secret and support never knew it.
     */
    public function sendPasswordLink(int $id)
    {
        $u = User::active()->findOrFail($id);
        try {
            Password::broker()->sendResetLink(['email' => $u->email]);
        } catch (\Throwable $e) {
            Log::warning('Admin reset link failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'The email could not be sent — check the mail settings.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Reset link sent to ' . $u->email . '.']);
    }

    /**
     * Password, the direct way: typed (or generated) on the admin's screen and
     * set at once — for the client on the phone who cannot do email today.
     */
    public function setPassword(Request $request, int $id)
    {
        $u = User::active()->findOrFail($id);
        $data = $request->validate(['password' => 'required|string|min:8|max:100']);

        $u->forceFill(['password' => Hash::make($data['password'])])->save();

        return response()->json(['success' => true, 'message' => 'Password changed. Tell the client — it is not emailed.']);
    }

    /**
     * Bar (or re-admit) a member from the Community.
     *
     * A date, not a flag: the sentence has a length and nothing has to
     * remember to flip a switch back. NULL lifts it today.
     */
    public function communitySuspend(Request $request, int $id)
    {
        $u = User::active()->findOrFail($id);
        $data = $request->validate(['until' => 'nullable|date|after:today']);

        $u->forceFill(['communitySuspendedUntil' => $data['until'] ?? null])->save();

        return response()->json(['success' => true, 'message' => $data['until']
            ? 'Suspended from the Community until ' . date('M j, Y', strtotime($data['until'])) . '.'
            : 'Community access restored.',
            'data' => $this->clientRow($u->fresh()),
        ]);
    }

    /**
     * Credits by hand — a goodwill grant, a correction. Signed: positive adds,
     * negative takes away. Through the same ledger everything else writes, so
     * the client's own credits log shows it with the reason given here.
     */
    public function adjustCredits(Request $request, int $id)
    {
        $u = User::active()->findOrFail($id);
        $data = $request->validate([
            'credits' => 'required|numeric|not_in:0|min:-100000|max:100000',
            'reason' => 'nullable|string|max:150',
        ]);

        $credits = (float) $data['credits'];
        $reason = trim((string) ($data['reason'] ?? '')) ?: 'Adjusted by support';
        $svc = app(AiCreditService::class);

        /* Which balance receives it. A worker's questions bill the farm
         * owner, so for a both-hats account the admin picks: their own
         * balance, or one of the farms they work for — verified against the
         * grant, because a typo must not credit a stranger. */
        $target = $u;
        $targetId = (int) $request->input('target', $u->id) ?: $u->id;
        if ($targetId !== $u->id) {
            $isBoss = WorkerGrant::active()->where('workerUserId', $u->id)
                ->where('bossUserId', $targetId)->where('status', WorkerGrant::STATUS_ACTIVE)->exists();
            if (! $isBoss) {
                return response()->json(['success' => false, 'message' => 'That farm is not one they work for.'], 422);
            }
            $target = User::active()->findOrFail($targetId);
            $reason .= ' (for worker ' . ($u->firstName ?: $u->email) . ')';
        }

        if ($credits > 0) {
            $balance = $svc->grant($target->id, $credits, $reason, 'admin', Auth::id());
        } else {
            $balance = $svc->chargeAllowingNegative($target->id, abs($credits), $reason);
        }

        return response()->json(['success' => true, 'message' => sprintf(
            '%s%s credits — %s now has %s.',
            $credits > 0 ? '+' : '−', rtrim(rtrim(number_format(abs($credits), 2), '0'), '.'),
            $target->firstName ?: $target->email, number_format((float) $balance, 2)
        )]);
    }

    /**
     * The admin hat, granted or taken from the panel.
     *
     * A panel-made admin is its own flag (panelAdmin), leaving adminUserId
     * to be what it has always been: the SuperAdminBridge's link to a
     * mother-site admin row. A mother-linked admin is the mother app's to
     * manage, so demoting one here is refused rather than broken.
     */
    public function setAdmin(Request $request, int $id)
    {
        $u = User::active()->findOrFail($id);
        $data = $request->validate(['admin' => 'required|boolean']);

        if ($u->id === Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Your own hat stays on your own head.'], 422);
        }
        if (! $data['admin'] && (int) $u->adminUserId > 0) {
            return response()->json(['success' => false, 'message' => 'This admin is linked to the mother site — manage that link there.'], 422);
        }

        $u->forceFill(['panelAdmin' => $data['admin'] ? 1 : 0])->save();

        return response()->json(['success' => true, 'message' => $data['admin']
            ? ($u->firstName ?: $u->email) . ' is now an admin.'
            : 'Admin access removed.',
            'data' => $this->clientRow($u->fresh(), $this->rolesFor([$u->id])[$u->id] ?? null)]);
    }

    /* -------------------------------------------------------- impersonation */

    /**
     * See the app as this client sees it.
     *
     * The admin's own id is kept in the session BEFORE the login swaps the
     * account, which is the whole mechanism: while that key exists, the bar
     * offers the way back and the single-session guard stands down so the
     * real client is not signed out of their phone by a look-around.
     */
    public function impersonate(Request $request, int $id)
    {
        $u = User::active()->findOrFail($id);
        if ($u->id === Auth::id()) {
            return response()->json(['success' => false, 'message' => 'That is already you.'], 422);
        }

        $request->session()->put('admin_impersonator', Auth::id());
        Auth::login($u);

        return response()->json(['success' => true, 'message' => 'You are now seeing the app as ' . ($u->firstName ?: $u->email) . '.', 'data' => ['redirect' => '/app']]);
    }

    /**
     * Back to being the admin. Gated by the session key, not the admin
     * middleware — mid-impersonation the signed-in user IS the client.
     */
    public function stopImpersonating(Request $request)
    {
        $adminId = (int) $request->session()->pull('admin_impersonator', 0);
        $admin = $adminId ? User::active()->find($adminId) : null;
        if (! $admin || ! $admin->isSuperAdmin()) {
            return redirect('/app');
        }

        Auth::login($admin);

        /* Auth::login regenerates the session id, and the single-session
         * guard remembers the OLD one on the admin's row. Without re-claiming
         * the slot here, the very next request on a live host reads "a newer
         * login owns the account" and signs the admin out — the return button
         * became a logout button. Dev hosts skip the guard, which is exactly
         * why a probe on .test could not see this. */
        $admin->forceFill(['currentSessionId' => $request->session()->getId()])->saveQuietly();

        return redirect('/admin')->with('success', 'Back to your own account.');
    }
}
