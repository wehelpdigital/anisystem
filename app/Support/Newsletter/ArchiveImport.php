<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use Carbon\Carbon;

/**
 * THE SEQUENCE INTO OUR OWN LIBRARY (2026-10-16, the owner: "let's not use
 * acumbamail emails, so transfer the templates to our own library"). The
 * owner's archive of the 356 emails (storage/app/public/anee-newsletter:
 * emails.json with the order, the Acumbamail ids and the holiday dates, and
 * content/*.json with each email's words) becomes builder emails: the same
 * rows the Acumbamail import made (matched by the Acumbamail template id),
 * now holding their own blocks, so the screen previews and edits them and
 * the send is our HTML in our design (createCampaign), not Acumbamail's.
 *
 * The words stay exactly as written. Their own sign off ("Rooting for you,
 * Anee" and the email's own tagline) and their P.S. become the last blocks.
 * Each row keeps its place, audience and draft or line status; the holiday
 * emails take the archive's dates.
 */
final class ArchiveImport
{
    /** S-numbers whose hold for "same topic" no longer stands, now the words can be read. */
    private const NOT_REPEATS = [40 => 'Read in full: S040 is about identifying a pest before spraying, not the miracle bottle of S013.',
        320 => 'Read in full: S320 answers why yield stays low with lots of fertilizer (the limiting factor), not S200\'s over fertilizing.'];

    /** Held back on reading the words. */
    private const STILL_REPEATS = [147 => 'Close to S145 two days earlier (both compare one season with the next). Keep one of the two.'];

    /**
     * @return array{found: int, updated: int, created: int, rows: list<string>, error: string|null}
     */
    public static function run(string $dir, bool $dry = false, string $by = 'Archive import'): array
    {
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        $list = json_decode((string) @file_get_contents($dir . '/emails.json'), true);
        if (! is_array($list)) {
            return ['found' => 0, 'updated' => 0, 'created' => 0, 'rows' => [], 'error' => 'No emails.json in ' . $dir];
        }
        $content = [];
        foreach (glob($dir . '/content/*.json') ?: [] as $f) {
            foreach ((array) json_decode((string) file_get_contents($f), true) as $e) {
                if (is_array($e) && ! empty($e['id'])) {
                    $content[$e['id']] = $e;
                }
            }
        }
        $today = Planner::now()->toDateString();
        $out = ['found' => 0, 'updated' => 0, 'created' => 0, 'rows' => [], 'error' => null];
        foreach ($list as $row) {
            $c = $content[$row['source_id'] ?? ''] ?? null;
            if (! $c) {
                $out['rows'][] = ($row['template_name'] ?? '?') . ': no content';

                continue;
            }
            $out['found']++;
            $tid = (int) ($row['acumbamail_template_id'] ?? 0);
            preg_match('~^anee-(S|HOL)(\d+)~', (string) $row['template_name'], $m);
            $code = ($m[1] ?? '') . ($m[2] ?? '');
            $n = (int) ($m[2] ?? 0);
            $e = $tid ? AsNlEmail::where('acumbaTemplateId', $tid)->where('deleteStatus', 1)->first() : null;

            $fields = [
                'kind' => 'builder',
                'subject' => mb_substr((string) $c['subject'], 0, 250),
                'preheader' => mb_substr((string) ($c['preheader'] ?? ''), 0, 250) ?: null,
                'blocks' => self::content($c),
                'updatedBy' => $by,
            ];
            $meta = array_merge((array) ($e->meta ?? []), [
                'archive' => $row['source_id'], 'purpose' => $c['purpose'] ?? null, 'theme' => $row['theme'] ?? null,
                'words' => $row['words'] ?? null, 'acumbaTemplateId' => $tid ?: null, 'series' => $code,
            ]);
            unset($meta['available']);
            $fields['meta'] = $meta;

            // The holidays take the archive's own next date (one passed today: the year after).
            if (($m[1] ?? '') === 'HOL' && ! empty($row['next_date'])) {
                $day = Carbon::parse($row['next_date'], 'Asia/Manila');
                if ($day->toDateString() <= $today) {
                    $day->addYear();
                }
                $fields['sendOn'] = $day->toDateString();
                $fields['note'] = 'Holiday email for ' . $row['send_rule'] . '. Bound to ' . $day->format('F j, Y') . '.'
                    . (str_contains((string) $row['send_rule'], 'varies') ? ' The date moves every year: check it before then.' : '');
            }
            if ($e) {
                // The words can be read now: the reasons that were about the template, not the email, go.
                $note = (string) $e->note;
                $note = preg_replace('~Acumbamail marks this template unavailable[^.]*\.[^.]*\.\s*~', '', $note) ?? $note;
                $note = str_replace("belongs in Acumbamail's own welcome automation for each new subscriber, not in the daily line everyone gets", 'fits a welcome sent to each new subscriber, not the daily line everyone gets', $note);
                if (($m[1] ?? '') === 'S' && isset(self::NOT_REPEATS[$n])) {
                    $note = self::NOT_REPEATS[$n];
                    $fields['status'] = $e->status === 'draft' ? 'queued' : $e->status;
                } elseif (($m[1] ?? '') === 'S' && isset(self::STILL_REPEATS[$n])) {
                    $note = self::STILL_REPEATS[$n];
                }
                if (! isset($fields['note'])) {
                    $fields['note'] = trim($note) !== '' ? mb_substr(trim($note), 0, 600) : null;
                }
                // A holiday draft held only for want of a day now has one.
                if (($m[1] ?? '') === 'HOL' && $e->status === 'draft' && isset($fields['sendOn'])) {
                    $fields['status'] = 'queued';
                }
                if (! $dry) {
                    $e->fill($fields)->save();
                }
                $out['updated']++;
            } else {
                if (! $dry) {
                    AsNlEmail::create($fields + [
                        'priority' => 'regular', 'position' => ($m[1] ?? '') === 'S' ? $n * 10 : 5000 + $n * 10,
                        'title' => $code . ' · ' . $c['subject'], 'audience' => [Seg::ALL], 'acumbaTemplateId' => $tid ?: null,
                        'status' => 'draft', 'createdBy' => $by, 'deleteStatus' => 1,
                    ]);
                }
                $out['created']++;
                $out['rows'][] = $code . ': new row (no Acumbamail import found), as a draft';
            }
        }

        return $out;
    }

