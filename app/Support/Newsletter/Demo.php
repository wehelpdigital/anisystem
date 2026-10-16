<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use Illuminate\Support\Facades\DB;

/**
 * Sample emails for the owner to open (2026-10-17): one per kind of email,
 * each showing what the builder can do, in John's voice. All drafts, so the
 * line never sends them; each carries meta.demo and is made once.
 */
final class Demo
{
    private const IMG = 'https://anee.io/images/site/';

    public static function run(string $by = 'Demo'): array
    {
        $made = [];
        foreach (self::emails() as $key => $e) {
            if (AsNlEmail::where('meta', 'like', '%"demo":"' . $key . '"%')->exists()) {
                continue;
            }
            $content = Renderer::clean($e['content']);
            AsNlEmail::create([
                'priority' => $e['priority'], 'position' => $e['position'] ?? -10, 'kind' => 'builder',
                'title' => $e['title'], 'subject' => $e['subject'], 'preheader' => $e['preheader'],
                'blocks' => $content, 'audience' => $e['audience'], 'sendOn' => $e['sendOn'] ?? null,
                'sendAt' => $e['sendAt'] ?? null, 'newsDate' => $e['newsDate'] ?? null,
                'status' => 'draft', 'note' => $e['note'], 'meta' => ['demo' => $key],
                'createdBy' => $by, 'deleteStatus' => 1,
            ]);
            $made[] = $e['title'];
        }
        // A news sample from the latest news page, if there is one and none was made.
        if (! AsNlEmail::where('meta', 'like', '%"demo":"news"%')->exists()) {
            $page = DB::table('as_site_pages')->where('kind', 'roundup')->where('status', 'published')->where('deleteStatus', 1)->orderByDesc('id')->first();
            if ($page) {
                $n = NewsEmail::fromPage($page, Planner::now(), 'draft');
                // Not tied to the page: the real news email of the day is made from it too.
                $n->update(['title' => 'Demo: ' . $n->title, 'meta' => ['demo' => 'news'], 'createdBy' => $by, 'newsPageId' => null,
                    'note' => 'A sample of the email made each morning from the farm news page. The real one joins the line by itself and is gone at the end of its day.']);
                $made[] = $n->title;
            }
        }

        return $made;
    }

