<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * What the community flagged, read from the answering side.
 *
 * Every report carries a snapshot taken at the moment of the complaint, so
 * the row still says what was said even after the author edits or deletes.
 * The link goes to where the thing lives; the judgment happens in the
 * Clients tab (a suspension) or in the community itself (a removal).
 */
class AdminReportsController extends Controller
{
    public function page()
    {
        return view('admin.reports');
    }

    /** Reports, newest first, paged for the scroll. */
    public function reports(Request $request)
    {
        $q = CommunityReport::where('deleteStatus', 1);

        $status = (string) $request->input('status', '');
        if (in_array($status, ['open', 'handled', 'dismissed'], true)) {
            $q->where('status', $status);
        }
        if ($request->filled('cursor')) {
            $q->where('id', '<', (int) $request->input('cursor'));
        }

        $rows = $q->orderByDesc('id')->limit(13)->get();
        $more = $rows->count() > 12;
        $rows = $rows->take(12);

        // Both ends of every report, named in two grouped queries.
        $ids = $rows->pluck('reporterUserId')->merge($rows->pluck('targetUserId'))->filter()->unique()->values();
        $names = User::whereIn('id', $ids)->get()
            ->mapWithKeys(fn ($u) => [$u->id => trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? '')) ?: $u->email]);

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'rows' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->targetType,
                'reason' => $r->reason,
                'details' => $r->details,
                'snapshot' => $r->snapshot,
                'status' => $r->status,
                'reporter' => $names[$r->reporterUserId] ?? 'Removed account',
                'target' => $r->targetUserId ? ($names[$r->targetUserId] ?? 'Removed account') : null,
                'targetUserId' => $r->targetUserId,
                'at' => $r->created_at?->format('M j, Y · g:ia'),
                'url' => $this->whereItLives($r),
            ])->values(),
            'nextCursor' => $more ? $rows->last()->id : null,
            'counts' => [
                'open' => CommunityReport::where('deleteStatus', 1)->where('status', 'open')->count(),
            ],
        ]]);
    }

    /** open → handled | dismissed, with the reviewer's name on it. */
    public function setStatus(Request $request, int $id)
    {
        $r = CommunityReport::where('deleteStatus', 1)->findOrFail($id);
        $data = $request->validate([
            'status' => 'required|in:open,handled,dismissed',
            'note' => 'nullable|string|max:500',
        ]);

        $r->update([
            'status' => $data['status'],
            'note' => trim((string) ($data['note'] ?? '')) ?: $r->note,
            'reviewedByUserId' => $data['status'] === 'open' ? null : Auth::id(),
            'reviewedAt' => $data['status'] === 'open' ? null : now(),
        ]);

        return response()->json(['success' => true, 'message' => [
            'open' => 'Reopened.', 'handled' => 'Marked handled.', 'dismissed' => 'Dismissed.',
        ][$data['status']]]);
    }

    /**
     * The nearest page the reported thing lives on. A member has an exact
     * door; content links land in its area — the snapshot in the row is the
     * evidence either way, deletion-proof.
     */
    private function whereItLives(CommunityReport $r): string
    {
        return match ($r->targetType) {
            'member' => '/app/community/members/' . $r->targetId,
            'group', 'topic', 'reply' => '/app/community/groups-page',
            default => '/app/community',
        };
    }
}
