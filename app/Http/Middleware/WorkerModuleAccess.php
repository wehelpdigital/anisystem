<?php

namespace App\Http\Middleware;

use App\Support\WorkerContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * One place that decides whether the worker in front of us may be here.
 *
 * The alternative is a check at the top of every action, and this app has
 * already been taught what that costs: a resolver that fused two questions
 * left twelve write endpoints open, because each new endpoint had to remember
 * to ask. A module has thirteen routes and a farm has seven modules; asking
 * ninety times is asking to miss one.
 *
 * So the gate is a table of route-name patterns, and the verb says how deep
 * the request is reaching: a GET wants to look, anything else wants to write.
 * Every rule that matches applies, which is how a photo inside a note can
 * need both the notes right and the camera right without either of them
 * needing to know about the other.
 *
 * Owners never meet this middleware's answer -- WorkerContext gives them
 * 'edit' everywhere -- and neither does anybody who is not signed in.
 */
class WorkerModuleAccess
{
    /**
     * Route-name patterns to the rights they need.
     *
     * A bare module name means "view to read it, edit to change it"; an
     * explicit `module:level` means that level whatever the verb. Patterns
     * are Str::is patterns, so `sm.map.*` covers the whole module including
     * the endpoints nobody has written yet.
     */
    private const RULES = [
        // ---- the plan's own modules -------------------------------------
        ['sm.notes',        'notes'],
        ['sm.notes.*',      'notes'],
        /* Global Notes is not the farm's notebook.
         *
         * The page holds the writer's own free-standing notes, and the store
         * and delete endpoints touch nothing else: both are pinned to
         * croppingScheduleId = 0 and to the caller's own userId. Gating them
         * on the farm's Notes right took a worker's own notebook away
         * because their boss had not lent them his. What the page shows OF
         * the farm is decided in NotesHubController::index, which asks for
         * the Notes right there.
         *
         * The media endpoints under this name keep their own rules below —
         * a drawing needs the Drawing module, a photo the camera. */
        // A day's note, an inline note, a note appended to an activity: all
        // the same permission wearing three route names.
        ['sm.activities.date-note.*',        'notes:edit'],
        ['sm.activities.inline-note.*',      'notes:edit'],
        ['sm.activities.append-note',        'notes:edit'],
        ['sm.activity-versions.global-note', 'notes:edit'],
        // The whiteboard lives by the Collab Room's own rule, except where it
        // leaves the room: saving the board writes a note on the schedule.
        ['sm.board.save-notes', 'notes:edit'],

        ['sm.maps',    'maps'],
        ['sm.map',     'maps'],
        ['sm.map.*',   'maps'],

        ['sm.draw',    'draw'],
        ['sm.draw.*',  'draw'],
        // A drawing filed into the notebook is both things at once.
        ['notes.hub.draw', 'draw'],

        ['ai.*',       'ai'],
        // Spending the farm's credits on the farm's questions is what the
        // permission is for; buying more of them is the owner's own act.
        ['ai.credits',   'owner'],
        ['ai.credits.*', 'owner'],
        ['sm.ai',      'ai'],
        ['sm.ai.*',    'ai'],

        // ---- the owner's own record of the farm --------------------------
        // The documentation a season carries and what came off it at the end
        // are the owner's, at every tier: no grant opens them, so the doors
        // are not drawn and the addresses answer the same way.
        ['sm.documentation',   'owner'],
        ['sm.doc-entries.*',   'owner'],
        ['sm.doc-tags.*',      'owner'],
        ['sm.protocol.*',      'owner'],
        ['sm.post-harvest',    'owner'],
        ['sm.post-harvest.*',  'owner'],

        ['sm.reports',           'reports'],
        ['sm.labor.report',      'reports'],

        // ---- the two that are tools rather than places -------------------
        // Taking a picture and filing it: the camera right, wherever the
        // picture is going. Looking at the gallery is not the camera.
        ['sm.gallery.image.store',      'camera'],
        ['quick-capture.gallery',       'camera'],
        ['quick-capture.albums',        'camera'],
        ['quick-capture.notes',         'camera'],
        ['sm.notes.image-upload',       'camera'],
        ['notes.hub.image-upload',      'camera'],
        ['sm.activities.image-upload',  'camera'],
        ['sm.post-harvest.image-upload', 'camera'],
        ['sm.photo',                    'camera'],
        ['sm.photo.*',                  'camera'],

        ['sm.notes.video-upload',   'video'],
        ['notes.hub.video-upload',  'video'],
        ['quick-record.clip',       'video'],

        // ---- the toggle that never did anything --------------------------
        // communityAccess has been on the grant since worker logins existed
        // and was never once read, so an owner could turn it off and watch
        // nothing happen.
        ['community.*', 'community'],
    ];

    /** What to call each module when refusing it. */
    private const LABELS = [
        'notes' => 'Notes',
        'maps' => 'the Maps module',
        'draw' => 'the Drawing module',
        'ai' => 'the AI Technician',
        'camera' => 'the camera',
        'video' => 'video recording',
        'reports' => 'Reports',
        'community' => 'the Community',
        'owner' => 'this — it belongs to the farm owner',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route || ! Auth::check() || ! WorkerContext::inWorkerContext()) {
            return $next($request);
        }

        $name = (string) $route->getName();
        if ($name === '') {
            return $next($request);
        }

        // A GET is looking; anything else is changing something.
        $wanted = $request->isMethodSafe() ? 'view' : 'edit';

        foreach (self::RULES as [$pattern, $need]) {
            if (! Str::is($pattern, $name)) {
                continue;
            }
            [$module, $level] = array_pad(explode(':', $need, 2), 2, null);
            $level = $level ?: $wanted;

            if ($module === 'owner') {
                return $this->refuse($request, 'owner');
            }

            if ($module === 'community') {
                if (WorkerContext::canUseCommunity()) {
                    continue;
                }

                return $this->refuse($request, 'community');
            }

            $has = WorkerContext::moduleAccess($module);
            if ($has === 'edit' || ($has === 'view' && $level === 'view')) {
                continue;
            }

            return $this->refuse($request, $module, $has !== 'none' && $level === 'edit');
        }

        return $next($request);
    }

    /**
     * The answer a worker gets at a door that is not theirs.
     *
     * A page for a page and JSON for everything else, so a refused save says
     * so in the toast the screen already knows how to show rather than
     * painting an HTML apology into a JSON parser.
     */
    private function refuse(Request $request, string $module, bool $readOnly = false): Response
    {
        $what = self::LABELS[$module] ?? $module;
        $message = $readOnly
            ? 'You have view-only access to ' . $what . ' on this farm.'
            : 'The farm owner has not given you access to ' . $what . '.';

        if ($request->expectsJson() || ! $request->isMethodSafe()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        return response()->view('sm.no-access', [
            'what' => $what,
            'backUrl' => null,
            'backLabel' => null,
        ], 403);
    }
}