    /** One archive email as the builder's content. */
    public static function content(array $c): array
    {
        $blocks = [];
        $i = 0;
        $para = [];
        $flush = function () use (&$para, &$blocks, &$i) {
            // Long emails read on a phone: a text block every eight paragraphs, so the builder stays easy to edit.
            foreach (array_chunk($para, 8) as $chunk) {
                $blocks[] = ['id' => 'p' . (++$i), 'type' => 'text', 'html' => implode('', $chunk)];
            }
            $para = [];
        };
        preg_match_all('~<(p|ul|ol)\b[^>]*>(.*?)</\1>~is', (string) $c['body_html'], $parts, PREG_SET_ORDER);
        foreach ($parts as $p) {
            $tag = strtolower($p[1]);
            if ($tag === 'p') {
                $para[] = '<p>' . trim($p[2]) . '</p>';

                continue;
            }
            $flush();
            preg_match_all('~<li\b[^>]*>(.*?)</li>~is', $p[2], $li);
            $items = array_values(array_filter(array_map('trim', $li[1] ?? [])));
            if ($items) {
                $blocks[] = ['id' => 'l' . (++$i), 'type' => 'list', 'style' => $tag === 'ol' ? 'number' : 'dot', 'items' => $items];
            }
        }
        $flush();

        $tag = trim((string) ($c['signoff_tag'] ?? ''));
        $blocks[] = ['id' => 'signoff', 'type' => 'text', 'html' => '<p>' . Voice::signOffHtml($tag) . '</p>'];
        $ps = trim((string) ($c['ps_html'] ?? ''));
        if ($ps !== '') {
            $ps = preg_replace('~^\s*<p>(.*)</p>\s*$~is', '$1', $ps) ?? $ps;
            $blocks[] = ['id' => 'ps', 'type' => 'text', 'size' => 'sm', 'html' => '<p><b>P.S.</b> ' . $ps . '</p>'];
        }

        return Renderer::clean(['kicker' => '', 'headline' => '', 'face' => '', 'hero' => ['src' => '', 'alt' => '', 'link' => ''], 'blocks' => $blocks]);
    }
}
