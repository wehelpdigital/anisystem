<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use App\Services\AcumbamailService;

/**
 * The anee.io email sequence written in Acumbamail before this module
 * (2026-10-06: 330 daily emails anee-S001..S330 and 26 holiday emails
 * anee-HOL01..26, in John's voice), brought into the regular line
 * (2026-10-17, the owner: "put it in the 3rd priority but check each email
 * first if it is complete, makes sense").
 *
 * Acumbamail's API sends a template by id but never hands its words back,
 * so the checks here are what can be read: the name, the subject, whether
 * Acumbamail marks it available, and what a subject promises. They were
 * written as a drip, one a day from the day someone joins; the line sends
 * one a day to everyone at once, so:
 *   - the ones that count the time since the reader joined ("isang buwan na
 *     pala tayong magkasama") would read wrong for most people: drafts;
 *   - a subject that repeats an earlier one's topic: a draft, so the line
 *     does not say the same thing twice;
 *   - the plan and price ones go only to who they are about;
 *   - the holiday ones are bound to their day, the next time it comes.
 * Everything kept out is a draft with a note, one switch away from the line.
 */
final class SequenceImport
{
    /** S-number => why it is held back (the reader's own count of days). */
    private const MILESTONES = [
        30 => 'Says "isang buwan na pala tayong magkasama" (a month since you joined).',
        57 => 'Says "2 buwan na" (two months since you joined).',
        93 => 'Says "100 araw" (a hundred days since you joined).',
        138 => 'Says "5 buwan na" (five months since you joined).',
        166 => 'Says "kalahating taon na" (half a year since you joined).',
        227 => 'Says "walong buwan na" (eight months since you joined).',
        272 => 'Says "sampung buwan na" (ten months since you joined).',
        330 => 'Says "isang taon na tayo" (a year since you joined).',
    ];

    /** S-number => the earlier email on the same topic. */
    private const REPEATS = [
        40 => 'S013 (the "pang-sigurado lang" bottle)',
        71 => 'S010 (what is free on Libre)',
        147 => 'S145 (is your harvest really better now)',
        225 => 'S180 (the upside down Y on the armyworm\'s head)',
        320 => 'S200 (more fertilizer is not more harvest)',
    ];

    /** S-number => who it is about (plans and prices). */
    private const AUDIENCES = [
        10 => [Seg::NONUSERS, Seg::FREE],
        211 => [Seg::FREE],
        245 => [Seg::NONUSERS, Seg::FREE],
        268 => [Seg::NONUSERS, Seg::FREE, Seg::PLUS],
        284 => [Seg::FREE, Seg::PLUS, Seg::SOLO],
    ];

    /** S-number => the day it belongs to. */
    private const SEASONAL = [
        247 => ['2026-10-24', 'Undas flowers sell in the week before November 1.'],
    ];

    /** HOL number => [day, note]; a null day stays a draft until one is picked. */
    private const HOLIDAYS = [
        1 => ['2027-01-01', 'New Year\'s Day.'],
        2 => ['2027-02-06', 'Chinese New Year 2027.'],
        3 => ['2027-02-14', 'Valentine\'s Day.'],
        4 => ['2027-04-09', 'Araw ng Kagitingan (Day of Valor).'],
        5 => ['2027-03-25', 'Maundy Thursday 2027 (Easter is March 28).'],
        6 => ['2027-03-28', 'Easter Sunday 2027.'],
        7 => ['2027-04-22', 'Earth Day.'],
        8 => ['2027-05-01', 'Labor Day.'],
        9 => [null, 'No day in its name ("Ilang magsasaka ang nasa almusal mo?"). Farmers\' and Fisherfolk Month (May) or World Food Day (October 16) would fit: pick one.'],
        10 => ['2027-05-09', 'Mother\'s Day 2027 (second Sunday of May).'],
        11 => ['2027-06-12', 'Independence Day.'],
        12 => ['2027-06-20', 'Father\'s Day 2027 (third Sunday of June).'],
        13 => [null, 'No day in its name ("Bakit noodles ang ulam ng nagtatanim ng gulay?"). Nutrition Month (July) would fit: pick a day.'],
        14 => ['2027-03-10', 'Eid\'l Fitr 2027, estimated: the government declares the day close to it, so check it then.'],
        15 => ['2027-05-17', 'Eid\'l Adha 2027, estimated: the government declares the day close to it, so check it then.'],
        16 => ['2027-08-30', 'National Heroes Day 2027 (last Monday of August).'],
        17 => ['2027-09-01', 'The first of the "ber" months.'],
        18 => ['2027-10-16', 'World Food Day (this year\'s was today, before the newsletter could send).'],
        19 => ['2026-11-01', 'Undas.'],
        20 => [null, 'No day in its name ("Ilang kutsarang kanin ang naiiwan sa plato ninyo?"). National Rice Awareness Month (November) would fit: pick a day.'],
        21 => ['2026-11-30', 'Bonifacio Day.'],
        22 => ['2026-12-16', 'The first Simbang Gabi.'],
        23 => ['2026-12-24', 'Christmas Eve.'],
        24 => ['2026-12-25', 'Christmas Day.'],
        25 => ['2026-12-29', 'The days between Christmas and New Year ("bago magbagong taon").'],
        26 => ['2026-12-31', 'New Year\'s Eve.'],
    ];

