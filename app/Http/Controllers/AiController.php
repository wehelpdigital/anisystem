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
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get(),
            'schedules' => AsCroppingSchedule::active()->forClient($userId)->orderByDesc('id')->get(),
        ]);
    }

    /** Ask a question. Charges credits based on the tokens actually used. */
    public function ask(Request $request)
    {
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

        // Attach the plan the question is about, when one is selected.
        $context = $this->scheduleContext($request->input('scheduleId'), $userId);

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
            Storage::disk('public')->putFileAs($dir, $file, $stem . '.' . $ext);
        } catch (\Throwable $e) {
            return $this->json(false, 'Photo upload failed: ' . $e->getMessage(), [], 500);
        }

        $path = $dir . '/' . $stem . '.' . $ext;

        return $this->json(true, 'Photo attached.', [
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
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

    // ------------------------------------------------------------------

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

        return view('sm.ai', [
            'schedule' => $schedule,
            'settings' => $settings,
            'balance' => $this->credits->balance($userId),
            'conversation' => $conversation,
            'messages' => $conversation ? $conversation->messages : collect(),
            'conversations' => AiConversation::active()
                ->where('userId', $userId)
                ->where('croppingScheduleId', $schedule->id)
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
        $expectedPrefix = 'ai-photos/' . $userId . '/';
        if (! str_starts_with($path, $expectedPrefix) || str_contains($path, '..')) {
            return null;
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
