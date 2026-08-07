<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Client-side Support: raise a ticket for an issue and follow the thread.
 * Admins answer from the mother app; their replies notify the client's bell.
 */
class SupportController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::active()
            ->where('userId', (int) Auth::id())
            ->withCount('messages')
            ->orderByRaw("CASE status WHEN 'answered' THEN 0 WHEN 'open' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->get();

        return view('support.index', ['tickets' => $tickets]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:200',
            'category' => 'nullable|string|max:40',
            'body' => 'required|string|max:8000',
        ]);
        $category = array_key_exists($data['category'] ?? '', SupportTicket::CATEGORIES) ? $data['category'] : 'general';

        $user = Auth::user();
        $ticket = SupportTicket::create([
            'userId' => (int) $user->id,
            'subject' => $data['subject'],
            'category' => $category,
            'status' => 'open',
            'lastReplyAt' => Carbon::now(),
            'deleteStatus' => 1,
        ]);
        SupportMessage::create([
            'ticketId' => $ticket->id,
            'authorType' => 'client',
            'authorId' => (int) $user->id,
            'authorName' => $user->full_name,
            'body' => $data['body'],
            'deleteStatus' => 1,
        ]);

        return redirect()->route('support.show', ['id' => $ticket->id])->with('success', 'Ticket submitted — our team will reply here.');
    }

    public function show(Request $request, int $id)
    {
        $ticket = SupportTicket::active()->where('userId', (int) Auth::id())->where('id', $id)->firstOrFail();
        $messages = $ticket->messages()->orderBy('id')->get();

        return view('support.show', ['ticket' => $ticket, 'messages' => $messages]);
    }

    public function reply(Request $request, int $id)
    {
        $ticket = SupportTicket::active()->where('userId', (int) Auth::id())->where('id', $id)->firstOrFail();
        $data = $request->validate(['body' => 'required|string|max:8000']);

        $user = Auth::user();
        SupportMessage::create([
            'ticketId' => $ticket->id,
            'authorType' => 'client',
            'authorId' => (int) $user->id,
            'authorName' => $user->full_name,
            'body' => $data['body'],
            'deleteStatus' => 1,
        ]);
        $ticket->update(['status' => 'open', 'lastReplyAt' => Carbon::now()]);

        return redirect()->route('support.show', ['id' => $ticket->id]);
    }
}
