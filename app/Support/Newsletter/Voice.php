<?php

namespace App\Support\Newsletter;

/**
 * HOW THE NEWSLETTER IS WRITTEN (2026-10-16, the owner: "we want the persona
 * of John Pagulayan on how he writes"). The writer is ANEE, the voice of
 * anee.io, and the style is John Pagulayan's newsletters, adapted for
 * farmers: that is how the owner's 356 email sequence was briefed and
 * written (the archive's docs/writer_brief.md, with John Pagulayan's own
 * emails as the style samples). These rules are the short form of that
 * brief; the builder shows them beside every email, and the samples (Demo)
 * and the daily news email (NewsEmail) follow them.
 */
final class Voice
{
    public const NAME = 'Anee';

    public const STYLE = 'John Pagulayan\'s newsletter style, for farmers';

    public const SIGN_OFF = "Rooting for you,\nAnee";

    /** [the rule, an example in the sequence's own words] */
    public const RULES = [
        ['Anee writes, as the voice of anee.io. She is not a person: no lolo, no farm of her own. "Kami sa anee.io" is fine for the team.', 'Ako si Anee, pero hindi ako tao. Wala akong sariling bukid.'],
        ['Taglish leaning Tagalog: the everyday words of a farmer in Nueva Ecija or Bukidnon, English for punch lines and common terms.', 'Hindi na babalik sa dati ang gastos. Kaya ang ani ang kailangang tumaas.'],
        ['Story or scene first, the lesson second: the tindahan, the palengke, a kapitbahay, a parable. Characters are framed as examples.', 'Isipin natin si Mang Ben... (halimbawa lang ito)'],
        ['Very short paragraphs, one or two sentences, long but easy on a phone (about 550 to 850 words).', 'Ganun din sa bukid.'],
        ['Talk like a real Filipino: eh, naman, kasi, pala, diba, haha, lol, a side comment in parentheses. At most two or three bold lines.', 'Hindi ko na itatanong ang eksaktong presyo, baka malungkot lang tayo pareho. (haha)'],
        ['Every email delivers what its subject promises: a real lesson, example or step. Numbers only as labeled examples, no doses, no brands.', 'Halimbawa lang ito, gawa gawang numero para madaling kwentahin.'],
        ['Nothing the farmer cannot control (no irrigation schedules). A soft CTA in about one email in three, usually in the P.S.', 'Kung hindi mo pa nata-try ang anee.io, libre lang mag-umpisa: anee.io/signup'],
        ['End on a real thought, a question to reply to, or a teaser for the next email. No dashes, no AI words, no tidy wrap up.', 'Sa susunod na email, titingnan natin kung saan madalas tumatagas ang ani.'],
        ['Sign off: Rooting for you, Anee, then a short witty tagline only this email has, then anee.io.', "Rooting for you,\nAnee\n\"sardinas economics\""],
    ];

    public static function forKit(): array
    {
        return ['name' => self::NAME, 'style' => self::STYLE, 'signOff' => self::SIGN_OFF,
            'rules' => array_map(fn ($r) => ['rule' => $r[0], 'example' => $r[1]], self::RULES)];
    }

    /** The sequence's own closing: "Rooting for you, / Anee / "tagline" / anee.io". */
    public static function signOffHtml(string $tag = ''): string
    {
        return 'Rooting for you,<br><b>Anee</b>'
            . ($tag !== '' ? '<br><i>"' . e($tag) . '"</i>' : '')
            . '<br><a href="https://anee.io/">anee.io</a>';
    }
}
