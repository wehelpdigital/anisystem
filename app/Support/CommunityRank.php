<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The community's ladder: fifty farming ranks, climbed on points.
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
 * The ladder steepens on purpose. The first promotion lands with one post;
 * the fiftieth is a season of seasons. That is the shape every ladder people
 * actually climb has (Discourse's trust levels, reputation systems): early
 * ranks greet you, late ranks mean something.
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

        // --- Show up -------------------------------------------------------
        'days' => ['emoji' => '📅', 'pts' => 3, 'group' => 'Show up',
            'label' => 'Visit AniSenso',
            'how' => 'Each day you open the app counts once — counted from today onward.'],
    ];

    /** The ten arcs the fifty ranks climb through; each colours its chips. */
    public const ARCS = [
        1 => 'Binhi — The Seed',
        2 => 'Tanim — The Planting',
        3 => 'Lagô — The Growing',
        4 => 'Magsasaka — The Farmer',
        5 => 'Bulaklak — The Flowering',
        6 => 'Ani — The Harvest',
        7 => 'Kamalig — The Granary',
        8 => 'Kapitbahayan — The Neighbourhood',
        9 => 'Dalubhasa — The Mastery',
        10 => 'Alamat — The Legend',
    ];

    /**
     * Fifty ranks, ten arcs of five, each step steeper than the last.
     *
     * The names walk a farming life: a seed soaked and sprouted, hands in the
     * mud, a crop grown, flowered, harvested, stored, traded; then the farmer
     * becomes a neighbour, a teacher, a master, and at last a legend of the
     * fields. Thresholds roughly ×1.12–1.5 per step — the second rank is one
     * post away, the fiftieth is years of showing up.
     */
    public const TIERS = [
        // Arc I — Binhi (The Seed)
        ['min' => 0, 'emoji' => '🌰', 'name' => 'Binhi', 'en' => 'Seed'],
        ['min' => 10, 'emoji' => '💧', 'name' => 'Binhing Basâ', 'en' => 'Soaked Seed'],
        ['min' => 25, 'emoji' => '🌱', 'name' => 'Sibol', 'en' => 'First Sprout'],
        ['min' => 45, 'emoji' => '🪴', 'name' => 'Punla', 'en' => 'Seedling'],
        ['min' => 70, 'emoji' => '🌿', 'name' => 'Ugat na Malalim', 'en' => 'Deep Roots'],
        // Arc II — Tanim (The Planting)
        ['min' => 100, 'emoji' => '🧤', 'name' => 'Bagong Kamay', 'en' => 'New Hands'],
        ['min' => 140, 'emoji' => '🥾', 'name' => 'Paa sa Putik', 'en' => 'Feet in the Mud'],
        ['min' => 190, 'emoji' => '🌾', 'name' => 'Magtatanim', 'en' => 'Planter'],
        ['min' => 250, 'emoji' => '💦', 'name' => 'Magdidilig', 'en' => 'Waterer'],
        ['min' => 320, 'emoji' => '🌤️', 'name' => 'Bantay-Punla', 'en' => 'Seedling Keeper'],
        // Arc III — Lagô (The Growing)
        ['min' => 400, 'emoji' => '🍃', 'name' => 'Unang Dahon', 'en' => 'First Leaf'],
        ['min' => 500, 'emoji' => '🌿', 'name' => 'Suwi', 'en' => 'Tiller'],
        ['min' => 620, 'emoji' => '📏', 'name' => 'Taas-Tuhod', 'en' => 'Knee-High'],
        ['min' => 760, 'emoji' => '🐛', 'name' => 'Tagahuli ng Peste', 'en' => 'Pest Catcher'],
        ['min' => 920, 'emoji' => '🟢', 'name' => 'Luntiang Bukid', 'en' => 'Green Field'],
        // Arc IV — Magsasaka (The Farmer)
        ['min' => 1100, 'emoji' => '🧑‍🌾', 'name' => 'Magsasaka', 'en' => 'Farmer'],
        ['min' => 1300, 'emoji' => '🐃', 'name' => 'Mag-aararo', 'en' => 'Plower'],
        ['min' => 1550, 'emoji' => '🧪', 'name' => 'Tagapag-abono', 'en' => 'Fertilizer Hand'],
        ['min' => 1850, 'emoji' => '🚿', 'name' => 'Tagapatubig', 'en' => 'Irrigator'],
        ['min' => 2200, 'emoji' => '🌦️', 'name' => 'Kaibigan ng Ulan', 'en' => 'Friend of the Rain'],
        // Arc V — Bulaklak (The Flowering)
        ['min' => 2600, 'emoji' => '🌸', 'name' => 'Namumulaklak', 'en' => 'In Bloom'],
        ['min' => 3050, 'emoji' => '🐝', 'name' => 'Kasama ng Bubuyog', 'en' => 'Keeper of Bees'],
        ['min' => 3550, 'emoji' => '🌾', 'name' => 'Nagbubutil', 'en' => 'Setting Grain'],
        ['min' => 4100, 'emoji' => '🌕', 'name' => 'Hinog sa Araw', 'en' => 'Sun-Ripened'],
        ['min' => 4700, 'emoji' => '✨', 'name' => 'Gintong Uhay', 'en' => 'Golden Panicle'],
        // Arc VI — Ani (The Harvest)
        ['min' => 5400, 'emoji' => '🔪', 'name' => 'Gumagapas', 'en' => 'Reaper'],
        ['min' => 6200, 'emoji' => '🧺', 'name' => 'Mang-aani', 'en' => 'Harvester'],
        ['min' => 7100, 'emoji' => '🌾', 'name' => 'Maggigiik', 'en' => 'Thresher'],
        ['min' => 8100, 'emoji' => '☀️', 'name' => 'Tagabilad', 'en' => 'Grain Dryer'],
        ['min' => 9200, 'emoji' => '🏆', 'name' => 'Masaganang Ani', 'en' => 'Bountiful Harvest'],
        // Arc VII — Kamalig (The Granary)
        ['min' => 10500, 'emoji' => '🛖', 'name' => 'Katiwala ng Kamalig', 'en' => 'Granary Steward'],
        ['min' => 12000, 'emoji' => '⚖️', 'name' => 'Mangangalakal', 'en' => 'Trader'],
        ['min' => 13700, 'emoji' => '🛒', 'name' => 'Bida ng Palengke', 'en' => 'Star of the Market'],
        ['min' => 15600, 'emoji' => '💰', 'name' => 'Masaganang Bukid', 'en' => 'Prosperous Farm'],
        ['min' => 17700, 'emoji' => '🌟', 'name' => 'Bantog na Magsasaka', 'en' => 'Renowned Farmer'],
        // Arc VIII — Kapitbahayan (The Neighbourhood)
        ['min' => 20000, 'emoji' => '🤝', 'name' => 'Kapit-Bukid', 'en' => 'Farm Neighbour'],
        ['min' => 22600, 'emoji' => '🗣️', 'name' => 'Tagapayo', 'en' => 'Adviser'],
        ['min' => 25500, 'emoji' => '📚', 'name' => 'Guro ng Bukid', 'en' => 'Teacher of the Field'],
        ['min' => 28700, 'emoji' => '🏡', 'name' => 'Haligi ng Barangay', 'en' => 'Pillar of the Barangay'],
        ['min' => 32200, 'emoji' => '🕊️', 'name' => 'Kagalang-galang', 'en' => 'The Respected'],
        // Arc IX — Dalubhasa (The Mastery)
        ['min' => 36000, 'emoji' => '🎓', 'name' => 'Dalubhasa', 'en' => 'Expert'],
        ['min' => 40200, 'emoji' => '🧭', 'name' => 'Maestro ng Palayan', 'en' => 'Master of the Paddies'],
        ['min' => 44800, 'emoji' => '🔥', 'name' => 'Panday ng Ani', 'en' => 'Smith of the Harvest'],
        ['min' => 49800, 'emoji' => '🌋', 'name' => 'Di-Natitinag', 'en' => 'The Unshaken'],
        ['min' => 55200, 'emoji' => '🦅', 'name' => 'Agila ng Bukid', 'en' => 'Eagle of the Field'],
        // Arc X — Alamat (The Legend)
        ['min' => 61000, 'emoji' => '🌙', 'name' => 'Diwa ng Bukid', 'en' => 'Spirit of the Field'],
        ['min' => 67500, 'emoji' => '⭐', 'name' => 'Bituin ng Ani', 'en' => 'Star of the Harvest'],
        ['min' => 74500, 'emoji' => '👑', 'name' => 'Lakan ng Lupa', 'en' => 'Lord of the Land'],
        ['min' => 82000, 'emoji' => '🐉', 'name' => 'Alamat ng Ani', 'en' => 'Legend of the Harvest'],
        ['min' => 90000, 'emoji' => '🏵️', 'name' => 'Datu ng Bukid', 'en' => 'Datu of the Fields'],
    ];

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
            $map = [];
            foreach ($counts as $userId => $c) {
                $map[$userId] = self::score($c);
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
     * The rank a number of points has climbed to.
     *
     * @return array{n: int, min: int, emoji: string, name: string, en: string, arc: int}
     */
    public static function rankOf(int $points): array
    {
        $hit = 0;
        foreach (self::TIERS as $i => $tier) {
            if ($points >= $tier['min']) {
                $hit = $i;
            } else {
                break;
            }
        }

        return self::TIERS[$hit] + ['n' => $hit + 1, 'arc' => intdiv($hit, 5) + 1];
    }

    /** One member's rank, straight off the scoreboard. */
    public static function rankFor(int $userId): array
    {
        return self::rankOf(self::pointsFor($userId));
    }

    /** The rank after this one, or null at the top of the ladder. */
    public static function next(array $rank): ?array
    {
        $i = $rank['n'];   // n is 1-based; TIERS is 0-based, so n IS the next index

        return isset(self::TIERS[$i])
            ? self::TIERS[$i] + ['n' => $i + 1, 'arc' => intdiv($i, 5) + 1]
            : null;
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
