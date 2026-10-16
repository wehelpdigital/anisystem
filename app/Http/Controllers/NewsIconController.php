<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * A newsroom's small icon, for a news roundup's "Read it at" chips
 * (2026-10-16, the owner: "multiple small icons on the sources where they
 * can select or choose to read").
 *
 *   GET /news-icon/{host}      e.g. /news-icon/newsinfo.inquirer.net
 *
 * Fetched once from Google's favicon service (DuckDuckGo's when Google has
 * none) and kept for a month, so a reader's browser asks only anee.io, the
 * Tech Blog's cleaned article keeps a plain image, and a newsroom with no
 * icon gets its first letter instead of a broken picture. Only for the hosts
 * our news stories link to: this is no open door to fetch the web.
 */
class NewsIconController extends Controller
{
    private const DAYS = 30;

    public function show(string $host)
    {
        $host = strtolower((string) preg_replace('/^www\./i', '', $host));
        if (strlen($host) > 120 || ! preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/', $host) || ! $this->known($host)) {
            abort(404);
        }
        $icon = Cache::get('news-icon:' . $host);
        if (! is_array($icon)) {
            $icon = $this->fetch($host) ?? ['type' => 'image/svg+xml', 'body' => base64_encode(self::letter($host))];
            Cache::put('news-icon:' . $host, $icon, now()->addDays(self::DAYS));
        }

        return response(base64_decode($icon['body']), 200, [
            'Content-Type' => $icon['type'],
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** A host a news story of ours links to (its own address, or with www). */
    private function known(string $host): bool
    {
        return (bool) Cache::remember('news-icon:known:' . $host, 86400, fn () => DB::table('as_news_items')
            ->where(fn ($q) => $q->where('link', 'like', 'https://' . $host . '/%')->orWhere('link', 'like', 'https://www.' . $host . '/%')
                ->orWhere('link', 'like', 'http://' . $host . '/%')->orWhere('link', 'like', 'http://www.' . $host . '/%'))
            ->exists());
    }

    /** The newsroom's own icon, or null when neither service has one. */
    private function fetch(string $host): ?array
    {
        $tries = [
            ['https://www.google.com/s2/favicons', ['domain' => $host, 'sz' => 64]],
            ['https://icons.duckduckgo.com/ip3/' . $host . '.ico', []],
        ];
        foreach ($tries as [$url, $query]) {
            try {
                $r = Http::timeout(6)->withHeaders(['User-Agent' => 'anee.io news icons (+https://anee.io)'])->get($url, $query);
                $type = strtolower(trim(explode(';', (string) $r->header('Content-Type'))[0]));
                // Google answers a host it does not know with 404 and a stock globe: that is no icon.
                if ($r->status() === 200 && str_starts_with($type, 'image/') && $type !== 'image/svg+xml' && strlen($r->body()) > 80) {
                    return ['type' => $type, 'body' => base64_encode($r->body())];
                }
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    /** The newsroom's first letter on a colour of its own. */
    private static function letter(string $host): string
    {
        $parts = explode('.', $host);
        $word = count($parts) > 2 && in_array($parts[0], ['news', 'newsinfo', 'm', 'en', 'business', 'globalnation'], true) ? $parts[1] : $parts[0];
        $letter = strtoupper(substr(preg_replace('/[^a-z0-9]/', '', $word) ?: 'N', 0, 1));
        $hues = ['#4a7c2a', '#2563eb', '#b45309', '#7c3aed', '#0f766e', '#be123c', '#4338ca', '#a16207'];
        $fill = $hues[crc32($host) % count($hues)];

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="' . $fill . '"/>'
            . '<text x="32" y="43" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="32" font-weight="700" fill="#fff">' . $letter . '</text></svg>';
    }
}
