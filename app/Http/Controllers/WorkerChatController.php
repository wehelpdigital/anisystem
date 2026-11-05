<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiSetting;
use App\Support\WorkerContext;
use Illuminate\Support\Facades\Auth;

/**
 * The chats workers had with Anee from their worker accounts, for the farm owner to read
 * (2026-11-06, the owner). The worker is told so beside the box they type in.
 *
 * Read only, and on a page of its own. Every other chat path stays fenced to the person
 * who asked, on purpose: a turn sent to the model is read off the conversation, so a chat
 * the owner could continue would put another person's words in the owner's context
 * (see AiController::resolveConversation and the check in ask()).
 */
class WorkerChatController extends Controller
{
    private function owner(): int
    {
        abort_if(WorkerContext::inWorkerContext(), 403);

        return (int) Auth::id();
    }

    public function index()
    {
        $chats = AiConversation::active()
            ->where('farmOwnerId', $this->owner())
            ->with('schedule:id,title')
            ->orderByDesc('updated_at')
            ->paginate(30);

        return view('ai.worker-chats', ['chats' => $chats]);
    }

    public function show(int $id)
    {
        $chat = AiConversation::active()->where('farmOwnerId', $this->owner())->where('id', $id)->first();
        abort_unless($chat, 404);

        return view('ai.worker-chat', [
            'chat' => $chat,
            'messages' => $chat->messages()->limit(400)->get(),
            'season' => $chat->schedule?->title,
            'settings' => AiSetting::current(),
        ]);
    }
}
