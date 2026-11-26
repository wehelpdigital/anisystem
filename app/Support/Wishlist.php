<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The First 50 wishlist (2026-11-26, the owner: "create a wishlist form or wizard that i can market for the first 50
 * farmers ... do not show any anee branding or links to the main site ... surprise me").
 *
 * One page at /unang-50, a question to a screen, in Tagalog or English: who they are, where, what they grow, how big,
 * what hurts most, what they wish a helper in their phone did, their phone and signal, how and how much they would pay,
 * how they heard, and how to reach them. At the end: their place in line, a card to share, a link that moves them up
 * three places for every farmer who answers through it, and three things to do this week for what hurts most.
 *
 * Every choice is a key here, with its words in both languages, so the page, the admin panel and the export say the
 * same thing. Rows on as_wishlist_entries, found again by phone or email (answering twice keeps the place).
 */
final class Wishlist
{
    public const TABLE = 'as_wishlist_entries';

    /** Places a farmer climbs for every friend who answers through their link. */
    public const CLIMB = 3;

    /** key => [Tagalog, English, a picture] for every question with choices. */
    public const CHOICES = [
        'role' => [
            'own' => ['May sariling lupa at ako ang nagsasaka', 'I own the land and farm it myself', '🧑‍🌾'],
            'tenant' => ['Umuupa o kasama sa hatian', 'I rent, or farm on a share', '🤝'],
            'caretaker' => ['Katiwala o manager ng bukid ng iba', 'I look after someone else\'s farm', '🗝️'],
            'away' => ['May-ari pero malayo sa bukid (OFW, nasa siyudad)', 'I own a farm but live away from it', '✈️'],
            'tech' => ['Technician, agri student o nagtuturo', 'Technician, agri student or teacher', '🎓'],
            'other' => ['Iba pa', 'Something else', '✳️'],
        ],
        'crops' => [
            'rice' => ['Palay', 'Rice', '🌾'], 'corn' => ['Mais', 'Corn', '🌽'], 'veg' => ['Gulay', 'Vegetables', '🥬'],
            'fruit' => ['Prutas', 'Fruit', '🥭'], 'banana' => ['Saging', 'Banana', '🍌'], 'coconut' => ['Niyog', 'Coconut', '🥥'],
            'cane' => ['Tubo', 'Sugarcane', '🎋'], 'root' => ['Kamote, gabi at iba pa', 'Root crops', '🍠'],
            'coffee' => ['Kape o kakaw', 'Coffee or cacao', '☕'], 'other' => ['Iba pa', 'Something else', '🌱'],
        ],
        'seasons' => [
            '1' => ['Isang beses', 'Once', '1'], '2' => ['Dalawang beses', 'Twice', '2'], '3' => ['Tatlo o higit pa', 'Three or more', '3+'],
            'year' => ['Buong taon (puno o tanim na matagal)', 'All year (trees, long crops)', '🌳'],
        ],
        'farmSize' => [
            'lt1' => ['Kulang sa 1 ektarya', 'Under 1 hectare', '·'], '1to3' => ['1 hanggang 3 ektarya', '1 to 3 hectares', '··'],
            '3to5' => ['3 hanggang 5 ektarya', '3 to 5 hectares', '···'], '5to10' => ['5 hanggang 10 ektarya', '5 to 10 hectares', '····'],
            '10plus' => ['Higit 10 ektarya', 'Over 10 hectares', '·····'],
        ],
        'lots' => [
            '1' => ['Isa', 'One', '1'], '2to3' => ['Dalawa o tatlo', 'Two or three', '2'], '4to10' => ['4 hanggang 10', '4 to 10', '4'], '10plus' => ['Higit 10', 'Over 10', '10'],
        ],
        'helpers' => [
            'me' => ['Ako lang', 'Just me', '🙋'], 'family' => ['Pamilya ko', 'My family', '👨‍👩‍👧'], 'w1to3' => ['1 hanggang 3 trabahador', '1 to 3 workers', '👷'],
            'w4to10' => ['4 hanggang 10 trabahador', '4 to 10 workers', '👷‍♂️'], 'w10plus' => ['Higit 10 trabahador', 'Over 10 workers', '🚜'],
        ],
        'pains' => [
            'pests' => ['Peste at sakit ng tanim', 'Pests and diseases', '🐛'],
            'fert' => ['Abono: alin, gaano karami, kailan', 'Fertilizer: which, how much, when', '🧪'],
            'water' => ['Tubig at patubig', 'Water and irrigation', '💧'],
            'weather' => ['Panahon, bagyo at tagtuyot', 'Weather, storms and drought', '⛈️'],
            'money' => ['Puhunan at utang', 'Capital and debt', '💸'],
            'price' => ['Mababang presyo pagbenta', 'Low prices when I sell', '📉'],
            'labor' => ['Trabahador at sahuran', 'Workers and wages', '👥'],
            'records' => ['Hindi ko alam kung kumita talaga ako', 'I never know if I really made money', '📒'],
            'yield' => ['Mababa ang ani', 'Low harvests', '🌾'],
            'knowhow' => ['Walang mapagtanungan na eksperto', 'No expert to ask', '❓'],
        ],
        'wishes' => [
            'remind' => ['Paalala kung kailan mag-abono, mag-spray at magdilig', 'Reminders for when to fertilize, spray and water', '⏰'],
            'ask' => ['Masagot ang tanong ko kahit hatinggabi', 'Answers to my questions, even at midnight', '💬'],
            'photo' => ['Kunan ng litrato ang sakit at malaman agad kung ano', 'Take a photo of a sick plant and know at once', '📸'],
            'plan' => ['Buong plano ng season para sa mismong lote ko', 'A whole season plan for my own lot', '🗓️'],
            'costs' => ['Tala ng gastos at kita, alam ko kung kumita', 'Costs and income written down, so I know if I made money', '🧾'],
            'weather' => ['Panahon para sa mismong lote ko', 'Weather for my own lot', '🌦️'],
            'prices' => ['Presyo sa merkado at kung kailan magbenta', 'Market prices and when to sell', '🏷️'],
            'workers' => ['Makita ang trabaho ng tauhan kahit wala ako', 'See my workers\' work even when I am away', '👀'],
            'seeds' => ['Anong binhi ang bagay sa lupa ko', 'Which seed suits my land', '🌱'],
        ],
        'yieldUnit' => [
            'cavan' => ['kaban kada ektarya', 'cavans per hectare', ''], 'ton' => ['tonelada kada ektarya', 'tons per hectare', ''],
            'unknown' => ['Hindi ko alam', 'I do not know', ''],
        ],
        'phoneType' => [
            'android' => ['Android', 'Android', '📱'], 'iphone' => ['iPhone', 'iPhone', '📱'],
            'basic' => ['Keypad lang (walang internet)', 'A keypad phone (no internet)', '☎️'], 'shared' => ['Gamit ng anak o kamag-anak', 'A child\'s or relative\'s phone', '👪'],
        ],
        'signal' => [
            'good' => ['Malakas, kahit sa bukid', 'Strong, even in the field', '📶'], 'weak' => ['Mahina o paputol-putol', 'Weak or on and off', '📶'],
            'none' => ['Wala sa bukid, sa bahay lang', 'None in the field, only at home', '🏠'],
        ],
        'payWay' => [
            'season' => ['Kada season o taniman', 'Once a season', '🌾'], 'harvest' => ['Pagkatapos ng ani', 'After the harvest', '🧺'],
            'month' => ['Buwan-buwan', 'Every month', '📅'], 'use' => ['Kada gamit, parang load', 'Each time I use it, like prepaid load', '📲'],
            'free' => ['Libre lang ang kaya ko ngayon', 'Only free for now', '🙏'],
        ],
        'payAmount' => [
            '0' => ['Wala muna', 'Nothing yet', '₱0'], '200' => ['₱200', '₱200', '200'], '500' => ['₱500', '₱500', '500'],
            '1000' => ['₱1,000', '₱1,000', '1K'], '2000' => ['₱2,000 o higit', '₱2,000 or more', '2K+'],
        ],
        'heardFrom' => [
            'fbgroup' => ['Facebook group', 'A Facebook group', '👥'], 'fbad' => ['Facebook post o ad', 'A Facebook post or ad', '📣'],
            'friend' => ['Kaibigan o kapitbahay', 'A friend or neighbour', '🗣️'], 'store' => ['Agri store o kooperatiba', 'An agri store or co-op', '🏪'],
            'tech' => ['Technician', 'A technician', '🧑‍🔬'], 'search' => ['Google o ChatGPT', 'Google or ChatGPT', '🔎'], 'other' => ['Iba pa', 'Somewhere else', '✳️'],
        ],
        'contactVia' => [
            'text' => ['Text', 'Text message', '💬'], 'call' => ['Tawag', 'A call', '📞'], 'viber' => ['Viber', 'Viber', '🟣'],
            'messenger' => ['Messenger', 'Messenger', '🔵'], 'whatsapp' => ['WhatsApp', 'WhatsApp', '🟢'], 'email' => ['Email', 'Email', '✉️'],
        ],
        'contactTime' => [
            'morning' => ['Umaga', 'Morning', '🌅'], 'noon' => ['Tanghali', 'Midday', '☀️'], 'afternoon' => ['Hapon', 'Afternoon', '🌤️'], 'evening' => ['Gabi', 'Evening', '🌙'],
        ],
    ];

    /** Three things to do this week, for what hurts most. In our own words. */
    private const TIPS = [
        'pests' => ['Maglakad sa lote dalawang beses isang linggo at silipin ang ilalim ng dahon. Mas mura pigilan ang peste kapag maaga mong nakita.',
            'Walk your lot twice a week and look under the leaves. A pest seen early is far cheaper to stop.'],
        'fert' => ['Huwag ibigay lahat ng nitrogen sa isang bigayan. Hatiin sa dalawa o tatlo, ayon sa yugto ng tanim, para hindi masayang.',
            'Do not give all the nitrogen at once. Split it into two or three, by the crop\'s stage, so less is wasted.'],
        'water' => ['Tingnan ang tubig sa lote bago mag-abono. Ang abono sa tuyong lupa o sa umaapaw na tubig ay madaling masayang.',
            'Check the water in the lot before you fertilize. Fertilizer on dry soil or in overflowing water is easily lost.'],
        'weather' => ['Bago mag-spray o mag-abono, silipin ang ulan sa susunod na dalawang araw. Sayang ang spray na naabutan ng ulan.',
            'Before you spray or fertilize, check the rain for the next two days. Spray caught by rain is money gone.'],
        'money' => ['Isulat ang bawat gastos kahit maliit, kasama ang trabaho ng pamilya. Doon mo makikita kung saan napupunta ang puhunan.',
            'Write down every cost, even small ones, family work included. That shows where the capital really goes.'],
        'price' => ['Magtanong sa dalawa o tatlong buyer bago magbenta. Madalas mas mataas ang presyo ng tuyong ani kaysa basa.',
            'Ask two or three buyers before you sell. Dry harvest often gets a better price than wet.'],
        'labor' => ['Isulat kung sino ang nagtrabaho at ilang araw, sa mismong araw. Mas madali ang sahuran at walang away sa bilang.',
            'Write down who worked and how many days, on the day itself. Pay day gets easier and nobody argues the count.'],
        'records' => ['Kunan ng litrato ang lote kada linggo, sa parehong puwesto. Sa susunod na season, makikita mo kung kailan nagsimula ang problema.',
            'Photograph the lot every week from the same spot. Next season you will see when the trouble began.'],
        'yield' => ['Itala ang ani kada lote. Ang loteng laging mababa ang ani ay may sinasabi: lupa, tubig o binhi.',
            'Write down the harvest of each lot. A lot that always yields less is telling you something: soil, water or seed.'],
        'knowhow' => ['Isulat ang tanong mo sa oras na maisip mo, kasama ang litrato at petsa. Mas mabilis masagot ang tanong na may larawan.',
            'Write your question down the moment you think of it, with a photo and the date. A question with a picture is answered faster.'],
    ];

    /* ------------------------------------------------------------------ the page */

    public static function count(): int
    {
        try {
            return (int) DB::table(self::TABLE)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function spotsLeft(): int
    {
        return max(0, (int) config('wishlist.limit', 50) - self::count());
    }

    /** What the page needs to draw itself: every choice in both languages. */
    public static function forPage(): array
    {
        $out = [];
        foreach (self::CHOICES as $q => $opts) {
            foreach ($opts as $key => [$tl, $en, $pic]) {
                $out[$q][] = ['k' => (string) $key, 'tl' => $tl, 'en' => $en, 'pic' => $pic];
            }
        }

        return $out;
    }

    /* ------------------------------------------------------------------ saving */

    /** +639XXXXXXXXX from what was typed, or null. */
    public static function phone(?string $raw): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $raw);
        if (preg_match('/^(?:63|0)?(9\d{9})$/', $d, $m)) {
            return '+63' . $m[1];
        }

        return null;
    }

    /**
     * Keep one farmer's answers. The same phone or email again updates their row and keeps their place.
     * Returns what the last screen shows.
     */
    public static function save(array $in, Request $request): array
    {
        $one = fn (string $q, $v) => is_string($v) && isset(self::CHOICES[$q][$v]) ? $v : null;
        $many = fn (string $q, $v, int $max = 12) => array_values(array_slice(array_unique(array_filter((array) $v, fn ($x) => is_string($x) && isset(self::CHOICES[$q][$x]))), 0, $max));
        $text = fn ($v, int $n) => ($t = trim(Str::limit(preg_replace('/\s+/u', ' ', strip_tags((string) $v)), $n, ''))) !== '' ? $t : null;
        $places = self::places();
        $province = is_string($in['province'] ?? null) && isset($places[$in['province']]) ? $in['province'] : null;
        $town = $province && is_string($in['town'] ?? null) && in_array($in['town'], $places[$province], true) ? $in['town'] : null;
        $phone = self::phone($in['phone'] ?? null);
        $email = filter_var(trim((string) ($in['email'] ?? '')), FILTER_VALIDATE_EMAIL) ? strtolower(trim((string) $in['email'])) : null;
        $utm = [];
        foreach ((array) ($in['utm'] ?? []) as $k => $v) {
            if (in_array($k, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid'], true) && is_string($v)) {
                $utm[$k] = Str::limit($v, 80, '');
            }
        }
        $ua = (string) $request->userAgent();
        $row = [
            'firstName' => $text($in['firstName'] ?? '', 60) ?? 'Farmer',
            'lastName' => $text($in['lastName'] ?? '', 60),
            'phone' => $phone,
            'email' => $email,
            'contactVia' => $one('contactVia', $in['contactVia'] ?? null),
            'contactTime' => $one('contactTime', $in['contactTime'] ?? null),
            'role' => $one('role', $in['role'] ?? null),
            'province' => $province,
            'town' => $town,
            'crops' => json_encode($many('crops', $in['crops'] ?? [])),
            'seasons' => $one('seasons', $in['seasons'] ?? null),
            'farmSize' => $one('farmSize', $in['farmSize'] ?? null),
            'lots' => $one('lots', $in['lots'] ?? null),
            'helpers' => $one('helpers', $in['helpers'] ?? null),
            'pains' => json_encode($many('pains', $in['pains'] ?? [], 3)),
            'yieldNum' => is_numeric($in['yieldNum'] ?? null) && (float) $in['yieldNum'] > 0 && (float) $in['yieldNum'] < 1000 ? (string) round((float) $in['yieldNum'], 1) : null,
            'yieldUnit' => $one('yieldUnit', $in['yieldUnit'] ?? null),
            'wishes' => json_encode($many('wishes', $in['wishes'] ?? [])),
            'wishText' => $text($in['wishText'] ?? '', 600),
            'phoneType' => $one('phoneType', $in['phoneType'] ?? null),
            'signal' => $one('signal', $in['signal'] ?? null),
            'payWay' => $one('payWay', $in['payWay'] ?? null),
            'payAmount' => $one('payAmount', $in['payAmount'] ?? null),
            'heardFrom' => $one('heardFrom', $in['heardFrom'] ?? null),
            'lang' => ($in['lang'] ?? 'tl') === 'en' ? 'en' : 'tl',
            'utm' => $utm ? json_encode($utm) : null,
            'device' => preg_match('/iPad|Tablet|Android(?!.*Mobile)/i', $ua) ? 'Tablet' : (preg_match('/Mobi|iPhone|Android/i', $ua) ? 'Phone' : 'Computer'),
            'updated_at' => now(),
        ];

        $have = ($phone || $email) ? DB::table(self::TABLE)
            ->where(fn ($q) => $q->when($phone, fn ($w) => $w->orWhere('phone', $phone))->when($email, fn ($w) => $w->orWhere('email', $email)))
            ->orderBy('id')->first() : null;
        if ($have) {
            // Answers left empty this time keep what they said before.
            $keep = array_filter($row, fn ($v) => $v !== null && $v !== '[]');
            DB::table(self::TABLE)->where('id', $have->id)->update($keep + ['answers' => (int) $have->answers + 1]);
            $id = (int) $have->id;
            $code = $have->refCode;
            $again = true;
        } else {
            $by = is_string($in['ref'] ?? null) && preg_match('/^[A-Z0-9]{4,12}$/', $in['ref']) ? $in['ref'] : null;
            if ($by && ! DB::table(self::TABLE)->where('refCode', $by)->exists()) {
                $by = null;
            }
            do {
                $code = strtoupper(Str::random(6));
                $code = preg_replace('/[^A-Z0-9]/', 'X', strtr($code, ['0' => '8', 'O' => 'Q', 'I' => '7', 'L' => '4']));
            } while (DB::table(self::TABLE)->where('refCode', $code)->exists());
            $id = (int) DB::table(self::TABLE)->insertGetId($row + ['refCode' => $code, 'referredBy' => $by, 'status' => 'new', 'answers' => 1, 'created_at' => now()]);
            $again = false;
            self::ring($row, $id);
        }

        return [
            'again' => $again,
            'position' => self::position($id),
            'code' => $code,
            'friends' => (int) DB::table(self::TABLE)->where('referredBy', $code)->count(),
            'spots' => self::spotsLeft(),
            'tips' => self::tips(json_decode($row['pains'], true) ?: [], $row['lang']),
        ];
    }

    /** Their place in line: first come first served, three places up for each friend who answered through their link. */
    public static function position(int $id): int
    {
        $rows = DB::table(self::TABLE)->get(['id', 'refCode']);
        $friends = DB::table(self::TABLE)->whereNotNull('referredBy')->selectRaw('referredBy, COUNT(*) n')->groupBy('referredBy')->pluck('n', 'referredBy');
        $score = [];
        foreach ($rows as $r) {
            $score[(int) $r->id] = (int) $r->id - self::CLIMB * (int) ($friends[$r->refCode] ?? 0);
        }
        $mine = $score[$id] ?? $id;

        return 1 + count(array_filter($score, fn ($s, $k) => $s < $mine || ($s === $mine && $k < $id), ARRAY_FILTER_USE_BOTH));
    }

    /** Three things to do this week, for what hurts most (records, weather and pests when they named fewer). */
    public static function tips(array $pains, string $lang): array
    {
        $keys = array_values(array_unique(array_merge($pains, ['records', 'weather', 'pests'])));
        $i = $lang === 'en' ? 1 : 0;

        return array_map(fn ($k) => ['k' => $k, 'pic' => self::CHOICES['pains'][$k][2] ?? '🌱', 'say' => self::TIPS[$k][$i]], array_slice($keys, 0, 3));
    }

    /** province => towns, from the same list the rest of the site reads. */
    public static function places(): array
    {
        static $p = null;
        if ($p === null) {
            $p = json_decode((string) @file_get_contents(public_path('data/ph-locations.json')), true) ?: [];
        }

        return $p;
    }

    /** A bell for each staff member, so a new farmer is seen the same hour. */
    private static function ring(array $row, int $id): void
    {
        try {
            $staff = DB::table('anisystem_users')->where(fn ($q) => $q->where('panelAdmin', 1)->orWhere(fn ($w) => $w->whereNotNull('adminUserId')->where('adminUserId', '!=', 0)))->pluck('id');
            $crop = collect(json_decode($row['crops'], true) ?: [])->map(fn ($c) => self::CHOICES['crops'][$c][1] ?? $c)->implode(', ');
            foreach ($staff as $uid) {
                \App\Models\AnisystemNotification::create([
                    'userId' => (int) $uid,
                    'type' => 'wishlist',
                    'title' => 'New on the wishlist: ' . $row['firstName'] . ($row['town'] ? ' of ' . $row['town'] : ''),
                    'body' => trim(($crop ? $crop . '. ' : '') . ($row['wishText'] ? '"' . Str::limit($row['wishText'], 120) . '"' : '')),
                    'url' => '/admin/wishlist?entry=' . $id,
                    'deleteStatus' => 1,
                ]);
            }
        } catch (\Throwable $e) {
            // A missed bell; the row is kept.
        }
    }

    /* ------------------------------------------------------------------ the admin panel */

    /** Words for a key, in English (the panel's language). */
    public static function say(string $q, ?string $key): ?string
    {
        return $key !== null && isset(self::CHOICES[$q][$key]) ? self::CHOICES[$q][$key][1] : $key;
    }

    /** Every entry, newest first, said in words, with their place in line and how many friends they brought. */
    public static function entries(): array
    {
        $rows = DB::table(self::TABLE)->orderByDesc('id')->get();
        $friends = DB::table(self::TABLE)->whereNotNull('referredBy')->selectRaw('referredBy, COUNT(*) n')->groupBy('referredBy')->pluck('n', 'referredBy');
        $score = [];
        foreach ($rows as $r) {
            $score[(int) $r->id] = (int) $r->id - self::CLIMB * (int) ($friends[$r->refCode] ?? 0);
        }
        asort($score);
        $place = array_flip(array_keys($score));
        $byCode = $rows->pluck('firstName', 'refCode');

        return $rows->map(function ($r) use ($place, $friends, $byCode) {
            $list = fn ($q, $json) => array_values(array_map(fn ($k) => self::say($q, $k), json_decode((string) $json, true) ?: []));

            return [
                'id' => (int) $r->id,
                'place' => ($place[(int) $r->id] ?? 0) + 1,
                'name' => trim($r->firstName . ' ' . $r->lastName),
                'phone' => $r->phone, 'email' => $r->email,
                'contactVia' => self::say('contactVia', $r->contactVia), 'contactTime' => self::say('contactTime', $r->contactTime),
                'role' => self::say('role', $r->role), 'roleKey' => $r->role,
                'where' => trim(($r->town ? $r->town . ', ' : '') . ($r->province ?? ''), ', '),
                'province' => $r->province,
                'crops' => $list('crops', $r->crops), 'seasons' => self::say('seasons', $r->seasons),
                'farmSize' => self::say('farmSize', $r->farmSize), 'lots' => self::say('lots', $r->lots), 'helpers' => self::say('helpers', $r->helpers),
                'pains' => $list('pains', $r->pains), 'wishes' => $list('wishes', $r->wishes), 'wishText' => $r->wishText,
                'yield' => $r->yieldNum ? $r->yieldNum . ' ' . self::say('yieldUnit', $r->yieldUnit) : ($r->yieldUnit === 'unknown' ? 'Does not know' : null),
                'phoneType' => self::say('phoneType', $r->phoneType), 'signal' => self::say('signal', $r->signal),
                'payWay' => self::say('payWay', $r->payWay), 'payAmount' => self::say('payAmount', $r->payAmount),
                'heardFrom' => self::say('heardFrom', $r->heardFrom),
                'friends' => (int) ($friends[$r->refCode] ?? 0),
                'referredBy' => $r->referredBy ? ($byCode[$r->referredBy] ?? $r->referredBy) : null,
                'utm' => json_decode((string) $r->utm, true) ?: null,
                'lang' => $r->lang === 'en' ? 'English' : 'Tagalog', 'device' => $r->device,
                'status' => $r->status, 'note' => $r->adminNote, 'answers' => (int) $r->answers,
                'at' => \Carbon\Carbon::parse($r->created_at)->format('M j, g:i A'),
                'ago' => \Carbon\Carbon::parse($r->created_at)->diffForHumans(),
            ];
        })->values()->all();
    }

    /** How the answers add up: for each question, each choice by how many farmers picked it. */
    public static function totals(array $entries): array
    {
        $n = max(1, count($entries));
        $count = function (string $field) use ($entries, $n) {
            $c = [];
            foreach ($entries as $e) {
                foreach ((array) $e[$field] as $v) {
                    if ($v !== null && $v !== '') {
                        $c[$v] = ($c[$v] ?? 0) + 1;
                    }
                }
            }
            arsort($c);

            return array_map(fn ($k, $v) => ['label' => $k, 'n' => $v, 'share' => round($v / $n * 100)], array_keys($c), $c);
        };

        return [
            'pains' => $count('pains'), 'wishes' => $count('wishes'), 'crops' => $count('crops'), 'role' => $count('role'),
            'payWay' => $count('payWay'), 'payAmount' => $count('payAmount'), 'heardFrom' => $count('heardFrom'),
            'province' => array_slice($count('province'), 0, 10), 'farmSize' => $count('farmSize'), 'signal' => $count('signal'),
            'phoneType' => $count('phoneType'),
        ];
    }
}
