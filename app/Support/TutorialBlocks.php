<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The block vocabulary shared by the page builder and the page.
 *
 * Deliberately small: every kind here is something a person writing a how-to
 * actually reaches for, and nothing here can carry markup of its own. The
 * builder drags these around; this class turns them into the page. A block it
 * has never heard of is skipped rather than guessed at, so an older app can
 * always render a page a newer builder wrote.
 */
class TutorialBlocks
{
    /** Kind => what the builder should call it. */
    public const KINDS = [
        'heading' => 'Heading',
        'text' => 'Paragraph',
        'steps' => 'Numbered steps',
        'tips' => 'Bullet list',
        'callout' => 'Callout',
        'image' => 'Image',
        'video' => 'Video (YouTube)',
        'divider' => 'Divider',
    ];

    /**
     * @param  array<int, array<string, mixed>>|null  $blocks
     */
    public static function render(?array $blocks): string
    {
        $out = '';
        foreach ($blocks ?? [] as $b) {
            $out .= self::one(is_array($b) ? $b : []);
        }

        return $out;
    }

    private static function one(array $b): string
    {
        $kind = (string) ($b['kind'] ?? '');
        $text = trim((string) ($b['text'] ?? ''));
        $e = fn ($v) => e((string) $v);

        switch ($kind) {
            case 'heading':
                return $text === '' ? '' : '<h2 class="tut-h">' . $e($text) . '</h2>';

            case 'text':
                // Paragraphs, not a blob: an empty line is where a writer meant
                // to draw breath, and the builder stores exactly what was typed.
                if ($text === '') {
                    return '';
                }
                $paras = preg_split('~\n\s*\n~', $text) ?: [];

                return implode('', array_map(
                    fn ($p) => '<p class="tut-p">' . nl2br($e(trim($p))) . '</p>',
                    array_filter($paras, fn ($p) => trim($p) !== '')
                ));

            case 'steps':
            case 'tips':
                $items = array_values(array_filter(
                    array_map('trim', (array) ($b['items'] ?? [])),
                    fn ($i) => $i !== ''
                ));
                if (! $items) {
                    return '';
                }
                $tag = $kind === 'steps' ? 'ol' : 'ul';
                $cls = $kind === 'steps' ? 'tut-steps' : 'tut-tips';

                return "<$tag class=\"$cls\">"
                    . implode('', array_map(fn ($i) => '<li>' . $e($i) . '</li>', $items))
                    . "</$tag>";

            case 'callout':
                if ($text === '') {
                    return '';
                }
                $tone = in_array($b['tone'] ?? '', ['warn', 'good'], true) ? $b['tone'] : 'note';

                return '<div class="tut-callout tut-' . $tone . '">'
                    . ($e($b['title'] ?? '') !== '' ? '<strong>' . $e($b['title']) . '</strong>' : '')
                    . '<span>' . nl2br($e($text)) . '</span></div>';

            case 'image':
                $src = self::safeUrl((string) ($b['url'] ?? ''));

                return $src === '' ? '' : '<figure class="tut-figure"><img src="' . $e($src) . '" alt="'
                    . $e($b['caption'] ?? '') . '" loading="lazy">'
                    . (trim((string) ($b['caption'] ?? '')) !== ''
                        ? '<figcaption>' . $e($b['caption']) . '</figcaption>' : '')
                    . '</figure>';

            case 'video':
                $id = self::youtubeId((string) ($b['url'] ?? ''));

                return $id === '' ? '' : '<div class="tut-video"><iframe src="https://www.youtube.com/embed/'
                    . $e($id) . '" title="Tutorial video" loading="lazy" allowfullscreen'
                    . ' allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture"></iframe></div>';

            case 'divider':
                return '<hr class="tut-hr">';
        }

        return '';
    }

    /** Only http(s) and same-origin paths — a block never becomes a javascript: link. */
    private static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return (Str::startsWith($url, ['http://', 'https://', '/'])) ? $url : '';
    }

    /** The id out of any of the shapes a YouTube link comes in. */
    public static function youtubeId(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (preg_match('~(?:youtu\.be/|v=|/embed/|/shorts/)([A-Za-z0-9_-]{6,20})~', $url, $m)) {
            return $m[1];
        }

        return preg_match('~^[A-Za-z0-9_-]{6,20}$~', $url) ? $url : '';
    }
}
