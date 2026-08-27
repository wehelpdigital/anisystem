<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The community's ladder: a hundred levels, ten titles, climbed on points.
 *
 * Nothing here writes anything. Points are COMPUTED from what members have
 * already done — posts, comments, discussions, reactions, followers,
 * co-farmers, AI questions, seasons, notes, days visited — so there is no
 * ledger to keep honest and no event that can be missed. The one table this
 * feature added is as_member_days, a diary of which days a member showed up,
 * written by UpdateLastSeen.
 *
 * The whole scoreboard is one cached map (userId => points, five minutes),
 * because the community is one neighbourhood: every badge on every card reads
 * from the same map, so a page of twenty authors costs one cache read, not
 * twenty computations.
 *
 * The ladder steepens on purpose. Level 2 lands with one post; level 100 is
 * years of seasons. That is the shape every ladder people actually climb has
 * (Discourse's trust levels, reputation systems): early levels greet you,
 * late levels mean something. The titles ride the levels a decade at a time,
 * so the number is the grind and the word is the brag.
 */
class CommunityRank
{
    /** How long the scoreboard may be stale. Bragging can wait five minutes. */
    private const MAP_TTL = 300;

    /**
     * What earns points, and how many.
     *
     * Received engagement outweighs performed clicks (a reaction GIVEN is one
     * point; a comment RECEIVED is three) so the ladder rewards being worth
     * reading, not pressing buttons. Grouped for the Tasks tab.
     */
    public const ACTIONS = [
        // --- Say something -------------------------------------------------
        'posts' => ['emoji' => '📝', 'pts' => 10, 'group' => 'Say something',
            'label' => 'Write a wall post',
            'how' => 'Share news, a question, or a photo from your field on the community wall.'],
        'reels' => ['emoji' => '🎬', 'pts' => 15, 'group' => 'Say something',
            'label' => 'Post a reel',
            'how' => 'A short video from the bukid — reels carry more than words.'],
        'shares' => ['emoji' => '🔁', 'pts' => 5, 'group' => 'Say something',
            'label' => 'Share a post',
            'how' => 'Pass on something worth reading, with your own words above it.'],
        'comments' => ['emoji' => '💬', 'pts' => 5, 'group' => 'Say something',
            'label' => 'Comment on a post',
            'how' => 'Answer a co-farmer on the wall.'],
        'topics' => ['emoji' => '🗣️', 'pts' => 10, 'group' => 'Say something',
            'label' => 'Start a discussion topic',
            'how' => 'Open a question inside a discussion room.'],
        'replies' => ['emoji' => '💭', 'pts' => 5, 'group' => 'Say something',
            'label' => 'Reply in a discussion',
            'how' => 'Answer inside a discussion room.'],
        'reactionsGiven' => ['emoji' => '👍', 'pts' => 1, 'group' => 'Say something',
            'label' => 'React to a post',
            'how' => 'A thumbs-up, a heart — small, but it counts.'],
        'blogComments' => ['emoji' => '📰', 'pts' => 5, 'group' => 'Say something',
            'label' => 'Comment on a blog article',
            'how' => 'Join the conversation under the technicians\' articles.'],
        'profileVideos' => ['emoji' => '📹', 'pts' => 10, 'group' => 'Say something',
            'label' => 'Upload a profile video',
            'how' => 'A film on your own profile shelf.'],

        // --- What comes back ----------------------------------------------
        'commentsReceived' => ['emoji' => '📥', 'pts' => 3, 'group' => 'What comes back',
            'label' => 'A comment lands on your post',
            'how' => 'Somebody answered what you wrote. Points come to you, the author.'],
        'commentRepliesReceived' => ['emoji' => '↩️', 'pts' => 3, 'group' => 'What comes back',
            'label' => 'A reply lands on your comment',
            'how' => 'Your comment started its own conversation.'],
        'topicRepliesReceived' => ['emoji' => '📨', 'pts' => 3, 'group' => 'What comes back',
            'label' => 'A reply lands on your topic',
            'how' => 'Your discussion topic drew an answer.'],
        'reactionsReceived' => ['emoji' => '💚', 'pts' => 1, 'group' => 'What comes back',
            'label' => 'A reaction lands on your post',
            'how' => 'Wall posts and discussion topics both count.'],
        'followers' => ['emoji' => '🔔', 'pts' => 10, 'group' => 'What comes back',
            'label' => 'Gain a follower',
            'how' => 'Somebody chose to hear more of you.'],
        'coFarmers' => ['emoji' => '🤝', 'pts' => 15, 'group' => 'What comes back',
            'label' => 'Gain a co-farmer',
            'how' => 'An accepted connection — the strongest tie there is here.'],
        'bookmarksReceived' => ['emoji' => '🔖', 'pts' => 2, 'group' => 'What comes back',
            'label' => 'Your post gets saved',
            'how' => 'Somebody kept your post to come back to.'],

        // --- Work the farm -------------------------------------------------
        'aiQuestions' => ['emoji' => '🤖', 'pts' => 5, 'group' => 'Work the farm',
            'label' => 'Ask the AI Technician',
            'how' => 'Every question you put to the technician counts.'],
        'seasons' => ['emoji' => '🌾', 'pts' => 20, 'group' => 'Work the farm',
            'label' => 'Start a cropping schedule',
            'how' => 'A new season planned in the Schedule Manager.'],
        'notes' => ['emoji' => '📒', 'pts' => 2, 'group' => 'Work the farm',
            'label' => 'Write a farm note',
            'how' => 'Notes pinned to your schedule days.'],
        'photos' => ['emoji' => '📷', 'pts' => 2, 'group' => 'Work the farm',
            'label' => 'Add a picture to an album',
            'how' => 'Photos and clips kept in your season albums.'],
        'albums' => ['emoji' => '🗂️', 'pts' => 10, 'group' => 'Work the farm',
            'label' => 'Put an album together',
            'how' => 'A named album in your season gallery.'],

        // --- Show up -------------------------------------------------------
        'days' => ['emoji' => '📅', 'pts' => 3, 'group' => 'Show up',
            'label' => 'Visit AniSenso',
            'how' => 'Each day you open the app counts once — counted from today onward.'],
    ];

    /**
     * The ten titles, one per decade of levels.
     *
     * A hundred levels would mean a hundred names, and nobody remembers a
     * hundred names — so the LEVEL is the number you grind and the TITLE is
     * the word you wear, changing only at each tenth level, always something
     * a member would actually say out loud about themselves.
     */
    public const TITLES = [
        1 => ['emoji' => '🌱', 'name' => 'New Member'],
        2 => ['emoji' => '🌿', 'name' => 'Rising Farmer'],
        3 => ['emoji' => '🧑‍🌾', 'name' => 'Green Thumb'],
        4 => ['emoji' => '🐝', 'name' => 'Community Bee'],
        5 => ['emoji' => '🌾', 'name' => 'Harvest Hero'],
        6 => ['emoji' => '⚔️', 'name' => 'Community Knight'],
        7 => ['emoji' => '🛡️', 'name' => 'Field Guardian'],
        8 => ['emoji' => '👑', 'name' => 'Harvest Royalty'],
        9 => ['emoji' => '🌟', 'name' => 'Living Legend'],
        10 => ['emoji' => '🐉', 'name' => 'Farm Immortal'],
    ];

    /** The top of the ladder. */
    public const MAX_LEVEL = 100;

    /**
     * The podium: the twenty seats at the top of the board, in metal.
     *
     * A level says how far somebody has walked; a placement says who is in
     * front right now, and only twenty can be. The metals narrow as they
     * climb — five seats of nickel, five of bronze, four silver, three gold,
     * two platinum, and one diamond that belongs to whoever is first — so
     * moving up a metal is rarer each time.
     *
     * 'to' is the last placement the metal covers; the list is read in order
     * and the first metal that reaches the placement wins it. To retune the
     * podium, move these numbers — nothing else knows the boundaries.
     */
    public const PODIUM = [
        ['to' => 1, 'key' => 'diamond', 'name' => 'Diamond'],
        ['to' => 3, 'key' => 'platinum', 'name' => 'Platinum'],
        ['to' => 6, 'key' => 'gold', 'name' => 'Gold'],
        ['to' => 10, 'key' => 'silver', 'name' => 'Silver'],
        ['to' => 15, 'key' => 'bronze', 'name' => 'Bronze'],
        ['to' => 20, 'key' => 'nickel', 'name' => 'Nickel'],
    ];

    /** How many seats the podium holds — the last metal's final place. */
    public const PODIUM_SIZE = 20;

    /**
     * The free summit. A member without a subscription climbs to here and
     * the ladder holds them: their banked points freeze one shy of Level 21's
     * floor until they subscribe, and everything they keep doing starts
     * counting again the moment they do. Bridged super admins are never held.
     */
    public const FREE_LEVEL_CAP = 20;

    /** The most points a free member can bank: one shy of Level 21's floor. */
    public static function freePointsCap(): int
    {
        return self::thresholds()[self::FREE_LEVEL_CAP] - 1;
    }

    /**
     * Everyone the gate stands open for, as a userId => true set.
     *
     * Read inside the scoreboard build so caps land in the same five-minute
     * cache as the scores themselves — a fresh subscription shows within
     * minutes, like every other number on the board.
     *
     * @return array<int, true>
     */
    private static function unlockedIds(): array
    {
        try {
            $subs = DB::table('anisystem_subscriptions')
                ->where('deleteStatus', 1)->where('status', 'active')
                ->where('expiresAt', '>', now('Asia/Manila'))
                ->pluck('userId')->all();
            $admins = DB::table('anisystem_users')->whereNotNull('adminUserId')->pluck('id')->all();

            return array_fill_keys(array_map('intval', array_merge($subs, $admins)), true);
        } catch (\Throwable $e) {
            Log::warning('CommunityRank: unlocked set failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * What each level costs, level 1 to 100.
     *
     * One curve instead of a hundred hand-picked numbers: 10 x (level-1)^2.1,
     * rounded to numbers a person can hold. Level 2 is one post away; level
     * 10 is a thousand points; level 100 is around 156,000 — every step
     * asks more than the one before it, which is what makes the number worth
     * saying.
     *
     * @return array<int, int> index 0 = level 1's floor (always 0)
     */
    public static function thresholds(): array
    {
        static $t = null;
        if ($t !== null) {
            return $t;
        }
        $t = [0];
        for ($n = 2; $n <= self::MAX_LEVEL; $n++) {
            $raw = 10 * (($n - 1) ** 2.1);
            $step = $raw < 1000 ? 5 : ($raw < 10000 ? 50 : 100);
            $t[] = (int) (round($raw / $step) * $step);
        }

        return $t;
    }

    /** In-request memo of the cached scoreboard. */
    private static ?array $map = null;

    /**
     * The whole scoreboard: userId => points, for everyone who has any.
     *
     * One five-minute cache serves every badge on every page. The assistant
     * is not on it — it has no rank to brag about and no ladder to climb.
     */
    public static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        return self::$map = Cache::remember('as-community-rank-map', self::MAP_TTL, function () {
            $counts = self::countsForAll();
            $unlocked = self::unlockedIds();
            $cap = self::freePointsCap();
            $map = [];
            foreach ($counts as $userId => $c) {
                $pts = self::score($c);
                // The free summit: without a subscription the bank stops here.
                if (! isset($unlocked[$userId])) {
                    $pts = min($pts, $cap);
                }
                $map[$userId] = $pts;
            }
            arsort($map);

            return $map;
        });
    }

    /** One member's points (0 for somebody who has done nothing yet). */
    public static function pointsFor(int $userId): int
    {
        return (int) (self::map()[$userId] ?? 0);
    }

    /**
     * The level a number of points has climbed to, and the title it wears.
     *
     * @return array{n: int, min: int, emoji: string, name: string, arc: int}
     */
    public static function rankOf(int $points): array
    {
        $t = self::thresholds();
        $level = 1;
        foreach ($t as $i => $min) {
            if ($points >= $min) {
                $level = $i + 1;
            } else {
                break;
            }
        }
        $arc = (int) ceil($level / 10);

        return self::TITLES[$arc] + ['n' => $level, 'min' => $t[$level - 1], 'arc' => $arc];
    }

    /** One member's level, straight off the scoreboard. */
    public static function rankFor(int $userId): array
    {
        return self::rankOf(self::pointsFor($userId));
    }

    /** The level after this one, or null at the top of the ladder. */
    public static function next(array $rank): ?array
    {
        $t = self::thresholds();
        $n = $rank['n'];   // n is 1-based; $t[$n] is the NEXT level's floor
        if (! isset($t[$n])) {
            return null;
        }
        $arc = (int) ceil(($n + 1) / 10);

        return self::TITLES[$arc] + ['n' => $n + 1, 'min' => $t[$n], 'arc' => $arc];
    }

    /** The next TITLE above this rank — the decade line — or null at the top. */
    public static function nextTitle(array $rank): ?array
    {
        if ($rank['arc'] >= 10) {
            return null;
        }
        $arc = $rank['arc'] + 1;
        $level = ($arc - 1) * 10 + 1;

        return self::TITLES[$arc] + ['n' => $level, 'min' => self::thresholds()[$level - 1], 'arc' => $arc];
    }

    /**
     * The per-source counts behind one member's points — the Tasks tab.
     *
     * @return array<string, int> keyed like ACTIONS
     */
    public static function breakdown(int $userId): array
    {
        return Cache::remember('as-community-rank-breakdown:' . $userId, 60, function () use ($userId) {
            $all = self::countsForAll();   // shares the heavy lifting (and its shape)

            return ($all[$userId] ?? []) + array_fill_keys(array_keys(self::ACTIONS), 0);
        });
    }

    /** Points a set of counts is worth. */
    public static function score(array $counts): int
    {
        $pts = 0;
        foreach (self::ACTIONS as $key => $action) {
            $pts += ($counts[$key] ?? 0) * $action['pts'];
        }

        return $pts;
    }

    /**
     * Every count for every member, in one pass of grouped queries.
     *
     * Each source is wrapped so one missing column (an environment whose
     * migrations lag) costs that source, not the whole community's pages.
     */
    private static function countsForAll(): array
    {
        $out = [];
        $add = function (string $key, $rows) use (&$out) {
            foreach ($rows as $r) {
                $u = (int) $r->u;
                if ($u > 0) {
                    $out[$u][$key] = ($out[$u][$key] ?? 0) + (int) $r->n;
                }
            }
        };
        $try = function (string $key, callable $q) use ($add) {
            try {
                $add($key, $q());
            } catch (\Throwable $e) {
                Log::warning('CommunityRank: source failed', ['source' => $key, 'error' => $e->getMessage()]);
            }
        };

        // --- Say something --------------------------------------------------
        // One pass over the wall sorts every post into plain / reel / share.
        // A plain closure, not an arrow fn: an arrow fn captures $out by
        // value, and the reels and shares written into the copy would vanish.
        $try('posts', function () use (&$out) {
            $rows = DB::table('as_community_wall_posts')->where('deleteStatus', 1)
                ->selectRaw('authorUserId as u,
                    SUM(CASE WHEN COALESCE(isReel, 0) = 0 AND sharedPostId IS NULL THEN 1 ELSE 0 END) as n,
                    SUM(CASE WHEN COALESCE(isReel, 0) = 1 THEN 1 ELSE 0 END) as reels,
                    SUM(CASE WHEN COALESCE(isReel, 0) = 0 AND sharedPostId IS NOT NULL THEN 1 ELSE 0 END) as shares')
                ->groupBy('authorUserId')->get();
            foreach ($rows as $r) {
                $u = (int) $r->u;
                if ($u > 0) {
                    $out[$u]['reels'] = ($out[$u]['reels'] ?? 0) + (int) $r->reels;
                    $out[$u]['shares'] = ($out[$u]['shares'] ?? 0) + (int) $r->shares;
                }
            }

            return $rows;   // $add reads ->n, the plain-post count
        });
        $try('comments', fn () => DB::table('as_community_wall_comments')
            ->where('deleteStatus', 1)->where(fn ($q) => $q->whereNull('isDeleted')->orWhere('isDeleted', 0))
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());
        $try('topics', fn () => DB::table('as_community_group_posts')->where('deleteStatus', 1)
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());
        $try('replies', fn () => DB::table('as_community_group_replies')
            ->where('deleteStatus', 1)->where(fn ($q) => $q->whereNull('isDeleted')->orWhere('isDeleted', 0))
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());
        $try('reactionsGiven', fn () => DB::table('as_community_reactions')
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());
        $try('blogComments', fn () => DB::table('as_community_blog_comments')
            ->where('deleteStatus', 1)
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());
        $try('profileVideos', fn () => DB::table('as_community_profile_videos')
            ->where('deleteStatus', 1)
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());

        // --- What comes back ------------------------------------------------
        // Somebody ELSE answered: your own comment under your own post is
        // conversation with yourself, and scores nothing.
        $try('commentsReceived', fn () => DB::table('as_community_wall_comments as c')
            ->join('as_community_wall_posts as p', 'p.id', '=', 'c.wallPostId')
            ->where('c.deleteStatus', 1)->where('p.deleteStatus', 1)
            ->whereColumn('c.userId', '!=', 'p.authorUserId')
            ->selectRaw('p.authorUserId as u, COUNT(*) as n')->groupBy('p.authorUserId')->get());
        $try('commentRepliesReceived', fn () => DB::table('as_community_wall_comments as r')
            ->join('as_community_wall_comments as c', 'c.id', '=', 'r.parentId')
            ->where('r.deleteStatus', 1)->where('c.deleteStatus', 1)
            ->whereColumn('r.userId', '!=', 'c.userId')
            ->selectRaw('c.userId as u, COUNT(*) as n')->groupBy('c.userId')->get());
        $try('topicRepliesReceived', fn () => DB::table('as_community_group_replies as r')
            ->join('as_community_group_posts as p', 'p.id', '=', 'r.postId')
            ->where('r.deleteStatus', 1)->where('p.deleteStatus', 1)
            ->whereColumn('r.userId', '!=', 'p.userId')
            ->selectRaw('p.userId as u, COUNT(*) as n')->groupBy('p.userId')->get());
        $try('reactionsReceived', fn () => DB::table('as_community_reactions as x')
            ->join('as_community_wall_posts as p', fn ($j) => $j->on('p.id', '=', 'x.targetId')->where('x.targetType', 'wallpost'))
            ->where('p.deleteStatus', 1)->whereColumn('x.userId', '!=', 'p.authorUserId')
            ->selectRaw('p.authorUserId as u, COUNT(*) as n')->groupBy('p.authorUserId')->get());
        $try('reactionsReceived', fn () => DB::table('as_community_reactions as x')
            ->join('as_community_group_posts as p', fn ($j) => $j->on('p.id', '=', 'x.targetId')->where('x.targetType', 'post'))
            ->where('p.deleteStatus', 1)->whereColumn('x.userId', '!=', 'p.userId')
            ->selectRaw('p.userId as u, COUNT(*) as n')->groupBy('p.userId')->get());
        $try('followers', fn () => DB::table('as_community_follows')->where('deleteStatus', 1)
            ->selectRaw('followedUserId as u, COUNT(*) as n')->groupBy('followedUserId')->get());
        // A connection has two ends and both members earned it.
        $try('coFarmers', fn () => DB::table('as_community_connections')
            ->where('deleteStatus', 1)->where('status', 'accepted')
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());
        $try('coFarmers', fn () => DB::table('as_community_connections')
            ->where('deleteStatus', 1)->where('status', 'accepted')
            ->selectRaw('friendUserId as u, COUNT(*) as n')->groupBy('friendUserId')->get());
        $try('bookmarksReceived', fn () => DB::table('as_community_bookmarks as b')
            ->join('as_community_wall_posts as p', fn ($j) => $j->on('p.id', '=', 'b.targetId')->where('b.targetType', 'wall'))
            ->where('b.deleteStatus', 1)->where('p.deleteStatus', 1)
            ->whereColumn('b.userId', '!=', 'p.authorUserId')
            ->selectRaw('p.authorUserId as u, COUNT(*) as n')->groupBy('p.authorUserId')->get());

        // --- Work the farm --------------------------------------------------
        $try('aiQuestions', fn () => DB::table('anisystem_ai_messages as m')
            ->join('anisystem_ai_conversations as c', 'c.id', '=', 'm.conversationId')
            ->where('m.role', 'user')->where('m.deleteStatus', 1)
            ->selectRaw('c.userId as u, COUNT(*) as n')->groupBy('c.userId')->get());
        $try('seasons', fn () => DB::table('as_cropping_schedules')->where('deleteStatus', 1)
            ->selectRaw('anisystemUserId as u, COUNT(*) as n')->groupBy('anisystemUserId')->get());
        $try('notes', fn () => DB::table('as_inline_notes as n')
            ->join('as_cropping_schedules as s', 's.id', '=', 'n.croppingScheduleId')
            ->where('n.deleteStatus', 1)->where('s.deleteStatus', 1)
            ->selectRaw('s.anisystemUserId as u, COUNT(*) as n')->groupBy('s.anisystemUserId')->get());
        $try('notes', fn () => DB::table('as_schedule_date_notes as n')
            ->join('as_cropping_schedules as s', 's.id', '=', 'n.croppingScheduleId')
            ->where('n.deleteStatus', 1)->where('s.deleteStatus', 1)
            ->selectRaw('s.anisystemUserId as u, COUNT(*) as n')->groupBy('s.anisystemUserId')->get());
        $try('photos', fn () => DB::table('as_gallery_images')->where('deleteStatus', 1)
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());
        $try('albums', fn () => DB::table('as_gallery_albums')->where('deleteStatus', 1)
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());

        // --- Show up ---------------------------------------------------------
        $try('days', fn () => DB::table('as_member_days')
            ->selectRaw('userId as u, COUNT(*) as n')->groupBy('userId')->get());

        // The assistant answers everywhere and would top every board; it is
        // not a member and holds no rank.
        try {
            $assistant = DB::table('anisystem_users')
                ->whereRaw('LOWER(email) = ?', [strtolower(\App\Models\User::ASSISTANT_EMAIL)])
                ->value('id');
            if ($assistant) {
                unset($out[(int) $assistant]);
            }
        } catch (\Throwable $e) {
            // No assistant row is no problem.
        }

        return $out;
    }

    /**
     * The board itself: position, member, rank and points for the top slice.
     *
     * @return array<int, array{user: \App\Models\User, points: int, rank: array}>
     */
    public static function leaderboard(int $limit = 100): array
    {
        $map = self::map();
        $top = array_slice($map, 0, $limit, true);
        if ($top === []) {
            return [];
        }

        $users = \App\Models\User::where('deleteStatus', 1)
            ->whereIn('id', array_keys($top))
            ->get()->keyBy('id');

        $rows = [];
        foreach ($top as $userId => $points) {
            $user = $users->get($userId);
            if (! $user || $user->is_assistant) {
                continue;   // a deleted member keeps no seat on the board
            }
            $rows[] = ['user' => $user, 'points' => (int) $points, 'rank' => self::rankOf((int) $points)];
        }

        return $rows;
    }

    /** In-request memo of the podium. */
    private static ?array $podium = null;

    /**
     * The twenty seats at the top, as userId => placement (1-based).
     *
     * Built once and cached with the scoreboard, because the badge it feeds is
     * worn on every card on every page: a wall of thirty posts asks this
     * question thirty times and must not pay for it thirty times.
     *
     * The seats are the ones the leaderboard shows, not the raw map's first
     * twenty rows — a departed member or the assistant sitting high in the
     * points would otherwise hold a seat nobody can see, and everyone below
     * would wear a placement one worse than the board they can read. Extra
     * rows are drawn in so those gaps close up rather than shortening the
     * podium. Nobody is placed on nothing: a seat needs at least one point.
     *
     * @return array<int, int>
     */
    public static function podium(): array
    {
        if (self::$podium !== null) {
            return self::$podium;
        }

        return self::$podium = Cache::remember('as-community-podium', self::MAP_TTL, function () {
            $map = self::map();
            // Deep enough that a run of hidden members cannot shorten the podium.
            $reach = array_slice($map, 0, self::PODIUM_SIZE * 3, true);
            $reach = array_filter($reach, static fn ($points) => (int) $points > 0);
            if ($reach === []) {
                return [];
            }

            try {
                $seated = \App\Models\User::where('deleteStatus', 1)
                    ->whereIn('id', array_keys($reach))
                    ->where(fn ($q) => $q->whereNull('email')
                        ->orWhereRaw('LOWER(email) <> ?', [\App\Models\User::ASSISTANT_EMAIL]))
                    ->pluck('id')->all();
            } catch (\Throwable $e) {
                Log::warning('CommunityRank: podium lookup failed', ['error' => $e->getMessage()]);

                return [];
            }
            $seated = array_fill_keys(array_map('intval', $seated), true);

            $podium = [];
            $place = 0;
            foreach ($reach as $userId => $points) {
                if (! isset($seated[(int) $userId])) {
                    continue;
                }
                $podium[(int) $userId] = ++$place;
                if ($place >= self::PODIUM_SIZE) {
                    break;
                }
            }

            return $podium;
        });
    }

    /**
     * The metal one member is wearing, or null for everybody off the podium.
     *
     * @return array{place: int, key: string, name: string}|null
     */
    public static function podiumFor(int $userId): ?array
    {
        $place = self::podium()[$userId] ?? 0;
        if ($place < 1) {
            return null;
        }
        foreach (self::PODIUM as $metal) {
            if ($place <= $metal['to']) {
                return ['place' => $place, 'key' => $metal['key'], 'name' => $metal['name']];
            }
        }

        return null;
    }

    /**
     * The whole podium in a shape a page can hand to JavaScript.
     *
     * Twenty rows at most, so shipping the lot to the browser costs less than
     * one avatar and lets a card built in JS wear the same chip as the card
     * built in Blade beside it.
     *
     * @return array<int, array{place: int, key: string, name: string}>
     */
    public static function podiumChips(): array
    {
        $out = [];
        foreach (array_keys(self::podium()) as $userId) {
            $metal = self::podiumFor((int) $userId);
            if ($metal) {
                $out[(int) $userId] = $metal;
            }
        }

        return $out;
    }

    /** Where one member stands on the board (1-based), 0 if unplaced. */
    public static function positionOf(int $userId): int
    {
        $pos = 0;
        foreach (self::map() as $id => $points) {
            $pos++;
            if ((int) $id === $userId) {
                return $pos;
            }
        }

        return 0;
    }
}
