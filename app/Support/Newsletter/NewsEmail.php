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
 * page every morning at 05:00 when the feeds have new stories. ensure() turns
 * today's page into a news email once: the page's photo on top, each story as
 * a card with what it means for the farm, and a button to the page. It joins
 * the line by itself (newsletter.news_auto, on unless switched off) and, like
 * all news, is gone if today ends before it is sent.
 */
final class NewsEmail
{
    private const MAX_STORIES = 5;

    public static function auto(): bool
    {
        return AsSiteSetting::yes('newsletter.news_auto', true);
    }

    /** Today's roundup page as an email, made once. Returns the email or null. */
    public static function ensure(?Carbon $now = null): ?AsNlEmail
    {
        $now ??= Carbon::now('Asia/Manila');
        if (! Schema::hasTable('as_site_pages')) {
            return null;
        }
        $page = DB::table('as_site_pages')->where('kind', 'roundup')->where('status', 'published')->where('deleteStatus', 1)
            ->where('created_at', '>=', $now->copy()->startOfDay())->orderByDesc('id')->first();
        if (! $page) {
            return null;
        }
        $made = AsNlEmail::where('newsPageId', $page->id)->where(fn ($q) => $q->whereNull('meta')->orWhere('meta', 'not like', '%"demo"%'))->first();
        if ($made) {
            return $made;
        }

        return self::fromPage($page, $now, self::auto() ? 'queued' : 'draft');
    }

    /**
     * A roundup page as its news email, made the moment the page is
     * published (NewsRoundup::finish, 2026-10-16, the owner: "every generated
     * news ... a 2nd level priority newsletter item ... added in the queue"),
     * dated the page's own day. Made once: the tick's ensure() finds it.
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
        $made = AsNlEmail::where('newsPageId', $page->id)->where(fn ($q) => $q->whereNull('meta')->orWhere('meta', 'not like', '%"demo"%'))->first();
        if ($made) {
            return $made;
        }
        $day = Carbon::parse((string) ($page->created_at ?? 'now'), 'Asia/Manila');

        return self::fromPage($page, $day, self::auto() ? 'queued' : 'draft');
    }

    public static function fromPage(object $page, Carbon $day, string $status = 'queued'): AsNlEmail
    {
        $url = EmailSkin::ASSET_BASE . '/' . trim((string) $page->section, '/') . '/' . $page->slug;
        $hero = json_decode((string) $page->heroImage, true) ?: [];
        $blocks = json_decode((string) $page->blocks, true) ?: [];

        // The stories first, so their "what it means" can be put in Taglish in one ask.
        $found = [];
        $n = count($blocks);
        for ($i = 0; $i < $n && count($found) < self::MAX_STORIES; $i++) {
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
            $found[] = ['title' => $title, 'text' => $text, 'means' => $means, 'meansTl' => $meansTl, 'source' => $source];
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
                'source' => $s['source'], 'url' => $url, 'label' => 'Basahin sa anee.io'];
            $stories++;
            if ($stories < self::MAX_STORIES) {
                $out[] = ['id' => 'div' . $stories, 'type' => 'divider', 'style' => 'line'];
            }
        }
        if (($out[count($out) - 1]['type'] ?? '') === 'divider') {
            array_pop($out);
        }
        $out[] = ['id' => 'more', 'type' => 'button', 'label' => 'Basahin ang buong balita', 'url' => $url, 'align' => 'center', 'style' => 'solid'];
        // Anee opens it (Voice): a greeting, how many, and why it matters to the farm.
        $count = [1 => 'Isang balita', 2 => 'Dalawang balita', 3 => 'Tatlong balita', 4 => 'Apat na balita', 5 => 'Limang balita'][$stories] ?? 'Ilang balita';
        array_unshift($out, [
            'id' => 'intro', 'type' => 'text',
            'html' => '<p>Hey there, Anee here... may balita sa bukid ngayong araw.</p><p>' . $count . ' ngayong ' . self::month($day) . ' ' . $day->format('j') . ' ang may kinalaman sa bukid. Hindi ko na pahahabain: ito ang nangyari, at ito ang ibig sabihin para sa\'yo.</p>',
        ]);
        $out[] = ['id' => 'today', 'type' => 'text',
            'html' => '<p>Isang tanong lang para sa\'yo ngayong araw: alin dito ang tatama sa susunod mong gagawin sa bukid? Doon ka magsimula.</p>'];
        $out[] = ['id' => 'signoff', 'type' => 'text', 'html' => '<p>' . Voice::signOffHtml('balita muna bago kape') . '</p>'];
        $out[] = ['id' => 'ps', 'type' => 'text', 'size' => 'sm',
            'html' => '<p><b>P.S.</b> May tanong ka kung paano ito tatama sa tanim mo? I-reply mo lang ito, o itanong mo sa akin sa <a href="https://anee.io/">anee.io</a>.</p>'];

        $content = [
            'kicker' => 'Balita sa bukid · ' . $day->format('M j'),
            'headline' => (string) $page->title,
            'face' => '',
            'hero' => ['src' => (string) ($hero['src'] ?? ''), 'alt' => (string) ($hero['alt'] ?? ''), 'link' => $url],
            'blocks' => $out,
        ];

        $email = AsNlEmail::create([
            'priority' => 'news', 'position' => 0, 'kind' => 'builder',
            'title' => 'Farm news · ' . $day->format('M j'),
            'subject' => mb_substr('Balita sa bukid: ' . $page->title, 0, 250),
            'preheader' => mb_substr((string) ($page->excerpt ?? ''), 0, 200),
            'blocks' => Renderer::clean($content),
            'audience' => [Seg::ALL],
            'newsDate' => $day->toDateString(),
            'newsPageId' => (int) $page->id,
            'status' => $status,
            'note' => 'Made from today\'s news page by itself.',
            'createdBy' => 'News roundup',
            'deleteStatus' => 1,
        ]);
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
