<?php

namespace Database\Seeders;

use App\Models\CommunityConnection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo data for the "People you may know" recommendations: gives the demo
 * account (id 3) friends-of-friends and a same-city co-farmer so the
 * recommendation ranking has something to show. Idempotent.
 */
class CommunityRecoSeeder extends Seeder
{
    public function run(): void
    {
        // demo(3) friends: Rosa(4) Tonyo(5) Lito(6) Nena(7) Edgar(9) Cely(12) Imelda(14).
        // Wire each non-contact below to some of those friends → mutual co-farmers.
        $links = [
            11 => [4, 6, 12],   // Boyet — 3 mutuals (top pick)
            15 => [5, 7],       // Nonoy — 2 mutuals
            13 => [7],          // Domeng — 1 mutual (+ same city, set below)
            16 => [9],          // Luz — 1 mutual
            18 => [14],         // Divina — 1 mutual
        ];

        foreach ($links as $candidate => $friends) {
            foreach ($friends as $friend) {
                if (CommunityConnection::between($friend, $candidate)) {
                    continue; // already linked (idempotent)
                }
                CommunityConnection::create([
                    'userId' => $friend,
                    'friendUserId' => $candidate,
                    'status' => 'accepted',
                    'respondedAt' => Carbon::now(),
                    'deleteStatus' => 1,
                ]);
            }
        }

        // Give Domeng(13) the demo's home town so he ranks on mutual + location.
        $domeng = User::find(13);
        if ($domeng && $domeng->email === 'domeng.reyes@demo.anisystem.test') {
            $domeng->update(['city' => 'Cabanatuan', 'province' => 'Nueva Ecija']);
        }

        $this->command?->info('Reco demo: friends-of-friends + a same-town co-farmer wired for demo(3).');
    }
}
