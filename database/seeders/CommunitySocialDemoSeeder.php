<?php

namespace Database\Seeders;

use App\Models\CommunityBookmark;
use App\Models\CommunityConnection;
use App\Models\CommunityFollow;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityWallPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demo content for everything the community grew this week: thought bubbles,
 * follows, bookmarks, a shared post, more discussions to join, and enough
 * unread topics that the badges have something to count.
 *
 * Idempotent throughout — every write is keyed on something stable, so running
 * it twice changes nothing. Safe to run against the shared dev database.
 */
class CommunitySocialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $members = User::where('deleteStatus', 1)
            ->whereNull('adminUserId')
            ->orderBy('id')
            ->limit(20)
            ->get();
        if ($members->count() < 4) {
            $this->command?->warn('Not enough demo members — run CommunityWorldSeeder first.');

            return;
        }

        $demo = User::where('email', 'demo@anisystem.test')->first() ?: $members->first();

        /* ---- thought bubbles ------------------------------------------
         * The wall shows what is on somebody's mind above their face, and an
         * empty bubble is the one thing that makes the feature look broken.
         * Only members who have none are given one. */
        $bubbles = [
            '☀️ Tag-init na — bawal makalimot magdilig',
            '🌾 Malapit na ang anihan!',
            '🐛 May bagong peste sa Lot B, inaaral ko pa',
            '💧 Kulang pa ang tubig sa kanal',
            '🌱 Bagong tanim, sana umulan',
            '📉 Mahal ang abono ngayon',
            '🚜 Kaka-ayos lang ng traktora',
            '🙏 Salamat sa mga natutunan dito',
            '🌤️ Maganda ang panahon para mag-spray',
            '🍚 Nag-aani na sa kabilang barangay',
        ];
        $given = 0;
        foreach ($members as $i => $m) {
            if (filled($m->statusBubble)) {
                continue;
            }
            $m->statusBubble = $bubbles[$i % count($bubbles)];
            $m->save();
            $given++;
        }
        $this->command?->info("Thought bubbles given: {$given}");

        /* ---- follows ---------------------------------------------------
         * Enough that the wall lifts somebody's newest post and the Following
         * tag has both states to show. */
        $follows = 0;
        foreach ($members as $i => $follower) {
            foreach ([1, 2, 3] as $step) {
                $target = $members[($i + $step) % $members->count()];
                if ((int) $target->id === (int) $follower->id) {
                    continue;
                }
                $row = CommunityFollow::firstOrNew([
                    'followerUserId' => $follower->id,
                    'followedUserId' => $target->id,
                ]);
                if (! $row->exists) {
                    $row->deleteStatus = 1;
                    $row->save();
                    $follows++;
                }
            }
        }
        $this->command?->info("Follows created: {$follows}");

        /* ---- bookmarks + one share for the demo account ----------------- */
        $posts = CommunityWallPost::active()
            ->where('authorUserId', '!=', $demo->id)
            ->orderByDesc('id')
            ->limit(4)
            ->get();
        $saved = 0;
        foreach ($posts as $post) {
            $row = CommunityBookmark::firstOrNew([
                'userId' => $demo->id,
                'targetType' => CommunityBookmark::TYPE_WALL,
                'targetId' => $post->id,
            ]);
            if (! $row->exists) {
                $row->deleteStatus = 1;
                $row->save();
                $saved++;
            }
        }
        $this->command?->info("Bookmarks for the demo account: {$saved}");

        if ($posts->isNotEmpty()) {
            $original = $posts->first();
            $already = CommunityWallPost::active()
                ->where('authorUserId', $demo->id)
                ->where('sharedPostId', $original->id)
                ->exists();
            if (! $already) {
                CommunityWallPost::create([
                    'wallUserId' => $demo->id,
                    'authorUserId' => $demo->id,
                    'body' => 'Tama ito — ganito rin ang ginawa ko noong isang taon. Worth reading. 👇',
                    'sharedPostId' => $original->id,
                    'deleteStatus' => 1,
                ]);
                $this->command?->info('Shared post created.');
            }
        }

        /* ---- more discussions, joined, with unread topics ---------------
         * The badges need rooms the demo account is IN, carrying topics
         * written by somebody else after the account last looked. */
        $rooms = [
            ['Mais at Gulay — Nueva Ecija', 'Corn and vegetable growers swapping what actually yields.'],
            ['Organic at Natural Farming', 'Compost, IMO, and giving up on the expensive stuff.'],
            ['Presyo at Merkado', 'What buyers are paying this week, by province.'],
        ];
        $others = $members->where('id', '!=', $demo->id)->values();
        $roomsMade = 0;
        $topicsMade = 0;

        foreach ($rooms as $r => [$name, $description]) {
            $group = CommunityGroup::where('name', $name)->first();
            if (! $group) {
                $group = CommunityGroup::create([
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)),
                    'description' => $description,
                    'createdByUserId' => $others[$r % $others->count()]->id,
                    'deleteStatus' => 1,
                ]);
                $roomsMade++;
            }

            // The demo account joins, plus a few neighbours so the room reads
            // as inhabited rather than empty.
            foreach ([$demo->id, $others[$r % $others->count()]->id, $others[($r + 1) % $others->count()]->id] as $uid) {
                CommunityGroupMember::firstOrCreate(
                    ['groupId' => $group->id, 'userId' => $uid],
                    ['deleteStatus' => 1],
                );
            }

            $topics = [
                ['Magkano ang abono sa inyo ngayon?', 'Tumaas ulit dito sa amin. Ano presyo sa lugar ninyo?'],
                ['Anong variety ang maganda sa tag-ulan?', 'Naghahanap ako ng matibay sa tubig. Suggestions?'],
                ['May nakatry na ba ng IMO?', 'Balak ko subukan sa isang lote muna bago sa lahat.'],
            ];
            foreach ($topics as $t => [$title, $body]) {
                $author = $others[($r + $t) % $others->count()];
                $exists = CommunityGroupPost::active()
                    ->where('groupId', $group->id)
                    ->where('title', $title)
                    ->exists();
                if ($exists) {
                    continue;
                }
                $post = CommunityGroupPost::create([
                    'groupId' => $group->id,
                    'userId' => $author->id,
                    'title' => $title,
                    'body' => '<p>' . e($body) . '</p>',
                    'deleteStatus' => 1,
                ]);
                $topicsMade++;

                // A couple of replies, so the list's reply counter is not zero.
                foreach ([1, 2] as $k) {
                    CommunityGroupReply::create([
                        'postId' => $post->id,
                        'userId' => $others[($r + $t + $k) % $others->count()]->id,
                        'body' => $k === 1
                            ? 'Ganito rin dito sa amin. Sulit subukan.'
                            : 'Salamat sa tip! Susubukan ko sa susunod na tanim.',
                        'deleteStatus' => 1,
                    ]);
                }
            }
        }
        $this->command?->info("Discussions created: {$roomsMade}, topics: {$topicsMade}");

        /* ---- a couple of pending requests, so that badge counts too ----- */
        $pending = 0;
        foreach ($others->take(2) as $sender) {
            $status = CommunityConnection::statusFor((int) $sender->id, (int) $demo->id);
            if ($status !== 'none') {
                continue;
            }
            CommunityConnection::create([
                'userId' => $sender->id,
                'friendUserId' => $demo->id,
                'status' => 'pending',
                'deleteStatus' => 1,
            ]);
            $pending++;
        }
        $this->command?->info("Pending co-farmer requests: {$pending}");
    }
}
