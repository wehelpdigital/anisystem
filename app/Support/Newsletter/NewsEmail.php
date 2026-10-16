<?php

namespace App\Support\Newsletter;

use App\Models\AsNlEmail;
use App\Models\AsSiteSetting;
use App\Support\EmailSkin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TODAY'S FARM NEWS AS AN EMAIL (2026-10-17, the owner: "a process to create
 * the template and put it in the scheduled email with the 2nd priority").
 *
 * The news roundup (App\Services\NewsRoundup) writes a Latest in Agriculture
 * page every morning at 07:00 when the feeds have new stories. ensure() turns
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

    public static function fromPage(object $page, Carbon $day, string $status = 'queued'): AsNlEmail
    {
        $url = EmailSkin::ASSET_BASE . '/' . trim((string) $page->section, '/') . '/' . $page->slug;
        $hero = json_decode((string) $page->heroImage, true) ?: [];
        $blocks = json_decode((string) $page->blocks, true) ?: [];

        $out = [];
        $stories = 0;
        $n = count($blocks);
        for ($i = 0; $i < $n && $stories < self::MAX_STORIES; $i++) {
            $b = $blocks[$i];
            if (($b['type'] ?? '') !== 'heading' || (int) ($b['level'] ?? 0) !== 3) {
                continue;
            }
            $title = trim((string) ($b['text'] ?? ''));
            $text = '';
            $means = '';
            $source = '';
            for ($j = $i + 1; $j < $n; $j++) {
                $x = $blocks[$j];
                $type = $x['type'] ?? '';
                if ($type === 'heading' || $type === 'divider') {
                    break;
                }
                if ($type === 'text') {
                    $words = (string) ($x['text'] ?? '');
                    if (preg_match('~^\*\*([^*,]+?)(,[^*]*)?\.?\*\*~', $words, $m) && str_contains($words, '](')) {
                        $source = trim($m[1]);
                    } elseif ($text === '') {
                        $text = $words;
                    }
                } elseif ($type === 'callout' && $means === '') {
                    $means = (string) ($x['text'] ?? '');
                }
            }
            if ($title === '') {
                continue;
            }
            $html = '<p>' . self::md(self::clip($text, 260)) . '</p>';
            if ($means !== '') {
                $html .= '<p><b>Para sa bukid mo:</b> ' . self::md(self::clip($means, 300)) . '</p>';
            }
            $out[] = ['id' => 'story' . $stories, 'type' => 'story', 'src' => '', 'title' => $title, 'html' => $html,
                'source' => $source, 'url' => $url, 'label' => 'Basahin sa anee.io'];
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

        return AsNlEmail::create([
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
