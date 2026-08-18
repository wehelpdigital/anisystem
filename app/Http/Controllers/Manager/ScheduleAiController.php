<?php

namespace App\Http\Controllers\Manager;

use App\Events\ScheduleAiMessagePushed;
use App\Models\AiSetting;
use App\Models\AsScheduleNote;
use App\Models\ScheduleAiMessage;
use App\Models\ScheduleAiSession;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\HtmlSanitizer;
use App\Support\ScheduleTeam;
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

    /** Save a whole session's transcript into the schedule notebook. */
    public function saveSessionNote(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $session = ScheduleAiSession::active()
            ->where('scheduleId', $schedule->id)
            ->find((int) $request->input('sessionId'));
        if (! $session) {
            return $this->jsonFail('Session not found.', 404);
        }

        $msgs = ScheduleAiMessage::active()
            ->where('sessionId', $session->id)
            ->with('author')
            ->orderBy('id')
            ->get();
        if ($msgs->isEmpty()) {
            return $this->jsonFail('This session has no messages yet.', 422);
        }

        $html = '';
        foreach ($msgs as $m) {
            $who = $m->role === 'assistant' ? 'AI Technician' : ($m->author?->full_name ?: 'Member');
            $cls = $m->role === 'assistant' ? 'color:#3d6823' : 'color:#1f2937';
            $html .= '<p><strong style="' . $cls . '">' . e($who) . ':</strong> '
                . nl2br(e((string) $m->content)) . '</p>';
        }

        $title = trim((string) $request->input('title')) ?: ('AI · ' . ($session->title ?: 'Session'));
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
            'message' => 'Saved this AI session to the schedule notebook.',
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
         * change, and anything the reader is not allowed to attach is simply
         * dropped by loadImage rather than failing the whole question. */
        $wanted = array_values(array_unique(array_filter(array_merge(
            [(string) $request->input('imagePath')],
            array_map('strval', (array) $request->input('imagePaths', []))
        ))));
        $images = array_values(array_filter(array_map(
            fn ($path) => $this->loadImage($askerId, $path),
            $wanted
        )));
        // The transcript keeps one picture per turn, so the first stands for
        // the set in the history; all of them go to the model.
        $imagePath = $wanted[0] ?? null;
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

        $history = ScheduleAiMessage::active()
            ->where('sessionId', $session->id)->where('id', '<', $q->id)
            ->orderByDesc('id')->limit(self::HISTORY)->get()
            ->reverse()->map(fn ($m) => ['role' => $m->role, 'text' => (string) $m->content])->values()->all();

        $result = $this->ai->ask($settings, $history, $this->scheduleContext($schedule) . $prompt, $image);

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

        return "The farm team is asking about their cropping plan \"{$schedule->title}\". "
            . implode('. ', $bits) . ".\n\nQuestion: ";
    }

    /**
     * Read the asker's uploaded photo for the provider call (scoped to them).
     * Mirrors AiController::loadImage: MediaStore files everything under an
     * app-level `anisystem/` folder (and marks mother-app files remote), so
     * both path shapes are the asker's own and a remote one is fetched over
     * HTTP rather than looked for on a disk it never touched.
     */
    private function loadImage(int $userId, string $path): ?array
    {
        $bare = \App\Support\MediaStore::isRemote($path)
            ? substr($path, strlen(\App\Support\MediaStore::REMOTE_PREFIX))
            : $path;
        $owned = ! str_contains($bare, '..')
            && (str_starts_with($bare, 'ai-photos/' . $userId . '/')
                || str_starts_with($bare, 'anisystem/ai-photos/' . $userId . '/'));
        if (! $owned) {
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
}
