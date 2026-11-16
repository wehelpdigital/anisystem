<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ContentGate;
use App\Support\VisitorJourney;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The first page after a signup that began on a members' guide (2026-11-16, the owner: "if user registers from a
 * gated page, once he/she registeres, ask to continue on the gated blog page or go to anee home, or anee dashboard.
 * why? because its confusing that after registration user goes to the dashboard and not the gated page").
 *
 * The confirmation link (or Google) brings a new member here instead of the dashboard when ContentGate holds the
 * guide they left; they pick the guide, the home page or their dashboard. A signup with no guide behind it still
 * lands on the dashboard. The guide's path is kept with the account at signup (afterSignup()), because the emailed
 * link is often opened in another browser than the form.
 */
class WelcomeController extends Controller
{
    /** How long a signup's guide waits for the emailed link: the link itself lasts three days. */
    private const KEEP_DAYS = 4;

    public function show(Request $request)
    {
        $reading = ContentGate::waiting();
        if (! $reading) {
            return redirect()->route('app.dashboard');
        }
        $section = explode('/', trim((string) parse_url($reading['url'], PHP_URL_PATH), '/'))[0] ?? '';

        return view('auth.welcome', [
            'reading' => $reading,
            // A guide behind Libre + Anee opens only its start on the free plan: said, not hidden.
            'plusOnly' => ContentGate::needsPlus($section) && ! ContentGate::hasPlus(),
            'first' => trim((string) $request->user()->firstName),
        ]);
    }

    /** The choice, kept on the visitor's road, then the page they picked. */
    public function go(Request $request, string $to)
    {
        VisitorJourney::chose((int) $request->user()->id, $to);
        if ($to === 'read') {
            $page = ContentGate::waiting();
            $request->session()->forget('gate.read');

            return $page ? redirect()->to($page['url']) : redirect()->route('app.dashboard');
        }

        // The guide stays on the dashboard's "Pick up where you left off" card for later.
        return $to === 'home' ? redirect()->route('home') : redirect()->route('app.dashboard');
    }

    /** At signup: the guide (and the plan) this form was opened for, kept with the account. */
    public static function keep(int $userId, Request $request): void
    {
        $after = array_filter([
            'read' => ContentGate::readPath($request->session()->get('gate.read')),
            'plan' => $request->filled('plan') ? (string) $request->input('plan') : null,
        ]);
        if ($after) {
            Cache::put('signup.after.' . $userId, $after, now()->addDays(self::KEEP_DAYS));
        }
    }

    /** At the confirmation link: what keep() saved, back in this browser's session. Returns the plan, if any. */
    public static function restore(int $userId, Request $request): ?string
    {
        $after = Cache::pull('signup.after.' . $userId) ?: [];
        if (! $request->session()->has('gate.read') && ! empty($after['read'])) {
            ContentGate::remember($after['read']);
        }

        return $request->session()->pull('signup.plan') ?: ($after['plan'] ?? null);
    }
}
