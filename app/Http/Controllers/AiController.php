<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The Agricultural AI Technician: a crop-only assistant, metered in AI Credits.
 */
class AiController extends Controller
{
    /** How many activities an attached plan may carry into one question. */
    private const PLAN_MAX_ROWS = 80;

    /** How many previous turns to send back as context. */
    private const HISTORY_TURNS = 10;

    public function __construct(
        private readonly AiClient $ai,
        private readonly AiCreditService $credits,
    ) {
    }

    /**
     * Whose plan and whose credits this request rides on.
     *
     * A worker the owner has given the Technician to is asking about the
     * owner's farm; the answer comes off the owner's balance, the same way
     * the Collab Room has always charged it. Standing on your own farm this
     * is simply you.
     */
    private function aiPayer(): \App\Models\User
    {
        $payerId = \App\Support\WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id()
            ? Auth::user()
            : (\App\Models\User::find($payerId) ?? Auth::user());
    }

    public function index(Request $request)
    {
        return $this->page($request, 'full');
    }

    /**
     * The same technician, opened from the homepage.
     *
     * One view, two dresses. The homepage is not standing in a season, so
     * there is no plan to attach and no plan to tie a thread to — and the
     * chat it keeps goes to Global Notes, which is where everything that
     * belongs to the farmer rather than to one season already lives.
     *
     * Not a second chat page. A second chat page would be a second copy of
     * the composer, the history rail, the photo chips and the markdown
     * renderer, and this app already knows what happens to a thing it keeps
     * two of.
     */
    public function home(Request $request)
    {
        return $this->page($request, 'home');
    }

    private function page(Request $request, string $chrome)
    {
        // AI is a Boss/Lifetime feature — Basic can't use it.
        if (! $this->aiPayer()->canUseAi()) {
            return view('ai.locked', ['tier' => $request->user()->planTier()]);
        }

        $userId = Auth::id();
        $settings = AiSetting::current();

        // A fresh chat unless one was named. Opening this page used to resume
        // the newest thread, which reads as a clean start and is not one --
        // the module page inside a season already starts clean, and the old
        // chats are one tap away under "Recent chats".
        $conversation = $request->filled('c') ? $this->resolveConversation($request, $userId) : null;

        return view('ai.index', [
            'settings' => $settings,
            'balance' => $this->credits->balance($userId),
            'conversation' => $conversation,
            'messages' => $conversation
                // Newest sixty, oldest first. The relation is every turn the
                // thread has ever had, markdown-rendered — a season of daily
                // questions was arriving as one document. Sixty covers what a
                // person scrolls back through; the full history is still in
                // the row for anything that ever needs it.
                ? $conversation->messages()->reorder('id', 'desc')->limit(60)->get()->reverse()->values()
                : collect(),
            'conversations' => AiConversation::active()
                ->where('userId', $userId)
                ->with('linkedActivity')
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get(),
            'aiChrome' => $chrome,
            // Offered nowhere in the home dress: the plan button already only
            // draws when there is something to offer, so an empty list is the
            // whole of "no season here".
            'schedules' => $chrome === 'home'
                ? collect()
                : AsCroppingSchedule::active()->forClient($userId)->orderByDesc('id')->get(),
        ]);
    }

