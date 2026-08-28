<?php

namespace Database\Seeders;

use App\Models\CommunityConnection;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates the Community so a fresh demo feels alive: a handful of farmer
 * members with locations, two groups with topics + replies, some accepted
 * connections, one pending request into the demo account, and wall activity.
 *
 * Fully idempotent — keyed off stable emails, group names and body text.
 */
class CommunityWorldSeeder extends Seeder
{
    /** @var array<int,array{email:string,first:string,last:string,city:string,province:string,bio:string}> */
    private array $farmers = [
        ['email' => 'aling.rosa@demo.anisystem.test', 'first' => 'Rosa', 'last' => 'Mendoza', 'city' => 'Muñoz', 'province' => 'Nueva Ecija', 'bio' => 'Rice farmer for 20 years. Happy to share what works in the wet season.'],
        ['email' => 'mang.tonyo@demo.anisystem.test', 'first' => 'Antonio', 'last' => 'Reyes', 'city' => 'Victoria', 'province' => 'Tarlac', 'bio' => 'Corn and rice. Always testing new varieties.'],
        ['email' => 'ka.lito@demo.anisystem.test', 'first' => 'Carlito', 'last' => 'Santos', 'city' => 'Bay', 'province' => 'Laguna', 'bio' => 'Vegetable grower — tomatoes, eggplant, ampalaya.'],
        ['email' => 'nena.cruz@demo.anisystem.test', 'first' => 'Nena', 'last' => 'Cruz', 'city' => 'Cabanatuan', 'province' => 'Nueva Ecija', 'bio' => 'Young farmer, learning every season.'],
    ];

    public function run(): void
    {
        $demo = User::where('email', 'demo@anisystem.test')->first();
        if (! $demo) {
            $this->command?->warn('CommunityWorldSeeder: demo user missing; skipped.');

            return;
        }

        $people = collect($this->farmers)->map(fn ($f) => $this->ensureFarmer($f));

        $this->seedGroups($people, $demo);
        $this->seedConnections($people, $demo);
        $this->seedWall($people, $demo);

        $this->command?->info('CommunityWorldSeeder: farmers, groups, connections and walls ready.');
    }

    private function ensureFarmer(array $f): User
    {
        $user = User::where('email', $f['email'])->first() ?: new User;
        $user->email = $f['email'];
        $user->firstName = $f['first'];
        $user->lastName = $f['last'];
        $user->phone = $user->phone ?: '0917' . str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);
        $user->password = $user->password ?: Hash::make(Str::random(24));
        $user->city = $f['city'];
        $user->province = $f['province'];
        $user->bio = $f['bio'];
        $user->status = 'active';
        $user->deleteStatus = 1;
        $user->save();

