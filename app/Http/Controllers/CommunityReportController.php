<?php

namespace App\Http\Controllers;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityReport;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Taking a report.
 *
 * A report changes nothing about the content — it is a message to the people
 * who run the place, and they decide in the mother app. What this does is
 * write it down carefully: who, what, why, and a copy of what was said at the
 * time, because the thing being complained about can be edited or deleted
 * before anybody looks at it.
 */
class CommunityReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|in:' . implode(',', CommunityReport::TYPES),
            'id' => 'required|integer|min:1',
            'reason' => 'required|string|max:60',
            'details' => 'nullable|string|max:1000',
        ]);

        if (! array_key_exists($data['reason'], CommunityReport::reasons())) {
            return $this->json(false, 'Pick a reason from the list.', [], 422);
        }

        $meId = (int) Auth::id();
        $subject = $this->subject($data['type'], (int) $data['id']);
        if (! $subject) {
            return $this->json(false, 'That is gone already.', [], 404);
        }

        // Reporting your own is not a report; it is a delete you have not
        // done yet.
        if ((int) ($subject['userId'] ?? 0) === $meId) {
            return $this->json(false, 'This is yours — you can delete it instead.', [], 422);
        }

        /* Once each. A second report from the same person on the same thing
         * tells the house nothing it does not already know, and a queue full
         * of duplicates is a queue nobody reads. */
        $already = CommunityReport::where('deleteStatus', 1)
            ->where('reporterUserId', $meId)
            ->where('targetType', $data['type'])
            ->where('targetId', (int) $data['id'])
            ->exists();
        if ($already) {
            return $this->json(true, 'You have already reported this — the team is looking at it.');
        }

        CommunityReport::create([
            'reporterUserId' => $meId,
            'targetType' => $data['type'],
            'targetId' => (int) $data['id'],
            'targetUserId' => $subject['userId'] ?? null,
            'reason' => $data['reason'],
            'details' => trim((string) ($data['details'] ?? '')) ?: null,
            'snapshot' => $subject['text'],
            'status' => 'open',
            'deleteStatus' => 1,
        ]);

        return $this->json(true, 'Thank you — the team will take a look.');
    }

    /**
     * What is being reported, as far as this app can describe it.
     *
     * @return array{userId:?int,text:string}|null
     */
    private function subject(string $type, int $id): ?array
    {
        $say = fn ($text) => Str::limit(trim(strip_tags((string) $text)), 500);

        if ($type === 'post' || $type === 'story') {
            $row = CommunityWallPost::active()->find($id);

            return $row ? ['userId' => (int) $row->authorUserId, 'text' => $say($row->body)] : null;
        }
        if ($type === 'comment') {
            // A wall comment keys its author as userId, not authorUserId.
            $row = CommunityWallComment::active()->find($id);

            return $row ? ['userId' => (int) $row->userId, 'text' => $say($row->body)] : null;
        }
        if ($type === 'topic') {
            $row = CommunityGroupPost::active()->find($id);

            return $row ? ['userId' => (int) $row->userId, 'text' => $say($row->title . ' — ' . $row->body)] : null;
        }
        if ($type === 'reply') {
            $row = CommunityGroupReply::active()->find($id);

            return $row ? ['userId' => (int) $row->userId, 'text' => $say($row->body)] : null;
        }
        if ($type === 'group') {
            $row = CommunityGroup::active()->find($id);

            return $row ? ['userId' => (int) $row->createdByUserId, 'text' => $say($row->name . ' — ' . $row->description)] : null;
        }
        if ($type === 'member') {
            // The profile's flag: the member themselves is the subject.
            $row = \App\Models\User::where('id', $id)->where('deleteStatus', 1)->first();

            return $row ? ['userId' => (int) $row->id, 'text' => $say($row->full_name . ' — member profile')] : null;
        }

        return null;
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
