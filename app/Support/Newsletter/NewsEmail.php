<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use App\Models\AsSiteSetting;
use App\Services\NewsRoundup;
use App\Support\EmailSkin;
use App\Support\NewsTaglish;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TODAY'S FARM NEWS AS AN EMAIL (2026-10-17, the owner: "a process to create
 * the template and put it in the scheduled email with the 2nd priority").
 *
 * The news roundup (App\Services\NewsRoundup) writes a Latest in Agriculture
 * page on a morning when the feeds have new stories. Each page becomes part
 * of a news email: the page's photo on top, each story as a card with what it
 * means for the farm, and a button to the page. It joins the line by itself
 * (newsletter.news_auto, on unless switched off) and, like all news, is gone
 * if its day ends before it is sent.
 *
 * GATHERED FOR THE NEXT SEND (2026-10-28, the owner: "adjust it only into 4
 * times a week so inline with this compile the news that has not been sent
 * like 2 days news"): the newsletter goes on its send days only
 * (NlSettings::sendDays), so a page belongs to the news email of the next
 * send, and a second page before that send is gathered into the same email
 * (meta.pages, newest first): its stories shared between the pages, the days
 * said, the button to all the news.
 */
final class NewsEmail
{
    private const MAX_STORIES = 5;

    /** The most stories a gathered email carries, shared between its pages. */
    private const MAX_GATHERED = 6;

    public static function auto(): bool
    {
        return AsSiteSetting::yes('newsletter.news_auto', true);
    }

    /**
     * The news email of today's send, brought up to date first: every roundup page of the last week that no
     * news email holds yet is gathered into the email of its send (forPage). Returns the email whose day is
     * today, or null (a day between the sends has none).
     */
    public static function ensure(?Carbon $now = null): ?AsNlEmail
    {
        $now ??= Carbon::now('Asia/Manila');
        if (! Schema::hasTable('as_site_pages') || ! Schema::hasTable('as_nl_emails')) {
            return null;
        }
        $held = self::heldPages();
        $ids = DB::table('as_site_pages')->where('kind', 'roundup')->where('status', 'published')->where('deleteStatus', 1)
            ->where('created_at', '>=', $now->copy()->subDays(8)->startOfDay())->orderBy('id')->pluck('id');
        foreach ($ids as $id) {
            if (! isset($held[(int) $id])) {
                self::forPage((int) $id);
            }
        }

        return self::emailFor($now->toDateString());
    }

    /**
     * A roundup page into the news email of its send, the moment it is published (NewsRoundup::finish,
     * 2026-10-16, the owner: "every generated news ... a 2nd level priority newsletter item ... added in the
     * queue"). The send is the page's own day when that is a send day whose email has not gone yet, else the
     * next send day; a news email already waiting for that send takes the page in beside its own.
     */
    public static function forPage(int $pageId): ?AsNlEmail
    {
        if (! Schema::hasTable('as_nl_emails')) {
            return null;
        }
        $page = DB::table('as_site_pages')->where('id', $pageId)->where('kind', 'roundup')->where('deleteStatus', 1)->first();
        if (! $page) {
            return null;
        }
        $held = self::heldPages();
        if (isset($held[$pageId])) {
            return AsNlEmail::find($held[$pageId]);
        }
        $day = self::sendDayFor(Carbon::parse((string) ($page->created_at ?? 'now'), 'Asia/Manila'));
        $into = self::emailFor($day->toDateString(), true);
        $ids = $into ? array_values(array_unique(array_merge([$pageId], self::pagesOf($into)))) : [$pageId];
        $pages = DB::table('as_site_pages')->whereIn('id', $ids)->where('deleteStatus', 1)->orderByDesc('id')->get()->all();

        return self::fromPages($pages, $day, $into ? $into->status : (self::auto() ? 'queued' : 'draft'), $into);
    }

    /**
     * The send a page published that day goes with: the first send day from that day (and not before today)
     * whose daily send has not run yet.
     */
    public static function sendDayFor(Carbon $day): Carbon
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();
        $d = $day->copy()->startOfDay()->max($today);
        for ($i = 0; $i < 15; $i++, $d->addDay()) {
            if (! NlSettings::isSendDay($d)) {
                continue;
            }
            $done = Schema::hasTable('as_nl_runs') && DB::table('as_nl_runs')->where('runDate', $d->toDateString())->where('kind', 'daily')
                ->whereIn('status', ['done', 'dry', 'running'])->exists();
            if (! $done) {
                return $d;
            }
        }

        return NlSettings::nextSendDay($today, true);
    }

    /** The news email (not a demo) of a send day; with $open, only one still waiting to go. */
    private static function emailFor(string $date, bool $open = false): ?AsNlEmail
    {
        return AsNlEmail::where('priority', 'news')->where('newsDate', $date)->where('deleteStatus', 1)
            ->whereIn('status', $open ? ['queued', 'draft'] : array_merge(AsNlEmail::LIVE, ['draft', 'sent']))
            ->where(fn ($q) => $q->whereNull('meta')->orWhere('meta', 'not like', '%"demo"%'))
            ->orderByDesc('id')->first();
    }

    /** @return list<int> the pages a news email was made from */
    private static function pagesOf(AsNlEmail $e): array
    {
        $ids = array_map('intval', (array) (($e->meta ?? [])['pages'] ?? []));

        return $ids ?: ($e->newsPageId ? [(int) $e->newsPageId] : []);
    }

    /** @return array<int, int> page id => the news email holding it, over the last forty days */
    private static function heldPages(): array
    {
        $out = [];
        AsNlEmail::where('priority', 'news')->where('created_at', '>=', now()->subDays(40))
            ->where(fn ($q) => $q->whereNull('meta')->orWhere('meta', 'not like', '%"demo"%'))
            ->get(['id', 'newsPageId', 'meta'])->each(function (AsNlEmail $e) use (&$out) {
                foreach (self::pagesOf($e) as $id) {
                    $out[$id] ??= (int) $e->id;
                }
            });

        return $out;
    }

    /** One page as its news email, dated $day (the Demo's sample, and the old callers). */
    public static function fromPage(object $page, Carbon $day, string $status = 'queued'): AsNlEmail
    {
        return self::fromPages([$page], $day, $status, null);
    }

    /**
     * Pages (newest first) as the news email of the send on $day, made or written over ($into). One page reads
     * as it always did; several share the stories (the newer page first) and say the days they cover.
     *
     * @param  list<object>  $pages
     */
    public static function fromPages(array $pages, Carbon $day, string $status = 'queued', ?AsNlEmail $into = null): AsNlEmail
    {
        $pages = array_values($pages);
        $many = count($pages) > 1;
        $page = $pages[0];
        $urlOf = fn ($pg) => EmailSkin::ASSET_BASE . '/' . trim((string) $pg->section, '/') . '/' . $pg->slug;
        $url = $many ? EmailSkin::ASSET_BASE . '/blog' : $urlOf($page);
        $hero = json_decode((string) $page->heroImage, true) ?: [];
        $quota = $many ? max(2, (int) ceil(self::MAX_GATHERED / count($pages))) : self::MAX_STORIES;
        $cap = $many ? self::MAX_GATHERED : self::MAX_STORIES;
        $dates = array_map(fn ($pg) => Carbon::parse((string) ($pg->created_at ?? 'now'), 'Asia/Manila'), $pages);
        usort($dates, fn ($x, $y) => $x->timestamp <=> $y->timestamp);
        $first = $dates[0];
        $last = end($dates);

        // The stories first, so their "what it means" can be put in Taglish in one ask.
        $found = [];
        foreach ($pages as $pg) {
            $blocks = json_decode((string) $pg->blocks, true) ?: [];
            $n = count($blocks);
            $took = 0;
            for ($i = 0; $i < $n && $took < $quota && count($found) < $cap; $i++) {
                $b = $blocks[$i];
                if (($b['type'] ?? '') !== 'heading' || (int) ($b['level'] ?? 0) !== 3) {
                    continue;
                }
                $title = trim((string) ($b['text'] ?? ''));
                $text = '';
                $means = '';
                $meansTl = '';
                $source = '';
                for ($j = $i + 1; $j < $n; $j++) {
                    $x = $blocks[$j];
                    $type = $x['type'] ?? '';
                    if ($type === 'heading' || $type === 'divider') {
                        break;
                    }
                    if ($type === 'text') {
                        $words = (string) ($x['text'] ?? '');
                        if (preg_match('~^\*\*[^*]*\*\*\s*Read it at\s~', $words) && preg_match_all('~\[([^\]]+)\]\(https?://~', $words, $ns)) {
                            // The same news from several newsrooms: their names (2026-10-16).
                            $names = array_values(array_unique(array_map('trim', $ns[1])));
                            $source = count($names) > 1 ? implode(', ', array_slice($names, 0, -1)) . ' at ' . end($names) : (string) ($names[0] ?? '');
                        } elseif (preg_match('~^\*\*([^*,]+?)(,[^*]*)?\.?\*\*~', $words, $m) && str_contains($words, '](')) {
                            $source = trim($m[1]);
                        } elseif ($text === '') {
                            $text = $words;
                        }
                    } elseif ($type === 'callout' && $means === '') {
                        $means = (string) ($x['text'] ?? '');
                        // The page's own Taglish (its Taglish tab, 2026-10-17), when it has one.
                        $meansTl = trim((string) ($x['textTl'] ?? ''));
                    }
                }
                if ($title === '') {
                    continue;
                }
                $found[] = ['title' => $title, 'text' => $text, 'means' => $means, 'meansTl' => $meansTl, 'source' => $source, 'url' => $urlOf($pg)];
                $took++;
            }
        }
        // Anee is asked only for the stories whose page has no Taglish (an older page): none, most days.
        $tl = self::taglish(array_filter($found, fn ($s) => $s['meansTl'] === ''), (int) $page->id);

        $out = [];
        $stories = 0;
        foreach ($found as $k => $s) {
            $html = '<p>' . self::md(self::clip($s['text'], 260)) . '</p>';
            // What it means for the farmer rides in the story's own light green box (2026-10-17, the owner),
            // in Taglish "for easier understanding": the page's Taglish, else Anee's, else the page's English.
            $means = $s['meansTl'] !== '' ? self::md(self::clip($s['meansTl'], 360))
                : ($tl[$k] ?? ($s['means'] !== '' ? self::md(self::clip($s['means'], 300)) : ''));
            $out[] = ['id' => 'story' . $stories, 'type' => 'story', 'src' => '', 'title' => $s['title'], 'html' => $html,
                'meansTitle' => $means !== '' ? NewsRoundup::SO_WHAT : '', 'means' => $means !== '' ? '<p>' . $means . '</p>' : '',
                'source' => $s['source'], 'url' => $s['url'], 'label' => 'Basahin sa anee.io'];
            $stories++;
            if ($stories < count($found)) {
                $out[] = ['id' => 'div' . $stories, 'type' => 'divider', 'style' => 'line'];
            }
        }
        if (($out[count($out) - 1]['type'] ?? '') === 'divider') {
            array_pop($out);
        }
        $out[] = ['id' => 'more', 'type' => 'button', 'label' => $many ? 'Basahin ang lahat ng balita' : 'Basahin ang buong balita', 'url' => $url, 'align' => 'center', 'style' => 'solid'];
        // Anee opens it (Voice): a greeting, how many, and why it matters to the farm.
        $count = [1 => 'Isang balita', 2 => 'Dalawang balita', 3 => 'Tatlong balita', 4 => 'Apat na balita', 5 => 'Limang balita', 6 => 'Anim na balita'][$stories] ?? 'Ilang balita';
        $span = self::month($first) . ' ' . $first->format('j') . ($first->isSameDay($last) ? '' : ' hanggang ' . ($first->month === $last->month ? '' : self::month($last) . ' ') . $last->format('j'));
        array_unshift($out, [
            'id' => 'intro', 'type' => 'text',
            'html' => $many
                ? '<p>Hey there, Anee here... naipon ang balita sa bukid nitong mga nakaraang araw.</p><p>' . $count . ' mula ' . $span . ' ang may kinalaman sa bukid. Pinagsama ko na para isang basahan lang: ito ang nangyari, at ito ang ibig sabihin para sa\'yo.</p>'
                : '<p>Hey there, Anee here... may balita sa bukid ngayong araw.</p><p>' . $count . ' ngayong ' . self::month($day) . ' ' . $day->format('j') . ' ang may kinalaman sa bukid. Hindi ko na pahahabain: ito ang nangyari, at ito ang ibig sabihin para sa\'yo.</p>',
        ]);
        $out[] = ['id' => 'today', 'type' => 'text',
            'html' => '<p>Isang tanong lang para sa\'yo ngayong araw: alin dito ang tatama sa susunod mong gagawin sa bukid? Doon ka magsimula.</p>'];
        $out[] = ['id' => 'signoff', 'type' => 'text', 'html' => '<p>' . Voice::signOffHtml('balita muna bago kape') . '</p>'];
        $out[] = ['id' => 'ps', 'type' => 'text', 'size' => 'sm',
            'html' => '<p><b>P.S.</b> May tanong ka kung paano ito tatama sa tanim mo? I-reply mo lang ito, o itanong mo sa akin sa <a href="https://anee.io/">anee.io</a>.</p>'];

        $spanEn = $first->format('M j') . ($first->isSameDay($last) ? '' : ' to ' . ($first->month === $last->month ? $last->format('j') : $last->format('M j')));
        $content = [
            'kicker' => 'Balita sa bukid · ' . ($many ? $spanEn : $day->format('M j')),
            'headline' => (string) $page->title,
            'face' => '',
            'hero' => ['src' => (string) ($hero['src'] ?? ''), 'alt' => (string) ($hero['alt'] ?? ''), 'link' => $url],
            'blocks' => $out,
        ];

        $fields = [
            'title' => 'Farm news · ' . ($many ? $spanEn : $day->format('M j')),
            'subject' => mb_substr('Balita sa bukid: ' . $page->title, 0, 250),
            'preheader' => mb_substr((string) ($page->excerpt ?? ''), 0, 200),
            'blocks' => Renderer::clean($content),
            'newsDate' => $day->toDateString(),
            'newsPageId' => (int) $page->id,
            'meta' => array_merge((array) ($into?->meta ?? []), ['pages' => array_map(fn ($pg) => (int) $pg->id, $pages)]),
            'note' => $many ? 'Gathered from ' . count($pages) . ' news pages by itself, for the ' . $day->format('l, M j') . ' send.' : 'Made from the news page by itself, for the ' . $day->format('l, M j') . ' send.',
        ];
        if ($into) {
            $into->forceFill($fields)->save();
            $email = $into;
        } else {
            $email = AsNlEmail::create($fields + [
                'priority' => 'news', 'position' => 0, 'kind' => 'builder', 'audience' => [Seg::ALL],
                'status' => $status, 'createdBy' => 'News roundup', 'deleteStatus' => 1,
            ]);
        }
        // Scored at once (2026-10-17, the owner: every email at a 0 spam score): above 0 it waits
        // in the line for its fix (Planner::SPAM_MAX), and its note says why.
        try {
            $r = SpamCheck::forSaved($email);
            if ($r['score'] !== null && (float) $r['score'] > Planner::SPAM_MAX) {
                $rules = implode(', ', array_map(fn ($x) => $x['plain'] ?? $x['name'], $r['assassin']['rules'] ?? []));
                $email->forceFill(['note' => mb_substr('Held: its spam score is ' . $r['score'] . ($rules !== '' ? ' (' . $rules . ')' : '') . '. Fix it in the builder and it goes.', 0, 500)])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $email;
    }

    /**
     * Each story's "what it means" in Taglish (2026-10-17, the owner: "make
     * that part taglish for easier understanding"): one ask to Flash (system
     * written, like the roundup itself), Anee's voice, the story's own facts
     * only. Returns [story index => escaped text]; a story Anee did not answer
     * keeps the page's English.
     *
     * @param  list<array{title: string, text: string, means: string}>  $stories
     * @return array<int, string>
     */
    public static function taglish(array $stories, int $refId = 0): array
    {
        // The ask is shared with the page's Taglish tab (App\Support\NewsTaglish, 2026-10-17): the
        // same rules, so the email and the page say it the same way. A dash between words is a
        // comma there, and a Tagalog word keeps its hyphen (nag-abono).
        $out = [];
        foreach (NewsTaglish::ask($stories, $refId ?: null, 'news-email', 300)['items'] as $i => $words) {
            $out[$i] = e(self::clip($words, 360));
        }

        return $out;
    }

    /**
     * An email already made, its green boxes put in Taglish and titled again
     * (the owner: "do it for the existing pending email as well"). Not for a
     * sent email. Returns how many boxes changed.
     */
    public static function retaglish(AsNlEmail $email): int
    {
        if ($email->status === 'sent') {
            return 0;
        }
        $c = is_array($email->blocks) ? $email->blocks : (json_decode((string) $email->getRawOriginal('blocks'), true) ?: []);
        $found = [];
        foreach ((array) ($c['blocks'] ?? []) as $k => $b) {
            if (($b['type'] ?? '') === 'story' && trim(strip_tags((string) ($b['means'] ?? ''))) !== '') {
                $found[$k] = ['title' => (string) ($b['title'] ?? ''), 'text' => (string) ($b['html'] ?? ''), 'means' => (string) $b['means']];
            }
        }
        $tl = self::taglish($found, (int) ($email->newsPageId ?? 0));
        foreach ($found as $k => $s) {
            $c['blocks'][$k]['meansTitle'] = NewsRoundup::SO_WHAT;
            if (isset($tl[$k])) {
                $c['blocks'][$k]['means'] = '<p>' . $tl[$k] . '</p>';
            }
        }
        if ($found) {
            $email->blocks = Renderer::clean($c);
            $email->save();
        }

        return count($tl);
    }

    private static function month(Carbon $d): string
    {
        return ['Enero', 'Pebrero', 'Marso', 'Abril', 'Mayo', 'Hunyo', 'Hulyo', 'Agosto', 'Setyembre', 'Oktubre', 'Nobyembre', 'Disyembre'][$d->month - 1];
    }

    /** The page's light markdown (**bold**, [label](url)) as the builder's rich text. */
    private static function md(string $s): string
    {
        $s = e($s);
        $s = preg_replace('~\*\*(.+?)\*\*~', '<b>$1</b>', $s) ?? $s;
        $s = preg_replace_callback('~\[([^\]]+)\]\((https?://[^)\s]+)\)~', fn ($m) => '<a href="' . $m[2] . '">' . $m[1] . '</a>', $s) ?? $s;

        return $s;
    }

    private static function clip(string $s, int $max): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        $cut = mb_substr($s, 0, $max);
        $dot = max(mb_strrpos($cut, '. ') ?: 0, 0);

        return $dot > $max * 0.5 ? mb_substr($cut, 0, $dot + 1) : rtrim(mb_substr($cut, 0, mb_strrpos($cut, ' ') ?: $max), ' ,;') . '…';
    }
}
