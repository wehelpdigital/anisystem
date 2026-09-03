<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\EmailQueue;
use App\Support\HtmlSanitizer;
use App\Support\MediaStore;
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

    /**
     * Tickets, grouped by the person who raised them.
     *
     * Support is a conversation with a person, not a pile of subjects: the
     * page walks CLIENTS, newest movement first, each carrying every ticket
     * of theirs that matches the filters. The cursor rides each group's
     * newest ticket id, so a fresh ticket cannot shift the pages mid-scroll.
     */
    public function tickets(Request $request)
    {
        $filtered = SupportTicket::active();
        $status = (string) $request->input('status', '');
        if (in_array($status, ['open', 'answered', 'closed'], true)) {
            $filtered->where('status', $status);
        }
        if ($request->filled('client')) {
            $filtered->where('userId', (int) $request->input('client'));
        }
        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $filtered->where(function ($w) use ($s) {
                $w->where('subject', 'like', "%{$s}%")
                    ->orWhere('ticketNumber', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%")
                        ->orWhere('firstName', 'like', "%{$s}%"));
            });
        }

        $heads = (clone $filtered)->selectRaw('userId, MAX(id) as newestId, COUNT(*) as n')
            ->groupBy('userId')->orderByDesc('newestId');
        if ($request->filled('cursor')) {
            $heads->having('newestId', '<', (int) $request->input('cursor'));
        }
        $heads = $heads->limit(9)->get();
        $more = $heads->count() > 8;
        $heads = $heads->take(8);

        $tickets = $heads->isEmpty() ? collect() : (clone $filtered)
            ->whereIn('userId', $heads->pluck('userId'))
            ->with('user')->withCount('messages')->orderByDesc('id')->get()->groupBy('userId');
        $users = User::whereIn('id', $heads->pluck('userId'))->get()->keyBy('id');

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'rows' => $heads->map(function ($h) use ($tickets, $users) {
                $u = $users[$h->userId] ?? null;

                return [
                    'clientId' => (int) $h->userId,
                    'clientName' => $u ? (trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? '')) ?: $u->email) : 'Removed account',
                    'clientEmail' => $u->email ?? '',
                    'count' => (int) $h->n,
                    'tickets' => ($tickets[$h->userId] ?? collect())->map(fn ($t) => $this->row($t))->values(),
                ];
            })->values(),
            'nextCursor' => $more ? (int) $heads->last()->newestId : null,
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
                'format' => $m->bodyFormat ?? 'text',
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
        $data = $request->validate([
            'body' => 'required|string|max:16000',
            'format' => 'nullable|in:text,html',
        ]);

        $admin = Auth::user();
        $adminName = trim((string) ($admin->firstName ?? '')) ?: 'Support team';

        /* Merge fields first — {first_name}, {ticket_no} and friends become
         * this client's own facts — then, for a rich reply, the same purifier
         * every client-authored rich text passes through. */
        $isHtml = ($data['format'] ?? 'text') === 'html';
        $body = $this->mergeFields($data['body'], $t, $adminName);
        $body = $isHtml ? HtmlSanitizer::rich($body) : $body;
        $data['body'] = $body;

        DB::transaction(function () use ($t, $data, $admin, $adminName, $isHtml) {
            SupportMessage::create([
                'ticketId' => $t->id,
                'authorType' => 'admin',
                'authorId' => (int) $admin->id,
                'authorName' => $adminName,
                'body' => $data['body'],
                'bodyFormat' => $isHtml ? 'html' : 'text',
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
                . ($isHtml ? $data['body'] : nl2br(e($data['body']))) . '</blockquote>'
                . '<p><a href="' . $link . '">Open the ticket</a> to reply.</p>',
                ['templateKey' => 'support_reply', 'relatedType' => 'support_ticket', 'relatedId' => $t->id],
            );
        }

        return response()->json(['success' => true, 'message' => 'Reply sent — the client is notified in the app and by email.']);
    }

    /**
     * {first_name} and friends, resolved from the ticket in hand. Single
     * braces (Blade never sees these), resolved at send time so one canned
     * answer greets every client by their own name.
     */
    private function mergeFields(string $body, SupportTicket $t, string $adminName): string
    {
        return strtr($body, [
            '{first_name}' => $t->user->firstName ?? 'there',
            '{last_name}' => $t->user->lastName ?? '',
            '{email}' => $t->user->email ?? '',
            '{ticket_no}' => (string) $t->ticketNumber,
            '{subject}' => (string) $t->subject,
            '{admin_name}' => $adminName,
        ]);
    }

    /* ------------------------------------------------------ canned answers */

    /** The shelf, alphabetical. */
    public function canned()
    {
        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'rows' => DB::table('as_support_canned')->where('deleteStatus', 1)
                ->orderBy('title')->get(['id', 'title', 'body']),
        ]]);
    }

    /** Write one — new when no id, edited when one is given. */
    public function saveCanned(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:16000',
        ]);
        $row = [
            'title' => trim($data['title']),
            'body' => HtmlSanitizer::rich($data['body']),
            'updated_at' => now(),
        ];

        if (! empty($data['id'])) {
            $hit = DB::table('as_support_canned')->where('id', (int) $data['id'])
                ->where('deleteStatus', 1)->update($row);
            if (! $hit) {
                return response()->json(['success' => false, 'message' => 'That template is gone already.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Template saved.']);
        }

        DB::table('as_support_canned')->insert($row + ['deleteStatus' => 1, 'created_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Template added to the shelf.']);
    }

    public function deleteCanned(int $id)
    {
        DB::table('as_support_canned')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Template removed.']);
    }

    /**
     * One picture or clip for a reply, through the house MediaStore. Images
     * go inline; a video rides as a link the thread turns into a player,
     * because the purifier speaks HTML4 and <video> is not its word.
     */
    public function media(Request $request)
    {
        $request->validate(['file' => 'required|file|max:51200|mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov']);
        $file = $request->file('file');
        $kind = str_starts_with((string) $file->getMimeType(), 'video') ? 'video' : 'image';
        $path = MediaStore::putFile($file, 'support');
        if (! $path) {
            return response()->json(['success' => false, 'message' => 'The file could not be stored.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'url' => MediaStore::url($path),
            'kind' => $kind,
        ]]);
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