    /**
     * Read the templates and file each one. Nothing already imported is touched.
     *
     * @return array{found: int, added: int, skipped: int, rows: list<array>, error: string|null}
     */
    public static function run(bool $dry = false, string $by = 'Import'): array
    {
        $am = app(AcumbamailService::class);
        $list = $am->templates();
        if ($list === null) {
            return ['found' => 0, 'added' => 0, 'skipped' => 0, 'rows' => [], 'error' => $am->lastError() ?: 'Acumbamail did not answer.'];
        }
        $rows = [];
        $added = 0;
        $skipped = 0;
        $have = AsNlEmail::whereNotNull('acumbaTemplateId')->pluck('id', 'acumbaTemplateId');
        foreach ((isset($list['result']) && is_array($list['result']) ? $list['result'] : $list) as $tpl) {
            if (! is_array($tpl) || ! preg_match('~^anee-(S|HOL)(\d+)~', (string) ($tpl['name'] ?? ''), $m)) {
                continue;
            }
            $id = (int) ($tpl['id'] ?? 0);
            $d = self::decide($m[1], (int) $m[2], $tpl);
            $rows[] = ['id' => $id, 'name' => $tpl['name'], 'subject' => $d['subject'], 'status' => $d['status'], 'sendOn' => $d['sendOn'], 'audience' => $d['audience'], 'note' => $d['note']];
            if (isset($have[$id])) {
                $skipped++;

                continue;
            }
            if ($dry) {
                continue;
            }
            AsNlEmail::create([
                'priority' => 'regular', 'position' => $d['position'], 'kind' => 'acumba',
                'title' => $d['title'], 'subject' => $d['subject'], 'preheader' => null,
                'acumbaTemplateId' => $id, 'audience' => $d['audience'], 'sendOn' => $d['sendOn'],
                'status' => $d['status'], 'note' => $d['note'],
                'meta' => ['acumbaName' => $tpl['name'], 'available' => (bool) ($tpl['available'] ?? true), 'series' => $m[1] . $m[2]],
                'createdBy' => $by, 'deleteStatus' => 1,
            ]);
            $added++;
        }

        return ['found' => count($rows), 'added' => $added, 'skipped' => $skipped, 'rows' => $rows, 'error' => null];
    }

    /** Where one template goes, and why. */
    private static function decide(string $series, int $n, array $tpl): array
    {
        $subject = trim((string) ($tpl['subject'] ?? '')) ?: self::fromName((string) $tpl['name']);
        $code = $series . str_pad((string) $n, $series === 'S' ? 3 : 2, '0', STR_PAD_LEFT);
        $d = [
            'title' => $code . ' · ' . $subject,
            'subject' => $subject,
            'status' => 'queued',
            'sendOn' => null,
            'audience' => [Seg::ALL],
            'position' => $series === 'S' ? $n * 10 : 5000 + $n * 10,
            'note' => null,
        ];
        $notes = [];
        if (array_key_exists('available', $tpl) && ! $tpl['available']) {
            $d['status'] = 'draft';
            $notes[] = 'Acumbamail marks this template unavailable (unfinished or broken there). Open it in Acumbamail and save it before it joins the line.';
        }

        if ($series === 'S') {
            if ($n === 1) {
                $d['status'] = 'draft';
                $notes[] = 'The welcome. It greets someone who just joined, so it belongs in Acumbamail\'s own welcome automation for each new subscriber, not in the daily line everyone gets.';
            }
            if (isset(self::MILESTONES[$n])) {
                $d['status'] = 'draft';
                $notes[] = self::MILESTONES[$n] . ' In a line that everyone gets on the same day, that count is wrong for most readers. Kept as a draft: change its words in Acumbamail or leave it out.';
            }
            if (isset(self::REPEATS[$n])) {
                $d['status'] = 'draft';
                $notes[] = 'Same topic as ' . self::REPEATS[$n] . '. Kept as a draft so the line does not say it twice.';
            }
            if (isset(self::AUDIENCES[$n])) {
                $d['audience'] = self::AUDIENCES[$n];
                $notes[] = 'About plans and prices: goes to ' . Seg::words(self::AUDIENCES[$n]) . ' only.';
            }
            if (isset(self::SEASONAL[$n])) {
                [$d['sendOn'], $why] = self::SEASONAL[$n];
                $notes[] = $why . ' Bound to that day.';
            }
        } else {
            [$day, $why] = self::HOLIDAYS[$n] ?? [null, 'A holiday email with no known day.'];
            $d['sendOn'] = $day;
            if ($day === null) {
                $d['status'] = 'draft';
            }
            $notes[] = $why;
        }
        $d['note'] = $notes ? mb_substr(implode(' ', $notes), 0, 600) : null;

        return $d;
    }

    /** "anee-S017-d016-bakit-kulang..." to words, for a template with no subject. */
    private static function fromName(string $name): string
    {
        $slug = preg_replace('~^anee-(S\d+-d\d+|HOL\d+)-~', '', $name) ?? $name;

        return ucfirst(str_replace('-', ' ', $slug));
    }
}
