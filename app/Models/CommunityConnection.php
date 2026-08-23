<?php

namespace App\Models;

/**
 * A co-farmer connection (friend request). Stored one-directional:
 * `userId` sent the request to `friendUserId`. `accepted` means the two are
 * mutually connected regardless of who asked.
 */
class CommunityConnection extends BaseModel
{
    protected $table = 'as_community_connections';

    protected $fillable = [
        'userId', 'friendUserId', 'status', 'respondedAt', 'deleteStatus',
    ];

    protected $casts = [
        'respondedAt' => 'datetime',
    ];

    /** The live row between two users in either direction, if any. */
    public static function between(int $a, int $b): ?self
    {
        return static::active()
            ->where(function ($q) use ($a, $b) {
                $q->where('userId', $a)->where('friendUserId', $b);
            })
            ->orWhere(function ($q) use ($a, $b) {
                $q->where('userId', $b)->where('friendUserId', $a);
            })
            ->first();
    }

    /**
     * Relationship of $otherId as seen by $viewerId:
     * 'self' | 'connected' | 'pending_out' (viewer asked) |
     * 'pending_in' (viewer was asked) | 'none'.
     */
    public static function statusFor(int $viewerId, int $otherId): string
    {
        if ($viewerId === $otherId) {
            return 'self';
        }
        $row = static::between($viewerId, $otherId);
        if (! $row) {
            return 'none';
        }
        if ($row->status === 'accepted') {
            return 'connected';
        }
        if ($row->status === 'pending') {
            return (int) $row->userId === $viewerId ? 'pending_out' : 'pending_in';
        }

        return 'none'; // declined → treat as connectable again
    }

    /** IDs of everyone $userId is accepted-connected to. */
    /**
     * Everyone this account has already dealt with, whatever came of it.
     *
     * Accepted, pending in either direction, declined, blocked — one row is
     * enough. A suggestion is an introduction, and you cannot be introduced
     * to somebody you have already met.
     *
     * @return list<int>
     */
    public static function spokenFor(int $userId): array
    {
        return static::active()
            ->where(fn ($q) => $q->where('userId', $userId)->orWhere('friendUserId', $userId))
            ->get(['userId', 'friendUserId'])
            ->map(fn ($r) => (int) $r->userId === $userId ? (int) $r->friendUserId : (int) $r->userId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Members this account has never met, in no particular order.
     *
     * Random rather than newest-first: the newest handful would be the same
     * handful for everybody, every day, and a strip that never changes is one
     * nobody looks at twice.
     *
     * @param  list<int>  $exclude
     */
    public static function strangers(int $viewerId, array $exclude, int $take): \Illuminate\Support\Collection
    {
        if ($take < 1) {
            return collect();
        }

        return User::where('deleteStatus', 1)
            ->whereNotIn('id', $exclude ?: [0])
            ->inRandomOrder()
            ->limit($take)
            ->get()
            ->map(function ($u) use ($viewerId) {
                $u->recoMutual = 0;
                $u->recoScore = 0;
                /* Where they farm, if they have said — true, useful, and not
                 * a claim of any connection. Nothing at all when they have
                 * not: better a card with one line than an invented reason. */
                $where = trim((string) ($u->city ?: $u->province));
                $u->recoReason = $where !== '' ? 'In ' . $where : '';
                $u->connStatus = static::statusFor($viewerId, (int) $u->id);

                return $u;
            });
    }

    /**
     * How many co-farmers the viewer shares with each of these people.
     *
     * One query for the whole page: every accepted connection that touches
     * one of the viewer's own co-farmers, counted per person. The recommender
     * has a pass of its own because it is still deciding who to show; this is
     * for lists whose people are already chosen.
     *
     * @param  list<int>  $userIds
     * @return array<int,int>  userId => mutual count
     */
    public static function mutualCounts(int $viewerId, array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        $friends = static::connectedIds($viewerId);
        if ($userIds === [] || $friends === []) {
            return [];
        }

        $friendSet = array_flip($friends);
        $wanted = array_flip($userIds);
        $counts = [];

        $rows = static::active()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userIds) {
                $q->whereIn('userId', $userIds)->orWhereIn('friendUserId', $userIds);
            })
            ->get(['userId', 'friendUserId']);

        foreach ($rows as $c) {
            $a = (int) $c->userId;
            $b = (int) $c->friendUserId;
            // A row counts when one end is somebody on the page and the
            // other end is somebody the viewer already farms with.
            if (isset($wanted[$a]) && isset($friendSet[$b]) && $b !== $viewerId) {
                $counts[$a] = ($counts[$a] ?? 0) + 1;
            }
            if (isset($wanted[$b]) && isset($friendSet[$a]) && $a !== $viewerId) {
                $counts[$b] = ($counts[$b] ?? 0) + 1;
            }
        }

        return $counts;
    }

