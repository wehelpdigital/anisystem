<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * A search engine or AI answer engine, proven by where it comes from (2026-11-16, the owner: "the individual pests
 * page are not gated. user can easily read it").
 *
 * The members' guides used to send every visitor the whole text and only blur it, so reader mode, view source or a
 * translation showed it all. Now the text behind the wall goes only to these crawlers, which the structured data
 * tells about the wall (isAccessibleForFree false), so the pages still rank and AI answers can still cite them.
 *
 * A name in the user agent is never enough: anyone can type "Googlebot". Google, Bing and Apple are proven by a
 * reverse DNS lookup that resolves back to the same address; OpenAI and Perplexity by the address lists they publish.
 * Each answer is kept a day per address (an hour when it failed), and the day's counts show on /deploy-check.
 */
final class Crawler
{
    private const BOTS = [
        'google' => ['ua' => '/Googlebot|Google-InspectionTool|GoogleOther|AdsBot-Google|Mediapartners-Google|APIs-Google|Storebot-Google/i',
            'hosts' => ['.googlebot.com', '.google.com']],
        'bing' => ['ua' => '/bingbot|BingPreview|msnbot|adidxbot/i', 'hosts' => ['.search.msn.com']],
        'apple' => ['ua' => '/Applebot/i', 'hosts' => ['.applebot.apple.com']],
        'openai' => ['ua' => '/GPTBot|OAI-SearchBot|ChatGPT-User/i',
            'ranges' => ['https://openai.com/gptbot.json', 'https://openai.com/searchbot.json', 'https://openai.com/chatgpt-user.json']],
        'perplexity' => ['ua' => '/PerplexityBot|Perplexity-User/i',
            'ranges' => ['https://www.perplexity.ai/perplexitybot.json', 'https://www.perplexity.ai/perplexity-user.json']],
    ];

    private static ?bool $memo = null;

    /** This request comes from a proven crawler. Never throws: unsure is no. */
    public static function trusted(?Request $request = null): bool
    {
        if ($request === null && self::$memo !== null) {
            return self::$memo;
        }
        $answer = false;
        try {
            $request ??= request();
            $ua = (string) $request->userAgent();
            // Cloudflare stands in front of the site and writes the visitor's own address here.
            $ip = (string) ($request->header('CF-Connecting-IP') ?: $request->ip());
            foreach (self::BOTS as $key => $bot) {
                if ($ip !== '' && preg_match($bot['ua'], $ua)) {
                    $answer = self::proven($key, $bot, $ip);
                    break;
                }
            }
        } catch (\Throwable) {
            $answer = false;
        }

        return self::$memo = $answer;
    }

    /** The name a proven crawler goes by, for the day's counts. */
    public static function name(?Request $request = null): ?string
    {
        $ua = (string) ($request ?? request())->userAgent();
        foreach (self::BOTS as $key => $bot) {
            if (preg_match($bot['ua'], $ua)) {
                return $key;
            }
        }

        return null;
    }

    /** Today's proven and refused crawler visits, by engine: the check after a deploy that Google still gets in. */
    public static function stats(): array
    {
        return (array) Cache::get('crawler:stats:' . now('Asia/Manila')->toDateString(), []);
    }

    private static function proven(string $key, array $bot, string $ip): bool
    {
        $cacheKey = 'crawler:' . $key . ':' . $ip;
        $kept = Cache::get($cacheKey);
        if ($kept !== null) {
            return (bool) $kept;
        }
        $ok = isset($bot['hosts']) ? self::reverseDns($ip, $bot['hosts']) : self::inRanges($ip, $bot['ranges']);
        Cache::put($cacheKey, $ok ? 1 : 0, $ok ? now()->addDay() : now()->addHour());
        self::count($key, $ok);

        return $ok;
    }

    /** The address names a host under one of these domains, and that host names the same address back. */
    public static function reverseDns(string $ip, array $suffixes): bool
    {
        $host = @gethostbyaddr($ip);
        if (! is_string($host) || $host === $ip) {
            return false;
        }
        $host = strtolower(rtrim($host, '.'));
        $under = false;
        foreach ($suffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                $under = true;
                break;
            }
        }
        if (! $under) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $back = array_column((array) @dns_get_record($host, DNS_AAAA), 'ipv6');

            return in_array(inet_pton($ip), array_map(fn ($a) => @inet_pton($a), $back), true);
        }

        return in_array($ip, (array) @gethostbynamel($host), true);
    }

    /** The address sits in one of the lists the engine publishes (kept a day). */
    private static function inRanges(string $ip, array $urls): bool
    {
        $key = 'crawler:ranges:' . md5(implode('|', $urls));
        $cidrs = Cache::get($key);
        if (! is_array($cidrs)) {
            $cidrs = [];
            foreach ($urls as $url) {
                $json = rescue(fn () => Http::timeout(4)->get($url)->json(), null, false);
                foreach ((array) ($json['prefixes'] ?? []) as $p) {
                    $c = $p['ipv4Prefix'] ?? $p['ipv6Prefix'] ?? null;
                    if (is_string($c) && $c !== '') {
                        $cidrs[] = $c;
                    }
                }
            }
            // A list that could not be read is asked for again soon, not a day later.
            Cache::put($key, $cidrs, $cidrs ? now()->addDay() : now()->addMinutes(10));
        }

        return $cidrs !== [] && IpUtils::checkIp($ip, $cidrs);
    }

    private static function count(string $key, bool $ok): void
    {
        rescue(function () use ($key, $ok) {
            $k = 'crawler:stats:' . now('Asia/Manila')->toDateString();
            $s = (array) Cache::get($k, []);
            $s[$key][$ok ? 'proven' : 'refused'] = ($s[$key][$ok ? 'proven' : 'refused'] ?? 0) + 1;
            Cache::put($k, $s, now()->addDays(3));
        }, null, false);
    }
}
