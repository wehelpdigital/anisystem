<?php

namespace App\Services;

use App\Models\AsCommunityBlogPost;
use App\Models\CommunityConnection;
use App\Models\CommunityGroupMember;
use App\Models\CommunityGroupPost;
use App\Models\CommunityRead;
use Illuminate\Support\Facades\Auth;

/**
 * "What is new since you were last here", for every badge in the community.
 *
 * One service because the three questions are the same question: something
 * happened, when did you last look. Discussions count new topics in the rooms
 * you actually joined (a room you never joined is not news, it is a room), the
 * blog counts articles published since you last opened it, and requests are
 * simply the ones still waiting — a pending request IS unread until answered.
 *
 * Everything is read in two queries and cached for the request, because the
 * nav asks for it on every single page.
 */
class CommunityUnreadService
{
    public const KIND_GROUP = 'group';
    public const KIND_BLOG = 'blog';

    /** @var array<string, array<string, mixed>> */
    private array $memo = [];

    /**
     * Per-discussion unread counts for the rooms this member joined.
     *
     * @return array<int,int>  groupId => new topics since their last visit
     */
    public function discussionCounts(?int $userId = null): array
    {
        $userId = $userId ?: (int) Auth::id();
        $key = 'disc.' . $userId;
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $groupIds = CommunityGroupMember::active()
            ->where('userId', $userId)
            ->pluck('groupId')
            ->map(fn ($id) => (int) $id)
            ->all();
        if ($groupIds === []) {
            return $this->memo[$key] = [];
        }

        $seen = CommunityRead::where('userId', $userId)
            ->where('kind', self::KIND_GROUP)
            ->whereIn('refId', $groupIds)
            ->pluck('lastReadAt', 'refId');

        $counts = [];
        // Topics somebody else wrote: your own post is not news to you.
        $rows = CommunityGroupPost::active()
            ->whereIn('groupId', $groupIds)
            ->where('userId', '!=', $userId)
            ->get(['groupId', 'created_at']);
        foreach ($rows as $row) {
            $gid = (int) $row->groupId;
            $since = $seen[$gid] ?? null;
            if ($since === null || $row->created_at?->gt($since)) {
                $counts[$gid] = ($counts[$gid] ?? 0) + 1;
            }
        }

        return $this->memo[$key] = $counts;
    }

    public function discussionTotal(?int $userId = null): int
    {
        return array_sum($this->discussionCounts($userId));
    }

    /** Articles published since this member last opened the blog. */
    public function blogCount(?int $userId = null): int
    {
        $userId = $userId ?: (int) Auth::id();
        $key = 'blog.' . $userId;
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $since = CommunityRead::where('userId', $userId)
            ->where('kind', self::KIND_BLOG)
            ->whereNull('refId')
            ->value('lastReadAt');

        return $this->memo[$key] = AsCommunityBlogPost::where('deleteStatus', 1)
            ->where('isPublished', 1)
            ->when($since, fn ($q) => $q->where('publishedAt', '>', $since))
            ->count();
    }

    /** Co-farmer requests still waiting on an answer. */
    public function requestCount(?int $userId = null): int
    {
        $userId = $userId ?: (int) Auth::id();

        return CommunityConnection::active()
            ->where('friendUserId', $userId)
            ->where('status', 'pending')
            ->count();
    }

    /** What the closed hamburger shows: everything, in one number. */
    public function total(?int $userId = null): int
    {
        return $this->discussionTotal($userId) + $this->blogCount($userId) + $this->requestCount($userId);
    }

    /**
     * Mark something read, now.
     *
     * Called when a page is actually opened rather than when a badge is
     * rendered: seeing that there is news is not the same as having read it.
     */
    public function markRead(string $kind, ?int $refId = null, ?int $userId = null): void
    {
        $userId = $userId ?: (int) Auth::id();
        if (! $userId) {
            return;
        }

        CommunityRead::updateOrCreate(
            ['userId' => $userId, 'kind' => $kind, 'refId' => $refId],
            ['lastReadAt' => now()],
        );
        // The memo was answered before this mark; forget it so anything asking
        // later in the same request gets the truth.
        $this->memo = [];
    }
}
