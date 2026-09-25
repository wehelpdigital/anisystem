<?php

namespace App\Support;

/**
 * Where the top-left back arrow goes.
 *
 * Most pages have one natural parent: a report sits under the reports shelf,
 * a module under its season's hub. Some pages can be opened from more than
 * one place. Global Notes, for one, is opened from the dashboard's Quick
 * tools, and Back used to take the member to the schedules list, a page they
 * had not been on. The page that opens such a door says where it is standing
 * with `?from=<origin>`, and the opened page asks this class where Back goes.
 *
 * Only the names below are honoured, so a `from` cannot be turned into an
 * open redirect. The older pages that already took a site path in `from`
 * (the AI Technician, a lot's map, help) keep working: a plain same-site path
 * is accepted too, and anything else falls back to the page's natural
 * parent.
 *
 * `carry()` keeps the origin on a page's own internal links, so
 * dashboard -> Protocol Builder -> an editor -> Back -> Back still ends on the
 * dashboard.
 */
final class BackTo
{
    /** The origins a door may name. */
    public const ORIGINS = ['dashboard', 'schedules', 'hub', 'reports', 'tags'];

    /** The origin this request came with, if it named one we know. */
    public static function key(): ?string
    {
        $from = (string) request()->query('from', '');

        return in_array($from, self::ORIGINS, true) ? $from : null;
    }

    /**
     * Where Back goes: the named origin, else a same-site path, else the
     * page's natural parent.
     *
     * `$scheduleId` is the season the hub/reports origins belong to; left
     * null, the request's own `id` is used (every season page carries it).
     */
    public static function url(string $fallback, $scheduleId = null): string
    {
        $key = self::key();
        if ($key !== null) {
            $sid = (int) ($scheduleId ?? request()->query('id', 0));

            $to = match ($key) {
                'dashboard' => route('app.dashboard'),
                'schedules' => route('sm.index'),
                'tags' => route('tags.global'),
                'hub' => $sid > 0 ? route('sm.hub', ['id' => $sid]) : null,
                'reports' => $sid > 0 ? route('sm.reports', ['id' => $sid]) : null,
                default => null,
            };
            // Never a back arrow that points at the page it is on.
            if ($to !== null && ! self::isHere($to)) {
                // The origin as it was left -- the Tags page with its tag
                // still open (?tag=), the gallery on its shelf -- when the
                // page before this one was that very origin.
                return self::sameAsReferer($to) ?? $to;
            }
        }

        $path = self::path();

        return ($path !== null && ! self::isHere($path)) ? $path : $fallback;
    }

    /**
     * Back to a list page, as the member left it.
     *
     * For a page that is opened both from its list and straight from
     * somewhere else (a discussion from the groups list, or from the
     * dashboard). A named origin wins. Otherwise, when the page before this
     * one was that list, Back returns to it with its own query, so a list
     * that was itself opened from the dashboard still knows it. Anything else
     * gets the plain list.
     */
    public static function parent(string $parentUrl, $scheduleId = null): string
    {
        if (self::key() !== null || self::path() !== null) {
            return self::url($parentUrl, $scheduleId);
        }

        $ref = (string) request()->headers->get('referer', '');
        if ($ref !== '') {
            $r = parse_url($ref);
            $p = parse_url($parentUrl);
            $here = request()->getHost();
            if (is_array($r) && is_array($p)
                && ($r['host'] ?? '') === $here
                && rtrim($r['path'] ?? '/', '/') === rtrim($p['path'] ?? '/', '/')) {
                return $ref;
            }
        }

        return $parentUrl;
    }

    /**
     * A same-site path from `from`, or null. A leading slash that is not
     * followed by a second slash or a backslash: browsers read both `//x` and
     * `/\x` as another host.
     */
    public static function path(): ?string
    {
        $from = (string) request()->query('from', '');
        if ($from === '' || $from[0] !== '/') {
            return null;
        }
        if (strlen($from) > 1 && ($from[1] === '/' || $from[1] === '\\')) {
            return null;
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $from)) {
            return null;
        }

        return $from;
    }

    /**
     * The Referer, when it is `$url`'s own page on this site (same path, same
     * season id) and not this page; otherwise null.
     */
    private static function sameAsReferer(string $url): ?string
    {
        $ref = (string) request()->headers->get('referer', '');
        if ($ref === '') {
            return null;
        }
        $r = parse_url($ref);
        $u = parse_url($url);
        if (! is_array($r) || ! is_array($u) || ($r['host'] ?? '') !== request()->getHost()) {
            return null;
        }
        parse_str($r['query'] ?? '', $rq);
        parse_str($u['query'] ?? '', $uq);
        if (rtrim($r['path'] ?? '/', '/') !== rtrim($u['path'] ?? '/', '/')
            || (string) ($rq['id'] ?? '') !== (string) ($uq['id'] ?? '')
            || self::isHere($ref)) {
            return null;
        }

        return $ref;
    }

    /** Whether `$url` is this very page (path and id alike). */
    private static function isHere(string $url): bool
    {
        $u = parse_url($url);
        if (! is_array($u)) {
            return false;
        }
        parse_str($u['query'] ?? '', $q);

        return rtrim($u['path'] ?? '/', '/') === rtrim('/' . ltrim(request()->path(), '/'), '/')
            && (string) ($q['id'] ?? '') === (string) request()->query('id', '');
    }

    /** `$url` with this request's origin added, when it has one. */
    public static function carry(string $url): string
    {
        $key = self::key();
        if ($key === null) {
            return $url;
        }

        return self::with($url, $key);
    }

    /** `$url` saying it was opened from `$key`. */
    public static function with(string $url, string $key): string
    {
        if (! in_array($key, self::ORIGINS, true)) {
            return $url;
        }
        $hash = '';
        if (($h = strpos($url, '#')) !== false) {
            $hash = substr($url, $h);
            $url = substr($url, 0, $h);
        }
        // Replace an existing from rather than stacking a second one.
        $url = preg_replace('/([?&])from=[^&]*(&|$)/', '$1', $url);
        $url = rtrim($url, '?&');

        return $url . (str_contains($url, '?') ? '&' : '?') . 'from=' . $key . $hash;
    }
}
