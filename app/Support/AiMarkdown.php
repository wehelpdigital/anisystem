<?php

namespace App\Support;

/**
 * Renders the light markdown the models answer in — bold, italics, bullets,
 * numbered lists, paragraphs — and nothing else.
 *
 * Everything is escaped first and the allow-list is put back afterwards, so no
 * markup a model emits can reach the page. Must stay in step with
 * renderAnswer() in resources/views/ai/index.blade.php.
 */
class AiMarkdown
{
    public static function toHtml(?string $text): string
    {
        $escaped = e((string) $text);
        $lines = preg_split('/\r?\n/', $escaped) ?: [];

        $html = '';
        $list = null;

        $closeList = function () use (&$html, &$list) {
            if ($list !== null) {
                $html .= "</{$list}>";
                $list = null;
            }
        };

        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '') {
                $closeList();
                continue;
            }

            if (preg_match('/^[-*•]\s+(.*)$/u', $line, $m)) {
                if ($list !== 'ul') {
                    $closeList();
                    $html .= '<ul>';
                    $list = 'ul';
                }
                $html .= '<li>'.self::inline($m[1]).'</li>';
            } elseif (preg_match('/^\d+[.)]\s+(.*)$/u', $line, $m)) {
                if ($list !== 'ol') {
                    $closeList();
                    $html .= '<ol>';
                    $list = 'ol';
                }
                $html .= '<li>'.self::inline($m[1]).'</li>';
            } else {
                $closeList();
                $html .= '<p>'.self::inline($line).'</p>';
            }
        }
        $closeList();

        return $html !== '' ? $html : '<p></p>';
    }

    private static function inline(string $s): string
    {
        $s = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $s);

        return preg_replace('/(^|\s)\*([^*]+)\*(?=\s|$|[.,;:!?])/u', '$1<em>$2</em>', $s);
    }
}
