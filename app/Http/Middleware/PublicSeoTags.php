<?php

namespace App\Http\Middleware;

use App\Support\ImageWords;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The public site's share and search tags, never missing (2026-10-07 SEO
 * audit). A page that writes its own canonical, Open Graph or Twitter tags
 * keeps them; whatever it left out is filled from its own title and
 * description, with the default share picture. Every public page also
 * carries the Organization and WebSite graph, and every picture with alt
 * words gets the same words as its title. The app, the admin and anything
 * that is not an HTML page pass straight through.
 */
class PublicSeoTags
{
    private const SKIP = ['app', 'admin', 'mother-api', 'cron', 'deploy-check', 'sitemap.xml', 'storage', 'tools', 'api', 'share'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        try {
            if (! $request->isMethod('GET') || $response->getStatusCode() !== 200
                || $response instanceof StreamedResponse || $response instanceof BinaryFileResponse
                || ! str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
                return $response;
            }
            $first = strtolower(explode('/', trim($request->path(), '/'))[0] ?? '');
            if (in_array($first, self::SKIP, true)) {
                return $response;
            }
            $html = (string) $response->getContent();
            if ($html === '' || stripos($html, '</head>') === false) {
                return $response;
            }
            $response->setContent(self::fill($html, $request));
        } catch (\Throwable $e) {
            // The tags are a courtesy; the page is not.
        }

        return $response;
    }