    /**
     * How many co-farmers each of these people has — one query for the page.
     *
     * A connection is one row for two people, so both ends are counted and
     * the pair is only ever counted once from each side.
     *
     * @param  list<int>  $userIds
     * @return array<int,int>
     */
    public static function connectionCounts(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if ($userIds === []) {
            return [];
        }

        $counts = array_fill_keys($userIds, 0);
        $wanted = array_flip($userIds);

        $rows = static::active()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userIds) {
                $q->whereIn('userId', $userIds)->orWhereIn('friendUserId', $userIds);
            })
            ->get(['userId', 'friendUserId']);

        foreach ($rows as $c) {
            $a = (int) $c->userId;
            $b = (int) $c->friendUserId;
            if (isset($wanted[$a])) {
                $counts[$a]++;
            }
            if (isset($wanted[$b])) {
                $counts[$b]++;
            }
        }

        return $counts;
    }

    public static function connectedIds(int $userId): array
    {
        return static::active()
            ->where('status', 'accepted')
            ->where(fn ($q) => $q->where('userId', $userId)->orWhere('friendUserId', $userId))
            ->get()
            ->map(fn ($r) => (int) $r->userId === $userId ? (int) $r->friendUserId : (int) $r->userId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * "People you may know" — non-contacts ranked by signal strength:
     *   mutual co-farmers (friends-of-friends)  ×5   (the strongest cue)
     *   same city                                +4
     *   talked under the same post               +3   (met, not just near)
     *   same province                            +2
     * Each candidate carries recoMutual, recoReason and connStatus so the card
     * can render a reason line and the right Connect button. Highest first.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function recommendationsFor(int $viewerId, int $limit = 8): \Illuminate\Support\Collection
    {
        $viewer = User::find($viewerId);
        if (! $viewer) {
            return collect();
        }

        $friends = static::connectedIds($viewerId);
        $friendSet = array_flip($friends);
        /* Anyone this account has already had dealings with is out.
         *
         * It used to exclude accepted co-farmers alone, so somebody you had
         * already sent a request to kept turning up in a strip whose only
         * offer is "ask them" — and the one waiting on YOUR answer belongs
         * in the requests card, not here. Pending both ways, declined and
         * blocked all count: this strip is for strangers. */
        $known = static::spokenFor($viewerId);
        /* And the AI technician.
         *
         * It has an account so its answers have a name and a face, not so
         * anybody can be introduced to it: there is no request to send and
         * nothing on the other end to accept one. The members list already
         * leaves it out; this strip was still offering it as somebody you
         * might know. */
        $assistant = (int) (User::where('email', User::ASSISTANT_EMAIL)->value('id') ?? 0);
        $excludeSet = array_flip(array_merge([$viewerId, $assistant], $friends, $known));

        // Mutual counts: one pass over accepted connections that touch a friend.
        $mutual = [];
        if (! empty($friends)) {
            $conns = static::active()->where('status', 'accepted')
                ->where(function ($q) use ($friends) {
                    $q->whereIn('userId', $friends)->orWhereIn('friendUserId', $friends);
                })
                ->get(['userId', 'friendUserId']);
            foreach ($conns as $c) {
                $a = (int) $c->userId;
                $b = (int) $c->friendUserId;
                if (isset($friendSet[$a]) && ! isset($excludeSet[$b])) {
                    $mutual[$b] = ($mutual[$b] ?? 0) + 1;
                }
                if (isset($friendSet[$b]) && ! isset($excludeSet[$a])) {
                    $mutual[$a] = ($mutual[$a] ?? 0) + 1;
                }
            }
        }

        $province = trim((string) $viewer->province);
        $city = trim((string) $viewer->city);

        // Location candidates (non-contacts in the same city/province).
        $locIds = [];
        if ($province !== '' || $city !== '') {
            $locIds = User::where('deleteStatus', 1)
                ->whereNotIn('id', array_keys($excludeSet) ?: [0])
                ->where(function ($q) use ($province, $city) {
                    if ($city !== '') {
                        $q->orWhere('city', $city);
                    }
                    if ($province !== '') {
                        $q->orWhere('province', $province);
                    }
                })
                ->limit(80)->pluck('id')->all();
        }

        /* People met in the comments.
         *
         * Two names under one post is the weakest kind of introduction and the
         * most human: you have already spoken. Read from the viewer's own
         * recent comments outward — the posts they commented on, then everyone
         * else who commented there — and capped at both ends, because this is
         * a suggestion strip, not a report. */
        $talked = [];
        $myPostIds = CommunityWallComment::active()
            ->where('userId', $viewerId)
            ->orderByDesc('id')
            ->limit(120)
            ->pluck('wallPostId')
            ->unique()
            ->all();
        if (! empty($myPostIds)) {
            $rows = CommunityWallComment::active()
                ->whereIn('wallPostId', $myPostIds)
                ->where('userId', '!=', $viewerId)
                ->orderByDesc('id')
                ->limit(400)
                ->pluck('userId');
            foreach ($rows as $uid) {
                $uid = (int) $uid;
                if (isset($excludeSet[$uid])) {
                    continue;
                }
                $talked[$uid] = ($talked[$uid] ?? 0) + 1;
            }
        }

        $ids = array_values(array_unique(array_merge(array_keys($mutual), $locIds, array_keys($talked))));

        $users = User::where('deleteStatus', 1)->whereIn('id', $ids)->get()->keyBy('id');
        $out = collect();
        foreach ($ids as $id) {
            $u = $users->get($id);
            if (! $u || isset($excludeSet[$id])) {
                continue;
            }
            $m = $mutual[$id] ?? 0;
            $sameCity = $city !== '' && trim((string) $u->city) === $city;
            $sameProvince = $province !== '' && trim((string) $u->province) === $province;
            $met = $talked[$id] ?? 0;
            $score = $m * 5 + ($sameCity ? 4 : 0) + ($met > 0 ? 3 : 0) + ($sameProvince ? 2 : 0);
            if ($score <= 0) {
                continue;
            }

            $reasons = [];
            if ($m > 0) {
                $reasons[] = $m . ' mutual co-farmer' . ($m > 1 ? 's' : '');
            }
            if ($met > 0) {
                $reasons[] = 'You both commented on the same post';
            }
            if ($sameCity && $u->city) {
                $reasons[] = 'Also in ' . $u->city;
            } elseif ($sameProvince && $u->province) {
                $reasons[] = 'Also in ' . $u->province;
            }

            $u->recoMutual = $m;
            $u->recoScore = $score;
            $u->recoReason = implode(' · ', $reasons);
            $u->connStatus = static::statusFor($viewerId, $id);
            $out->push($u);
        }

        $out = $out->sortByDesc('recoScore')->take($limit)->values();

        /* Top up with strangers when the reasons run out.
         *
         * A new account has no mutual co-farmers, has commented nowhere and
         * often has not said where it farms — so nothing scores, and the
         * account that most needs introducing to somebody was shown nobody.
         * These carry no reason line, because there is no reason beyond "they
         * are here too", and that is a fair thing to say. */
        if ($out->count() < $limit) {
            $out = $out->concat(static::strangers(
                $viewerId,
                array_merge(array_keys($excludeSet), $out->pluck('id')->map(fn ($i) => (int) $i)->all()),
                $limit - $out->count()
            ));
        }

        return $out->values();
    }
}
