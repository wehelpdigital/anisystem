<?php

namespace App\Http\Controllers\Manager;

use App\Events\ScheduleAiMessagePushed;
use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleNote;
use App\Models\ScheduleAiMessage;
use App\Models\ScheduleAiSession;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\HtmlSanitizer;
use App\Support\ScheduleTeam;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The Collab Room's shared "AI Technician": named conversations ("sessions")
 * per schedule that any team member can ask in and everyone can see. The
 * question broadcasts to the team immediately, the answer once it returns.
 * Funded by the SCHEDULE OWNER's AI credits (workers have no plan), so the
 * owner's balance is the shared pool. Reuses AiClient / AiCreditService /
 * AiSetting verbatim; only storage + gating differ from the per-user AiController.
 */
class ScheduleAiController extends BaseScheduleController
{
    /** Previous turns of shared context sent to the model. */
    private const HISTORY = 10;

    public function __construct(
        private readonly AiClient $ai,
        private readonly AiCreditService $credits,
    ) {
    }

    /** The team's saved AI sessions (most recent first); ensures one exists. */
    public function sessions(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $current = $this->resolveSession($schedule, $request->query('sessionId'));
        $list = ScheduleAiSession::active()
            ->where('scheduleId', $schedule->id)
            ->with('starter')
            ->orderByDesc(DB::raw('COALESCE(lastMessageAt, created_at)'))
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $list->map(fn ($s) => $this->shapeSession($s))->all(),
                'currentId' => $current->id,
            ],
        ]);
    }

    /** Start a fresh session; broadcast it so the team's sidebar updates. */
    public function createSession(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $session = ScheduleAiSession::create([
            'scheduleId' => $schedule->id,
            'title' => null,
            'startedByUserId' => $meId,
            'lastMessageAt' => now(),
            'deleteStatus' => 1,
        ]);
        $shaped = $this->shapeSession($session->load('starter'));
        $this->emit($schedule->id, 'ai.session', ['session' => $shaped]);

        return response()->json(['success' => true, 'data' => ['session' => $shaped]]);
    }

    /**
     * Save a whole session's transcript into the schedule notebook — on its
     * own, or titled onto one of the schedule's tasks. The team names the note
     * and may say why it was kept; the transcript is the attachment, not the
     * whole story (same shape as AiController::saveToNote).
     */
    public function saveSessionNote(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }
        // Writing the notebook is the note right — the same line every other
        // door into the schedule's records draws for a worker.
        if (WorkerContext::activeGrant() && ! WorkerContext::canAddNotes()) {
            return $this->jsonFail('You are not allowed to write notes on this schedule.', 403);
        }

        $validator = Validator::make($request->all(), [
            'sessionId' => 'required|integer',
            'activityId' => 'nullable|integer',
            'title' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $session = ScheduleAiSession::active()
            ->where('scheduleId', $schedule->id)
            ->find((int) $request->input('sessionId'));
        if (! $session) {
            return $this->jsonFail('Session not found.', 404);
        }

        $activity = null;
        if ($request->filled('activityId')) {
            $activity = AsScheduleActivity::query()
                ->where('croppingScheduleId', $schedule->id)
                ->find((int) $request->input('activityId'));
            if (! $activity) {
                return $this->jsonFail('That task is not on this schedule.', 404);
            }
        }

        $msgs = ScheduleAiMessage::active()
            ->where('sessionId', $session->id)
            ->with('author')
            ->orderBy('id')
            ->get();
        if ($msgs->isEmpty()) {
            return $this->jsonFail('This session has no messages yet.', 422);
        }

        // Why it was kept leads; what it was filed onto follows; the talk last.
        $html = '';
        if (filled($request->input('description'))) {
            $html .= '<p>' . nl2br(e(trim((string) $request->input('description')))) . '</p>';
        }
        if ($activity) {
            $when = $activity->targetDate
                ? \Illuminate\Support\Carbon::parse($activity->targetDate)->format('M j, Y')
                : 'no set date';
            $html .= '<p><em>Attached to the task "' . e($activity->activityTitle ?: 'Task') . '" (' . e($when) . ').</em></p>';
        }
        foreach ($msgs as $m) {
            $who = $m->role === 'assistant' ? 'AI Technician' : ($m->author?->full_name ?: 'Member');
            $cls = $m->role === 'assistant' ? 'color:#3d6823' : 'color:#1f2937';
            $html .= '<p><strong style="' . $cls . '">' . e($who) . ':</strong> '
                . nl2br(e((string) $m->content)) . '</p>';
        }

        $title = trim((string) $request->input('title'))
            ?: ('AI · ' . ($session->title ?: 'Session')
                . ($activity ? ' — ' . ($activity->activityTitle ?: 'Task') : ''));
        $note = AsScheduleNote::create([
            'croppingScheduleId' => $schedule->id,
            'userId' => $meId,
            'title' => mb_substr($title, 0, 180),
            'body' => HtmlSanitizer::rich($html),
            'media' => [],
            'deleteStatus' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => $activity
                ? 'Saved this AI session onto the task, in the schedule notebook.'
                : 'Saved this AI session to the schedule notebook.',
            'data' => ['noteId' => $note->id],
        ]);
    }

    /** History + live poll for one session. `?after=<id>` returns newer turns. */
    public function messages(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $session = $this->resolveSession($schedule, $request->query('sessionId'));
        $after = (int) $request->query('after', 0);
        $query = ScheduleAiMessage::active()
            ->where('scheduleId', $schedule->id)
            ->where('sessionId', $session->id)
            ->with('author');
        $rows = $after > 0
            ? $query->where('id', '>', $after)->orderBy('id')->limit(300)->get()
            : $query->orderByDesc('id')->limit(60)->get()->sortBy('id')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'sessionId' => $session->id,
                'messages' => $rows->map(fn ($m) => $this->shape($m, $meId))->all(),
                'maxId' => $rows->max('id') ?: $after,
                'balance' => $this->credits->balance((int) $schedule->anisystemUserId),
            ],
        ]);
    }

    /** Ask the shared AI in a session. Question broadcasts first, then the answer. */
    public function ask(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $askerId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $askerId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        // Every question here is charged to the owner's AI credits. Spending
        // somebody else's balance is not covered by being in their room.
        $this->assertCanEdit();

        $ownerId = (int) $schedule->anisystemUserId;
        $owner = User::find($ownerId);
        if (! $owner || ! $owner->canUseAi()) {
            return $this->jsonFail('The AI Technician needs a Boss/Lifetime plan on the schedule owner\'s account.', 403);
        }

        $settings = AiSetting::current();
        if (! $settings || ! $settings->isUsable()) {
            return $this->jsonFail('The AI Technician is not switched on yet. Please check back soon.', 503);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
            'imagePath' => 'nullable|string|max:500',
            // Several pictures of the same problem is one question, not six.
            // Capped, because each one is a whole image sent to the model and
            // charged to the owner's pool.
            'imagePaths' => 'nullable|array|max:6',
            'imagePaths.*' => 'string|max:500',
            // Index-aligned with imagePaths: which season's gallery a picture
            // was referenced from, null for the asker's own uploads.
            'imageScheduleIds' => 'nullable|array|max:6',
            'imageScheduleIds.*' => 'nullable|integer',
            'sessionId' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $session = $this->resolveSession($schedule, $request->input('sessionId'));
        $prompt = trim((string) $request->input('message'));
        /* One picture or several.
         *
         * imagePath is what every existing caller sends; imagePaths is the
         * newer list. Both are read so nothing that already worked has to
         * change. Every path must load — a path the ownership check throws out
         * is refused loudly rather than quietly answering a question about
         * fewer photos than the team attached. */
        $scheds = array_values((array) $request->input('imageScheduleIds', []));
        $wanted = [];
        if (filled($request->input('imagePath'))) {
            $wanted[(string) $request->input('imagePath')] = null;
        }
        foreach (array_values((array) $request->input('imagePaths', [])) as $i => $p) {
            // First mention wins on a duplicate — same photo, same rights.
            $wanted[(string) $p] = $wanted[(string) $p] ?? (($scheds[$i] ?? null) ? (int) $scheds[$i] : null);
        }
        $images = [];
        $imagePaths = [];
        foreach ($wanted as $path => $gallerySid) {
            $loaded = $this->loadImage($askerId, (string) $path, $gallerySid);
            if ($loaded === null) {
                return $this->jsonFail('One of the attached photos could not be read. Remove it and try again.', 422);
            }
            $images[] = $loaded;
            $imagePaths[] = (string) $path;
        }
        // The transcript keeps one picture per turn, so the first stands for
        // the set in the history; all of them go to the model.
        $imagePath = $imagePaths[0] ?? null;
        $image = $images ?: null;

        // Refuse before spending the owner's pool on something it can't cover.
        $balance = $this->credits->balance($ownerId);
        $estimate = $this->credits->estimate($settings, $prompt, count($images));
        if ($balance < $estimate && ! $this->credits->unlimited((int) \Illuminate\Support\Facades\Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => $balance <= 0
                    ? 'The team AI is out of credits — the schedule owner can top up.'
                    : 'Not enough owner credits for this question (needs ~' . ceil($estimate) . ').',
                'data' => ['balance' => $balance, 'needed' => $estimate, 'outOfCredits' => true],
            ], 402);
        }

        // Persist + broadcast the question so the team sees it before the slow call.
        $q = ScheduleAiMessage::create([
            'scheduleId' => $schedule->id, 'sessionId' => $session->id, 'userId' => $askerId,
            'role' => 'user', 'content' => $prompt, 'imagePath' => $imagePath, 'deleteStatus' => 1,
        ]);
        $q->setRelation('author', Auth::user());
        $qShaped = $this->shape($q, $askerId);
        $this->emit($schedule->id, 'ai.question', $qShaped);

        // Name the session from its first question + keep it at the top of the list.
        $titled = ! $session->title;
        if ($titled) {
            $session->title = Str::limit($prompt, 60, '');
        }
        $session->lastMessageAt = now();
        $session->save();
        if ($titled) {
            $this->emit($schedule->id, 'ai.session', ['session' => $this->shapeSession($session->load('starter'))]);
        }

        /* The rest of this thread, unless a clean read was asked for.
         * Memory inside one thread is what makes it a thread — "and for
         * corn?" has to mean something — but it is one of the two things
         * that quietly move an answer, so it is a switch the team can see. */
        $history = $request->boolean('forget') ? [] : ScheduleAiMessage::active()
            ->where('sessionId', $session->id)->where('id', '<', $q->id)
            ->orderByDesc('id')->limit(self::HISTORY)->get()
            ->reverse()->map(fn ($m) => ['role' => $m->role, 'text' => (string) $m->content])->values()->all();

        /* And the season, only when somebody said to send it.
         * This tab lives inside a schedule, so it used to hand that
         * schedule's crop, variety and lots over with every question purely
         * because of where it was opened — and it changed the answers. A
         * question about a beetle came back about their rice. The season is
         * where the thread LIVES, not a premise it argues from. */
        $context = $request->boolean('usePlan') ? $this->scheduleContext($schedule) : '';

        $result = $this->ai->ask($settings, $history, $context . $prompt, $image);

        if (! $result['ok']) {
            $this->emit($schedule->id, 'ai.answer', ['error' => true, 'sessionId' => $session->id, 'content' => $result['error'] ?: 'The AI could not answer. Try again.']);

            return $this->jsonFail($result['error'] ?: 'The AI could not answer.', 502, ['question' => $qShaped]);
        }

        $charged = $this->credits->priceFor($settings, $result['tokensIn'], $result['tokensOut'], count($images));
        $a = ScheduleAiMessage::create([
            'scheduleId' => $schedule->id, 'sessionId' => $session->id, 'userId' => $ownerId,
            'role' => 'assistant', 'content' => $result['text'], 'creditsCharged' => $charged, 'deleteStatus' => 1,
        ]);
        $a->setRelation('author', $owner);
        $newBalance = $this->credits->chargeAllowingNegative($ownerId, $charged, 'Team AI — ' . Str::limit($schedule->title, 50), $a->id);

        $aShaped = $this->shape($a, $askerId);
        $aShaped['balance'] = $newBalance;
        $this->emit($schedule->id, 'ai.answer', $aShaped);

        return response()->json(['success' => true, 'message' => 'Answered.', 'data' => [
            'question' => $qShaped, 'answer' => $aShaped, 'balance' => $newBalance,
        ]]);
    }

    /**
     * Resolve the session to act on: an explicit valid id, else the team's most
     * recent session, else a freshly created one (guarantees a session exists).
     */
    private function resolveSession($schedule, $sessionId): ScheduleAiSession
    {
        $sessionId = (int) $sessionId;
        if ($sessionId) {
            $found = ScheduleAiSession::active()
                ->where('scheduleId', $schedule->id)->find($sessionId);
            if ($found) {
                return $found;
            }
        }

        $latest = ScheduleAiSession::active()
            ->where('scheduleId', $schedule->id)
            ->orderByDesc(DB::raw('COALESCE(lastMessageAt, created_at)'))
            ->first();
        if ($latest) {
            return $latest;
        }

        return ScheduleAiSession::create([
            'scheduleId' => $schedule->id,
            'title' => null,
            'startedByUserId' => (int) Auth::id(),
            'lastMessageAt' => now(),
            'deleteStatus' => 1,
        ]);
    }

    private function shapeSession(ScheduleAiSession $s): array
    {
        return [
            'id' => $s->id,
            'title' => $s->title ?: 'New session',
            'untitled' => ! $s->title,
            'startedBy' => $s->starter?->full_name ?: 'Team',
            'at' => optional($s->lastMessageAt ?: $s->created_at)->format('M j, g:i A'),
        ];
    }

    /** Best-effort broadcast (never breaks the request); poll is the fallback. */
    private function emit(int $scheduleId, string $name, array $payload): void
    {
        $driver = config('broadcasting.default');
        $ready = in_array($driver, ['pusher', 'reverb', 'ably'], true) && filled(config("broadcasting.connections.$driver.key"));
        if (! $ready) {
            return;
        }
        try {
            broadcast(new ScheduleAiMessagePushed($scheduleId, $name, $payload));
        } catch (\Throwable $e) {
            // swallow — persistence already landed
        }
    }

    private function shape(ScheduleAiMessage $m, int $meId): array
    {
        $author = $m->author;

        return [
            'id' => $m->id,
            'sessionId' => (int) $m->sessionId,
            'role' => $m->role,
            'content' => $m->content,
            'image' => $m->imagePath ? \App\Support\MediaStore::url($m->imagePath) : null,
            'userId' => (int) $m->userId,
            'mine' => (int) $m->userId === $meId && $m->role === 'user',
            'name' => $m->role === 'assistant' ? 'AI Technician' : ($author?->full_name ?: 'Member'),
            'initials' => $author?->initials ?: '·',
            'creditsCharged' => $m->creditsCharged !== null ? (float) $m->creditsCharged : null,
            'at' => optional($m->created_at)->format('M j, g:i A'),
        ];
    }

    /** A short factual preamble about the plan (reuses the AiController shape). */
    private function scheduleContext($schedule): string
    {
        $schedule->loadMissing('lots');
        $lots = $schedule->lots->map(fn ($l) => trim((string) $l->lotName))->filter()->implode(', ');
        $bits = array_filter([
            'Crop: ' . ($schedule->cropType ?: 'not set'),
            $schedule->cropVariety ? 'Variety: ' . $schedule->cropVariety : null,
            $lots ? 'Lots: ' . $lots : null,
        ]);

        // Reference, not premise — same lesson as the personal page: assert
        // the plan as the subject and every answer wears its assumptions.
        return "Background on the team's cropping plan \"{$schedule->title}\", for reference only: "
            . implode('. ', $bits) . '. '
            . 'Use it only if the question is clearly about this plan; never assume the question '
            . 'refers to this plan, its crop, or its conditions — if that matters and is unclear, ask.'
            . "\n\nQuestion: ";
    }

    /**
     * Read the asker's uploaded photo for the provider call (scoped to them).
     * Mirrors AiController::loadImage: MediaStore files everything under an
     * app-level `anisystem/` folder (and marks mother-app files remote), so
     * both path shapes are the asker's own and a remote one is fetched over
     * HTTP rather than looked for on a disk it never touched.
     */
    private function loadImage(int $userId, string $path, ?int $galleryScheduleId = null): ?array
    {
        $bare = \App\Support\MediaStore::isRemote($path)
            ? substr($path, strlen(\App\Support\MediaStore::REMOTE_PREFIX))
            : $path;
        $owned = ! str_contains($bare, '..')
            && (str_starts_with($bare, 'ai-photos/' . $userId . '/')
                || str_starts_with($bare, 'anisystem/ai-photos/' . $userId . '/'));
        // Not the asker's own AI folder: the one other door is a REFERENCE to
        // season media the whole room can already see. The chip lands instantly
        // because nothing is copied — the allowlist at send is exactly what the
        // picker offered for that schedule.
        if (! $owned && ! ($galleryScheduleId && $this->galleryAllows($galleryScheduleId, $path))) {
            return null;
        }

        if (\App\Support\MediaStore::isRemote($path)) {
            $url = \App\Support\MediaStore::url($path);
            try {
                $res = \Illuminate\Support\Facades\Http::timeout(15)->get($url);
                if (! $res->successful()) {
                    return null;
                }

                return [
                    'data' => base64_encode($res->body()),
                    'mime' => $res->header('Content-Type') ?: 'image/jpeg',
                ];
            } catch (\Throwable $e) {
                return null;
            }
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }
        $mime = $disk->mimeType($path) ?: 'image/jpeg';
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return ['mime' => $mime, 'data' => base64_encode($disk->get($path))];
    }

    /** Season-media paths per schedule, built once per request. */
    private array $galleryPaths = [];

    /**
     * Whether this path is on the named schedule's own media list — the same
     * list the picker offered, resolved by the same URL-to-path inverse. The
     * schedule must be reachable by the asker before its list says anything.
     */
    private function galleryAllows(int $scheduleId, string $path): bool
    {
        if (! array_key_exists($scheduleId, $this->galleryPaths)) {
            $schedule = AsCroppingSchedule::active()
                ->forClient(WorkerContext::effectiveOwnerId())
                ->find($scheduleId);
            $this->galleryPaths[$scheduleId] = $schedule
                ? collect(\App\Support\SeasonMedia::all($schedule))
                    ->flatMap(fn ($m) => [
                        \App\Support\MediaStore::pathFromUrl($m['url'] ?? null),
                        \App\Support\MediaStore::pathFromUrl($m['posterUrl'] ?? null),
                    ])
                    ->filter()
                    ->values()
                    ->all()
                : [];
        }

        return in_array($path, $this->galleryPaths[$scheduleId], true);
    }
}