    public static function fill(string $html, Request $request): string
    {
        $cut = stripos($html, '</head>');
        $head = substr($html, 0, $cut);
        $has = fn (string $re) => (bool) preg_match($re, $head);
        $attr = fn (string $s) => htmlspecialchars(html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
        preg_match('#<title>(.*?)</title>#si', $head, $t);
        $title = trim(html_entity_decode(strip_tags($t[1] ?? 'anee.io'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        preg_match('#<meta\s+name="description"\s+content="([^"]*)"#i', $head, $d);
        $desc = trim(html_entity_decode($d[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        preg_match('#<link\s+rel="canonical"\s+href="([^"]+)"#i', $head, $c);
        $canonical = $c[1] ?? $request->url();
        preg_match('#<meta\s+property="og:image"\s+content="([^"]+)"#i', $head, $i);
        $image = $i[1] ?? asset('images/site/og-default.jpg');

        $add = [];
        if (! $has('#rel="canonical"#i')) {
            $add[] = '<link rel="canonical" href="' . $attr($canonical) . '">';
        }
        $og = [
            'og:type' => 'website', 'og:site_name' => 'anee.io', 'og:locale' => 'en_PH', 'og:title' => $title,
            'og:description' => $desc, 'og:url' => $canonical, 'og:image' => $image,
        ];
        foreach ($og as $k => $v) {
            if ($v !== '' && ! $has('#property="' . preg_quote($k, '#') . '"#i')) {
                $add[] = '<meta property="' . $k . '" content="' . $attr($v) . '">';
            }
        }
        if (empty($i[1])) {
            $add[] = '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">';
            $add[] = '<meta property="og:image:alt" content="anee.io, the smart farm app for Filipino farmers">';
        }
        $tw = ['twitter:card' => 'summary_large_image', 'twitter:title' => $title, 'twitter:description' => $desc, 'twitter:image' => $image];
        foreach ($tw as $k => $v) {
            if ($v !== '' && ! $has('#name="' . preg_quote($k, '#') . '"#i')) {
                $add[] = '<meta name="' . $k . '" content="' . $attr($v) . '">';
            }
        }
        // Who publishes the site, on every page; a page with no graph of its own also says what it is.
        $root = rtrim(url('/'), '/');
        $graph = [
            ['@type' => 'Organization', '@id' => $root . '/#org', 'name' => 'anee.io', 'url' => $root, 'logo' => asset('images/site/logo-white.png'),
                'email' => 'support@anee.io', 'areaServed' => 'PH'],
            ['@type' => 'WebSite', '@id' => $root . '/#site', 'name' => 'anee.io', 'url' => $root, 'publisher' => ['@id' => $root . '/#org'], 'inLanguage' => 'en-PH'],
        ];
        if (stripos($html, 'application/ld+json') === false) {
            $graph[] = ['@type' => 'WebPage', '@id' => $canonical . '#page', 'url' => $canonical, 'name' => $title, 'description' => $desc,
                'isPartOf' => ['@id' => $root . '/#site'], 'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image]];
        }
        $add[] = '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';

        $html = substr($html, 0, $cut) . "\n    " . implode("\n    ", $add) . "\n" . substr($html, $cut);
        $html = self::slimStyles(self::hoistStyles($html));

        // Past a page's first screen (the early lift of partials.boot-veil), a picture that says nothing about
        // loading waits until it is near the screen (2026-11-18 PageSpeed: "defer offscreen images").
        $fold = strpos($html, 'window.__bootEarly && window.__bootEarly()');

        // A picture drawn as decoration (an empty alt) or with no alt at all
        // gets readable words (App\Support\ImageWords); a decorative one
        // stays out of a screen reader's way, as its empty alt kept it. Then
        // a picture with alt words carries them as its title too. Markup
        // inside a script (a string the page builds later) is left alone.
        return preg_replace_callback('#<script\b[^>]*>.*?</script>|<img\b([^>]*)>#is', function ($m) use ($fold) {
            if (strncasecmp($m[0][0], '<script', 7) === 0) {
                return $m[0][0];
            }
            $tag = $m[1][0];
            if ($fold !== false && $m[0][1] > $fold && ! preg_match('#\bloading\s*=#i', $tag)) {
                $tag .= ' loading="lazy"' . (preg_match('#\bdecoding\s*=#i', $tag) ? '' : ' decoding="async"');
            }
            if (! preg_match('#\balt="[^"]+"#i', $tag) && preg_match('#\bsrc="([^"]+)"#i', $tag, $s)
                && ($words = ImageWords::forSrc($s[1])) !== '') {
                $decor = (bool) preg_match('#\balt=""#i', $tag);
                $tag = ' alt="' . htmlspecialchars($words, ENT_QUOTES, 'UTF-8') . '"'
                    . ($decor && ! preg_match('#\baria-hidden=#i', $tag) ? ' aria-hidden="true"' : '')
                    . preg_replace('#\s*\balt=""#i', '', $tag);
            }
            if (preg_match('#\btitle\s*=#i', $tag) || ! preg_match('#\balt="([^"]+)"#i', $tag, $a)) {
                return '<img' . $tag . '>';
            }

            return '<img title="' . $a[1] . '"' . $tag . '>';
        }, $html, -1, $count, PREG_OFFSET_CAPTURE) ?? $html;
    }

    /**
     * The head's styles without their notes (2026-11-18 PageSpeed: "minify CSS", 33 KB of comments and indentation
     * on the home page alone). Comments go and runs of white space become one space; a quoted string is copied as it
     * is, so a "/*" inside content: or a url() is safe. Only the head: after hoistStyles every page style is there.
     */
    private static function slimStyles(string $html): string
    {
        $cut = stripos($html, '</head>');
        if ($cut === false) {
            return $html;
        }
        $head = preg_replace_callback('#(<style\b[^>]*>)(.*?)(</style>)#is', fn ($m) => $m[1] . self::slimCss($m[2]) . $m[3], substr($html, 0, $cut));

        return $head === null ? $html : $head . substr($html, $cut);
    }

    public static function slimCss(string $css): string
    {
        $out = '';
        $n = strlen($css);
        for ($i = 0; $i < $n; $i++) {
            $c = $css[$i];
            if ($c === '"' || $c === "'") {
                $j = $i + 1;
                while ($j < $n && $css[$j] !== $c) {
                    $j += $css[$j] === '\\' ? 2 : 1;
                }
                $out .= substr($css, $i, $j - $i + 1);
                $i = $j;
                continue;
            }
            if ($c === '/' && ($css[$i + 1] ?? '') === '*') {
                $end = strpos($css, '*/', $i + 2);
                if ($end === false) {
                    break;
                }
                $i = $end + 1;
                continue;
            }
            if (ctype_space($c)) {
                while ($i + 1 < $n && ctype_space($css[$i + 1])) {
                    $i++;
                }
                $last = substr($out, -1);
                $next = $css[$i + 1] ?? '';
                if ($last !== '' && ! in_array($last, ['{', '}', ';', ','], true) && ! in_array($next, ['{', '}', ';'], true)) {
                    $out .= ' ';
                }
                continue;
            }
            $out .= $c;
        }

        return trim($out);
    }

    /**
     * Every <style> in the body moves to the end of the head, in its order.
     * A markup check counts a <style> in the body an error, and many parts
     * ship their own (the forms, the finders, Anee's wait). The cascade is
     * the same: they came after the head's styles, and still do. A style
     * inside a script, a template or an SVG drawing stays where it is.
     */
    private static function hoistStyles(string $html): string
    {
        $cut = stripos($html, '</head>');
        if ($cut === false) {
            return $html;
        }
        $moved = [];
        $body = preg_replace_callback(
            '#<script\b[^>]*>.*?</script>|<template\b[^>]*>.*?</template>|<svg\b[^>]*>.*?</svg>|<textarea\b[^>]*>.*?</textarea>|<style\b[^>]*>.*?</style>#is',
            function ($m) use (&$moved) {
                if (strncasecmp($m[0], '<style', 6) !== 0) {
                    return $m[0];
                }
                $moved[] = $m[0];

                return '';
            },
            substr($html, $cut + 7)
        );
        if ($body === null || ! $moved) {
            return $html;
        }

        return substr($html, 0, $cut) . implode("\n", $moved) . "\n</head>" . $body;
    }
}