    /** Ask a question. Charges credits based on the tokens actually used. */
    public function ask(Request $request)
    {
        $payer = $this->aiPayer();
        if (! $payer->canUseAi()) {
            return $this->json(false, (int) $payer->id === (int) Auth::id()
                ? 'AI is available on Boss and Lifetime plans. Upgrade to unlock the AI Technician.'
                : 'The AI Technician needs a Boss/Lifetime plan on the farm owner\'s account.', [], 403);
        }

        $userId = Auth::id();
        // Whose balance the question is drawn from — see aiPayer().
        $payerId = (int) $payer->id;
        $settings = AiSetting::current();

        if (! $settings->isUsable()) {
            return $this->json(false, 'The AI Technician is not switched on yet. Please check back soon.', [], 503);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
            'conversationId' => 'nullable|integer',
            'imagePath' => 'nullable|string|max:500',
            // Several pictures of the same problem is one question, not six.
            // Capped, because each one is a whole image sent to the model.
            'imagePaths' => 'nullable|array|max:6',
            'imagePaths.*' => 'string|max:500',
            // Index-aligned with imagePaths: which season's gallery a picture
            // was referenced from, null for the asker's own uploads.
            'imageScheduleIds' => 'nullable|array|max:6',
            'imageScheduleIds.*' => 'nullable|integer',
            'scheduleId' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        $prompt = trim($request->input('message'));

        /* One picture or several.
         *
         * imagePath is what every existing caller sends; imagePaths is the
         * newer list. Both are read so nothing that already worked has to
         * change. Every path must load — a path the ownership check throws
         * out is refused loudly rather than quietly answering a question
         * about fewer photos than the farmer attached. */
        $scheds = (array) $request->input('imageScheduleIds', []);
        $wanted = [];
        if (filled($request->input('imagePath'))) {
            $wanted[(string) $request->input('imagePath')] = null;
        }
        foreach (array_values((array) $request->input('imagePaths', [])) as $i => $p) {
            // First mention wins on a duplicate — same photo, same rights.
            $wanted[(string) $p] = $wanted[(string) $p] ?? ($scheds[$i] ?? null ? (int) $scheds[$i] : null);
        }
        $images = [];
        $imagePaths = [];
        foreach ($wanted as $path => $gallerySid) {
            $loaded = $this->loadImage($userId, (string) $path, $gallerySid);
            if ($loaded === null) {
                return $this->json(false, 'One of the attached photos could not be read. Remove it and try again.', [], 422);
            }
            $images[] = $loaded;
            $imagePaths[] = (string) $path;
        }
        $imagePath = $imagePaths[0] ?? null;
        $image = $images ?: null;

        // Refuse before spending anything the client does not have.
        $balance = $this->credits->balance($payerId);
        // Priced with the plan in it when one is attached — the composer
        // quotes that number, and a wall that disagreed with the quote would
        // refuse a question the farmer was told they could afford.
        $priced = $request->boolean('attachPlan')
            ? $prompt . $this->planContext($request->input('scheduleId'), $userId)
            : $prompt;
        $estimate = $this->credits->estimate($settings, $priced, count($images));
        if ($balance < $estimate && ! $this->credits->unlimited($payerId)) {
            $whose = $payerId === (int) Auth::id() ? 'You have' : 'This farm has';
            return $this->json(false, $balance <= 0
                ? $whose . ' no AI Credits left. Top up to keep asking questions.'
                : 'You need about ' . ceil($estimate) . ' credits for this question and have ' . rtrim(rtrim(number_format($balance, 2), '0'), '.') . '.',
                ['balance' => $balance, 'needed' => $estimate, 'outOfCredits' => true], 402);
        }

        $conversation = $this->resolveConversation($request, $userId, true);

        /* The one invariant this endpoint has.
         *
         * Every turn sent to the model below is read off this conversation, so
         * whose it is decides whose words the model sees. resolveConversation
         * scopes by account and always has -- this is the assertion that says
         * so out loud, immediately before the history is read, where a future
         * refactor of that query would have to trip over it.
         */
        if ((int) $conversation->userId !== (int) $userId) {
            report(new \RuntimeException(
                'AI conversation ' . $conversation->id . ' belongs to user '
                . $conversation->userId . ', asked by ' . $userId
            ));

            return $this->json(false, 'That chat could not be opened. Start a new one.', [], 403);
        }

        /* What the question is allowed to carry with it.
         *
         * Nothing, unless the farmer said so. A chat opened inside a season
         * used to hand over that season's crop, variety and lots with every
         * single question, purely because the chat belonged to the season —
         * and it changed the answers. Asked what a beetle was, Anee talked
         * about their rice. Asked a general fertiliser question, she answered
         * for the one variety she had been told about. The farmer never asked
         * for any of that and could not see it happening.
         *
         * So the season is now a place the chat LIVES, not a premise it
         * argues from. Two levels, both asked for out loud:
         *   attachPlan — the whole plan, priced and shown as an attachment
         *   usePlan    — the light label: crop, variety, lots
         * With neither, the question goes over on its own. */
        $context = '';
        if ($request->boolean('attachPlan')) {
            $context = $this->planContext($request->input('scheduleId'), $userId);
        } elseif ($request->boolean('usePlan')) {
            $context = $this->scheduleContext($request->input('scheduleId'), $userId);
        }
        $context = $this->applyLinkContext($context, $conversation);

        $userMessage = AiMessage::create([
            'conversationId' => $conversation->id,
            'role' => 'user',
            'content' => $prompt,
            // Both columns on purpose: the first photo where old renderers
            // look, the whole set where the new ones do. Schema-guarded so a
            // deploy whose release step skipped the migration degrades to
            // first-photo transcripts instead of a column-not-found 500 —
            // every photo still reaches the model either way.
            'imagePath' => $imagePath,
        ] + (\Illuminate\Support\Facades\Schema::hasColumn((new AiMessage)->getTable(), 'imagePaths')
            ? ['imagePaths' => $imagePaths ?: null] : []) + [
            'deleteStatus' => 1,
        ]);

        /* The rest of this chat, unless the farmer asked for a clean read.
         *
         * Memory inside one chat is what makes a chat a chat — "and for
         * corn?" has to mean something. But it is also the other thing that
         * quietly moves an answer, so it is a switch the farmer can see and
         * turn off for a question that should be judged on its own. */
        $history = $request->boolean('forget')
            ? []
            : $conversation->messages()
            ->where('id', '<', $userMessage->id)
            // reorder(), not orderByDesc(): the relation bakes ASC, and a
            // stacked DESC loses - the model was being briefed with the
            // conversation's OLDEST turns instead of its latest.
            ->reorder('id', 'desc')
            ->limit(self::HISTORY_TURNS)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'text' => (string) $m->content])
            ->values()
            ->all();

        $result = $this->ai->ask($settings, $history, $context . $prompt, $image);

