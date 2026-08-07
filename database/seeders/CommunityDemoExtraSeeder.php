<?php

namespace Database\Seeders;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityMessage;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Extra demo content on top of CommunityWorldSeeder: more group discussions,
 * more co-farmer wall posts with threaded (replied) comments, so the dashboard
 * feed + Latest Discussions rail feel lived-in. Idempotent (keyed on body).
 * Blog covers are handled separately (they need a download).
 */
class CommunityDemoExtraSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure the base world (demo user, farmers, groups, connections)
        // exists first — this is additive on top of it.
        $this->call(CommunityWorldSeeder::class);

        $demo  = User::where('email', 'demo@anisystem.test')->first();
        $rosa  = User::where('email', 'aling.rosa@demo.anisystem.test')->first();
        $tonyo = User::where('email', 'mang.tonyo@demo.anisystem.test')->first();
        $lito  = User::where('email', 'ka.lito@demo.anisystem.test')->first();
        $nena  = User::where('email', 'nena.cruz@demo.anisystem.test')->first();
        if (! $demo || ! $rosa || ! $tonyo || ! $lito || ! $nena) {
            $this->command?->warn('CommunityDemoExtraSeeder: demo cast missing; skipped.');

            return;
        }

        $rice = CommunityGroup::where('name', 'Rice Growers PH')->first();
        $veg  = CommunityGroup::where('name', 'Vegetable Farmers')->first();

        $this->seedDiscussions($rice, $veg, compact('demo', 'rosa', 'tonyo', 'lito', 'nena'));
        $this->seedThreadedWall($demo, compact('demo', 'rosa', 'tonyo', 'lito', 'nena'));
        $this->seedChats(compact('demo', 'rosa', 'tonyo', 'nena'));

        $this->command?->info('CommunityDemoExtraSeeder: extra discussions, threaded wall comments + chats ready.');
    }

    /** 10 more discussion posts across the two groups, each with a few replies. */
    private function seedDiscussions(?CommunityGroup $rice, ?CommunityGroup $veg, array $c): void
    {
        $posts = [];

        if ($rice) {
            $posts = array_merge($posts, [
                [$rice, $c['tonyo'], 'Foliar fertilizer — worth it?', 'Nag-try na ba kayo ng foliar spray during panicle initiation? Curious if the yield bump is real.', [
                    [$c['rosa'], 'Slight bump lang for me, but timing matters — do it before flowering.'],
                    [$c['nena'], 'Following! Balak ko subukan this season.'],
                ]],
                [$rice, $c['rosa'], 'When to drain before harvest?', 'I usually drain the paddy 7–10 days before harvest so the field firms up. Kayo?', [
                    [$c['tonyo'], '10 days for me. Mas madali mag-combine kapad tuyo ang lupa.'],
                ]],
                [$rice, $c['nena'], 'First time seeing tungro — help!', 'Yellow-orange leaves on a few hills. Is this tungro? What do I do?', [
                    [$c['rosa'], 'Rogue the infected hills agad and control the green leafhopper. Wag hayaang kumalat.'],
                    [$c['tonyo'], 'Send a photo sa group, mas madaling ma-confirm.'],
                ]],
                [$rice, $c['tonyo'], 'Best combine harvester rental rate?', 'Magkano ang rental ng combine sa lugar niyo per hectare? Nagtataas dito sa Tarlac.', [
                    [$c['rosa'], 'Around ₱3,500/ha samin, kasama na ang hauling.'],
                ]],
                [$rice, $c['rosa'], 'Seed rate for direct seeding', 'Shifting to wet direct seeding. Ilang kilo/ha ang gamit niyo? Ayoko masyadong siksik.', [
                    [$c['nena'], '40kg/ha daw ang recommended, pero 30 na lang ako para hindi masyadong dense.'],
                    [$c['tonyo'], 'Pre-germinate mo muna for even stand.'],
                ]],
                [$rice, $c['nena'], 'Rat control na hindi nakakalason sa aso', 'Daga problem this month. May safe option ba na hindi delikado sa alagang hayop?', [
                    [$c['rosa'], 'Community-wide baiting + linisin ang bunds. Mas effective kapad sabay-sabay ang lahat.'],
                ]],
            ]);
        }

        if ($veg) {
            $posts = array_merge($posts, [
                [$veg, $c['lito'], 'Tomato leaf curl — anong gamot?', 'Kulot ang dahon ng kamatis ko, whitefly ang kasama. Anong pinaka-epektibo?', [
                    [$c['nena'], 'Yellow sticky traps + neem. Tanggalin agad ang badly infected.'],
                ]],
                [$veg, $c['nena'], 'Eggplant spacing for higher yield', 'Ano bang spacing niyo sa talong? Nasa 60cm ako pero baka pwede pang i-adjust.', [
                    [$c['lito'], '60x75cm sakin, sakto ang airflow at dami pa rin ng bunga.'],
                ]],
                [$veg, $c['lito'], 'Ampalaya price crash — hold or sell?', 'Bumagsak ang presyo ng ampalaya. Nag-a-abang ba kayo o benta agad?', [
                    [$c['nena'], 'Benta ko agad, mabilis masira. Hindi worth i-hold.'],
                    [$c['lito'], 'Tama, mas malaki loss kapad naiwan.'],
                ]],
                [$veg, $c['nena'], 'Organic pechay for the palengke', 'May suki ako na gusto organic pechay. Worth ba ang premium price sa effort?', [
                    [$c['lito'], 'Oo kung may steady buyer ka. Ang hirap lang ng pest management pag walang spray.'],
                ]],
            ]);
        }

        foreach ($posts as [$group, $author, $title, $body, $replies]) {
            $post = CommunityGroupPost::where('groupId', $group->id)->where('body', $body)->first();
            if (! $post) {
                $post = CommunityGroupPost::create([
                    'groupId' => $group->id,
                    'userId' => $author->id,
                    'title' => $title,
                    'body' => $body,
                    'deleteStatus' => 1,
                ]);
            }
            foreach ($replies as [$rAuthor, $rBody]) {
                CommunityGroupReply::firstOrCreate(
                    ['postId' => $post->id, 'userId' => $rAuthor->id, 'body' => $rBody],
                    ['deleteStatus' => 1]
                );
            }
        }
    }

    /**
     * Co-farmer wall posts (authored by the demo's connected co-farmers, so they
     * surface in "From your co-farmers") with threaded comments: top-level
     * comments + replies (parentId) to some of them.
     */
    private function seedThreadedWall(User $demo, array $c): void
    {
        // Each: [wall owner, author, body, [ [commenter, body, [ [replier, body], ... ]], ... ]]
        $posts = [
            [$demo, $c['rosa'], 'Harvest done! 5.4t/ha this wet season — best yield yet. 🌾🙏', [
                [$c['demo'], 'Ang galing! Anong variety po ginamit?', [
                    [$c['rosa'], 'Rc222 pa rin, sakto sa lupa namin.'],
                    [$c['demo'], 'Noted, susubukan ko next season!'],
                ]],
                [$c['tonyo'], 'Congrats Aling Rosa! Ako 4.8 lang this year.', [
                    [$c['rosa'], 'Malapit na rin! Baka water management lang kulang.'],
                ]],
                [$c['nena'], 'Inspiring po! 👏', []],
            ]],
            [$c['rosa'], $c['tonyo'], 'Testing a new hybrid corn on 1 hectare. Will share results after harvest.', [
                [$c['rosa'], 'Excited for this — keep us posted!', [
                    [$c['tonyo'], 'Sure, weekly updates dito sa wall.'],
                ]],
                [$c['demo'], 'Anong hybrid kung pwede itanong?', [
                    [$c['tonyo'], 'DM kita, medyo experimental pa.'],
                ]],
            ]],
            [$demo, $c['tonyo'], 'Reminder: bawas na ng nitrogen 3 weeks before harvest. Nakaka-lodging pag sobra.', [
                [$c['demo'], 'Salamat sa reminder!', []],
                [$c['rosa'], 'Totoo ito, natumba yung isang lote ko last year kaka-urea.', [
                    [$c['tonyo'], 'Exactly. Split application na lang tayo.'],
                ]],
            ]],
            [$c['tonyo'], $c['rosa'], 'Anyone selling certified seeds near Nueva Ecija? Need for next planting.', [
                [$c['demo'], 'May kilala ako sa Muñoz, i-share ko contact.', [
                    [$c['rosa'], 'Salamat! PM mo na lang.'],
                ]],
            ]],
        ];

        foreach ($posts as [$wall, $author, $body, $comments]) {
            $post = CommunityWallPost::where('wallUserId', $wall->id)->where('body', $body)->first();
            if (! $post) {
                $post = CommunityWallPost::create([
                    'wallUserId' => $wall->id,
                    'authorUserId' => $author->id,
                    'body' => $body,
                    'deleteStatus' => 1,
                ]);
            }
            foreach ($comments as [$cUser, $cBody, $replies]) {
                $top = CommunityWallComment::firstOrCreate(
                    ['wallPostId' => $post->id, 'userId' => $cUser->id, 'body' => $cBody, 'parentId' => null],
                    ['deleteStatus' => 1]
                );
                foreach ($replies as [$rUser, $rBody]) {
                    CommunityWallComment::firstOrCreate(
                        ['wallPostId' => $post->id, 'userId' => $rUser->id, 'body' => $rBody, 'parentId' => $top->id],
                        ['deleteStatus' => 1]
                    );
                }
            }
        }
    }

    /**
     * Direct-message chat history between the demo user and co-farmers, so the
     * Messenger dock shows real threads (a couple left unread for the badge).
     * Order of creation = chronological order in the thread.
     */
    private function seedChats(array $c): void
    {
        // [from, to, body, isRead]
        $messages = [
            [$c['rosa'], $c['demo'], 'Kamusta ang tanim mo ngayon?', 1],
            [$c['demo'], $c['rosa'], 'Maganda naman po, malapit na mag-flowering. 🌾', 1],
            [$c['rosa'], $c['demo'], 'Ayos! Wag lang kalimutan ang tubig sa flowering ha.', 1],
            [$c['demo'], $c['rosa'], 'Noted po, salamat sa reminder!', 1],
            [$c['rosa'], $c['demo'], 'May extra akong certified seeds kung kailangan mo next season.', 0],

            [$c['tonyo'], $c['demo'], 'Pre, magkano ang rental ng combine sa lugar niyo?', 1],
            [$c['demo'], $c['tonyo'], '3,500/ha po dito, kasama na ang hauling.', 1],
            [$c['tonyo'], $c['demo'], 'Sige salamat. Baka pahingi ako ng contact ng operator.', 1],
            [$c['demo'], $c['tonyo'], 'Sige, ipapadala ko mamaya.', 1],
            [$c['tonyo'], $c['demo'], 'Salamat pre! 👍', 0],

            [$c['nena'], $c['demo'], 'Kuya, first harvest ko na this month. Kinakabahan ako haha', 1],
            [$c['demo'], $c['nena'], 'Kaya mo yan! I-follow mo lang ang schedule mo.', 1],
            [$c['nena'], $c['demo'], 'Salamat sa tips, malaking tulong! 🙏', 0],
        ];

        foreach ($messages as [$from, $to, $body, $read]) {
            CommunityMessage::firstOrCreate(
                ['senderId' => $from->id, 'recipientId' => $to->id, 'body' => $body],
                ['isRead' => $read, 'deleteStatus' => 1]
            );
        }
    }
}
