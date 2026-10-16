<?php

namespace App\Support\Newsletter;

/**
 * HOW JOHN WRITES (2026-10-16, the owner: "we want the persona of John
 * Pagulayan on how he writes"). The newsletter is written as John of anee.io:
 * a farmer writing to a fellow farmer, not a company writing to a customer.
 * Read off the 356 emails of John's sequence (their subjects: "Ang pinakamurang
 * farm tool: ballpen", "Kailan ka huling nag-abono? Sigurado ka?", "Si Juan
 * Tamad at ang bayabas", "Bakit lumalaki ang ani pag gabi na? (lol)").
 *
 * The rules are shown in the builder beside every email, and the samples
 * (Demo) and the daily news email (NewsEmail) follow them.
 */
final class Voice
{
    public const NAME = 'John';

    public const SIGN_OFF = "Kasama mo sa bukid,\nJohn";

    /** [the rule, an example in John's words] */
    public const RULES = [
        ['Taglish, Tagalog first. English only for the words farmers already say in English.', 'Ang LCC mo, nasa level 3 na. Oras na ng abono.'],
        ['A fellow farmer talking. Call the reader kasama. Say ako, ko, tayo, never "we at anee.io".', 'Kasama, may aaminin ako.'],
        ['Open with a small story: a kumpare, Mang Tonyo, Aling Nena, a fable, a saying.', 'Si Pareng Ben, bumili ng sampung sako ng abono. Ang ani? Pareho lang.'],
        ['One idea per email. Short paragraphs, one to three sentences.', 'Hindi kulang ang abono niya. Kulang ang tanong.'],
        ['Ask the reader straight, and answer honestly. Talk money in pesos, per kilo, per kaban.', 'Magkano talaga ang isang kaban mo? Kwentahin natin.'],
        ['A little humor in parentheses, never at the farmer.', '(Oo, literal.) (Seryosong tanong.) (Spoiler: marami.)'],
        ['End with one small thing to do today, then point to Anee or anee.io gently. No hard selling.', 'Ngayong araw, isulat mo lang ang petsa ng huling abono mo.'],
        ['Sign off as John. No dashes in the words.', "Kasama mo sa bukid,\nJohn"],
    ];

    public static function forKit(): array
    {
        return ['name' => self::NAME, 'signOff' => self::SIGN_OFF, 'rules' => array_map(fn ($r) => ['rule' => $r[0], 'example' => $r[1]], self::RULES)];
    }
}