        if (! $result['ok']) {
            // Nothing was produced, so nothing is charged.
            $userMessage->update(['deleteStatus' => 0]);

            return $this->json(false, $result['error'], [], 502);
        }

        $charged = $this->credits->priceFor($settings, $result['tokensIn'], $result['tokensOut'], count($images));

        $answer = AiMessage::create([
            'conversationId' => $conversation->id,
            'role' => 'assistant',
            'content' => $result['text'],
            'tokensIn' => $result['tokensIn'],
            'tokensOut' => $result['tokensOut'],
            'creditsCharged' => $charged,
            'deleteStatus' => 1,
        ]);

        // The work is already done, so the charge lands even if it runs the
        // balance to zero — the pre-flight check above keeps that within a
        // credit or two of the estimate.
        $newBalance = $this->credits->chargeAllowingNegative(
            $payerId,
            $charged,
            'Question in "' . Str::limit($conversation->title, 60) . '"',
            $answer->id
        );

        // The first question names the conversation.
        if ($conversation->messages()->count() <= 2) {
            $conversation->update(['title' => Str::limit($prompt, 60)]);
        }
        $conversation->touch();

        return $this->json(true, 'Answered.', [
            'conversationId' => $conversation->id,
            'conversationTitle' => $conversation->fresh()->title,
            // First answer in this session: the page adds it to its list of
            // chats there and then, rather than at the next page load.
            'conversationIsNew' => $conversation->messages()->count() <= 2,
            'answer' => [
                'id' => $answer->id,
                'content' => $result['text'],
                'creditsCharged' => $charged,
                'tokensIn' => $result['tokensIn'],
                'tokensOut' => $result['tokensOut'],
            ],
            'balance' => $newBalance,
        ]);
    }

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'image.required' => 'Pick a photo first.',
            'image.mimes' => 'Allowed types: JPG, PNG, WebP.',
            'image.max' => 'Photo is too large — max 8 MB.',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        $file = $request->file('image');
        $ext = UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'webp']);
        $stem = Str::uuid()->toString();
        // Namespaced per client, which is also what stops one client reading
        // another's photo back through `imagePath`.
        $dir = 'ai-photos/' . Auth::id();

        try {
            // Through MediaStore so a photo the AI was asked about is still
            // there tomorrow — and visible in the mother app with the rest.
            $stored = \App\Support\MediaStore::putFile($file, 'ai-photos', Auth::id());
            if ($stored === null) {
                throw new \RuntimeException('Upload failed.');
            }
        } catch (\Throwable $e) {
            return $this->json(false, 'Photo upload failed: ' . $e->getMessage(), [], 500);
        }

        return $this->json(true, 'Photo attached.', [
            'path' => $stored,
            'url' => \App\Support\MediaStore::url($stored),
        ]);
    }

    /**
     * Attach a picture the app is already showing.
     *
     * "Ask the AI about this" needs the photo in the asker's own ai-photos
     * folder, because that prefix is the only thing stopping one account
     * reading another's attachments back through imagePath. A gallery photo
     * lives under the schedule instead, so it is copied rather than pointed
     * at — the copy is what makes the permission check meaningful.
     *
     * Only from the two hosts this app publishes from, for the same reason
     * the save route is fussy: an open fetcher is a way to make the server
     * read things on somebody else's behalf.
     */
    public function attachExisting(Request $request)
    {
        $url = (string) $request->input('url');
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->json(false, 'Nothing to attach.', [], 422);
        }

        // Both from configuration, and deliberately NOT from the request. We
        // trust every proxy (the platform's address is not knowable in
        // advance) and set no trusted-host list, so getHost() is whatever the
        // caller put in Host or X-Forwarded-Host — an allowlist that asks the
        // caller which hosts are allowed is a fetcher that will read any URL
        // on the server's behalf. Every URL this app hands out is built from
        // one of these two anyway: the public disk's is APP_URL + /storage,
        // and the mother app's is MOTHER_APP_URL + /storage.
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $ours = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower((string) parse_url((string) config('mother.url'), PHP_URL_HOST)),
        ]);
        if (! $ours || ! in_array($host, $ours, true)) {
            return $this->json(false, 'That file is not ours to attach.', [], 403);
        }

        // The models take stills. A clip has to be asked about in words.
        // Asked of the shared list, off the path alone so a `?v=2` on the end
        // does not hide the extension — a private copy of this regex is how
        // .avi got treated as a photo everywhere it was written out.
        if (\App\Support\SeasonMedia::kindOf((string) parse_url($url, PHP_URL_PATH)) === 'video') {
            return $this->json(false, 'The technician reads photos, not video — take a still from it and ask about that.', [], 422);
        }

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(30)->get($url);
            if (! $res->successful()) {
                throw new \RuntimeException('Could not read that file.');
            }
            $tmp = tempnam(sys_get_temp_dir(), 'aiimg');
            file_put_contents($tmp, $res->body());

            $mime = mime_content_type($tmp) ?: '';
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                @unlink($tmp);

                return $this->json(false, 'That is not a photo the technician can read.', [], 422);
            }

            $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
            $file = new \Illuminate\Http\UploadedFile($tmp, 'attached.' . $ext, $mime, null, true);
            $stored = \App\Support\MediaStore::putFile($file, 'ai-photos', Auth::id());
            @unlink($tmp);

            if ($stored === null) {
                throw new \RuntimeException('Could not keep a copy.');
            }
        } catch (\Throwable $e) {
            return $this->json(false, $e->getMessage() ?: 'Could not attach that photo.', [], 500);
        }

        return $this->json(true, 'Photo attached.', [
            'path' => $stored,
            'url' => \App\Support\MediaStore::url($stored),
        ]);
    }

    public function newConversation(Request $request)
    {
        $conversation = AiConversation::create([
            'userId' => Auth::id(),
            'croppingScheduleId' => $request->input('scheduleId') ?: null,
            'title' => 'New question',
            'deleteStatus' => 1,
        ]);

        return $this->json(true, 'Started a new conversation.', ['conversationId' => $conversation->id]);
    }

    /** Rename a chat session (titles otherwise come from the first question). */
    public function renameConversation(Request $request)
    {
        $conversation = AiConversation::active()
            ->where('userId', Auth::id())
            ->where('id', $request->input('id'))
            ->first();

        if (! $conversation) {
            return $this->json(false, 'Conversation not found.', [], 404);
        }

        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->json(false, 'Give the chat a name.', [], 422);
        }

        $conversation->update(['title' => \Illuminate\Support\Str::limit($title, 60, '')]);

        return $this->json(true, 'Renamed.', ['title' => $conversation->fresh()->title]);
    }

    /**
     * The floating chat's doorway into the past: recent conversations as
     * JSON, because the float lives on pages that never render the AI
     * page's server-side history rail.
     */
    public function conversations()
    {
        $rows = AiConversation::active()
            ->where('userId', Auth::id())
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'title' => $c->title ?: 'New question',
                'when' => $c->updated_at?->diffForHumans(),
            ])
            ->values();

        return $this->json(true, 'Conversations.', ['conversations' => $rows]);
    }

    /** One conversation replayed for the float — newest 60 turns, oldest first. */
    public function transcript(Request $request)
    {
        $conversation = AiConversation::active()
            ->where('userId', Auth::id())
            ->find((int) $request->query('conversationId'));
        if (! $conversation) {
            return $this->json(false, 'That conversation is gone.', [], 404);
        }

        $messages = $conversation->messages()
            ->reorder('id', 'desc')
            ->limit(60)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => (string) $m->content,
                'images' => collect((array) ($m->imagePaths ?: ($m->imagePath ? [$m->imagePath] : [])))
                    ->map(fn ($p) => \App\Support\MediaStore::url($p))
                    ->filter()
                    ->values(),
                'at' => $m->created_at?->format('g:i A'),
            ]);

        return $this->json(true, 'Transcript.', [
            'conversationId' => (int) $conversation->id,
            'title' => $conversation->title ?: 'New question',
            'messages' => $messages,
        ]);
    }

    /**
     * File a conversation into the schedule's notebook — on its own, or
     * titled onto one of the schedule's tasks. The transcript becomes a
     * note because that is where everything a season keeps ends up; the
     * task variant names the activity so the note reads as its record.
     */
    public function saveToNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversationId' => 'required|integer',
            'scheduleId' => 'required|integer',
            'activityId' => 'nullable|integer',
            // A day, when the chat belongs to one rather than to a job on it.
            'noteDate' => 'nullable|date',
            // The farmer names the note and may say why it was kept — the
            // transcript is the attachment, not the whole story.
            'title' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        $conversation = AiConversation::active()
            ->where('userId', Auth::id())
            ->find((int) $request->input('conversationId'));
        if (! $conversation) {
            return $this->json(false, 'That conversation is gone.', [], 404);
        }

        $schedule = AsCroppingSchedule::active()
            ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
            ->where('id', (int) $request->input('scheduleId'))
            ->first();
        if (! $schedule) {
            return $this->json(false, 'That schedule is not yours to write to.', [], 404);
        }
        // Writing the notebook is the note right — the same line every other
        // door into the schedule's records draws for a worker.
        if (\App\Support\WorkerContext::activeGrant() && ! \App\Support\WorkerContext::canAddNotes()) {
            return $this->json(false, 'You are not allowed to write notes on this schedule.', [], 403);
        }

        $activity = null;
        if ($request->filled('activityId')) {
            $activity = \App\Models\AsScheduleActivity::query()
                ->where('croppingScheduleId', $schedule->id)
                ->find((int) $request->input('activityId'));
            if (! $activity) {
                return $this->json(false, 'That task is not on this schedule.', [], 404);
            }
        }

        $messages = $conversation->messages()->orderBy('id')->limit(120)->get();
        if ($messages->isEmpty()) {
            return $this->json(false, 'This conversation has no messages yet.', [], 422);
        }

        $tech = AiSetting::current()?->assistantName ?: 'AI Technician';
        $html = '';
        if (filled($request->input('description'))) {
            $html .= '<p>' . nl2br(e(trim((string) $request->input('description')))) . '</p>';
        }
        if ($activity) {
            $when = $activity->targetDate
                ? \Illuminate\Support\Carbon::parse($activity->targetDate)->format('M j, Y')
                : 'no set date';
            $html .= '<p><em>Attached to the task "' . e($activity->activityTitle ?: 'Task') . '" (' . e($when) . ').</em></p>';
        } elseif ($request->filled('noteDate')) {
            // No task, but a day: said in the note, because a note filed
            // under a date and not saying so is a note about nothing.
            $html .= '<p><em>Kept for '
                . e(\Illuminate\Support\Carbon::parse($request->input('noteDate'))->format('M j, Y'))
                . '.</em></p>';
        }
        foreach ($messages as $m) {
            $who = $m->role === 'assistant' ? $tech : 'You';
            $cls = $m->role === 'assistant' ? 'color:#3d6823' : 'color:#1f2937';
            $html .= '<p><strong style="' . $cls . '">' . e($who) . ':</strong> '
                . nl2br(e((string) $m->content)) . '</p>';
        }

        $title = trim((string) $request->input('title'))
            ?: ('AI · ' . ($conversation->title ?: 'Conversation')
                . ($activity ? ' — ' . ($activity->activityTitle ?: 'Task') : ''));
        $note = \App\Models\AsScheduleNote::create([
            'croppingScheduleId' => $schedule->id,
            'userId' => (int) Auth::id(),
            'title' => mb_substr($title, 0, 180),
            'body' => \App\Support\HtmlSanitizer::rich($html),
            'media' => [],
            'deleteStatus' => 1,
        ]);

        return $this->json(true, $activity
            ? 'Saved this conversation onto the task, in the schedule notebook.'
            : 'Saved this conversation to the schedule notebook.', ['noteId' => (int) $note->id]);
    }

    /**
     * File a conversation into Global Notes.
     *
     * The same act as saveToNote, without a season: a chat opened from the
     * homepage is not about one, and asking which notebook to put it in would
     * be asking the farmer to invent an answer. Global Notes is where the
     * things that belong to the farm rather than to one season already live.
     */
    public function saveToGlobalNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversationId' => 'required|integer',
            'title' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        $conversation = AiConversation::active()
            ->where('userId', Auth::id())
            ->find((int) $request->input('conversationId'));
        if (! $conversation) {
            return $this->json(false, 'That conversation is gone.', [], 404);
        }

        $messages = $conversation->messages()->orderBy('id')->limit(120)->get();
        if ($messages->isEmpty()) {
            return $this->json(false, 'This conversation has no messages yet.', [], 422);
        }

        $tech = AiSetting::current()?->assistantName ?: 'AI Technician';
        $html = '';
        if (filled($request->input('description'))) {
            $html .= '<p>' . nl2br(e(trim((string) $request->input('description')))) . '</p>';
        }
        foreach ($messages as $m) {
            $who = $m->role === 'assistant' ? $tech : 'You';
            $cls = $m->role === 'assistant' ? 'color:#3d6823' : 'color:#1f2937';
            $html .= '<p><strong style="' . $cls . '">' . e($who) . ':</strong> '
                . nl2br(e((string) $m->content)) . '</p>';
        }

        $title = trim((string) $request->input('title'))
            ?: ('AI · ' . ($conversation->title ?: 'Conversation'));

        $note = \App\Models\AsScheduleNote::create([
            // The notebook that belongs to nobody's season. Named by the hub
            // that owns it, so the two can never drift apart.
            'croppingScheduleId' => \App\Http\Controllers\NotesHubController::GLOBAL_SCHEDULE_ID,
            'userId' => (int) Auth::id(),
            'title' => mb_substr($title, 0, 180),
            'body' => \App\Support\HtmlSanitizer::rich($html),
            'media' => [],
            'deleteStatus' => 1,
        ]);

        return $this->json(true, 'Saved to your Global Notes.', ['noteId' => $note->id]);
    }

    /**
     * The seasons a chat may be filed into, and what is on a given day.
     *
     * Two questions the "attach to a task" sheet asks, answered from one
     * door: with no date, the list of seasons; with one, that season's tasks
     * on it. Both scoped the way every other read on this controller is.
     */
    public function attachOptions(Request $request)
    {
        $userId = (int) Auth::id();
        $date = trim((string) $request->query('date'));
        $scheduleId = (int) $request->query('scheduleId');

        if (! $scheduleId) {
            return $this->json(true, 'Seasons.', [
                'schedules' => AsCroppingSchedule::active()->forClient($userId)
                    ->orderByDesc('id')->limit(60)->get()
                    ->map(fn ($s) => ['id' => $s->id, 'title' => $s->title])->all(),
            ]);
        }

        $schedule = AsCroppingSchedule::active()->forClient($userId)->find($scheduleId);
        if (! $schedule) {
            return $this->json(false, 'That season is not yours.', [], 404);
        }
        if (! $date || ! strtotime($date)) {
            return $this->json(true, 'Season.', ['tasks' => []]);
        }

        $day = date('Y-m-d', strtotime($date));
        $tasks = \App\Models\AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereDate('targetDate', $day)
            ->orderBy('id')
            ->limit(60)
            ->get()
            ->map(fn ($a) => ['id' => $a->id, 'title' => $a->activityTitle ?: 'Task'])
            ->all();

        return $this->json(true, 'Tasks.', ['tasks' => $tasks, 'date' => $day]);
    }

    public function deleteConversation(Request $request)
    {
        $conversation = AiConversation::active()
            ->where('userId', Auth::id())
            ->where('id', $request->query('id'))
            ->first();

        if (! $conversation) {
            return $this->json(false, 'Conversation not found.', [], 404);
        }

        $conversation->update(['deleteStatus' => 0]);

        return $this->json(true, 'Conversation deleted.');
    }

    /** Pin this thread to a day or an activity of its schedule (or clear it). */
    public function linkConversation(Request $request)
    {
        $conversation = AiConversation::active()
            ->where('userId', Auth::id())
            ->where('id', $request->input('conversationId'))
            ->first();

        if (! $conversation) {
            return $this->json(false, 'Conversation not found.', [], 404);
        }

        $linkType = $request->input('linkType'); // 'date' | 'activity' | 'none'
        $conversation->linkedDate = null;
        $conversation->linkedActivityId = null;

        if ($linkType === 'date') {
            $date = $request->input('linkedDate');
            if (! $date || ! strtotime($date)) {
                return $this->json(false, 'Pick a day.', [], 422);
            }
            $conversation->linkedDate = date('Y-m-d', strtotime($date));
        } elseif ($linkType === 'activity') {
            $activity = $conversation->croppingScheduleId
                ? \App\Models\AsScheduleActivity::where('id', (int) $request->input('linkedActivityId'))
                    ->where('croppingScheduleId', $conversation->croppingScheduleId)->first()
                : null;
            if (! $activity) {
                return $this->json(false, 'That activity is not part of this plan.', [], 422);
            }
            $conversation->linkedActivityId = $activity->id;
        }

        $conversation->save();
        $conversation->loadMissing('linkedActivity');

        return $this->json(true, $linkType === 'none' ? 'Link removed.' : 'Thread linked.', [
            'linkLabel' => $conversation->link_label,
            'linkedDate' => $conversation->linkedDate?->format('Y-m-d'),
            'linkedActivityId' => $conversation->linkedActivityId,
        ]);
    }

    // ------------------------------------------------------------------

    /** Fold the pinned day/activity into the plan preamble sent to the model. */
    private function applyLinkContext(string $context, ?AiConversation $conversation): string
    {
        $focus = $this->linkFocusText($conversation);
        if ($focus === '') {
            return $context;
        }
        if ($context === '') {
            return $focus . "\n\nQuestion: ";
        }

        return str_replace("\n\nQuestion: ", "\n" . $focus . "\n\nQuestion: ", $context);
    }

    private function linkFocusText(?AiConversation $conversation): string
    {
        if (! $conversation) {
            return '';
        }
        if ($conversation->linkedActivityId && ($a = $conversation->linkedActivity)) {
            $when = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate)->format('M j, Y') : 'no set date';
            $desc = trim(strip_tags((string) $a->description));
            $t = 'This thread is focused on the activity "' . ($a->activityTitle ?: 'Activity') . '" (scheduled ' . $when . ').';
            if ($desc !== '') {
                $t .= ' Details: ' . Str::limit($desc, 400) . '.';
            }

            return $t;
        }
        if ($conversation->linkedDate && $conversation->schedule) {
            $day = $conversation->linkedDate->format('Y-m-d');
            $titles = $conversation->schedule->activities
                ->filter(fn ($a) => $a->targetDate && \Illuminate\Support\Carbon::parse($a->targetDate)->format('Y-m-d') === $day)
                ->pluck('activityTitle')->filter()->take(12)->implode('; ');
            $t = 'This thread is focused on ' . $conversation->linkedDate->format('M j, Y') . '.';
            if ($titles !== '') {
                $t .= ' Activities that day: ' . $titles . '.';
            }

            return $t;
        }

        return '';
    }

    private function resolveConversation(Request $request, int $userId, bool $createIfMissing = false): ?AiConversation
    {
        // The AI now lives inside a schedule, so conversations are scoped to it —
        // one schedule's chat history never bleeds into another's.
        $scheduleId = $request->input('scheduleId') ?? $request->query('scheduleId');
        $id = $request->input('conversationId') ?? $request->query('c');

        $base = fn () => AiConversation::active()->where('userId', $userId)
            ->when($scheduleId, fn ($q) => $q->where('croppingScheduleId', $scheduleId));

        if ($id) {
            $found = $base()->where('id', $id)->first();
            if ($found) {
                return $found;
            }
        }

        /* No chat named, and something is about to be written: this is a new
         * session.
         *
         * Adopting the newest thread instead is what glued every chat this
         * account had ever had into one. Open the floating technician, ask
         * one thing, and the answer arrived on the end of a conversation
         * from last week -- with last week's turns briefed to the model, so
         * it answered about a plan nobody had mentioned. Each opening is its
         * own session; the last one is not lost, it is in the list. */
        if ($createIfMissing) {
            return AiConversation::create([
                'userId' => $userId,
                'croppingScheduleId' => $scheduleId ?: null,
                'title' => 'New question',
                'deleteStatus' => 1,
            ]);
        }

        // Only a reader gets here -- a transcript or a save asked for without
        // an id -- and for those the newest thread is the one meant.
        return $base()->orderByDesc('updated_at')->first();
    }

    /**
     * The AI Technician scoped to one cropping schedule (an in-shell module).
     * Same chat + endpoints as the standalone page, but history is this
     * schedule's only, and the plan context is always attached.
     */
    public function schedulePage(Request $request)
    {
        $userId = Auth::id();
        // Resolved against the farm being worked, not the account doing the
        // working: under a worker's own id a boss's schedule is invisible, and
        // the page answered "no such schedule" when the truth is "not yours".
        $schedule = AsCroppingSchedule::active()
            ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
            ->where('id', $request->query('id'))
            ->first();
        if (! $schedule) {
            abort(404);
        }
        // Whether a worker may be here is their owner's answer, given per
        // person in the Workers module and asked by WorkerModuleAccess for
        // every ai.* route -- including this one.

        $settings = AiSetting::current();
        // A fresh session unless one was asked for by id: opening the module
        // used to resume the latest thread, and the owner wants a clean desk —
        // the old chats wait behind "Recent chats".
        $conversation = $request->filled('c')
            ? $this->resolveConversation(
                $request->merge(['scheduleId' => $schedule->id]),
                $userId
            )
            : null;
        $conversation?->loadMissing('linkedActivity');

        return view('sm.ai', [
            'schedule' => $schedule,
            'settings' => $settings,
            'balance' => $this->credits->balance($userId),
            // What the "This season's plan" switch would add to a question.
            'planTokens' => $this->planTokenCost($schedule->id, $userId),
            'conversation' => $conversation,
            'messages' => $conversation
                // Newest sixty, oldest first. The relation is every turn the
                // thread has ever had, markdown-rendered — a season of daily
                // questions was arriving as one document. Sixty covers what a
                // person scrolls back through; the full history is still in
                // the row for anything that ever needs it.
                ? $conversation->messages()->reorder('id', 'desc')->limit(60)->get()->reverse()->values()
                : collect(),
            'conversations' => AiConversation::active()
                ->where('userId', $userId)
                ->where('croppingScheduleId', $schedule->id)
                ->with('linkedActivity')
                ->orderByDesc('updated_at')
                ->limit(30)
                ->get(),
        ]);
    }

    /**
     * The plan itself, attached on purpose.
     *
     * scheduleContext() below is a label — crop, variety, lots — and it is
     * what a chat bound to a season carries in the background. This is what
     * the farmer means by "read my plan first": the work itself, in order,
     * with what is done and what is still ahead. It is only ever built when
     * somebody attached it, because it is long enough to be worth credits.
     *
     * Capped hard. A season with three hundred activities is not a preamble,
     * and the tail of it is the part least likely to bear on the question.
     */
    private function planContext($scheduleId, int $userId): string
    {
        $schedule = $this->planFor($scheduleId, $userId);
        if (! $schedule) {
            return '';
        }

        $schedule->load('lots');
        $lots = $schedule->lots
            ->map(fn ($l) => trim($l->lotName . ' (' . rtrim(rtrim((string) $l->lotSize, '0'), '.') . ' ' . $l->lotSizeUnit . ')'))
            ->implode(', ');

        $head = array_filter([
            'Crop: ' . ($schedule->cropType ?: 'not set'),
            $schedule->cropVariety ? 'Variety: ' . $schedule->cropVariety : null,
            $schedule->dayType ? 'Day counting: ' . $schedule->dayType : null,
            $lots ? 'Lots: ' . $lots : null,
        ]);

        $rows = [];
        foreach ($this->planActivities($schedule) as $a) {
            $when = $a->targetDate ? \Carbon\Carbon::parse($a->targetDate)->format('M j, Y') : 'no date';
            $done = (int) ($a->isDone ?? 0) === 1 ? 'done' : 'planned';
            $rows[] = '- ' . $when . ': ' . trim((string) $a->activityTitle) . ' (' . $done . ')';
            if (count($rows) >= self::PLAN_MAX_ROWS) {
                $rows[] = '- (…older entries left out)';
                break;
            }
        }

        return "The farmer has attached their cropping plan \"{$schedule->title}\" as background for THIS question.\n"
            . implode('. ', $head) . ".\n"
            . ($rows ? "Work so far, newest first:\n" . implode("\n", $rows) . "\n" : "No activities are on this plan yet.\n")
            . "Read it before answering, and use it where it bears on the question. It is the farmer's own record, "
            . "not a rule: where it disagrees with good practice, say so plainly.\n\nQuestion: ";
    }

    /**
     * Roughly how many input tokens attaching this plan would add.
     *
     * The switch on the composer is not free: it sends the season — its crop,
     * its lots, and every activity on it up to the cap — in front of the
     * question, and the model is billed for every word of it. A farmer
     * turning it on deserves to see the price move before they press send,
     * not after.
     *
     * Four characters to the token is the usual rule of thumb and it is
     * close enough for a quote; the charge itself is always the provider's
     * own count, taken after the answer comes back.
     */
    public function planTokenCost($scheduleId, int $userId): int
    {
        $text = $this->planContext($scheduleId, $userId);

        return $text === '' ? 0 : (int) ceil(mb_strlen($text) / 4);
    }

    /** The plan a caller may attach, or null. */
    private function planFor($scheduleId, int $userId): ?AsCroppingSchedule
    {
        $scheduleId = (int) $scheduleId;

        return $scheduleId
            ? AsCroppingSchedule::active()->forClient($userId)->where('id', $scheduleId)->first()
            : null;
    }

    /** The plan's activities, newest first — what a question is most likely about. */
    private function planActivities(AsCroppingSchedule $schedule)
    {
        return \App\Models\AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->orderByDesc('targetDate')
            ->orderByDesc('id')
            ->limit(self::PLAN_MAX_ROWS + 1)
            ->get();
    }

    /**
     * What attaching this plan would cost, before it is attached.
     *
     * The composer prices a question as it is typed; a plan is by far the
     * biggest thing that can join one, so it cannot be guessed at from the
     * page. Asked here, from the same builder the question will use.
     */
    public function planPreview(Request $request)
    {
        $userId = (int) Auth::id();
        $schedule = $this->planFor($request->query('scheduleId'), $userId);
        if (! $schedule) {
            return $this->json(false, 'That plan could not be found.', [], 404);
        }

        $text = $this->planContext($schedule->id, $userId);
        // The same rule the credit service uses: about four characters a token.
        $tokens = (int) ceil(mb_strlen($text) / 4);

        return $this->json(true, 'Plan measured.', [
            'id' => (int) $schedule->id,
            'title' => (string) $schedule->title,
            'activities' => $this->planActivities($schedule)->count(),
            'tokens' => $tokens,
        ]);
    }

    /** A short factual preamble about the plan the question is about. */
    private function scheduleContext($scheduleId, int $userId): string
    {
        if (! $scheduleId) {
            return '';
        }

        $schedule = AsCroppingSchedule::active()->forClient($userId)->where('id', $scheduleId)->first();
        if (! $schedule) {
            return '';
        }

        $schedule->load('lots');
        $lots = $schedule->lots
            ->map(fn ($l) => trim($l->lotName . ' (' . rtrim(rtrim((string) $l->lotSize, '0'), '.') . ' ' . $l->lotSizeUnit . ')'))
            ->implode(', ');

        $bits = array_filter([
            'Crop: ' . ($schedule->cropType ?: 'not set'),
            $schedule->cropVariety ? 'Variety: ' . $schedule->cropVariety : null,
            $lots ? 'Lots: ' . $lots : null,
        ]);

        // Reference, not premise: told as "the question is about this plan",
        // the model dressed every answer in the schedule's assumptions — the
        // farmer asked about a weed and heard about their rice at 25 DAS.
        return "Background on the farmer's cropping plan \"{$schedule->title}\", for reference only: "
            . implode('. ', $bits) . '. '
            . 'Use it only if the question is clearly about this plan; never assume the question '
            . 'refers to this plan, its crop, or its conditions — if that matters and is unclear, ask.'
            . "\n\nQuestion: ";
    }

    /**
     * Read an uploaded photo back for the provider call. The path is forced
     * into the caller's own folder, so a tampered `imagePath` cannot reach
     * another client's photo or anywhere else on disk.
     */
    private function loadImage(int $userId, string $path, ?int $galleryScheduleId = null): ?array
    {
        // A photo kept by the mother app is fetched over HTTP; one kept here
        // is read off the disk. Either way the folder rule holds: the path
        // must be inside this client's own folder, remote marker and all.
        // MediaStore files everything under an app-level `anisystem/` folder
        // (local and remote alike), so both shapes are this client's own —
        // checking only the bare one is how attached photos were silently
        // dropped before they ever reached the model.
        $bare = \App\Support\MediaStore::isRemote($path)
            ? substr($path, strlen(\App\Support\MediaStore::REMOTE_PREFIX))
            : $path;
        $owned = ! str_contains($bare, '..')
            && (str_starts_with($bare, 'ai-photos/' . $userId . '/')
                || str_starts_with($bare, 'anisystem/ai-photos/' . $userId . '/'));
        // Not the asker's own AI folder: the one other door is a REFERENCE to
        // season media the asker can already see. Attaching used to copy the
        // file at attach time — a download and a re-upload through the mother
        // app before a word was typed, which is the slowness the owner asked
        // about. A reference costs nothing until send, and the allowlist is
        // exactly what the picker offered.
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
     * schedule must be reachable by the asker (their own, or their boss's
     * with a grant) before its list says anything at all.
     */
    private function galleryAllows(int $scheduleId, string $path): bool
    {
        if (! array_key_exists($scheduleId, $this->galleryPaths)) {
            $schedule = AsCroppingSchedule::active()
                ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
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

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
