<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use Illuminate\Support\Facades\DB;

/**
 * Sample emails for the owner to open (2026-10-17): one per kind of email,
 * each showing what the builder can do, written the way John writes (Voice),
 * one column from top to bottom. All drafts, so the line never sends them;
 * each carries meta.demo and is made once. run(refresh: true) writes the
 * samples' words again over the ones already made (their status stays).
 */
final class Demo
{
    private const IMG = 'https://anee.io/images/site/';

    public static function run(string $by = 'Demo', bool $refresh = false): array
    {
        $made = [];
        foreach (self::emails() as $key => $e) {
            $have = AsNlEmail::where('meta', 'like', '%"demo":"' . $key . '"%')->where('deleteStatus', 1)->first();
            $fields = [
                'title' => $e['title'], 'subject' => $e['subject'], 'preheader' => $e['preheader'],
                'blocks' => Renderer::clean($e['content']), 'audience' => $e['audience'],
                'sendOn' => $e['sendOn'] ?? null, 'sendAt' => $e['sendAt'] ?? null, 'note' => $e['note'], 'updatedBy' => $by,
            ];
            if ($have) {
                if ($refresh) {
                    $have->update($fields);
                    $made[] = $e['title'] . ' (rewritten)';
                }

                continue;
            }
            AsNlEmail::create($fields + [
                'priority' => $e['priority'], 'position' => $e['position'] ?? -10, 'kind' => 'builder',
                'status' => 'draft', 'meta' => ['demo' => $key], 'createdBy' => $by, 'deleteStatus' => 1,
            ]);
            $made[] = $e['title'];
        }
        // A news sample from the latest news page.
        $news = AsNlEmail::where('meta', 'like', '%"demo":"news"%')->where('deleteStatus', 1)->first();
        if (! $news || $refresh) {
            $page = DB::table('as_site_pages')->where('kind', 'roundup')->where('status', 'published')->where('deleteStatus', 1)->orderByDesc('id')->first();
            if ($page) {
                $n = NewsEmail::fromPage($page, Planner::now(), 'draft');
                $note = 'A sample of the email made each morning from the farm news page. The real one joins the line by itself and is gone at the end of its day.';
                if ($news) {
                    $news->update(['title' => 'Demo: ' . $n->title, 'subject' => $n->subject, 'preheader' => $n->preheader, 'blocks' => $n->blocks, 'note' => $note, 'updatedBy' => $by]);
                    $n->delete();
                    $made[] = $news->title . ' (rewritten)';
                } else {
                    // Not tied to the page: the real news email of the day is made from it too.
                    $n->update(['title' => 'Demo: ' . $n->title, 'meta' => ['demo' => 'news'], 'createdBy' => $by, 'newsPageId' => null, 'note' => $note]);
                    $made[] = $n->title;
                }
            }
        }

        return $made;
    }

