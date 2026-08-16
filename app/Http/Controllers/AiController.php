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
    /** How many previous turns to send back as context. */
    private const HISTORY_TURNS = 10;

    public function __construct(
        private readonly AiClient $ai,
        private readonly AiCreditService $credits,
    ) {
    }

    public function index(Request $request)
    {
        // AI is a Boss/Lifetime feature — Basic can't use it.
        if (! $request->user()->canUseAi()) {
            return view('ai.locked', ['tier' => $request->user()->planTier()]);
        }

        $userId = Auth::id();
        $settings = AiSetting::current();

        $conversation = $this->resolveConversation($request, $userId);

        return view('ai.index', [
            'settings' => $settings,
            'balance' => $this->credits->balance($userId),
            'conversation' => $conversation,
            'messages' => $conversation ? $conversation->messages : collect(),
            'conversations' => AiConversation::active()
                ->where('userId', $userId)
                ->with('linkedActivity')
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get(),
            'schedules' => AsCroppingSchedule::active()->forClient($userId)->orderByDesc('id')->get(),
        ]);
    }

    /** Ask a question. Charges credits based on the tokens actually used. */
    public function ask(Request $request)
    {
        if (! $request->user()->canUseAi()) {
            return $this->json(false, 'AI is available on Boss and Lifetime plans. Upgrade to unlock the AI Technician.', [], 403);
        }

        $userId = Auth::id();
        $settings = AiSetting::current();

        if (! $settings->isUsable()) {
            return $this->json(false, 'The AI Technician is not switched on yet. Please check back soon.', [], 503);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:4000',
            'conversationId' => 'nullable|integer',
            'imagePath' => 'nullable|string|max:500',
            'scheduleId' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return $this->json(false, 'Validation failed.', ['errors' => $validator->errors()], 422);
        }

        $prompt = trim($request->input('message'));
        $imagePath = $request->input('imagePath');
        $image = $imagePath ? $this->loadImage($userId, $imagePath) : null;

        // Refuse before spending anything the client does not have.
        $balance = $this->credits->balance($userId);
        $estimate = $this->credits->estimate($settings, $prompt, $image ? 1 : 0);
        if ($balance < $estimate) {
            return $this->json(false, $balance <= 0
                ? 'You have no AI Credits left. Top up to keep asking questions.'
                : 'You need about ' . ceil($estimate) . ' credits for this question and have ' . rtrim(rtrim(number_format($balance, 2), '0'), '.') . '.',
                ['balance' => $balance, 'needed' => $estimate, 'outOfCredits' => true], 402);
        }

        $conversation = $this->resolveConversation($request, $userId, true);

        // Attach the plan the question is about, when one is selected — plus the
        // day/activity this thread is pinned to, when the farmer set one.
        $context = $this->scheduleContext($request->input('scheduleId'), $userId);
        $context = $this->applyLinkContext($context, $conversation);

        $userMessage = AiMessage::create([
            'conversationId' => $conversation->id,
            'role' => 'user',
            'content' => $prompt,
            'imagePath' => $imagePath,
            'deleteStatus' => 1,
        ]);

        $history = $conversation->messages()
            ->where('id', '<', $userMessage->id)
            ->orderByDesc('id')
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

        $charged = $this->credits->priceFor($settings, $result['tokensIn'], $result['tokensOut'], $image ? 1 : 0);

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
            $userId,
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

        $latest = $base()->orderByDesc('updated_at')->first();
        if ($latest) {
            return $latest;
        }

        return $createIfMissing
            ? AiConversation::create([
                'userId' => $userId,
                'croppingScheduleId' => $scheduleId ?: null,
                'title' => 'New question',
                'deleteStatus' => 1,
            ])
            : null;
    }

    /**
     * The AI Technician scoped to one cropping schedule (an in-shell module).
     * Same chat + endpoints as the standalone page, but history is this
     * schedule's only, and the plan context is always attached.
     */
    public function schedulePage(Request $request)
    {
        $userId = Auth::id();
        $schedule = AsCroppingSchedule::active()->forClient($userId)->where('id', $request->query('id'))->first();
        if (! $schedule) {
            abort(404);
        }

        $settings = AiSetting::current();
        $conversation = $this->resolveConversation(
            $request->merge(['scheduleId' => $schedule->id]),
            $userId
        );
        $conversation?->loadMissing('linkedActivity');

        return view('sm.ai', [
            'schedule' => $schedule,
            'settings' => $settings,
            'balance' => $this->credits->balance($userId),
            'conversation' => $conversation,
            'messages' => $conversation ? $conversation->messages : collect(),
            'conversations' => AiConversation::active()
                ->where('userId', $userId)
                ->where('croppingScheduleId', $schedule->id)
                ->with('linkedActivity')
                ->orderByDesc('updated_at')
                ->limit(30)
                ->get(),
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

        return "The farmer is asking about their cropping plan \"{$schedule->title}\". "
            . implode('. ', $bits) . ".\n\nQuestion: ";
    }

    /**
     * Read an uploaded photo back for the provider call. The path is forced
     * into the caller's own folder, so a tampered `imagePath` cannot reach
     * another client's photo or anywhere else on disk.
     */
    private function loadImage(int $userId, string $path): ?array
    {
        // A photo kept by the mother app is fetched over HTTP; one kept here
        // is read off the disk. Either way the folder rule holds: the path
        // must be inside this client's own folder, remote marker and all.
        $bare = \App\Support\MediaStore::isRemote($path)
            ? substr($path, strlen(\App\Support\MediaStore::REMOTE_PREFIX))
            : $path;
        $expectedPrefix = 'ai-photos/' . $userId . '/';
        if (! str_starts_with($bare, $expectedPrefix) || str_contains($bare, '..')) {
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

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
