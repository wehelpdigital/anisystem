<?php

namespace App\Support;

use App\Models\AsCommunityBlogPost;
use App\Models\CommunityConnection;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityMessage;
use App\Models\CommunityWallPost;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * What the community's desktop rail is made of.
 *
 * On a wide screen every community page keeps a column beside its content
 * (community/partials/plaza-rail): the conversations you were last in, the
 * rooms where people are talking now, a few of what the wall is saying,
 * and what is new in the blog. Each page picks which of those it shows --
 * the wall does not need a card of its own posts, the discussions page
 * does not need a card of rooms -- and this class answers for all of them
 * so no controller has to know the rail exists. Every answer is small,
 * bounded, and asked only when its card is drawn.
 */
class CommunityRail
{
    private int $meId;

    public function __construct(?int $meId = null)
    {
        $this->meId = $meId ?? (int) Auth::id();
    }

    /**
     * The people this member last spoke with, newest first, with the last
     * line said and how many of theirs are still unread. The same reading
     * the messenger's own list makes, only shorter.
     *
     * @return Collection<int, array{user: User, lastBody: string, lastAt: ?string, unread: int}>
     */
    public function recentChats(int $take = 5): Collection
    {
        $me = $this->meId;
        $rows = CommunityMessage::where('deleteStatus', 1)
            ->where(fn ($q) => $q->where('senderId', $me)->orWhere('recipientId', $me))
            ->orderByDesc('id')
            ->limit(150)
            ->get();

        $threads = [];
        foreach ($rows as $m) {
            $other = (int) $m->senderId === $me ? (int) $m->recipientId : (int) $m->senderId;
            if (! isset($threads[$other])) {
                $threads[$other] = ['last' => $m, 'unread' => 0];
            }
            if ((int) $m->recipientId === $me && ! $m->isRead) {
                $threads[$other]['unread']++;
            }
            if (count($threads) > $take * 3) {
                break;
            }
        }
        $threads = array_slice($threads, 0, $take, true);
        if (! $threads) {
            return collect();
        }

        $users = User::whereIn('id', array_keys($threads))->where('deleteStatus', 1)->get()->keyBy('id');

        return collect($threads)->map(function ($t, $otherId) use ($users, $me) {
            $u = $users->get($otherId);
            if (! $u) {
                return null;
            }
            $m = $t['last'];
            $text = trim((string) $m->body) !== ''
                ? Str::limit(CommunityText::plain($m->body, 200), 44)
                : ($m->imagePath ? '📷 Photo' : '');

            return [
                'user' => $u,
                'lastBody' => ((int) $m->senderId === $me ? 'You: ' : '') . $text,
                'lastAt' => $m->created_at?->diffForHumans(null, true),
                'unread' => (int) $t['unread'],
            ];
        })->filter()->values();
    }

    /**
     * Rooms where somebody said something lately, the freshest first. A
     * private room is not advertised to somebody outside it. Each carries
     * member_count and last_post_at, which is what side-groups draws.
     *
     * @return Collection<int, CommunityGroup>
     */
    public function latestDiscussions(int $take = 5): Collection
    {
        $mine = CommunityGroupMember::active()
            ->where('userId', $this->meId)
            ->pluck('groupId')
            ->map(fn ($id) => (int) $id)
            ->all();

        // The last word in each room: a topic, or a reply to one.
        $lastTopic = CommunityGroupPost::active()
            ->selectRaw('groupId, MAX(created_at) as at')
            ->groupBy('groupId')
            ->pluck('at', 'groupId');
        $lastReply = CommunityGroupReply::active()
            ->join('as_community_group_posts as t', 't.id', '=', 'as_community_group_replies.postId')
            ->where('t.deleteStatus', 1)
            ->selectRaw('t.groupId, MAX(as_community_group_replies.created_at) as at')
            ->groupBy('t.groupId')
            ->pluck('at', 'groupId');
        $spoken = collect();
        foreach ([$lastTopic, $lastReply] as $set) {
            foreach ($set as $groupId => $at) {
                $groupId = (int) $groupId;
                if (! $spoken->has($groupId) || $spoken->get($groupId) < $at) {
                    $spoken->put($groupId, (string) $at);
                }
            }
        }
        $spoken = $spoken->sortDesc();

        $rooms = CommunityGroup::active()
            ->withCount(['members as member_count'])
            ->where(function ($w) use ($mine) {
                $w->where('privacy', '!=', CommunityGroup::PRIVATE)
                    ->orWhereNull('privacy')
                    ->orWhereIn('id', $mine ?: [0]);
            })
            ->when($spoken->isNotEmpty(), fn ($q) => $q->whereIn('id', $spoken->keys()->take($take * 2)->all()))
            ->orderByDesc('id')
            ->limit($take * 2)
            ->get();

        foreach ($rooms as $room) {
            $room->last_post_at = $spoken->get($room->id);
            $room->joined = in_array((int) $room->id, $mine, true);
        }

        return $rooms
            ->sortByDesc(fn ($r) => $r->last_post_at ?? '')
            ->take($take)
            ->values();
    }

    /**
     * A few of what the wall is saying: drawn at random from the newest
     * thirty, so the card reads differently on every visit rather than
     * naming the same three posts all week. Shares and restricted posts
     * are left out -- a share quotes somebody else, and a restricted post
     * is not to be pointed at.
     *
     * @return Collection<int, CommunityWallPost>
     */
    public function recentPosts(int $take = 5): Collection
    {
        $pool = CommunityWallPost::active()
            ->with('author')
            ->withCount(['comments as comment_count'])
            ->whereNull('sharedPostId')
            ->where(fn ($q) => $q->where('isRestricted', 0)->orWhereNull('isRestricted'))
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->filter(fn ($p) => $p->author && (int) $p->author->deleteStatus === 1 && ! $p->author->is_assistant);

        return $pool->shuffle()->take($take)->values();
    }

    /**
     * What is new in the Technician's Blog, newest first.
     *
     * @return Collection<int, AsCommunityBlogPost>
     */
    public function newInBlog(int $take = 4): Collection
    {
        return AsCommunityBlogPost::where('deleteStatus', 1)
            ->where('isPublished', 1)
            ->withCount('comments')
            ->orderByDesc('publishedAt')
            ->orderByDesc('id')
            ->limit($take)
            ->get();
    }

    /**
     * Co-farmer requests waiting on this member: the first few, and the
     * whole count.
     *
     * @return array{0: Collection<int, User>, 1: int}
     */
    public function requests(int $take = 6): array
    {
        $rows = CommunityConnection::active()
            ->where('friendUserId', $this->meId)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();
        if ($rows->isEmpty()) {
            return [collect(), 0];
        }
        $users = User::whereIn('id', $rows->pluck('userId'))
            ->where('deleteStatus', 1)
            ->orderByDesc('id')
            ->limit($take)
            ->get();

        return [$users, $rows->count()];
    }
}
