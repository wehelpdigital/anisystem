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

        return [
            'feature' => [
                'priority' => 'high', 'position' => -30,
                'title' => 'Demo: Global Lots is here',
                'subject' => 'Isang beses mo na lang sasagutin ito',
                'preheader' => 'Ang lupa mo, hindi mo na kailangang ikwento ulit kay Anee.',
                'audience' => [Seg::ALL],
                'note' => 'A sample high priority email: a new feature, in John\'s words, with a photo on top, a checklist, Anee and a button. High priority goes on the next daily send ahead of everything.',
                'content' => [
                    'kicker' => 'Bago sa anee.io',
                    'headline' => 'Kilala na ni Anee ang bawat lote mo',
                    'face' => '',
                    'hero' => ['src' => self::IMG . 'fields-aerial.jpg', 'alt' => 'Mga palayan mula sa itaas', 'link' => 'https://anee.io/app/farm-lots'],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Kasama,</p><p>Noong isang linggo, may nagreklamo sa akin. Sabi niya, "John, bakit tuwing magtatanong ako kay Anee, tinatanong niya ulit kung maalat ba ang lupa ko? Sinabi ko na \'yan."</p><p>Tama siya. Kaya inayos namin.</p>'],
                        ['type' => 'divider', 'style' => 'dots'],
                        ['type' => 'heading', 'text' => 'Ang bago: Global Lots', 'size' => 'md'],
                        ['type' => 'text', 'html' => '<p>Ngayon, may sariling talaan ang bawat lote mo. Isang beses mo lang sasagutin ang tungkol sa tubig, sa alat at sa baha. Pagkatapos noon, tanda na ni Anee.</p>'],
                        ['type' => 'list', 'style' => 'check', 'items' => [
                            'Ang <b>pin sa mapa</b> at ang <b>boundary</b> ng bawat lote',
                            'Ang sagot tungkol sa lupa at tubig, <b>isang beses lang</b>',
                            'Ang <b>soil test</b> mo, kahit luma na, may petsa',
                        ]],
                        ['type' => 'anee', 'face' => 'happy', 'html' => '<p>Pag pinili mo ang lote sa When to Plant o What to Plant, diretso na tayo sa plano. Wala nang paulit ulit na tanong.</p>'],
                        ['type' => 'button', 'label' => 'Buksan ang Global Lots', 'url' => 'https://anee.io/app/farm-lots', 'align' => 'center', 'style' => 'solid'],
                        ['type' => 'callout', 'tone' => 'gold', 'title' => 'Isang maliit na gawain ngayong araw', 'html' => '<p>Unahin ang lote na pinaka madalas mong taniman. Limang minuto lang ito, pero ilang season mo itong pakikinabangan.</p>'],
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
                    'kicker' => 'Para sa mga nasa Libre',
                    'headline' => 'Ano ang mas mahal: ang ₱70, o ang isang maling spray?',
                    'face' => 'thinking',
                    'hero' => ['src' => '', 'alt' => '', 'link' => ''],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Kasama, diretsahan na tayo.</p><p>Ang Libre + Anee ay ₱70 isang buwan. Alam kong hindi maliit na pera \'yan sa bukid.</p><p>Pero tanong ko sa\'yo: ilang beses ka nang nag-spray dahil hindi ka sigurado kung ano talaga ang problema ng tanim? (Ako, maraming beses. Hindi ko na bibilangin.)</p><p>Isang maling spray, mas mahal pa sa isang buwan ni Anee.</p>'],
                        ['type' => 'divider', 'style' => 'line'],
                        ['type' => 'story', 'src' => self::IMG . 'photos/palay-phone.jpg', 'title' => 'Isang plano para sa buong season', 'html' => '<p>Mula punla hanggang ani, may petsa ang bawat gawain. Hindi mo na kailangang tandaan lahat.</p>', 'url' => 'https://anee.io/how-it-works', 'label' => 'Tingnan kung paano'],
                        ['type' => 'divider', 'style' => 'line'],
                        ['type' => 'story', 'src' => self::IMG . 'photos/inspect.jpg', 'title' => 'Picture lang ng dahon', 'html' => '<p>Ipakita mo kay Anee ang dahon. Sasabihin niya kung peste, sakit, o kulang lang sa pagkain ang tanim.</p>', 'url' => 'https://anee.io/how-it-works', 'label' => 'Tingnan kung paano'],
                        ['type' => 'button', 'label' => 'Subukan ang Libre + Anee', 'url' => 'https://anee.io/pricing', 'align' => 'center', 'style' => 'solid'],
                        ['type' => 'text', 'size' => 'sm', 'align' => 'center', 'html' => '<p>Walang credit card, GCash lang. At kung hindi mo sulit, huwag mo nang ituloy. Ganun lang kasimple.</p>'],
                    ],
                ],
            ],
            'tips' => [
                'priority' => 'regular', 'position' => -10,
                'title' => 'Demo: Tatlong tanong bago mag abono',
                'subject' => 'Tatlong tanong bago ka bumili ng abono',
                'preheader' => 'Bago ka pumunta sa agrivet, sagutin mo muna ito.',
                'audience' => [Seg::ALL],
                'note' => 'A sample regular email in John\'s words: a story, a picture, numbered steps, a saying, a video and the social links, one column. Regular emails go one a day in the order you drag them.',
                'content' => [
                    'kicker' => 'Kwentong bukid',
                    'headline' => 'Tatlong tanong bago ka bumili ng abono',
                    'face' => 'thinking',
                    'hero' => ['src' => '', 'alt' => '', 'link' => ''],
                    'blocks' => [
                        ['type' => 'text', 'html' => '<p>Kasama, may kwento ako.</p><p>Si Pareng Ben, kapitbahay namin, bumili ng sampung sako ng abono noong isang season. "Ganun daw ang tama," sabi niya. Ang ani niya? Halos pareho lang noong nakaraan.</p><p>Hindi kulang ang abono niya. Kulang ang tanong.</p>'],
                        ['type' => 'image', 'src' => self::IMG . 'photos/sacks-shed.jpg', 'alt' => 'Mga sako sa kamalig', 'caption' => 'Ang pinakamahal na sako ay yung hindi mo kailangan.', 'width' => 'full'],
                        ['type' => 'heading', 'text' => 'Bago ka pumunta sa agrivet', 'size' => 'md'],
                        ['type' => 'list', 'style' => 'number', 'items' => [
                            '<b>Ano ang kulang sa lupa ko?</b> Hindi sa lupa ng kapitbahay.',
                            '<b>Kailan ito kailangan ng tanim?</b> Ang tamang abono sa maling araw ay sayang.',
                            '<b>Magkano ang balik?</b> Kwentahin per kilo ng ani, hindi per sako.',
                        ]],
                        ['type' => 'quote', 'html' => 'Ang magaling na magsasaka, hindi ang pinakamaraming binili. Ang pinaka tamang binili.', 'by' => 'Mang Tonyo, Nueva Ecija'],
                        ['type' => 'divider', 'style' => 'dots'],
                        ['type' => 'video', 'url' => 'https://anee.io/how-it-works', 'thumb' => 'https://anee.io/images/anee-song-poster.jpg', 'label' => 'Panoorin: paano tumutulong si Anee', 'caption' => 'Dalawang minuto lang.'],
                        ['type' => 'callout', 'tone' => 'leaf', 'title' => 'Isang maliit na gawain ngayong araw', 'html' => '<p>Ilagay sa NPK Plus ang tanim mo at ang laki ng lote. May computation ka na ng abono bago ka pa umalis ng bahay.</p>'],
                        ['type' => 'button', 'label' => 'Kwentahin ang abono ko', 'url' => 'https://anee.io/app/npk-plus', 'align' => 'left', 'style' => 'outline'],
                        ['type' => 'text', 'html' => '<p>Isang tanong bago ako umalis: ano ang huling abono na binili mo nang hindi mo alam kung bakit? (Walang huhusga. Lahat tayo dumaan diyan.)</p>'],
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
                        ['type' => 'divider', 'style' => 'dots'],
                        ['type' => 'text', 'html' => '<p>Kasama, tuwing Undas, naaalala ko si Lolo. Hindi siya nag-aral ng agrikultura. Pero alam niya kung kailan babaha, kung saan unang tumutubo ang damo, at kung aling sulok ng palayan ang laging mahina.</p><p>Wala siyang notebook. Nasa ulo niya lahat. Nang mawala siya, nawala rin ang karamihan doon.</p><p>Kaya dalawa ang hiling ko sa\'yo ngayong Undas: alalahanin natin sila, at isulat natin ang alam natin. Para sa susunod na magtatanim sa lupang ito.</p>'],
                        ['type' => 'anee', 'face' => 'flower', 'html' => '<p>Magpahinga ka rin, kasama. Ako muna ang magbabantay ng kalendaryo mo.</p>'],
                    ],
                ],
            ],
        ];
    }
}
