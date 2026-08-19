<?php

namespace App\Services;

use App\Models\CommunityBookmark;
use App\Models\CommunityFollow;
use App\Models\CommunityWallPost;

/**
 * The two one-sided gestures of the community: following somebody, and keeping
 * a post for yourself.
 *
 * Both are toggles, and both are written as an upsert on a unique pair rather
 * than a delete — an unfollow that removed the row would lose the fact that
 * you once followed, and a second tap would race the first. Flipping
 * deleteStatus is idempotent, which is what a button pressed twice on a bad
 * connection actually needs.
 */
class CommunitySocialService
{
    /** Follow or unfollow, and report the state the button should now show. */
    public function toggleFollow(int $followerId, int $followedId): bool
    {
        // Following yourself is not a relationship, it is a bug with a badge.
        if ($followerId === $followedId) {
            return false;
        }

        $row = CommunityFollow::firstOrNew([
            'followerUserId' => $followerId,
            'followedUserId' => $followedId,
        ]);
        $row->deleteStatus = $row->exists && (int) $row->deleteStatus === 1 ? 0 : 1;
        $row->save();

        return (int) $row->deleteStatus === 1;
    }

    public function isFollowing(int $followerId, int $followedId): bool
    {
        return CommunityFollow::active()
            ->where('followerUserId', $followerId)
            ->where('followedUserId', $followedId)
            ->exists();
    }

    /** @return array<int,int> */
    public function followingIds(int $followerId): array
    {
        return CommunityFollow::active()
            ->where('followerUserId', $followerId)
            ->pluck('followedUserId')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function followerCount(int $userId): int
    {
        return CommunityFollow::active()->where('followedUserId', $userId)->count();
    }

    public function followingCount(int $userId): int
    {
        return CommunityFollow::active()->where('followerUserId', $userId)->count();
    }

    /**
     * The newest post from each person you follow — at most one apiece.
     *
     * One per author on purpose: the point is that nobody you follow goes
     * unheard, not that a busy poster takes the whole screen. Two queries
     * whatever the following count, because the ids come back grouped.
     *
     * @param  array<int,int>  $excludeIds  posts the feed is already showing
     * @return \Illuminate\Support\Collection<int, CommunityWallPost>
     */
    public function latestFromFollowed(int $viewerId, array $excludeIds = [], int $limit = 6)
    {
        $followed = $this->followingIds($viewerId);
        if ($followed === []) {
            return collect();
        }

        $ids = CommunityWallPost::active()
            ->whereIn('authorUserId', $followed)
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->selectRaw('MAX(id) as id')
            ->groupBy('authorUserId')
            ->pluck('id')
            ->all();
        if ($ids === []) {
            return collect();
        }

        return CommunityWallPost::active()
            ->whereIn('id', $ids)
            ->with('author')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** Save or unsave, reporting the state the button should now show. */
    public function toggleBookmark(int $userId, int $targetId, string $type = CommunityBookmark::TYPE_WALL): bool
    {
        $row = CommunityBookmark::firstOrNew([
            'userId' => $userId,
            'targetType' => $type,
            'targetId' => $targetId,
        ]);
        $row->deleteStatus = $row->exists && (int) $row->deleteStatus === 1 ? 0 : 1;
        $row->save();

        return (int) $row->deleteStatus === 1;
    }

    /** @return array<int,int> */
    public function bookmarkedIds(int $userId, string $type = CommunityBookmark::TYPE_WALL): array
    {
        return CommunityBookmark::active()
            ->where('userId', $userId)
            ->where('targetType', $type)
            ->pluck('targetId')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function bookmarkCount(int $userId): int
    {
        return CommunityBookmark::active()->where('userId', $userId)->count();
    }
}