    private static function emails(): array
    {
        $later = Planner::now()->addDays(3)->setTime(18, 0)->toDateTimeString();
        $sign = fn (string $tag) => ['type' => 'text', 'html' => '<p>' . Voice::signOffHtml($tag) . '</p>'];

        return [
            'feature' => [
                'priority' => 'high', 'position' => -30,
                'title' => 'Demo: Global Lots is here',
                'subject' => 'Isang beses mo na lang sasagutin ito',
                'preheader' => 'Ang lupa mo, hindi mo na kailangang ikwento ulit sa akin.',
                'audience' => [Seg::ALL],
                'note' => 'A sample high priority email: a new feature, in Anee\'s voice, with a photo on top, a checklist, a tip and a button. High priority goes on the next daily send ahead of everything.',
                'content' => [
                    'kicker' => 'Bago sa anee.io',
                    'headline' => 'Kilala ko na ang bawat lote mo',
                    'face' => '',
                    'hero' => ['src' => self::IMG . 'fields-aerial.jpg', 'alt' => 'Mga palayan mula sa itaas', 'link' => 'https://anee.io/'],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Hey there, Anee here...</p><p>May reply kaming natanggap noong isang linggo na medyo tumama sa akin. Sabi niya, "Bakit tuwing magtatanong ako, tinatanong mo ulit kung maalat ba ang lupa ko? Sinabi ko na \'yan eh."</p><p>Tama siya. (Medyo nakakahiya, aaminin ko.) Kaya inayos namin.</p>'],
                        ['type' => 'heading', 'text' => 'Ang bago: Global Lots', 'size' => 'md'],
                        ['type' => 'text', 'html' => '<p>Ngayon, may sariling talaan ang bawat lote mo sa anee.io. Isang beses mo lang sasagutin ang tungkol sa tubig, sa alat at sa baha. Pagkatapos noon, tanda ko na.</p><p>Kapag pinili mo ang lote sa When to Plant o What to Plant, diretso na tayo sa plano. Wala nang paulit ulit na tanong.</p>'],
                        ['type' => 'list', 'style' => 'check', 'items' => [
                            'Ang <b>pin sa mapa</b> at ang boundary ng bawat lote',
                            'Ang sagot tungkol sa lupa at tubig, isang beses lang',
                            'Ang soil test mo, kahit luma na, may petsa',
                        ]],
                        ['type' => 'anee', 'face' => 'idea', 'html' => '<p>Unahin mo ang lote na pinaka madalas mong taniman. Limang minuto lang ito, pero ilang season mo itong pakikinabangan.</p>'],
                        ['type' => 'button', 'label' => 'Buksan ang anee.io', 'url' => 'https://anee.io/', 'align' => 'center', 'style' => 'solid'],
                        $sign('isang beses lang, promise'),
                        ['type' => 'text', 'size' => 'sm', 'html' => '<p><b>P.S.</b> Kung hindi mo pa nata-try ang anee.io, libre lang mag-umpisa: <a href="https://anee.io/signup">anee.io/signup</a></p>'],
                    ],
                ],
            ],
            'promo' => [
                'priority' => 'high', 'position' => -20,
                'title' => 'Demo: Libre + Anee weekend (Libre only, exact time)',
                'subject' => 'Isang seryosong tanong para sa\'yo na nasa Libre',
                'preheader' => 'Hindi ito sales talk. Pangako.',
                'audience' => [Seg::FREE],
                'sendAt' => $later,
                'note' => 'A sample high priority email for the Libre list only, set to an exact time: it would go at that time as its own campaign, beside the day\'s email. Everyone else still gets the regular line that day.',
                'content' => [
                    'kicker' => '',
                    'headline' => '',
                    'face' => '',
                    'hero' => ['src' => '', 'alt' => '', 'link' => ''],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Kumusta, kaibigan?</p><p>Diretsahan na tayo, kasi alam kong mas gusto mo yun.</p><p>Ang Libre + Anee ay ₱70 isang buwan. Alam kong hindi maliit na pera \'yan sa bukid, lalo na kung kakatapos lang ng anihan at may bayarin pa sa agrivet.</p><p>Pero eto ang tanong ko: ilang beses ka nang nag-spray dahil hindi ka sigurado kung ano talaga ang problema ng tanim? (Walang huhusga. Madalas itong ikwento ng mga nagre-reply sa amin.)</p><p>Halimbawa lang ito, gawa gawang numero: kung ₱450 ang isang bote ng gamot, isang maling spray ay mas mahal na kaysa anim na buwan ng Libre + Anee.</p>'],
                        ['type' => 'divider', 'style' => 'line'],
                        ['type' => 'story', 'src' => self::IMG . 'photos/palay-phone.jpg', 'title' => 'Isang plano para sa buong season', 'html' => '<p>Mula punla hanggang ani, may petsa ang bawat gawain. Hindi mo na kailangang tandaan lahat.</p>', 'url' => 'https://anee.io/pricing', 'label' => 'Tingnan ang Libre + Anee'],
                        ['type' => 'divider', 'style' => 'line'],
                        ['type' => 'story', 'src' => self::IMG . 'photos/inspect.jpg', 'title' => 'Picture lang ng dahon', 'html' => '<p>Ipakita mo sa akin ang dahon. Sasabihin ko kung peste, sakit, o kulang lang sa pagkain ang tanim, at ano ang susunod na hakbang.</p>', 'url' => 'https://anee.io/pricing', 'label' => 'Tingnan ang Libre + Anee'],
                        ['type' => 'text', 'html' => '<p>Walang credit card, GCash lang. At kung hindi mo sulit, huwag mo nang ituloy. Ganun lang kasimple.</p>'],
                        $sign('isang spray lang ang usapan'),
                    ],
                ],
            ],
            'tips' => [
                'priority' => 'regular', 'position' => -10,
                'title' => 'Demo: Tatlong tanong bago mag abono',
                'subject' => 'Tatlong tanong bago ka bumili ng abono',
                'preheader' => 'Bago ka pumunta sa agrivet, sagutin mo muna ito.',
                'audience' => [Seg::ALL],
                'note' => 'A sample regular email in Anee\'s voice: a story, a picture, numbered steps, a saying, a video and the social links, one column. Regular emails go one a day in the order you drag them.',
                'content' => [
                    'kicker' => '',
                    'headline' => '',
                    'face' => '',
                    'hero' => ['src' => '', 'alt' => '', 'link' => ''],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Isipin natin si Pareng Ben. (Halimbawa lang siya, pero baka kilala mo na siya.)</p><p>Bumili siya ng sampung sako ng abono noong isang season. "Ganun daw ang tama," sabi ng kapitbahay niya. Ang ani niya? Halos pareho lang noong nakaraan.</p><p><b>Hindi kulang ang abono niya. Kulang ang tanong.</b></p>'],
                        ['type' => 'image', 'src' => self::IMG . 'photos/sacks-shed.jpg', 'alt' => 'Mga sako sa kamalig', 'caption' => 'Ang pinakamahal na sako ay yung hindi mo naman kailangan.', 'width' => 'full'],
                        ['type' => 'text', 'html' => '<p>Bago ka pumunta sa agrivet, subukan mo munang sagutin ang tatlong ito:</p>'],
                        ['type' => 'list', 'style' => 'number', 'items' => [
                            '<b>Ano ang kulang sa lupa ko?</b> Hindi sa lupa ng kapitbahay. Iba ang lote mo.',
                            '<b>Kailan ito kailangan ng tanim?</b> Ang tamang abono sa maling araw, sayang lang.',
                            '<b>Magkano ang balik?</b> Kwentahin per kilo ng ani, hindi per sako.',
                        ]],
                        ['type' => 'quote', 'html' => 'Ang magaling na magsasaka, hindi ang pinakamaraming binili. Ang pinaka tamang binili.', 'by' => 'Isang kasabihan sa bukid'],
                        ['type' => 'video', 'url' => 'https://anee.io/how-it-works', 'thumb' => 'https://anee.io/images/anee-song-poster.jpg', 'label' => 'Panoorin: paano ako tumutulong', 'caption' => 'Dalawang minuto lang.'],
                        ['type' => 'text', 'html' => '<p>Kung gusto mo ng kwenta, may libreng NPK Plus sa anee.io: ilagay mo lang ang tanim at ang laki ng lote. Pero kahit papel at ballpen lang, basta sagutin mo muna ang tatlong tanong.</p><p>Isang tanong bago ako umalis: ano ang huling abono na binili mo nang hindi mo alam kung bakit? I-reply mo. Walang tatawa.</p>'],
                        $sign('tanong muna bago sako'),
                        ['type' => 'divider', 'style' => 'leaf'],
                        ['type' => 'social', 'title' => 'Sumama sa amin'],
                    ],
                ],
            ],
            'holiday' => [
                'priority' => 'regular', 'position' => -5,
                'title' => 'Demo: Undas greeting (bound to a day)',
                'subject' => 'Ngayong Undas, salamat sa mga nauna sa atin sa bukid',
                'preheader' => 'Ang pilapil na nilalakaran mo, may gumawa.',
                'audience' => [Seg::ALL],
                'sendOn' => '2026-11-01',
                'note' => 'A sample email bound to one day: it goes only on November 1, ahead of the regular line that day, and is gone if that day passes.',
                'content' => [
                    'kicker' => 'Undas',
                    'headline' => 'Salamat sa mga nauna sa atin',
                    'face' => 'calm',
                    'hero' => ['src' => self::IMG . 'hero-terraces.jpg', 'alt' => 'Hagdan hagdang palayan', 'link' => ''],
                    'blocks' => [
                        ['type' => 'text', 'align' => 'center', 'size' => 'lg', 'html' => '<p>Ang pilapil na nilalakaran mo, may gumawa.<br>Ang kanal na dinadaluyan ng tubig, may naghukay.</p>'],
                        ['type' => 'divider', 'style' => 'dots'],
                        ['type' => 'text', 'html' => '<p>Tuwing Undas, maraming nagre-reply sa amin tungkol sa lolo o lola nila sa bukid. Halos pare pareho ang kwento: hindi sila nag-aral ng agrikultura, pero alam nila kung kailan babaha, kung saan unang tumutubo ang damo, at kung aling sulok ng palayan ang laging mahina.</p><p>Wala silang notebook. Nasa ulo nila lahat. At nang mawala sila, nawala rin ang karamihan doon.</p><p>Kaya dalawa ang hiling ko sa\'yo ngayong Undas: alalahanin natin sila, at isulat natin ang alam natin. Kahit isang pahina lang. Para sa susunod na magtatanim sa lupang ito.</p>'],
                        $sign('para sa mga nauna'),
                        ['type' => 'text', 'size' => 'sm', 'html' => '<p><b>P.S.</b> Magpahinga ka rin. Ako muna ang magbabantay ng kalendaryo mo.</p>'],
                    ],
                ],
            ],
        ];
    }
}
