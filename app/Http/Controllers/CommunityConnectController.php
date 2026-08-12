<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\CommunityConnection;
use App\Models\CommunityProfilePhoto;
use App\Models\CommunityProfileVideo;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\VideoOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    /**
     * @-mention autocomplete: members matching the typed name, with the
     * viewer's own co-farmers surfaced first. Returns id + name + avatar.
     */
    public function mentionSearch(Request $request)
    {
        $meId = (int) Auth::id();
        $q = trim((string) $request->query('q'));
        $friendIds = CommunityConnection::connectedIds($meId);

        $rows = User::where('deleteStatus', 1)
            ->where('id', '!=', $meId)
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($w) use ($like) {
                    $w->where('firstName', 'like', $like)
                        ->orWhere('lastName', 'like', $like)
                        ->orWhereRaw("CONCAT(firstName,' ',lastName) like ?", [$like]);
                });
            })
            ->limit(40)->get(['id', 'firstName', 'lastName', 'avatarPath']);

        // Connections first, then alphabetical; cap at 6 to leave room for
        // location suggestions in the same dropdown.
        $friendSet = array_flip($friendIds);
        $items = $rows->sortBy(fn ($u) => [isset($friendSet[$u->id]) ? 0 : 1, mb_strtolower($u->full_name)])
            ->take(6)
            ->map(fn ($u) => [
                'type' => 'user',
                'id' => $u->id,
                'name' => $u->full_name,
                'avatar' => $u->avatarPath ? \App\Support\MediaStore::url($u->avatarPath) : null,
                'initials' => $u->initials,
                'isFriend' => isset($friendSet[$u->id]),
            ])->values();

        // Location suggestions: distinct "Town, Province" of members, matched
        // on the typed text. Picking one inserts a @[Town, Province](loc:slug)
        // token that links to the location page.
        $locations = $this->locationSuggestions($q);

        return response()->json(['success' => true, 'data' => [
            'items' => $items->all(),
            'locations' => $locations,
        ]]);
    }

    /**
     * Place suggestions from the full PH gazetteer (as_locations) matching the
     * typed text — provinces, cities and barangays, not just where members live.
     * Picking one inserts a @[Label](loc:slug) token linking to the place page.
     */
    private function locationSuggestions(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }

        // Prefix match on the place name (indexed); provinces first, then cities,
        // then barangays, with the shortest (closest) names first.
        $rows = \App\Models\AsLocation::where('name', 'like', $q . '%')
            ->orderBy('sort')
            ->orderByRaw('CHAR_LENGTH(name)')
            ->orderBy('name')
            ->limit(6)
            ->get(['label', 'slug']);

        return $rows->map(fn ($r) => [
            'type' => 'location',
            'id' => 'loc:' . $r->slug,
            'name' => \Illuminate\Support\Str::limit($r->label, 78, ''),
            'slug' => $r->slug,
        ])->all();
    }

    /**
     * The viewer's accepted co-farmers as JSON — id, name, avatar, and whether
     * they accept messages. Used by the schedule-share sheet's "send to a
     * co-farmer" picker.
     */
    public function cofarmersList(Request $request)
    {
        $meId = (int) Auth::id();
        $ids = CommunityConnection::connectedIds($meId);

        $items = User::whereIn('id', $ids ?: [0])
            ->where('deleteStatus', 1)
            ->orderBy('firstName')
            ->get(['id', 'firstName', 'lastName', 'avatarPath', 'allowMessages'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'avatar' => $u->avatarPath ? \App\Support\MediaStore::url($u->avatarPath) : null,
                'initials' => $u->initials,
                'allowMessages' => (bool) $u->allowMessages,
            ])->values();

        return response()->json(['success' => true, 'data' => ['items' => $items]]);
    }

    /** Quick-set the viewer's status bubble (the thought over their avatar). */
    public function updateStatus(Request $request)
    {
        // Capped short so the thought-bubble stays a single tidy line.
        $data = $request->validate([
            'statusBubble' => ['nullable', 'string', 'max:60'],
        ]);
        $value = trim((string) ($data['statusBubble'] ?? ''));
        $request->user()->update(['statusBubble' => $value !== '' ? $value : null]);

        return response()->json(['success' => true, 'message' => $value !== '' ? 'Status updated.' : 'Status cleared.', 'data' => ['statusBubble' => $value]]);
    }

    /** Directory of members with the viewer's relationship to each. */
    /** My Co-Farmers: every accepted connection plus the latest from each. */
    public function cofarmers(Request $request)
    {
        $meId = (int) Auth::id();
        $ids = \App\Models\CommunityConnection::connectedIds($meId);
        $friends = \App\Models\User::whereIn('id', $ids)
            ->where('deleteStatus', 1)
            ->orderBy('firstName')
            ->get();

        $latestPosts = \App\Models\CommunityWallPost::whereIn('wallUserId', $ids)
            ->where('deleteStatus', 1)
            ->with(['author', 'comments.author'])
            ->withCount('comments')
            ->orderByDesc('created_at')
            ->get()
            ->unique('wallUserId')
            ->keyBy('wallUserId');

        // Reaction summaries so the inline "Latest" post is react/commentable.
        \App\Models\CommunityReaction::attach($latestPosts, 'wallpost', $meId);
        \App\Models\CommunityReaction::attach($latestPosts->flatMap->comments, 'wallcomment', $meId);

        return view('community.cofarmers', [
            'friends' => $friends,
            'latestPosts' => $latestPosts,
        ]);
    }

    public function members(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'q' => trim((string) $request->query('q')),
            'province' => trim((string) $request->query('province')),
            'crop' => trim((string) $request->query('crop')),
        ];

        $result = $this->pageMembers(Auth::id(), $filters, $page);

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
            'recommendations' => CommunityConnection::recommendationsFor((int) Auth::id(), 10),
            'filters' => $filters,
            'provinces' => User::where('deleteStatus', 1)
                ->whereNotNull('province')->where('province', '!=', '')
                ->distinct()->orderBy('province')->pluck('province'),
            'crops' => AsCroppingSchedule::active()
                ->where('isPublic', 1)->whereNotNull('cropType')->where('cropType', '!=', '')
                ->distinct()->orderBy('cropType')->pluck('cropType'),
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

        $isSelf = (int) $member->id === (int) Auth::id();

        // The Photos / Videos tabs combine the member's own album with any media
        // they shared on walls (posts they authored), newest first. Album items
        // are deletable by the owner; wall-sourced media is read-only here and
        // stays managed from the wall itself.
        $photos = $this->collectProfilePhotos($member->id, $isSelf);
        $videos = $this->collectProfileVideos($member->id, $isSelf);

        return view('community.connect.profile', [
            'member' => $member,
            'status' => $status,
            'isSelf' => $isSelf,
            'plans' => $plans,
            'connectionCount' => count(CommunityConnection::connectedIds($member->id)),
            'photos' => $photos,
            'videos' => $videos,
        ]);
    }

    /** Album photos + wall images the member shared, normalised + newest first. */
    private function collectProfilePhotos(int $memberId, bool $isSelf): \Illuminate\Support\Collection
    {
        $items = collect();

        foreach (CommunityProfilePhoto::active()->where('userId', $memberId)->get() as $p) {
            $items->push([
                'url' => \App\Support\MediaStore::url($p->imagePath),
                'deletable' => $isSelf,
                'deleteId' => $p->id,
                'ts' => optional($p->created_at)->timestamp ?? $p->id,
            ]);
        }

        $wallImages = \App\Models\CommunityWallPost::where('authorUserId', $memberId)
            ->where('deleteStatus', 1)
            ->whereNotNull('imagePath')->where('imagePath', '!=', '')
            ->get();
        foreach ($wallImages as $wp) {
            $items->push([
                'url' => \App\Support\MediaStore::url($wp->imagePath),
                'deletable' => false,
                'deleteId' => null,
                'ts' => optional($wp->created_at)->timestamp ?? 0,
            ]);
        }

        return $items->sortByDesc('ts')->values();
    }

    /** Album videos + wall videos the member shared, normalised + newest first. */
    private function collectProfileVideos(int $memberId, bool $isSelf): \Illuminate\Support\Collection
    {
        $items = collect();

        foreach (CommunityProfileVideo::active()->where('userId', $memberId)->get() as $v) {
            $items->push([
                'url' => \App\Support\MediaStore::url($v->videoPath),
                'poster' => $v->posterPath ? \App\Support\MediaStore::url($v->posterPath) : null,
                'deletable' => $isSelf,
                'deleteId' => $v->id,
                'ts' => optional($v->created_at)->timestamp ?? $v->id,
            ]);
        }

        $wallVideos = \App\Models\CommunityWallPost::where('authorUserId', $memberId)
            ->where('deleteStatus', 1)
            ->whereNotNull('videoPath')->where('videoPath', '!=', '')
            ->get();
        foreach ($wallVideos as $wv) {
            $items->push([
                'url' => \App\Support\MediaStore::url($wv->videoPath),
                'poster' => $wv->videoPoster ? \App\Support\MediaStore::url($wv->videoPoster) : null,
                'deletable' => false,
                'deleteId' => null,
                'ts' => optional($wv->created_at)->timestamp ?? 0,
            ]);
        }

        return $items->sortByDesc('ts')->values();
    }

    /** Upload one or more photos to the signed-in member's own profile album. */
    public function uploadPhotos(Request $request)
    {
        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $meId = (int) Auth::id();
        $created = [];
        foreach ($request->file('photos') as $file) {
            $path = \App\Support\MediaOptimizer::storeImageAsWebp($file, 'community/profile-photos');
            $photo = CommunityProfilePhoto::create([
                'userId' => $meId,
                'imagePath' => $path,
                'deleteStatus' => 1,
            ]);
            $created[] = view('community.connect.partials.photo-tile', ['item' => [
                'url' => \App\Support\MediaStore::url($path),
                'deletable' => true,
                'deleteId' => $photo->id,
            ]])->render();
        }

        return response()->json(['success' => true, 'message' => count($created) . ' photo(s) added.', 'data' => ['html' => implode('', $created)]]);
    }

    /** Remove a photo from the signed-in member's album (owner only). */
    public function deletePhoto(Request $request, int $photoId)
    {
        $photo = CommunityProfilePhoto::active()
            ->where('id', $photoId)
            ->where('userId', (int) Auth::id())
            ->first();
        if (! $photo) {
            return response()->json(['success' => false, 'message' => 'Photo not found.'], 404);
        }
        $photo->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Photo removed.']);
    }

    /**
     * Upload one video to the signed-in member's own profile album. The file is
     * compressed to a web-friendly ≤720p MP4 (with a poster frame) before being
     * stored, so albums stay small and stream smoothly.
     */
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,webm,mkv,avi,3gp,m4v|max:307200',
        ]);

        try {
            $stored = VideoOptimizer::storeCompressed($request->file('video'), 'community/profile-videos');
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $video = CommunityProfileVideo::create([
            'userId' => (int) Auth::id(),
            'videoPath' => $stored['video'],
            'posterPath' => $stored['poster'],
            'deleteStatus' => 1,
        ]);

        $html = view('community.connect.partials.video-tile', ['item' => [
            'url' => \App\Support\MediaStore::url($stored['video']),
            'poster' => $stored['poster'] ? \App\Support\MediaStore::url($stored['poster']) : null,
            'deletable' => true,
            'deleteId' => $video->id,
        ]])->render();

        return response()->json(['success' => true, 'message' => 'Video added.', 'data' => ['html' => $html]]);
    }

    /** Remove a video from the signed-in member's album (owner only). */
    public function deleteVideo(Request $request, int $videoId)
    {
        $video = CommunityProfileVideo::active()
            ->where('id', $videoId)
            ->where('userId', (int) Auth::id())
            ->first();
        if (! $video) {
            return response()->json(['success' => false, 'message' => 'Video not found.'], 404);
        }
        $video->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Video removed.']);
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
    private function pageMembers(int $viewerId, array $filters, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;
        $q = trim((string) ($filters['q'] ?? ''));
        $province = trim((string) ($filters['province'] ?? ''));
        $crop = trim((string) ($filters['crop'] ?? ''));

        // "Members not yet in your contacts" — hide accepted connections (and self).
        $exclude = array_merge([$viewerId], CommunityConnection::connectedIds($viewerId));

        $query = User::where('deleteStatus', 1)
            ->whereNotIn('id', $exclude ?: [0])
            ->when($q !== '', function ($sub) use ($q) {
                $term = '%' . $q . '%';
                $sub->where(function ($w) use ($term) {
                    $w->where('firstName', 'like', $term)
                        ->orWhere('lastName', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('province', 'like', $term);
                });
            })
            ->when($province !== '', fn ($s) => $s->where('province', $province))
            ->when($crop !== '', function ($s) use ($crop) {
                // Members who share a public plan growing this crop.
                $growerIds = AsCroppingSchedule::active()
                    ->where('isPublic', 1)
                    ->where('cropType', $crop)
                    ->pluck('anisystemUserId')->filter()->unique()->values();
                $s->whereIn('id', $growerIds->all() ?: [0]);
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
