<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\CommunityConnection;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Co-farmer connections: a member directory, member profiles, and mutual
 * friend requests (request → accept). Accepted connections see more of each
 * other (their wall, in the account-wall feature).
 */
class CommunityConnectController extends Controller
{
    private const PER_PAGE = 12;

    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /** Directory of members with the viewer's relationship to each. */
    public function members(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $q = trim((string) $request->query('q'));

        $result = $this->pageMembers(Auth::id(), $q, $page);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'html' => view('community.connect.partials.members', ['members' => $result['items']])->render(),
                    'hasMore' => $result['hasMore'],
                    'nextPage' => $page + 1,
                ],
            ]);
        }

        return view('community.connect.index', [
            'members' => $result['items'],
            'hasMore' => $result['hasMore'],
            'q' => $q,
            'pendingCount' => CommunityConnection::active()
                ->where('friendUserId', Auth::id())->where('status', 'pending')->count(),
        ]);
    }

    /** Incoming friend requests waiting on the viewer. */
    public function requests(Request $request)
    {
        $rows = CommunityConnection::active()
            ->where('friendUserId', Auth::id())
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();

        $requesters = User::whereIn('id', $rows->pluck('userId'))->get()->keyBy('id');

        return view('community.connect.requests', [
            'rows' => $rows,
            'requesters' => $requesters,
        ]);
    }

    /** A member's public profile. */
    public function profile(Request $request, int $userId)
    {
        $member = User::where('id', $userId)->where('deleteStatus', 1)->first();
        if (! $member) {
            abort(404);
        }

        $status = CommunityConnection::statusFor(Auth::id(), $member->id);

        $plans = AsCroppingSchedule::active()
            ->where('anisystemUserId', $member->id)
            ->where('isPublic', 1)
            ->orderByDesc('publishedAt')
            ->get();

        return view('community.connect.profile', [
            'member' => $member,
            'status' => $status,
            'isSelf' => (int) $member->id === (int) Auth::id(),
            'plans' => $plans,
            'connectionCount' => count(CommunityConnection::connectedIds($member->id)),
        ]);
    }

    public function connect(Request $request, int $userId)
    {
        if ($userId === (int) Auth::id()) {
            return $this->fail('You cannot connect with yourself.');
        }
        $target = User::where('id', $userId)->where('deleteStatus', 1)->first();
        if (! $target) {
            return $this->fail('Member not found.', 404);
        }

        $status = CommunityConnection::statusFor(Auth::id(), $userId);
        if ($status === 'connected') {
            return $this->fail('You are already connected.');
        }
        if ($status === 'pending_out') {
            return $this->ok('Request already sent.', ['status' => 'pending_out']);
        }
        if ($status === 'pending_in') {
            // They already asked you — treat this as an accept.
            return $this->accept($request, $userId);
        }

        CommunityConnection::updateOrCreate(
            ['userId' => Auth::id(), 'friendUserId' => $userId],
            ['status' => 'pending', 'respondedAt' => null, 'deleteStatus' => 1]
        );

        $actor = Auth::user();
        $this->notifications->notify(
            userId: $userId,
            type: 'connection',
            title: ($actor->full_name ?: 'A member') . ' wants to connect',
            body: 'Tap to review the request.',
            url: route('community.connect.requests'),
            actorUserId: (int) Auth::id(),
        );

        return $this->ok('Request sent.', ['status' => 'pending_out']);
    }

    public function accept(Request $request, int $userId)
    {
        $row = CommunityConnection::active()
            ->where('userId', $userId)->where('friendUserId', Auth::id())
            ->where('status', 'pending')->first();
        if (! $row) {
            return $this->fail('No pending request from this member.', 404);
        }

        $row->update(['status' => 'accepted', 'respondedAt' => now()]);

        $actor = Auth::user();
        $this->notifications->notify(
            userId: $userId,
            type: 'connection',
            title: ($actor->full_name ?: 'A member') . ' accepted your request',
            body: 'You are now connected.',
            url: route('community.connect.profile', ['userId' => Auth::id()]),
            actorUserId: (int) Auth::id(),
        );

        return $this->ok('You are now connected.', ['status' => 'connected']);
    }

    public function decline(Request $request, int $userId)
    {
        CommunityConnection::active()
            ->where('userId', $userId)->where('friendUserId', Auth::id())
            ->where('status', 'pending')
            ->update(['status' => 'declined', 'respondedAt' => now()]);

        return $this->ok('Request declined.', ['status' => 'none']);
    }

    public function disconnect(Request $request, int $userId)
    {
        $row = CommunityConnection::between(Auth::id(), $userId);
        if ($row) {
            $row->update(['deleteStatus' => 0]);
        }

        return $this->ok('Connection removed.', ['status' => 'none']);
    }

    // ------------------------------------------------------------------

    /**
     * @return array{items:\Illuminate\Support\Collection, hasMore:bool}
     */
    private function pageMembers(int $viewerId, string $q, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        $query = User::where('deleteStatus', 1)
            ->where('id', '!=', $viewerId)
            ->when($q !== '', function ($sub) use ($q) {
                $term = '%' . $q . '%';
                $sub->where(function ($w) use ($term) {
                    $w->where('firstName', 'like', $term)
                        ->orWhere('lastName', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('province', 'like', $term);
                });
            })
            ->orderBy('firstName')
            ->orderBy('lastName');

        $rows = $query->skip($offset)->take(self::PER_PAGE + 1)->get();
        $hasMore = $rows->count() > self::PER_PAGE;
        $items = $rows->take(self::PER_PAGE)->values();

        foreach ($items as $m) {
            $m->connStatus = CommunityConnection::statusFor($viewerId, $m->id);
        }

        return ['items' => $items, 'hasMore' => $hasMore];
    }

    private function ok(string $message, array $data = [])
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    private function fail(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
