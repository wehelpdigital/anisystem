<?php

namespace App\Http\Middleware;

use App\Support\WorkerContext;
use App\Support\WorkerTools;
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
        /* The notebook's own routes, named one by one rather than swept up by
         * `sm.notes.*`, because three of the seven are not the notebook's at
         * all: putting a photo or a clip into the store is the act of the
         * CAMERA and the RECORDER, and they are listed with the other media
         * doors below. A new sm.notes route belongs on this list — a name
         * left off it is a door with no lock. */
        ['sm.notes',          'notes'],
        ['sm.notes.store',    'notes'],
        ['sm.notes.update',   'notes'],
        ['sm.notes.destroy',  'notes'],
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
        /* WRITING a note on the board is one act reached three ways: with
         * words, with a drawing, or with a map. Whoever holds the pen for
         * what the note CARRIES may file it, so this one rule takes any of
         * the three — a worker given the Drawing pen and no notebook can
         * still put a drawing on a day, which is the whole of what that
         * permission was given to them for.
         *
         * Only the save. Everything else an inline note can have done to it
         * is the notebook's, so the routes are named rather than swept up by
         * a wildcard — a new one belongs on this list, deliberately. */
        ['sm.activities.inline-note.save',   'notes:edit|draw:edit|maps:edit|camera:edit|video:edit'],
        // A capture that files itself on a day: any one of the three tools
        // opens the door, and the controller then checks the one that was
        // actually used. Same shape as the line above it.
        ['sm.activities.note-capture',       'camera:edit|video:edit|voice:edit'],
        ['sm.activities.inline-note.delete', 'notes:edit'],
        ['sm.activities.append-note',        'notes:edit'],
        ['sm.activity-versions.global-note', 'notes:edit'],
        // The whiteboard lives by the Collab Room's own rule, except where it
        // leaves the room: saving the board writes a note on the schedule.
        ['sm.board.save-notes', 'notes:edit'],

        ['sm.maps',    'maps'],
        ['sm.map',     'maps'],
        ['sm.map.*',   'maps'],
        // A lot's map is the Maps module wearing the lot's name: opening it
        // is looking at a map, and attaching, detaching or pinning writes one.
        ['sm.lots.map',   'maps'],
        ['sm.lots.map.*', 'maps'],
        ['sm.lots.pin',   'maps'],

        // The shed: what the farm owns, what moved, what it cost. A level
        // like Notes — a worker can be shown the stock without being handed
        // the ledger's pen.
        ['sm.inventory',   'inventory'],
        ['sm.inventory.*', 'inventory'],

        /* What came off the season. It was the owner's alone (2026-08-23)
         * until the owner asked for a switch: now it is a level like the
         * shed. The upload below also wants the camera or the recorder, so
         * a photo on an observation needs both pens. */
        ['sm.post-harvest',   'observations'],
        ['sm.post-harvest.*', 'observations'],
        // Marking a lot harvested files its harvest record in Observations;
        // the controller also asks the board's pen, since the lot is the plan's.
        ['sm.lots.harvest',      'observations:edit'],
        ['sm.lots.harvest.undo', 'observations:edit'],

        // Tags ride the activity forms, so whoever may work the board may
        // read and coin them; the writes still pass canEdit inside.
        ['sm.tags',    'activities'],
        ['sm.tags.*',  'activities'],

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
        /* EVERY ANEE RUN THE FARM PAYS FOR (2026-10-22, the owner: "make sure that the setup to what workers
         * can access are complete"). In a farm, each of these charges the owner's credits
         * (effectiveOwnerId pays), and only the chat asked the Anee switch: a worker with Anee off could
         * still realign a lot, read the fertilizer or run Crop Protocol, When to Plant and the rest on the
         * owner's balance. The runs only; their pages and finished results stay open. The season reports
         * (sm.anee.*, sm.compare.*) answer to Reports above. A new paid run belongs on this list. */
        ['sm.growth.realign', 'ai:edit'],
        ['sm.npk.generate',   'ai:edit'],
        ['proto.generate',    'ai:edit'],
        ['pb.analyze',        'ai:edit'],
        ['pb.npk.generate',   'ai:edit'],
        ['npk.analyze',       'ai:edit'],
        ['landprep.generate', 'ai:edit'],
        ['wtp.generate',      'ai:edit'],
        ['whatp.generate',    'ai:edit'],
        ['vary.generate',     'ai:edit'],
        ['lcc.generate',      'ai:edit'],
        ['compat.anee',       'ai:edit'],
        ['sat.generate',      'ai:edit'],
        ['sky.generate',      'ai:edit'],

        // ---- the owner's own record of the farm --------------------------
        // The documentation a season carries is the owner's, at every tier:
        // no grant opens it, so the doors are not drawn and the addresses
        // answer the same way.
        ['sm.documentation',   'owner'],
        ['sm.doc-entries.*',   'owner'],
        ['sm.doc-tags.*',      'owner'],
        // Named singly rather than sm.protocol.*: the documentation module's
        // protocol sheet shares the prefix with the protocol REPORT below,
        // and the wildcard was refusing a report the grant may well allow.
        ['sm.protocol.save',     'owner'],
        ['sm.protocol.download', 'owner'],
        /* The season's own settings and its lots are the owner's (2026-10-22): the Settings page told a
         * worker "only the owner can change them" and the Lots page drew no add, edit or delete, while
         * these answered any worker who could edit the board. Making a copy of a season is making a
         * season, which sm.store already refuses a worker. The diary carries pay and logins, as the
         * Workers page does, so it is the owner's to read. */
        ['sm.update',                 'owner'],
        ['sm.day-type',               'owner'],
        ['sm.digest.test',            'owner'],
        ['sm.duplicate',              'owner'],
        ['sm.default-groupings.save', 'owner'],
        ['sm.settings.logs',          'owner'],
        ['sm.lots.store',             'owner'],
        ['sm.lots.update',            'owner'],
        ['sm.lots.destroy',           'owner'],
        // A saved plan put into a season or the builder is the owner's act (2026-10-22).
        ['planport.*',                'owner'],

        // Reports wear many route names — one per engine — and a name left
        // off this list is a door with no lock, which is how the expenses
        // and profit pages stood open while sm.reports itself was gated.
        ['sm.reports',           'reports'],
        ['sm.labor.report',      'reports'],
        // The labor report's own data (every worker's days and pay): the page was gated, its numbers were not.
        ['sm.activities.labor',  'reports'],

        /* MONEY (2026-10-22, the owner: "All money on the board"). The day book's two strips are money
         * outright; the board itself is handed its money blanked (MoneyView) when this says None. Every
         * report but View as Protocol is money too, so those need a look at Money as well as Reports:
         * a worker kept from the wages must not read them off the labor report. */
        ['sm.activities.day-expense.*',        'money'],
        ['sm.activities.day-income.*',         'money'],
        ['sm.activities.day-money.block-move', 'money:edit'],
        ['sm.labor.report',      'money:view'],
        ['sm.activities.labor',  'money:view'],
        ['sm.expenses.report',   'money:view'],
        ['sm.expenses.report.*', 'money:view'],
        ['sm.profit.report',     'money:view'],
        ['sm.profit.report.*',   'money:view'],
        ['sm.anee.*',            'money:view'],
        ['sm.compare.*',         'money:view'],
        ['sm.report.*',          'money:view'],
        ['sm.report.*',          'reports'],
        ['sm.expenses.report',   'reports'],
        ['sm.expenses.report.*', 'reports'],
        ['sm.profit.report',     'reports'],
        ['sm.profit.report.*',   'reports'],
        ['sm.anee.*',            'reports'],
        ['sm.compare.*',         'reports'],
        ['sm.protocol.report',   'reports'],
        ['sm.protocol.generate', 'reports'],

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
        // One endpoint takes the observation's photos AND its clips, so
        // either tool opens it and the controller asks for the one used.
        ['sm.post-harvest.image-upload', 'camera:edit|video:edit'],
        ['sm.photo',                    'camera'],
        ['sm.photo.*',                  'camera'],

        ['sm.notes.video-upload',   'video'],
        ['notes.hub.video-upload',  'video'],
        ['quick-record.clip',       'video'],
        // quick-voice.clip is deliberately absent: a quick voice note is the
        // speaker's own GLOBAL note now, not a season's record, so it needs
        // no farm grant — same rule as writing a global note.
        // The microphone answers to its own switch, not the camcorder's.
        ['sm.notes.audio-upload',   'voice'],

        // ---- the toggle that never did anything --------------------------
        // communityAccess has been on the grant since worker logins existed
        // and was never once read, so an owner could turn it off and watch
        // nothing happen.
        ['community.*', 'community'],
    ];

    /** What to call each module when refusing it. */
    private const LABELS = [
        'notes' => 'Notes',
        'inventory' => 'the Inventory',
        'observations' => 'Observations',
        'money' => 'the farm\'s money',
        'maps' => 'the Maps module',
        'draw' => 'the Drawing module',
        'ai' => 'Anee',
        'camera' => 'the camera',
        'video' => 'video recording',
        'reports' => 'Reports',
        'community' => 'the Community',
        'owner' => 'this page, which only the farm owner can open',
    ];

    /**
     * The season pages whose `?id=` IS the season (2026-10-22, access by
     * cropping schedule). Elsewhere an `id` is a task, a note, a map; those
     * calls name their season as scheduleId, or the controller says it
     * (WorkerContext::enterSeason) and this table is asked again.
     */
    private const SEASON_BY_ID = [
        'sm.hub', 'sm.settings', 'sm.settings.logs', 'sm.reports', 'sm.ai', 'sm.weather.page', 'sm.lots', 'sm.workers',
        'sm.inventory', 'sm.inventory.list', 'sm.documentation', 'sm.activities', 'sm.notes', 'sm.lots.map', 'sm.gallery',
        'sm.growth', 'sm.growth.card', 'sm.media', 'sm.collab', 'sm.activities.readiness', 'sm.post-harvest',
        'sm.labor.report', 'sm.expenses.report', 'sm.expenses.report.data', 'sm.profit.report', 'sm.profit.report.data',
        'sm.anee.season', 'sm.anee.sofar', 'sm.anee.status', 'sm.anee.list', 'sm.protocol.report',
        'sm.tags', 'sm.tags.list', 'sm.tags.items', 'sm.tags.of', 'sm.activities.day-income.list', 'sm.activities.day-expense.list',
        'sm.update', 'sm.destroy', 'sm.duplicate', 'sm.digest.test', 'sm.day-type', 'sm.status',
    ];

    /** The season a request names, as far as it can be read before the controller runs. */
    public static function seasonOf(Request $request, string $name)
    {
        foreach (['scheduleId', 'croppingScheduleId'] as $key) {
            $v = $request->input($key);
            if (is_numeric($v) && (int) $v > 0) {
                return (int) $v;
            }
        }
        if (in_array($name, self::SEASON_BY_ID, true) && is_numeric($request->input('id')) && (int) $request->input('id') > 0) {
            return (int) $request->input('id');
        }

        // A season's own screen whose season is still to be read; anything else is the farm as a whole.
        return str_starts_with($name, 'sm.') ? 'pending' : null;
    }

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

        WorkerContext::useSeason(self::seasonOf($request, $name));

        return self::verdict($request) ?? $next($request);
    }

    /**
     * The table's answer for this request, in the season WorkerContext holds:
     * a refusal, or null to go on. Asked by handle() on the way in and again
     * by WorkerContext::enterSeason() once a controller knows the season.
     */
    public static function verdict(Request $request): ?Response
    {
        $name = (string) optional($request->route())->getName();
        if ($name === '' || ! WorkerContext::inWorkerContext()) {
            return null;
        }

        /* The home screen's tools (2026-10-22): a tool the owner switched off
         * for this login is refused at its address too, not only left off the
         * dashboard. A route two tools share opens while either is on. */
        $tools = WorkerTools::forRequest($name, $request);
        if ($tools && ! array_filter($tools, fn ($key) => WorkerContext::canUseTool($key))) {
            return self::refuse($request, WorkerTools::all()[$tools[0]][0]);
        }

        /* Maps and drawings of your OWN (2026-09-30): the canvas and save
         * endpoints serve the global Maps and Draw pages when no season is
         * named, and those are the worker's own tools, not the farm's -- the
         * same rule Global Notes keeps. A farm map or drawing reached that
         * way is still fenced: MapAccess asks the Maps pen before opening or
         * changing one, and a season's drawing is only reached by naming
         * its season, which brings this gate back. */
        if (Str::is(['sm.map', 'sm.map.*', 'sm.draw.*'], $name) && (int) $request->query('scheduleId') <= 0) {
            return null;
        }

        // A GET is looking; anything else is changing something.
        $wanted = $request->isMethodSafe() ? 'view' : 'edit';

        foreach (self::RULES as [$pattern, $need]) {
            if (! Str::is($pattern, $name)) {
                continue;
            }

            /* A rule can name ALTERNATIVES, separated by "|": any one of them
             * opens the door. One act reached by several permissions is real
             * — a note carrying a drawing is the Drawing module's act as much
             * as the notebook's — and saying so here keeps it in the one table
             * every gate is read from. The refusal names the first, which is
             * the permission the endpoint is chiefly about. */
            $alternatives = explode('|', $need);
            $satisfied = false;
            $firstModule = null;
            $firstLevel = null;

            foreach ($alternatives as $one) {
                [$module, $level] = array_pad(explode(':', $one, 2), 2, null);
                $level = $level ?: $wanted;
                $firstModule ??= $module;
                $firstLevel ??= $level;

                if ($module === 'owner') {
                    continue;   // never satisfied by a grant; refused below
                }

                if ($module === 'community') {
                    if (WorkerContext::canUseCommunity()) {
                        $satisfied = true;
                        break;
                    }

                    continue;
                }

                $has = WorkerContext::moduleAccess($module);
                if ($has === 'edit' || ($has === 'view' && $level === 'view')) {
                    $satisfied = true;
                    break;
                }
            }

            if ($satisfied) {
                continue;
            }

            $module = $firstModule;
            $level = $firstLevel;

            if ($module === 'owner') {
                return self::refuse($request, 'owner');
            }

            if ($module === 'community') {
                // A page asks to switch to the worker's own account and go
                // on to it (2026-10-15); calls behind a page still get JSON.
                if (! $request->expectsJson() && $request->isMethodSafe()) {
                    return response()->view('sm.community-switch', ['next' => $request->getRequestUri()], 403);
                }

                return self::refuse($request, 'community');
            }

            $has = WorkerContext::moduleAccess($module);

            return self::refuse($request, $module, $has !== 'none' && $level === 'edit');
        }

        return null;
    }

    /**
     * The answer a worker gets at a door that is not theirs.
     *
     * A page for a page and JSON for everything else, so a refused save says
     * so in the toast the screen already knows how to show rather than
     * painting an HTML apology into a JSON parser.
     */
    private static function refuse(Request $request, string $module, bool $readOnly = false): Response
    {
        $what = self::LABELS[$module] ?? $module;
        // Access is set per cropping schedule (2026-10-22): a refusal in a season is about that season.
        $where = is_int(WorkerContext::season()) ? ' in this season' : ' on this farm';
        $message = $readOnly
            ? 'You have view-only access to ' . $what . $where . '.'
            : 'The farm owner has not given you access to ' . $what . (is_int(WorkerContext::season()) && ! in_array($module, ['owner', 'community'], true) && isset(self::LABELS[$module]) ? $where : '') . '.';

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