        return $user;
    }

    private function seedGroups($people, User $demo): void
    {
        $groups = [
            [
                'name' => 'Rice Growers PH',
                'description' => 'Tips, timing and troubleshooting for palay across the seasons.',
                'owner' => $people[0],
                'members' => [$people[0], $people[1], $people[3], $demo],
                'posts' => [
                    ['author' => $people[0], 'title' => 'Best DAS to start top-dressing?', 'body' => 'For NSIC Rc222 in the wet season, when do you apply your first urea top-dress? I usually go around DAS 20.', 'replies' => [
                        ['author' => $people[1], 'body' => 'I do DAS 18–21 depending on the weather. Watch for the leaf color.'],
                        ['author' => $people[3], 'body' => 'Thanks, this helps! First season for me.'],
                    ]],
                    ['author' => $people[1], 'title' => 'Golden apple snail control', 'body' => 'Kuhol is bad this year. What are you all using that is safe?', 'replies' => [
                        ['author' => $people[0], 'body' => 'Hand-pick early morning and keep water shallow for the first 2 weeks.'],
                    ]],
                ],
            ],
            [
                'name' => 'Vegetable Farmers',
                'description' => 'Tomatoes, eggplant, ampalaya and more — grow and sell better.',
                'owner' => $people[2],
                'members' => [$people[2], $people[3], $demo],
                'posts' => [
                    ['author' => $people[2], 'title' => 'Trellising ampalaya', 'body' => 'What spacing gives you the best yield on bitter gourd? I am trying 1.5m rows.', 'replies' => [
                        ['author' => $people[3], 'body' => 'Wider rows made harvesting much easier for me.'],
                    ]],
                ],
            ],
        ];

        foreach ($groups as $g) {
            $group = CommunityGroup::where('name', $g['name'])->first();
            if (! $group) {
                $group = CommunityGroup::create([
                    'name' => $g['name'],
                    'slug' => Str::slug($g['name']),
                    'description' => $g['description'],
                    'createdByUserId' => $g['owner']->id,
                    'deleteStatus' => 1,
                ]);
            }

            foreach ($g['members'] as $m) {
                CommunityGroupMember::firstOrCreate(
                    ['groupId' => $group->id, 'userId' => $m->id],
                    ['role' => $m->id === $g['owner']->id ? 'owner' : 'member', 'deleteStatus' => 1]
                );
            }

            foreach ($g['posts'] as $p) {
                $post = CommunityGroupPost::where('groupId', $group->id)->where('body', $p['body'])->first();
                if (! $post) {
                    $post = CommunityGroupPost::create([
                        'groupId' => $group->id,
                        'userId' => $p['author']->id,
                        'title' => $p['title'],
                        'body' => $p['body'],
                        'deleteStatus' => 1,
                    ]);
                }
                foreach ($p['replies'] as $r) {
                    CommunityGroupReply::firstOrCreate(
                        ['postId' => $post->id, 'userId' => $r['author']->id, 'body' => $r['body']],
                        ['deleteStatus' => 1]
                    );
                }
            }
        }
    }

    private function seedConnections($people, User $demo): void
    {
        // Demo is accepted-connected with the first two farmers.
        foreach ([$people[0], $people[1]] as $friend) {
            if (! CommunityConnection::between($demo->id, $friend->id)) {
                CommunityConnection::create([
                    'userId' => $friend->id,
                    'friendUserId' => $demo->id,
                    'status' => 'accepted',
                    'respondedAt' => now(),
                    'deleteStatus' => 1,
                ]);
            }
        }

        // A fresh pending request into the demo account, so the bell + Requests
        // page have something to show.
        if (! CommunityConnection::between($demo->id, $people[2]->id)) {
            CommunityConnection::create([
                'userId' => $people[2]->id,
                'friendUserId' => $demo->id,
                'status' => 'pending',
                'deleteStatus' => 1,
            ]);
        }

        // The two Nueva Ecija farmers know each other.
        if (! CommunityConnection::between($people[0]->id, $people[3]->id)) {
            CommunityConnection::create([
                'userId' => $people[0]->id,
                'friendUserId' => $people[3]->id,
                'status' => 'accepted',
                'respondedAt' => now(),
                'deleteStatus' => 1,
            ]);
        }
    }

    private function seedWall($people, User $demo): void
    {
        $posts = [
            ['wall' => $demo, 'author' => $people[0], 'body' => 'Welcome to anee.io! Your wet-season plan looks solid. 🌾', 'comments' => [
                ['user' => $demo, 'body' => 'Salamat po, Aling Rosa!'],
            ]],
            ['wall' => $demo, 'author' => $people[1], 'body' => 'Let us swap notes on urea timing this season.', 'comments' => []],
            ['wall' => $people[0], 'author' => $people[3], 'body' => 'Thank you for the snail tip — it worked!', 'comments' => []],
        ];

        foreach ($posts as $p) {
            $post = CommunityWallPost::where('wallUserId', $p['wall']->id)->where('body', $p['body'])->first();
            if (! $post) {
                $post = CommunityWallPost::create([
                    'wallUserId' => $p['wall']->id,
                    'authorUserId' => $p['author']->id,
                    'body' => $p['body'],
                    'deleteStatus' => 1,
                ]);
            }
            foreach ($p['comments'] as $comment) {
                CommunityWallComment::firstOrCreate(
                    ['wallPostId' => $post->id, 'userId' => $comment['user']->id, 'body' => $comment['body']],
                    ['deleteStatus' => 1]
                );
            }
        }
    }
}
