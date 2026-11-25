<?php

namespace App\Support;

/**
 * The notes to ourselves stay on our side of the wire (2026-11-26).
 *
 * Every page carries its own styles and scripts, and they are written with
 * long comments. Those were sent to every phone, on every page: 29% of what
 * the dashboard weighs in transfer, 186 KB of a season's board. Blade already
 * drops its own comments when a view is compiled; this drops the CSS and the
 * JavaScript ones at the same moment, so it costs nothing when a page is asked
 * for (AppServiceProvider registers it as a precompiler).
 *
 * Careful on purpose, because a view is not parsed here, only read by line:
 *   - inside <style> and <script> only, and never in JSON or a template
 *   - a comment that has its lines to itself (in CSS also one that ends its
 *     line after a ; or a brace)
 *   - never one that holds Blade or PHP, and never a licence (/*!)
 * A line inside a JavaScript `template` that begins with // or /* would be
 * taken for a comment: there is none, and there must be none.
 */
final class LeanViews
{
    public static function strip(string $view): string
    {
        $out = '';
        $at = 0;
        while (preg_match('#<(style|script)\b([^>]*)>#i', $view, $m, PREG_OFFSET_CAPTURE, $at)) {
            $tag = strtolower($m[1][0]);
            $from = $m[0][1] + strlen($m[0][0]);
            $to = stripos($view, '</' . $tag, $from);
            if ($to === false) {
                break;
            }
            $body = substr($view, $from, $to - $from);
            $attrs = $m[2][0];
            $out .= substr($view, $at, $from - $at);
            if ($tag === 'style') {
                $out .= self::lines($body, true);
            } elseif (! preg_match('/\btype\s*=/i', $attrs) || preg_match('/\btype\s*=\s*["\']?(module|text\/javascript)/i', $attrs)) {
                $out .= self::lines($body, false);
            } else {
                $out .= $body;
            }
            $at = $to;
        }

        return $out . substr($view, $at);
    }

    /*
     * An empty line is left where a note stood. PHP swallows the line break
     * after a closing tag, so `const A = @json($a)` with no semicolon, a note,
     * and `const B` had only the note's own line between them.
     */
    private static function lines(string $code, bool $css): string
    {
        if (! str_contains($code, '/*') && ! str_contains($code, '//')) {
            return $code;
        }
        $lines = explode("\n", $code);
        $keep = [];
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            $line = $lines[$i];
            $t = ltrim($line);
            if (! $css && str_starts_with($t, '//')) {
                if (self::ours($t)) {
                    $keep[] = '';

                    continue;
                }
            } elseif (str_starts_with($t, '/*') && ! str_starts_with($t, '/*!')) {
                $note = '';
                for ($j = $i; $j < $n; $j++) {
                    $note .= ($j === $i ? $t : "\n" . $lines[$j]);
                    if (str_contains($j === $i ? substr($t, 2) : $lines[$j], '*/')) {
                        break;
                    }
                }
                // It must end its last line, and be one comment, not two with something between.
                if ($j < $n && strpos($note, '*/', 2) === strlen(rtrim($note)) - 2 && self::ours($note)) {
                    $i = $j;
                    $keep[] = '';

                    continue;
                }
                // Kept whole, so a line inside it is never read as one of its own.
                if ($j < $n) {
                    for (; $i < $j; $i++) {
                        $keep[] = $lines[$i];
                    }
                    $line = $lines[$j];
                }
            } elseif ($css && str_contains($line, '/*')
                && preg_match('~^(.*[;{}])[ \t]*(/\*(?:(?!\*/|/\*).)*\*/)[ \t]*(\r?)$~', $line, $m) && self::ours($m[2])) {
                $line = $m[1] . $m[3];
            }
            $keep[] = $line;
        }

        return implode("\n", $keep);
    }

    /** Nothing in it for Blade or PHP to read. */
    private static function ours(string $note): bool
    {
        return ! preg_match('/\{\{|\{!!|<\?|\?>|@[A-Za-z_]|##|<\/?(script|style)/i', $note);
    }
}
