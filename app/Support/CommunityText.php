<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Renders stored community text (post/comment bodies) into safe display HTML:
 *  - HTML is escaped first, so nothing user-typed can inject markup.
 *  - @[Display Name](userId) mention tokens become profile links.
 *  - #hashtags become links to the hashtag page.
 * Newlines are preserved by the caller's `whitespace-pre-line` container.
 *
 * Mentions are stored as tokens (not bare @names) because the users table has
 * no unique handle — the composer's picker inserts @[Name](id) so rendering
 * and notifications are unambiguous.
 */
class CommunityText
{
    /** Matches a mention token: @[Any Name](123) */
    public const MENTION_RE = '/@\[([^\]]{1,80})\]\((\d+)\)/u';

    /** Matches a location token: @[Town, Province](loc:town-province) — legacy. */
    public const LOCATION_RE = '/@\[([^\]]{1,80})\]\(loc:([a-z0-9\-]{1,80})\)/u';

    /** Matches a location pin token: 📍[Town, Province] — the current form. */
    public const PIN_RE = '/📍\s*\[([^\]]{1,90})\]/u';

    /** Inline map-pin icon for rendered location links (inherits link colour). */
    private const MAP_ICON = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="display:inline-block;width:1em;height:1em;vertical-align:-0.14em;margin-right:.1em"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/></svg>';

    /** Matches a hashtag: #word (letters, digits, underscore), not mid-word/URL. */
    public const HASHTAG_RE = '/(?<![\w\/&#])#([\p{L}0-9_]{1,50})/u';

    public static function render(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        // Escape everything first — user text can never become live markup.
        $safe = e($raw);

        // 📍[Town, Province] → bold map link to the location page. The slug is
        // derived from the label (matches how the gazetteer is slugged).
        $safe = preg_replace_callback(self::PIN_RE, function ($m) {
            $label = $m[1];
            $url = route('community.location', ['slug' => \Illuminate\Support\Str::slug($label)]);
            return '<a href="' . $url . '" class="location-link">' . self::MAP_ICON . $label . '</a>';
        }, $safe);

        // Legacy @[Town, Province](loc:slug) tokens → same bold map link.
        $safe = preg_replace_callback(self::LOCATION_RE, function ($m) {
            $url = route('community.location', ['slug' => $m[2]]);
            return '<a href="' . $url . '" class="location-link">' . self::MAP_ICON . $m[1] . '</a>';
        }, $safe);

        // @[Name](id) → profile link. Name is already escaped inside $safe.
        $safe = preg_replace_callback(self::MENTION_RE, function ($m) {
            $url = route('community.connect.profile', ['userId' => (int) $m[2]]);
            return '<a href="' . $url . '" class="mention-link">@' . $m[1] . '</a>';
        }, $safe);

        // #hashtag → hashtag page. Tag chars are safe for the URL.
        $safe = preg_replace_callback(self::HASHTAG_RE, function ($m) {
            $tag = $m[1];
            $url = route('community.hashtag', ['tag' => $tag]);
            return '<a href="' . $url . '" class="hashtag-link">#' . $tag . '</a>';
        }, $safe);

        return $safe;
    }

    /**
     * Render a body that may contain WYSIWYG HTML (group topics). If it has
     * HTML tags, sanitize with a strict allow-list and return it raw; if it's
     * plain text, fall back to the mention/hashtag linkifier. Safe either way.
     */
    public static function safeHtml(?string $raw): string
    {
        $raw = (string) $raw;
        if (trim($raw) === '') {
            return '';
        }
        if ($raw === strip_tags($raw)) {
            return static::render($raw);   // plain text → linkify mentions/hashtags
        }

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,s,ol,ul,li,a[href|title],h3,h4,blockquote,span');
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Cache.DefinitionImpl', null);

        return (new \HTMLPurifier($config))->purify($raw);
    }

    /**
     * The same text as plain words, for places that cannot show markup.
     *
     * A notification preview is text in a small grey line — no HTML, no
     * links — so render() is the wrong tool and the raw token is worse: it
     * reads as "@[admin john tugare](24)", which is a name wearing the
     * machinery that found it. Here the tokens become what they name.
     *
     * Also strips any HTML, because some bodies (a note, a whiteboard save)
     * arrive as rich text rather than as composer text.
     */
    public static function plain(?string $raw, int $limit = 0): string
    {
        $text = (string) $raw;
        if ($text === '') {
            return '';
        }

        $text = preg_replace(self::MENTION_RE, '@$1', $text);
        $text = preg_replace(self::LOCATION_RE, '$1', $text);
        $text = preg_replace(self::PIN_RE, '$1', $text);

        // <br> and </p> are line breaks before tags are stripped, or two
        // paragraphs become one run-on word.
        $text = preg_replace('~<br\s*/?>|</p>|</div>~i', ' ', $text);
        $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text);

        return $limit > 0 ? \Illuminate\Support\Str::limit($text, $limit) : $text;
    }

    /**
     * Extract the distinct user ids mentioned in a body (from @[Name](id)
     * tokens), for notifications. Only returns ids of real, active users.
     */
    public static function mentionedUserIds(?string $raw): array
    {
        if (! preg_match_all(self::MENTION_RE, (string) $raw, $matches)) {
            return [];
        }
        $ids = array_values(array_unique(array_map('intval', $matches[2])));
        if (empty($ids)) {
            return [];
        }

        return User::where('deleteStatus', 1)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Extract distinct lowercased hashtags from a body (no leading #).
     */
    public static function hashtags(?string $raw): array
    {
        if (! preg_match_all(self::HASHTAG_RE, (string) $raw, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map('mb_strtolower', $matches[1])));
    }

    /**
     * Extract distinct location slugs referenced by @[Town, Province](loc:slug)
     * tokens in a body.
     */
    public static function locations(?string $raw): array
    {
        $slugs = [];
        if (preg_match_all(self::PIN_RE, (string) $raw, $m)) {
            foreach ($m[1] as $label) {
                $slugs[] = \Illuminate\Support\Str::slug($label);
            }
        }
        if (preg_match_all(self::LOCATION_RE, (string) $raw, $m2)) {
            foreach ($m2[2] as $slug) {
                $slugs[] = mb_strtolower($slug);
            }
        }

        return array_values(array_unique(array_filter($slugs)));
    }

    /**
     * The first YouTube video id referenced in a body, or null. Handles
     * watch?v=, youtu.be/, /embed/ and /shorts/ URL shapes.
     */
    public static function youtubeId(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $re = '~(?:youtube\.com/(?:watch\?(?:[^ ]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~i';
        if (preg_match($re, $raw, $m)) {
            return $m[1];
        }

        return null;
    }
}
