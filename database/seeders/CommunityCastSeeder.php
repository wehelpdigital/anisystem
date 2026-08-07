<?php

namespace Database\Seeders;

use App\Models\CommunityConnection;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fills the community demo: a cast of Filipino farmers with portrait avatars
 * (public/storage/avatars), locations clustered around the demo user's
 * province, friendships with the demo account, and a lively wall.
 * Idempotent — upserts by email; safe to re-run.
 */
class CommunityCastSeeder extends Seeder
{
    public function run(): void
    {
        $demo = User::where('email', 'demo@anisystem.test')->first();
        if (! $demo) {
            $this->command?->warn('Demo user missing — aborting.');
            return;
        }
        // The demo farmer gets a home base + portrait too.
        $demo->update(['city' => 'Cabanatuan', 'province' => 'Nueva Ecija', 'avatarPath' => 'avatars/m11.jpg']);

        $cast = [
            // [first, last, city, province, avatar, bio, email]
            ['Tonyo', 'Ramos', 'Cabanatuan', 'Nueva Ecija', 'avatars/m12.jpg', 'Palay farmer for 20 years. NSIC Rc222 believer.', 'mang.tonyo@demo.anisystem.test'],
            ['Rosa', 'Mendoza', 'Talavera', 'Nueva Ecija', 'avatars/w21.jpg', 'Gulay at palay. Ask me about foliar feeding.', 'aling.rosa@demo.anisystem.test'],
            ['Lito', 'Garcia', 'Muñoz', 'Nueva Ecija', 'avatars/m13.jpg', 'Rice-onion rotation. CLSU short course grad.', 'ka.lito@demo.anisystem.test'],
            ['Nena', 'Cruz', 'Gapan', 'Nueva Ecija', 'avatars/w22.jpg', 'Backyard to 2 hectares in 5 years. Kaya mo rin!', 'nena.cruz@demo.anisystem.test'],
            ['Edgar', 'Santos', 'San Jose City', 'Nueva Ecija', 'avatars/m14.jpg', 'Onion + palay. Drying and storage tips welcome.', 'edgar.santos@demo.anisystem.test'],
            ['Marites', 'Dela Cruz', 'Ilagan', 'Isabela', 'avatars/w23.jpg', 'Corn farmer. Yellow and white, sabay pa.', 'marites.dc@demo.anisystem.test'],
            ['Boyet', 'Aquino', 'Cauayan', 'Isabela', 'avatars/m15.jpg', 'Hybrid corn + rice. Machine-first mindset.', 'boyet.aquino@demo.anisystem.test'],
            ['Cely', 'Villanueva', 'Urdaneta', 'Pangasinan', 'avatars/w24.jpg', 'Palay + mungo after harvest. Soil health muna.', 'cely.v@demo.anisystem.test'],
            ['Domeng', 'Reyes', 'Tarlac City', 'Tarlac', 'avatars/m16.jpg', 'Rice technician turned farmer.', 'domeng.reyes@demo.anisystem.test'],
            ['Imelda', 'Bautista', 'Iloilo City', 'Iloilo', 'avatars/w25.jpg', 'High-value crops: ampalaya, sitaw, okra.', 'imelda.b@demo.anisystem.test'],
            ['Nonoy', 'Fernandez', 'Digos', 'Davao del Sur', 'avatars/m17.jpg', 'Cacao + saging intercrop. Slowly but surely.', 'nonoy.f@demo.anisystem.test'],
            ['Luz', 'Navarro', 'Lipa', 'Batangas', 'avatars/w26.jpg', 'Coffee at gulay. Barako pride.', 'luz.navarro@demo.anisystem.test'],
            ['Carding', 'Torres', 'Ormoc', 'Leyte', 'avatars/m18.jpg', 'Palay + kamote. Typhoon-proofing everything.', 'carding.t@demo.anisystem.test'],
            ['Divina', 'Salazar', 'Santiago', 'Isabela', 'avatars/w27.jpg', 'Rice + duck integration. Itik power.', 'divina.s@demo.anisystem.test'],
            ['Ramon', 'Ocampo', 'Guimba', 'Nueva Ecija', 'avatars/m11.jpg', 'Seed grower. Certified palay seeds.', 'ramon.ocampo@demo.anisystem.test'],
            ['Perla', 'Del Rosario', 'Roxas', 'Isabela', 'avatars/w28.jpg', 'Farm records nerd. Numbers do not lie.', 'perla.dr@demo.anisystem.test'],
        ];

        $users = [];
        foreach ($cast as [$first, $last, $city, $province, $avatar, $bio, $email]) {
            $users[$email] = User::updateOrCreate(
                ['email' => $email],
                [
                    'firstName' => $first,
                    'lastName' => $last,
                    'city' => $city,
                    'province' => $province,
                    'avatarPath' => $avatar,
                    'bio' => $bio,
                    'password' => Hash::make('demo1234'),
                    'clientId' => $demo->clientId,
                    'status' => 'active',
                    'deleteStatus' => 1,
                ]
            );
        }

        // Friendships with the demo account, plus a couple of incoming
        // requests so the Requests page has life.
        $accepted = ['mang.tonyo@demo.anisystem.test', 'aling.rosa@demo.anisystem.test', 'ka.lito@demo.anisystem.test',
            'nena.cruz@demo.anisystem.test', 'edgar.santos@demo.anisystem.test', 'cely.v@demo.anisystem.test',
            'imelda.b@demo.anisystem.test'];
        foreach ($accepted as $i => $email) {
            $u = $users[$email];
            $existing = CommunityConnection::between($demo->id, $u->id);
            if (! $existing) {
                CommunityConnection::create([
                    'userId' => $i % 2 ? $demo->id : $u->id,
                    'friendUserId' => $i % 2 ? $u->id : $demo->id,
                    'status' => 'accepted',
                    'respondedAt' => now()->subDays(20 - $i),
                    'deleteStatus' => 1,
                ]);
            } elseif ($existing->status !== 'accepted') {
                $existing->update(['status' => 'accepted', 'respondedAt' => now()->subDays(3)]);
            }
        }
        foreach (['divina.s@demo.anisystem.test', 'domeng.reyes@demo.anisystem.test'] as $email) {
            $u = $users[$email];
            if (! CommunityConnection::between($demo->id, $u->id)) {
                CommunityConnection::create([
                    'userId' => $u->id, 'friendUserId' => $demo->id,
                    'status' => 'pending', 'deleteStatus' => 1,
                ]);
            }
        }
        // Friends of friends so the graph feels real.
        $pairs = [
            ['mang.tonyo@demo.anisystem.test', 'aling.rosa@demo.anisystem.test'],
            ['ka.lito@demo.anisystem.test', 'ramon.ocampo@demo.anisystem.test'],
            ['marites.dc@demo.anisystem.test', 'boyet.aquino@demo.anisystem.test'],
            ['imelda.b@demo.anisystem.test', 'luz.navarro@demo.anisystem.test'],
        ];
        foreach ($pairs as [$a, $b]) {
            if (! CommunityConnection::between($users[$a]->id, $users[$b]->id)) {
                CommunityConnection::create([
                    'userId' => $users[$a]->id, 'friendUserId' => $users[$b]->id,
                    'status' => 'accepted', 'respondedAt' => now()->subDays(8), 'deleteStatus' => 1,
                ]);
            }
        }

        // The wall: farm life over the last two weeks.
        $posts = [
            ['mang.tonyo@demo.anisystem.test', "Day 21 na ang palay ko sa North field. First top dressing done kanina umaga. 💪🌾", 13],
            ['aling.rosa@demo.anisystem.test', "Foliar spray tip: mas okay talaga bago mag-alas otso ng umaga. Yung dahon, hindi nasusunog. ☀️🌱", 12],
            ['ka.lito@demo.anisystem.test', "Onion beds prepped na. Pag natapos ang palay harvest, dire-diretso na tayo. 🧅", 11],
            ['nena.cruz@demo.anisystem.test', "5 years ago backyard lang ito. Ngayon 2 hectares na. Sa lahat ng nagsisimula pa lang — kaya mo rin! 🙏", 10],
            ['edgar.santos@demo.anisystem.test', "May nakakaalam ba ng magandang dryer service dito sa San Jose? Yung last namin sobrang bagal. 🤔", 9],
            ['marites.dc@demo.anisystem.test', "Yellow corn looking good this season. Kulang na lang ulan ng konti. 🌽", 8],
            ['cely.v@demo.anisystem.test', "Mungo after palay = libreng nitrogen sa susunod na season. Try niyo. 🌱", 7],
            ['boyet.aquino@demo.anisystem.test', "Bagong drone sprayer ng coop namin. 3 hectares in one morning. Ibang level na talaga. 🚜", 6],
            ['imelda.b@demo.anisystem.test', "Ampalaya harvest today! Presyo maganda sa Iloilo terminal. 😍", 5],
            ['domeng.reyes@demo.anisystem.test', "Reminder sa lahat: mag-check ng stem borer ngayong linggo. Nakita ko na sa amin. 🐛", 4],
            ['nonoy.f@demo.anisystem.test', "Cacao pods turning na. Mga 3 weeks pa siguro bago harvest. ☕", 4],
            ['luz.navarro@demo.anisystem.test', "Kape muna bago harvest. Barako break. ☕", 3],
            ['carding.t@demo.anisystem.test', "Na-secure na ang mga punla bago ang bagyo. Laging handa. 💪", 3],
            ['divina.s@demo.anisystem.test', "Itik sa palayan = walang kuhol, may itlog ka pa. Double win. 🦆", 2],
            ['ramon.ocampo@demo.anisystem.test', "Certified seeds vs good seeds — may pinagkaiba talaga sa yield. Usap tayo. 🌾", 2],
            ['perla.dr@demo.anisystem.test', "Farm record tip: isulat ang LAHAT ng gastos, pati pamasahe. Malaking gising sa season-end. 📒", 1],
            ['mang.tonyo@demo.anisystem.test', "Salamat sa lahat ng pumunta sa lakbay-aral kahapon. Next time sa farm ni Ka Lito naman. 🤝", 1],
        ];
        foreach ($posts as [$email, $body, $daysAgo]) {
            $u = $users[$email];
            if (! CommunityWallPost::where('wallUserId', $u->id)->where('body', $body)->exists()) {
                $p = CommunityWallPost::create([
                    'wallUserId' => $u->id, 'authorUserId' => $u->id,
                    'body' => $body, 'deleteStatus' => 1,
                ]);
                $p->created_at = now()->subDays($daysAgo)->subMinutes(random_int(0, 600));
                $p->save();
            }
        }

        // A few comments so walls feel answered.
        $comments = [
            ['Day 21 na ang palay ko', 'aling.rosa@demo.anisystem.test', 'Ang ganda ng progress, Mang Tonyo! 👏'],
            ['magandang dryer service', 'mang.tonyo@demo.anisystem.test', 'Try mo yung sa coop sa Muñoz — mabilis at maayos.'],
            ['stem borer', 'cely.v@demo.anisystem.test', 'Salamat sa abiso! Magche-check ako bukas ng umaga. 🙏'],
        ];
        foreach ($comments as [$needle, $email, $body]) {
            $post = CommunityWallPost::where('body', 'like', "%{$needle}%")->first();
            if ($post && ! CommunityWallComment::where('wallPostId', $post->id)->where('body', $body)->exists()) {
                CommunityWallComment::create([
                    'wallPostId' => $post->id,
                    'userId' => $users[$email]->id,
                    'body' => $body,
                    'deleteStatus' => 1,
                ]);
            }
        }

        $this->command?->info('Community cast seeded: ' . User::where('email', 'like', '%@demo.anisystem.test')->count() . ' members.');
    }
}