    private static function emails(): array
    {
        $tomorrow = Planner::now()->addDays(3)->setTime(18, 0)->toDateTimeString();

        return [
            'feature' => [
                'priority' => 'high', 'position' => -30,
                'title' => 'Demo: Global Lots is here',
                'subject' => 'Bago sa anee.io: kilala na ni Anee ang bawat lote mo',
                'preheader' => 'Isang beses mo lang sasagutin ang tanong tungkol sa lupa. Si Anee na ang bahala sa iba.',
                'audience' => [Seg::ALL],
                'note' => 'A sample high priority email: a new feature, with a photo on top, Anee speaking, a checklist and a button. High priority goes on the next daily send ahead of everything.',
                'content' => [
                    'kicker' => 'Bago sa anee.io',
                    'headline' => 'Kilala na ni Anee ang bawat lote mo',
                    'face' => '',
                    'hero' => ['src' => self::IMG . 'fields-aerial.jpg', 'alt' => 'Mga palayan mula sa itaas', 'link' => 'https://anee.io/app/farm-lots'],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Magandang araw, kasama!</p><p>Napansin mo ba na sa bawat analysis, paulit ulit ang tanong tungkol sa lupa mo? Ang kulay ng tubig, kung may alat, kung bumabaha. Tapos na ang ganun.</p>'],
                        ['type' => 'heading', 'text' => 'Isang sagot, para sa lahat ng analysis', 'size' => 'md'],
                        ['type' => 'list', 'style' => 'check', 'items' => [
                            'I-save ang bawat lote mo, may <b>pin sa mapa</b> at <b>boundary</b>.',
                            'Sagutin ang tanong tungkol sa lupa at tubig <b>isang beses lang</b>.',
                            'Itala ang <b>soil test</b> mo, kahit luma na, may petsa.',
                        ]],
                        ['type' => 'anee', 'face' => 'happy', 'html' => '<p>Pag pinili mo ang lote sa When to Plant o What to Plant, alam ko na agad ang sagot mo. Diretso na tayo sa plano.</p>'],
                        ['type' => 'button', 'label' => 'Buksan ang Global Lots', 'url' => 'https://anee.io/app/farm-lots', 'align' => 'center', 'style' => 'solid'],
                        ['type' => 'callout', 'tone' => 'gold', 'title' => 'Tip', 'html' => '<p>Unahin ang lote na pinaka madalas mong itanim. Doon mo agad mararamdaman ang ginhawa.</p>'],
                    ],
                ],
            ],
            'promo' => [
                'priority' => 'high', 'position' => -20,
                'title' => 'Demo: Libre + Anee weekend (Libre only, exact time)',
                'subject' => 'Para lang sa Libre: subukan si Anee ngayong weekend',
                'preheader' => 'Isang tanong sa umaga, isang plano sa hapon.',
                'audience' => [Seg::FREE],
                'sendAt' => $tomorrow,
                'note' => 'A sample high priority email for the Libre list only, set to an exact time: it would go at that time as its own campaign, beside the day\'s email. Everyone else still gets the regular line that day.',
                'content' => [
                    'kicker' => 'Para sa mga nasa Libre',
                    'headline' => 'Si Anee, para sa season mo',
                    'face' => 'wave',
                    'hero' => ['src' => '', 'alt' => '', 'link' => ''],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Kasama, ilang season ka na sa Libre. Salamat at kasama ka namin.</p><p>Ngayong weekend, gusto kong ipakita sa\'yo kung ano ang kaya ni Anee kapag kasama mo siya sa buong season.</p>'],
                        ['type' => 'pair', 'items' => [
                            ['src' => self::IMG . 'photos/palay-phone.jpg', 'title' => 'Plano ng buong season', 'html' => 'Mula punla hanggang ani, may petsa ang bawat gawain.', 'url' => 'https://anee.io/how-it-works', 'label' => 'Tingnan'],
                            ['src' => self::IMG . 'photos/inspect.jpg', 'title' => 'Tanong sa peste', 'html' => 'Picture lang ng dahon, may sagot si Anee.', 'url' => 'https://anee.io/how-it-works', 'label' => 'Tingnan'],
                        ]],
                        ['type' => 'button', 'label' => 'Tingnan ang Libre + Anee', 'url' => 'https://anee.io/pricing', 'align' => 'center', 'style' => 'solid'],
                        ['type' => 'text', 'size' => 'sm', 'align' => 'center', 'html' => '<p>Walang credit card. GCash lang, at ikaw ang bahala kung kailan.</p>'],
                    ],
                ],
            ],
            'tips' => [
                'priority' => 'regular', 'position' => -10,
                'title' => 'Demo: Tatlong tanong bago mag abono',
                'subject' => 'Tatlong tanong bago ka bumili ng abono',
                'preheader' => 'Bago ka pumunta sa agrivet, sagutin mo muna ito.',
                'audience' => [Seg::ALL],
                'note' => 'A sample regular email made in the builder: a story, numbered steps, a quote, a video and the social links. Regular emails go one a day in the order you drag them.',
                'content' => [
                    'kicker' => 'Kwentong bukid',
                    'headline' => 'Tatlong tanong bago ka bumili ng abono',
                    'face' => 'thinking',
                    'hero' => ['src' => '', 'alt' => '', 'link' => ''],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Kasama, noong isang season, may kumpare akong bumili ng sampung sako ng abono dahil "ganun daw ang tama." Ang ani niya? Halos pareho lang noong nakaraan.</p><p>Hindi kulang ang abono niya. Kulang ang tanong.</p>'],
                        ['type' => 'image', 'src' => self::IMG . 'photos/sacks-shed.jpg', 'alt' => 'Mga sako sa kamalig', 'caption' => 'Ang pinakamahal na sako ay yung hindi kailangan.', 'width' => 'full'],
                        ['type' => 'heading', 'text' => 'Bago ka pumunta sa agrivet', 'size' => 'md'],
                        ['type' => 'list', 'style' => 'number', 'items' => [
                            '<b>Ano ang kulang sa lupa ko?</b> Hindi sa lupa ng kapitbahay.',
                            '<b>Kailan ito kailangan ng tanim?</b> Ang tamang abono sa maling araw ay sayang.',
                            '<b>Magkano ang balik?</b> Kwentahin per kilo ng ani, hindi per sako.',
                        ]],
                        ['type' => 'quote', 'html' => 'Ang magaling na magsasaka, hindi ang pinakamaraming binili. Ang pinaka tamang binili.', 'by' => 'Mang Tonyo, Nueva Ecija'],
                        ['type' => 'video', 'url' => 'https://anee.io/how-it-works', 'thumb' => 'https://anee.io/images/anee-song-poster.jpg', 'label' => 'Panoorin: paano tumutulong si Anee', 'caption' => 'Dalawang minuto lang.'],
                        ['type' => 'callout', 'tone' => 'leaf', 'title' => 'Subukan ang NPK Plus', 'html' => '<p>Nasa anee.io: ilagay ang tanim at laki ng lote, may computation ka na ng abono.</p>'],
                        ['type' => 'button', 'label' => 'Kwentahin ang abono ko', 'url' => 'https://anee.io/app/npk-plus', 'align' => 'left', 'style' => 'outline'],
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
                        ['type' => 'text', 'align' => 'center', 'size' => 'lg', 'html' => '<p>Ang pilapil na nilalakaran mo, may gumawa.<br>Ang patubig na dinadaluyan ng tubig mo, may naghukay.</p>'],
                        ['type' => 'text', 'align' => 'center', 'html' => '<p>Ngayong Undas, alalahanin natin sila. At ituloy natin ang bukid na iniwan nila sa atin.</p>'],
                        ['type' => 'divider', 'style' => 'dots'],
                        ['type' => 'anee', 'face' => 'flower', 'html' => '<p>Magpahinga ka rin, kasama. Babantayan ko muna ang kalendaryo mo.</p>'],
                    ],
                ],
            ],
        ];
    }
}
