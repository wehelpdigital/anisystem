<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * THE SPAM CHECK (2026-10-17, the owner: "a dynamic spam scorer ... based on a
 * reputable real criteria, to see each email created if good").
 *
 * Every point it gives comes from a named, published source:
 *
 *  1. Apache SpamAssassin, the open source filter most mail servers run or
 *     learn from. The email, built as an inbox receives it (the From line,
 *     the subject, the HTML and its plain text twin), is scored by
 *     SpamAssassin itself through Postmark's free public checker
 *     (spamcheck.postmarkapp.com). The score is SpamAssassin's own, rule by
 *     rule: at 5 points or more SpamAssassin's default calls it spam. The
 *     network blocklist lookups do not run there, so those rules never count.
 *
 *  2. The rules Gmail and Yahoo set for bulk senders (February 2024): SPF,
 *     DKIM and one valid DMARC record on the From domain, and an unsubscribe
 *     link (the one-click header is Acumbamail's to add). Gmail also clips an
 *     email over 102 KB, hiding its end, the unsubscribe link with it.
 *
 *  3. What the US CAN-SPAM Act asks of marketing email (a postal address, a
 *     way out) and what the blocklists act on: link shorteners, and a link
 *     whose words show one site while it goes to another.
 *
 * The verdict is about the email (1 and 3). The sender setup (2) is the same
 * for every email, so it is reported beside it, not folded into each score.
 * Results are kept by the email's exact words, so the same email is never
 * sent to SpamAssassin twice.
 */
class SpamCheck
{
    /** SpamAssassin's default: this many points or more is spam. */
    public const SPAM_AT = 5.0;

    /** At or above this, the email is worth a second look. */
    public const CHECK_AT = 2.5;

    /** Gmail clips a message whose HTML is bigger than this. */
    public const GMAIL_CLIP = 102 * 1024;

    private const POSTMARK = 'https://spamcheck.postmarkapp.com/filter';

    /** Link shorteners: the blocklists and Gmail treat them as hiding where a link goes. */
    private const SHORTENERS = ['bit.ly', 'bitly.com', 'tinyurl.com', 'goo.gl', 't.co', 'ow.ly', 'is.gd', 'buff.ly', 'cutt.ly', 'rebrand.ly',
        'shorturl.at', 'rb.gy', 'tiny.cc', 'bl.ink', 's.id', 'lnkd.in', 'shorte.st', 'adf.ly', 'v.gd', 'qr.ae'];

    /** Free mailbox domains: their DMARC refuses mail sent in their name by anyone else. */
    private const FREEMAIL = ['gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'outlook.com', 'hotmail.com', 'live.com', 'icloud.com', 'aol.com', 'proton.me', 'protonmail.com'];

    /** SpamAssassin's rules that only say how this check was run, not anything about the email. */
    private const SETUP_RULES = '/^(URIBL_BLOCKED|URIBL_\w*BLOCKED\w*|RCVD_IN_\w+|DNSWL_BLOCKED|RCVD_IN_VALIDITY_\w+|NO_RELAYS|NO_RECEIVED|\w+_BLOCKED_OPENDNS|HTML_MESSAGE)$/';

    /** SpamAssassin's terse rule words, said plainly, with what to do. */
    private const RULE_WORDS = [
        'MIME_HTML_ONLY' => ['The email has only an HTML version.', 'A plain text version is added when it is sent; nothing to do unless this stays.'],
        'MIME_HTML_MOSTLY' => ['The email is mostly HTML with a thin text version.', 'Keep enough real words in the blocks.'],
        'MPART_ALT_DIFF' => ['The HTML and the plain text versions say different things.', 'Avoid text that only shows in the HTML (for example words inside pictures).'],
        'MPART_ALT_DIFF_COUNT' => ['The HTML and plain text versions differ a lot.', 'Avoid text that only shows in the HTML.'],
        'HTML_IMAGE_RATIO_02' => ['Very few words for the size of the pictures.', 'Add a few lines of text, or use fewer or smaller pictures.'],
        'HTML_IMAGE_RATIO_04' => ['Few words for the size of the pictures.', 'Add some text, or use fewer or smaller pictures.'],
        'HTML_IMAGE_RATIO_06' => ['Rather few words for the size of the pictures.', 'Add some text around the pictures.'],
        'HTML_IMAGE_RATIO_08' => ['A little light on words for its pictures.', 'A sentence or two more helps.'],
        'HTML_IMAGE_ONLY_04' => ['Almost only a picture, with hardly any words.', 'Put the message in words, not only in a picture.'],
        'HTML_IMAGE_ONLY_08' => ['Almost only a picture, with hardly any words.', 'Put the message in words, not only in a picture.'],
        'HTML_IMAGE_ONLY_12' => ['Mostly pictures, few words.', 'Put the message in words, not only in pictures.'],
        'HTML_IMAGE_ONLY_16' => ['Mostly pictures, few words.', 'Put the message in words, not only in pictures.'],
        'HTML_IMAGE_ONLY_20' => ['Mostly pictures, few words.', 'Add more text.'],
        'HTML_IMAGE_ONLY_24' => ['Mostly pictures, few words.', 'Add more text.'],
        'HTML_IMAGE_ONLY_28' => ['Pictures outweigh the words.', 'Add more text.'],
        'HTML_IMAGE_ONLY_32' => ['Pictures outweigh the words.', 'Add more text.'],
        'HTML_SHORT_LINK_IMG_1' => ['A short email whose main link is a picture.', 'Add words around the picture, and a text link.'],
        'HTML_SHORT_LINK_IMG_2' => ['A short email whose main link is a picture.', 'Add words around the picture, and a text link.'],
        'HTML_SHORT_LINK_IMG_3' => ['A short email whose main link is a picture.', 'Add words around the picture, and a text link.'],
        'HTML_FONT_SIZE_LARGE' => ['Some text is set very large.', 'Use the normal title sizes.'],
        'HTML_FONT_SIZE_HUGE' => ['Some text is set huge.', 'Use the normal title sizes.'],
        'HTML_FONT_LOW_CONTRAST' => ['Some text is nearly the colour of its background.', 'Text that is hard to see looks like hidden text to filters.'],
        'HTML_FONT_FACE_BAD' => ['A font name filters do not expect.', 'Use the theme fonts.'],
        'SUBJ_ALL_CAPS' => ['The subject is in capital letters.', 'Write it in normal case.'],
        'UPPERCASE_50_75' => ['Half or more of the words are in capitals.', 'Use capitals only where they belong.'],
        'UPPERCASE_75_100' => ['Most of the words are in capitals.', 'Use capitals only where they belong.'],
        'PLING_QUERY' => ['The subject has "!?" or "?!".', 'One mark is enough.'],
        'SUBJ_EXCESS_QP' => ['The subject is encoded in an odd way.', 'Keep the subject to plain words and common emoji.'],
        'LOTS_OF_MONEY' => ['Talk of big amounts of money.', 'Fine for prices; avoid "millions", "cash prizes" and the like.'],
        'MONEY_NOHTML' => ['Money talk in a plain email.', 'Keep money words to what the email is about.'],
        'FREEMAIL_FROM' => ['Sent from a free mailbox address.', 'Send from your own domain (anee.io).'],
        'T_REMOTE_IMAGE' => ['Pictures load from the web.', 'Normal for a newsletter; no change needed.'],
        'HTML_TAG_BALANCE_BODY' => ['The HTML has an unclosed tag.', 'Usually a pasted block of HTML; check pasted text.'],
        'HTML_OBFUSCATE_05_10' => ['Some words are written with odd character codes.', 'Type the words instead of pasting from a designed page.'],
        'HTML_OBFUSCATE_10_20' => ['Many words are written with odd character codes.', 'Type the words instead of pasting from a designed page.'],
        'DEAR_SOMETHING' => ['It opens with "Dear ..." in a way spam often does.', 'Greet by name, the way John writes.'],
        'URI_PHISH' => ['A link looks like a phishing link.', 'Link only to anee.io and well known sites.'],
        'GUARANTEED_100_PERCENT' => ['It says "100% guaranteed".', 'Drop the guarantee wording.'],
        'SHORTENER_SHORT_IMG' => ['A short email with a picture and a shortened link.', 'Link to the page itself, and add words.'],
        'ADVANCE_FEE_2_NEW_MONEY' => ['Wording like advance fee scams, with talk of big money.', 'Talk about the farm, not about earning money.'],
        'ADVANCE_FEE_3_NEW_MONEY' => ['Wording like advance fee scams, with talk of big money.', 'Talk about the farm, not about earning money.'],
        'ADVANCE_FEE_4_NEW_MONEY' => ['Wording like advance fee scams, with talk of big money.', 'Talk about the farm, not about earning money.'],
        'FREE_QUOTE_INSTANT' => ['Offers a free instant quote.', 'Say what the reader gets in plain words.'],
        'BODY_SINGLE_WORD' => ['The body is a single word.', 'Write at least a few sentences.'],
        'EMPTY_MESSAGE' => ['The email has no words.', 'Add the blocks of the email.'],
    ];

    /**
     * The check of one email as it would be sent.
     *
     * @param  array{subject?:string, preheader?:string, content?:mixed, kind?:string}  $email
     */
    public static function check(array $email): array
    {
        $subject = trim((string) ($email['subject'] ?? ''));
        $preheader = trim((string) ($email['preheader'] ?? ''));
        $acumba = ($email['kind'] ?? '') === 'acumba';
        $fromName = NlSettings::fromName();
        $fromEmail = NlSettings::fromEmail();
        $sample = ['NAME' => 'Juan', 'GREET' => 'Juan', 'EMAIL' => 'juan@example.com'];

        $content = [];
        $assassin = ['available' => false, 'score' => null, 'rules' => [], 'info' => [], 'error' => null];
        if ($acumba) {
            $assassin['error'] = 'Its words live in Acumbamail, so only the subject and the sender are checked here.';
            $content[] = self::row('subject', 'Subject line', $subject !== '' ? 'ok' : 'fail',
                $subject !== '' ? 'Present.' : 'There is no subject line.', 'Gmail and Yahoo bulk sender guidelines');
        } else {
            $parts = ['subject' => $subject, 'preheader' => $preheader, 'content' => $email['content'] ?? []];
            $live = Renderer::html($parts, Theme::get(), 'live', '', 'auto');
            $read = Renderer::html($parts, Theme::get(), 'test', '', 'auto', $sample);
            $text = Renderer::text($read);
            $assassin = self::assassin(self::message($fromName, $fromEmail, MergeTags::fillText($subject, $sample), $read, $text));
            $content = self::contentChecks($subject, $live, $read);
        }

        $score = $assassin['available'] ? (float) $assassin['score'] : null;
        $fails = count(array_filter($content, fn ($c) => $c['status'] === 'fail'));
        $warns = count(array_filter($content, fn ($c) => $c['status'] === 'warn'));
        $level = match (true) {
            $fails > 0 || ($score !== null && $score >= self::SPAM_AT) => 'poor',
            $warns > 0 || ($score !== null && $score >= self::CHECK_AT) || $score === null && ! $acumba => 'fair',
            default => 'good',
        };

        return [
            'level' => $level,
            'levelWords' => ['good' => 'Looks good', 'fair' => 'Worth a look', 'poor' => 'Likely to land in spam'][$level],
            'score' => $score,
            'spamAt' => self::SPAM_AT,
            'assassin' => $assassin,
            'content' => $content,
            'sender' => [...self::senderChecks($fromEmail), self::addressCheck()],
            'from' => $fromName . ' <' . $fromEmail . '>',
            'checkedAt' => now()->format('Y-m-d H:i'),
        ];
    }

    /** The check of a saved email, kept on it (meta.spam) for the list's badge. */
    public static function forSaved(AsNlEmail $e): array
    {
        $report = self::check(['subject' => $e->subject, 'preheader' => $e->preheader, 'content' => $e->blocks ?? [], 'kind' => $e->kind]);
        $meta = (array) ($e->meta ?? []);
        $meta['spam'] = ['score' => $report['score'], 'level' => $report['level'], 'at' => $report['checkedAt'], 'sig' => self::sig($e)];
        // Not a change of the email itself: its "last changed" stays as it was.
        $e->getConnection()->table($e->getTable())->where('id', $e->id)->update(['meta' => json_encode($meta)]);

        return $report;
    }

    /**
     * Checks up to $limit emails whose badge is missing or stale: the ones in the
     * line first, then drafts. For the newsletter's clock and the backfill
     * command, so the list's badges fill in without a browser doing the work.
     *
     * @return int how many were checked
     */
    public static function fillBadges(int $limit, ?callable $report = null): int
    {
        $done = 0;
        $rows = AsNlEmail::active()->where('kind', 'builder')->whereIn('status', ['queued', 'partial', 'draft'])
            ->orderByRaw("case when status = 'draft' then 1 else 0 end")->orderBy('priority')->orderBy('position')->orderBy('id')->get();
        foreach ($rows as $e) {
            if ($done >= $limit) {
                break;
            }
            // A badge without a score is one SpamAssassin could not give: ask again.
            $b = self::badge($e);
            if ($b && $b['score'] !== null) {
                continue;
            }
            $r = self::forSaved($e);
            $done++;
            if ($report) {
                $report($e, $r);
            }
        }

        return $done;
    }

    /** The badge the list shows, while it still matches the email's words. */
    public static function badge(AsNlEmail $e): ?array
    {
        $s = $e->meta['spam'] ?? null;

        return is_array($s) && ($s['sig'] ?? '') === self::sig($e) ? ['score' => $s['score'], 'level' => $s['level'], 'at' => $s['at']] : null;
    }

    private static function sig(AsNlEmail $e): string
    {
        return md5(json_encode([$e->kind, $e->subject, $e->preheader, $e->blocks, $e->acumbaTemplateId, NlSettings::fromEmail(), NlSettings::fromName()]));
    }

    /* ======================================================== SpamAssassin */

    /** The email as a mail server receives it: headers, then the text and HTML twins. */
    private static function message(string $fromName, string $fromEmail, string $subject, string $html, string $text): string
    {
        $enc = fn (string $s) => preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
        $domain = Str::after($fromEmail, '@') ?: 'anee.io';
        $b = 'nlk' . md5($html);

        return implode("\r\n", [
            'From: ' . $enc($fromName) . ' <' . $fromEmail . '>',
            'To: Juan <juan@example.com>',
            'Subject: ' . $enc($subject !== '' ? $subject : '(no subject)'),
            'Date: ' . now()->toRfc2822String(),
            'Message-ID: <' . substr($b, 3, 24) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $b . '"',
            '',
            '--' . $b,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($text),
            '--' . $b,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($html),
            '--' . $b . '--',
            '',
        ]);
    }

    /** SpamAssassin's verdict, kept for a week by the message's words (the date line left out). */
    private static function assassin(string $raw): array
    {
        $key = 'nl:spam:' . md5(preg_replace('/^(Date|Message-ID): .*$/mi', '', $raw));
        $hit = Cache::get($key);
        if (is_array($hit)) {
            return $hit;
        }
        try {
            $res = Http::timeout(25)->connectTimeout(8)->acceptJson()->post(self::POSTMARK, ['email' => $raw, 'options' => 'long']);
            $j = $res->json();
            if (! $res->successful() || ! is_array($j) || empty($j['success'])) {
                throw new \RuntimeException('the checker answered ' . $res->status() . (is_array($j) && isset($j['message']) ? ': ' . $j['message'] : ''));
            }
        } catch (\Throwable $e) {
            return ['available' => false, 'score' => null, 'rules' => [], 'info' => [],
                'error' => 'SpamAssassin could not be reached just now (' . Str::limit($e->getMessage(), 120) . '). The other checks still ran.'];
        }
        $rules = [];
        $info = [];
        foreach (self::parseReport((string) ($j['report'] ?? '')) as $r) {
            if (preg_match(self::SETUP_RULES, $r['name'])) {
                continue;
            }
            [$plain, $fix] = self::RULE_WORDS[$r['name']] ?? [null, null];
            $row = ['name' => $r['name'], 'points' => $r['points'], 'says' => $r['text'], 'plain' => $plain, 'fix' => $fix];
            abs($r['points']) >= 0.05 ? $rules[] = $row : $info[] = $row;
        }
        usort($rules, fn ($a, $b) => $b['points'] <=> $a['points']);
        $out = ['available' => true, 'score' => round((float) ($j['score'] ?? 0), 1), 'rules' => $rules, 'info' => $info, 'error' => null];
        Cache::put($key, $out, now()->addDays(7));

        return $out;
    }

    /** SpamAssassin's report table: " pts rule  description", a description running on over lines. */
    private static function parseReport(string $report): array
    {
        $rows = [];
        foreach (preg_split('/\r?\n/', $report) as $line) {
            if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s+([A-Z][A-Z0-9_]+)\s+(.*)$/', $line, $m)) {
                $rows[] = ['points' => (float) $m[1], 'name' => $m[2], 'text' => trim($m[3])];
            } elseif ($rows && preg_match('/^\s{12,}(\S.*)$/', $line, $m)) {
                $rows[count($rows) - 1]['text'] = trim($rows[count($rows) - 1]['text'] . ' ' . trim($m[1]));
            }
        }

        return $rows;
    }

    /* ============================================================ content */

    private static function contentChecks(string $subject, string $live, string $read): array
    {
        $out = [];
        $out[] = self::row('subject', 'Subject line', $subject !== '' ? 'ok' : 'fail',
            $subject !== '' ? 'Present (' . mb_strlen($subject) . ' characters).' : 'There is no subject line.', 'Gmail and Yahoo bulk sender guidelines');

        $hasUnsub = (bool) preg_match('/href="\*\|UNSUBSCRIBE_URL\|\*"/i', $live);
        $out[] = self::row('unsubscribe', 'Unsubscribe link', $hasUnsub ? 'ok' : 'fail',
            $hasUnsub ? 'In the footer; Acumbamail fills it for each person.' : 'The email has no unsubscribe link.', 'Gmail and Yahoo bulk sender guidelines; CAN-SPAM Act');

        $size = strlen($live);
        $out[] = self::row('size', 'Size (Gmail clips over 102 KB)', $size > self::GMAIL_CLIP ? 'fail' : ($size > 90 * 1024 ? 'warn' : 'ok'),
            number_format($size / 1024, 1) . ' KB' . ($size > self::GMAIL_CLIP ? ': Gmail will cut it off and hide the rest, the unsubscribe link with it.' : ($size > 90 * 1024 ? ': close to the limit once Acumbamail adds its link tracking.' : '.')),
            'Gmail');

        [$short, $mismatch, $noAlt, $links] = self::links($read);
        $out[] = self::row('shorteners', 'Link shorteners', $short ? 'fail' : 'ok',
            $short ? 'Shortened links: ' . implode(', ', array_slice($short, 0, 4)) . '. Blocklists and Gmail treat them as hiding where a link goes; link to the page itself.' : 'None (' . $links . ' ' . Str::plural('link', $links) . ' checked).',
            'Spamhaus and SURBL blocklists; Gmail');
        $out[] = self::row('mismatch', 'Links go where their words say', $mismatch ? 'warn' : 'ok',
            $mismatch ? 'A link shows one address but goes to another: ' . implode('; ', array_slice($mismatch, 0, 3)) . '. That is how phishing links look.' : 'Yes.',
            'SpamAssassin and Gmail phishing checks');
        $out[] = self::row('alt', 'Pictures have words (alt text)', $noAlt ? 'warn' : 'ok',
            $noAlt ? $noAlt . ' ' . Str::plural('picture', $noAlt) . ' without a description. Many inboxes hide pictures at first; without words they show nothing.' : 'Every picture is described.',
            'Gmail and Outlook show pictures only on request');

        return $out;
    }

    /** @return array{0: list<string>, 1: list<string>, 2: int, 3: int} shorteners, mismatches, pictures without alt, links */
    private static function links(string $html): array
    {
        $dom = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $short = [];
        $mismatch = [];
        $links = 0;
        foreach ($dom->getElementsByTagName('a') as $a) {
            $href = trim($a->getAttribute('href'));
            if (! preg_match('#^https?://#i', $href)) {
                continue;
            }
            $links++;
            $host = self::host($href);
            if (in_array($host, self::SHORTENERS, true)) {
                $short[] = $host;
            }
            // Words that are themselves an address must lead to that address's site.
            $words = trim(preg_replace('/\s+/u', ' ', $a->textContent));
            if (preg_match('#^(?:https?://)?(?:www\.)?([a-z0-9-]+(?:\.[a-z0-9-]+)+)(?:/\S*)?$#i', $words, $m) && ! str_contains($words, ' ')) {
                $shown = strtolower($m[1]);
                if ($shown !== $host && ! str_ends_with($host, '.' . $shown) && ! str_ends_with($shown, '.' . $host)) {
                    $mismatch[] = '"' . Str::limit($words, 40) . '" goes to ' . $host;
                }
            }
        }
        // A picture with no alt at all, or a big one with an empty alt. A small mark with
        // alt="" (the footer's logo) is decoration, and empty is the right alt for it.
        $noAlt = 0;
        foreach ($dom->getElementsByTagName('img') as $img) {
            $w = (int) $img->getAttribute('width');
            if (! $img->hasAttribute('alt') ? $w === 0 || $w > 2 : (trim($img->getAttribute('alt')) === '' && ($w === 0 || $w >= 120))) {
                $noAlt++;
            }
        }

        return [array_values(array_unique($short)), array_values(array_unique($mismatch)), $noAlt, $links];
    }

    private static function host(string $url): string
    {
        return strtolower(preg_replace('/^www\./i', '', (string) parse_url($url, PHP_URL_HOST)));
    }

    /* ============================================================= sender */

    /** The From domain's published setup, checked once an hour. */
    private static function senderChecks(string $fromEmail): array
    {
        $domain = strtolower(Str::after($fromEmail, '@'));

        return Cache::remember('nl:spam:dns:' . $domain, 3600, function () use ($domain) {
            $out = [];
            $free = in_array($domain, self::FREEMAIL, true);
            $out[] = self::row('own_domain', 'Sent from your own domain', $free ? 'fail' : 'ok',
                $free ? $domain . ' is a free mailbox: its DMARC rejects mail sent in its name through Acumbamail. Send from anee.io.' : 'From ' . $domain . '.',
                'Gmail and Yahoo bulk sender guidelines');
            if ($free) {
                return $out;
            }

            $txt = fn (string $host) => array_map(fn ($r) => (string) ($r['txt'] ?? implode('', (array) ($r['entries'] ?? []))), @dns_get_record($host, DNS_TXT) ?: []);
            $spf = array_values(array_filter($txt($domain), fn ($t) => stripos($t, 'v=spf1') === 0));
            $out[] = self::row('spf', 'SPF record', count($spf) === 1 ? 'ok' : 'fail',
                count($spf) === 1 ? Str::limit($spf[0], 140) : (count($spf) ? 'There are ' . count($spf) . ' SPF records; a domain may have only one (RFC 7208).' : 'No SPF record on ' . $domain . '.'),
                'Gmail and Yahoo bulk sender guidelines; RFC 7208');

            $dkim = @dns_get_record('acumbamail._domainkey.' . $domain, DNS_CNAME) ?: [];
            $dkimTxt = $txt('acumbamail._domainkey.' . $domain);
            $hasDkim = $dkim || array_filter($dkimTxt, fn ($t) => stripos($t, 'p=') !== false);
            $out[] = self::row('dkim', 'DKIM signing for Acumbamail', $hasDkim ? 'ok' : 'fail',
                $hasDkim ? 'acumbamail._domainkey.' . $domain . ' is set' . ($dkim ? ' (points to ' . ($dkim[0]['target'] ?? 'Acumbamail') . ')' : '') . ', so Acumbamail signs as ' . $domain . '.'
                    : 'acumbamail._domainkey.' . $domain . ' is not set, so mail is not signed as ' . $domain . '. Add the record Acumbamail gives under its sender domains.',
                'Gmail and Yahoo bulk sender guidelines');

            $dmarc = array_values(array_filter($txt('_dmarc.' . $domain), fn ($t) => stripos(ltrim($t), 'v=DMARC1') === 0));
            if (count($dmarc) === 1) {
                $policy = preg_match('/\bp=\s*(none|quarantine|reject)/i', $dmarc[0], $m) ? strtolower($m[1]) : '?';
                $out[] = self::row('dmarc', 'DMARC record', 'ok', 'One record, policy p=' . $policy . ($policy === 'none' ? ' (the minimum Gmail and Yahoo accept).' : '.'),
                    'Gmail and Yahoo bulk sender guidelines; RFC 7489');
            } else {
                $out[] = self::row('dmarc', 'DMARC record', 'fail', count($dmarc)
                    ? 'There are ' . count($dmarc) . ' DMARC records on _dmarc.' . $domain . '. A domain with more than one is treated as having none (RFC 7489, 6.6.3), so Gmail and Yahoo see no DMARC. Delete all but one in the DNS: ' . implode('  |  ', array_map(fn ($t) => Str::limit($t, 70), $dmarc))
                    : 'No DMARC record on _dmarc.' . $domain . '. Gmail and Yahoo require one from bulk senders; "v=DMARC1; p=none;" is enough to start.',
                    'Gmail and Yahoo bulk sender guidelines; RFC 7489');
            }

            return $out;
        });
    }

    /** The footer every email wears (the theme's line): does it carry a postal address? */
    private static function addressCheck(): array
    {
        $line = Str::lower((string) (Theme::get()['footer'] ?? ''));
        $has = (bool) preg_match('/\b(street|st\.|avenue|ave\.|road|rd\.|barangay|brgy\.?|city|province|philippines|p\.?o\.? box)\b.{0,80}\b\d{4}\b|\b\d{4}\b.{0,40}\b(philippines|city)\b/u', $line);

        return self::row('address', 'Postal address in the footer', $has ? 'ok' : 'warn',
            $has ? 'The theme\'s footer line has one.' : 'The footer has no postal address. The US CAN-SPAM Act requires one in marketing email, and some filters look for it. Add it to the footer line in the theme.',
            'CAN-SPAM Act');
    }

    private static function row(string $key, string $label, string $status, string $detail, string $source): array
    {
        return compact('key', 'label', 'status', 'detail', 'source');
    }
}
