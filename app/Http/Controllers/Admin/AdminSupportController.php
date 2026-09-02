<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Services\EmailQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Support, from the answering side.
 *
 * The same tickets the client raises at /app/support, read from the other end
 * of the table. A reply here lands in the client's thread, rings their bell,
 * and goes out as an email — three deliveries for one answer, because a farmer
 * checks email more often than an app.
 */
class AdminSupportController extends Controller
{
    public function page()
    {
        return view('admin.support');
    }

    /** Tickets, newest movement first, filtered and paged for the scroll. */
    public function tickets(Request $request)
    {
        $q = SupportTicket::active()->with('user')->withCount('messages');

        $status = (string) $request->input('status', '');
        if (in_array($status, ['open', 'answered', 'closed'], true)) {
            $q->where('status', $status);
        }
        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $q->where(function ($w) use ($s) {
                $w->where('subject', 'like', "%{$s}%")
                    ->orWhere('ticketNumber', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")
                        ->orWhere('firstName', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('cursor')) {
            $q->where('id', '<', (int) $request->input('cursor'));
        }

        $rows = $q->orderByDesc('id')->limit(13)->get();
        $more = $rows->count() > 12;
        $rows = $rows->take(12);

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'rows' => $rows->map(fn ($t) => $this->row($t))->values(),
            'nextCursor' => $more ? $rows->last()->id : null,
            'counts' => [
                'open' => SupportTicket::active()->where('status', 'open')->count(),
                'answered' => SupportTicket::active()->where('status', 'answered')->count(),
                'closed' => SupportTicket::active()->where('status', 'closed')->count(),
            ],
        ]]);
    }

    /** One ticket with its whole thread. */
    public function one(int $id)
    {
        $t = SupportTicket::active()->with('user')->findOrFail($id);
        $messages = SupportMessage::where('ticketId', $t->id)->where('deleteStatus', 1)
            ->orderBy('id')->get();

        /* array_merge, not `+`: row() already carries a 'messages' COUNT for
         * the list, and `+` keeps the left key — the thread arrived as null
         * and the sheet drew an empty conversation over a real one. */
        return response()->json(['success' => true, 'message' => 'ok', 'data' => array_merge($this->row($t), [
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'mine' => $m->authorType === 'admin',
                'author' => $m->authorName ?: ($m->authorType === 'admin' ? 'Support team' : 'Client'),
                'body' => $m->body,
                'at' => $m->created_at?->format('M j, Y · g:ia'),
            ])->values(),
        ])]);
    }

    /**
     * Answer. One transaction writes the message, flips the ticket to
     * answered and rings the bell; the email goes after the commit, because a
     * failed mail must not unsay a reply that the client can already see.
     */
    public function reply(Request $request, int $id)
    {
        $t = SupportTicket::active()->with('user')->findOrFail($id);
        $data = $request->validate(['body' => 'required|string|max:8000']);

        $admin = Auth::user();
        $adminName = trim((string) ($admin->firstName ?? '')) ?: 'Support team';

        DB::transaction(function () use ($t, $data, $admin, $adminName) {
            SupportMessage::create([
                'ticketId' => $t->id,
                'authorType' => 'admin',
                'authorId' => (int) $admin->id,
                'authorName' => $adminName,
                'body' => $data['body'],
                'deleteStatus' => 1,
            ]);
            $t->update(['status' => 'answered', 'lastReplyAt' => now()]);

            DB::table('anisystem_notifications')->insert([
                'userId' => $t->userId,
                'type' => 'support',
                'title' => 'Support replied to your ticket',
                'body' => Str::limit($t->subject, 90),
                'url' => '/app/support/' . $t->id,
                'actorUserId' => null,
                'croppingScheduleId' => null,
                'readAt' => null,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        /* The email: the reply itself, under the ticket number, with the way
         * back to the thread. Queued through the house mail book so the Mail
         * Log shows it like everything else. */
        if ($t->user && $t->user->email) {
            $link = url('/app/support/' . $t->id);
            app(EmailQueue::class)->queueAndSend(
                $t->user->email,
                trim(($t->user->firstName ?? '') . ' ' . ($t->user->lastName ?? '')),
                '[' . $t->ticketNumber . '] Re: ' . Str::limit($t->subject, 120),
                '<p>Hi ' . e($t->user->firstName ?: 'there') . ',</p>'
                . '<p>Our team replied to your support ticket <strong>' . e($t->ticketNumber) . '</strong> — “' . e($t->subject) . '”:</p>'
                . '<blockquote style="margin:12px 0;padding:10px 14px;border-left:3px solid #4c9a2a;background:#f6f9f2;">'
                . nl2br(e($data['body'])) . '</blockquote>'
                . '<p><a href="' . $link . '">Open the ticket</a> to reply.</p>',
                ['templateKey' => 'support_reply', 'relatedType' => 'support_ticket', 'relatedId' => $t->id],
            );
        }

        return response()->json(['success' => true, 'message' => 'Reply sent — the client is notified in the app and by email.']);
    }

    /** Manage: open ↔ closed. Answered is earned by replying, not picked. */
    public function setStatus(Request $request, int $id)
    {
        $t = SupportTicket::active()->findOrFail($id);
        $data = $request->validate(['status' => 'required|in:open,closed']);

        $t->update(['status' => $data['status']]);

        return response()->json(['success' => true, 'message' => $data['status'] === 'closed'
            ? 'Ticket closed.' : 'Ticket reopened.', 'data' => $this->row($t->fresh()->load('user'))]);
    }

    private function row(SupportTicket $t): array
    {
        return [
            'id' => $t->id,
            'no' => $t->ticketNumber,
            'subject' => $t->subject,
            'category' => SupportTicket::CATEGORIES[$t->category] ?? ucfirst((string) $t->category),
            'status' => $t->status,
            'clientName' => trim(($t->user->firstName ?? '') . ' ' . ($t->user->lastName ?? '')) ?: ($t->user->email ?? 'Removed account'),
            'clientEmail' => $t->user->email ?? '',
            'clientId' => $t->userId,
            'messages' => $t->messages_count ?? null,
            'last' => ($t->lastReplyAt ?? $t->created_at)?->format('M j, Y · g:ia'),
        ];
    }
}
