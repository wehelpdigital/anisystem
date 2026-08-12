<?php

namespace App\Support;

use App\Models\AsEmailTemplate;

/**
 * Fills in an email layout written in the mother app.
 *
 * The layout arrives as finished HTML with {{tags}} left in it — the builder
 * cannot know whose email this is, so the app that sends it substitutes. A
 * missing or empty template means "no layout was written", and the caller
 * falls back to its own view rather than sending a blank page.
 */
class MailTemplate
{
    /**
     * @param  array<string, string>  $values  tag name (no braces) => text
     */
    public static function render(string $templateKey, array $values): ?string
    {
        $template = AsEmailTemplate::forKey($templateKey);
        $html = trim((string) ($template->bodyHtml ?? ''));

        return $html === '' ? null : self::fill($html, $values);
    }

    /** The subject line, which carries the same tags. */
    public static function subject(string $templateKey, array $values, string $fallback): string
    {
        $template = AsEmailTemplate::forKey($templateKey);
        $subject = trim((string) ($template->subject ?? ''));

        return $subject === '' ? $fallback : self::fill($subject, $values);
    }

    /**
     * Substitute every {{tag}}. Unknown tags are emptied rather than left in —
     * a reader should never see the plumbing, and a tag the app has no value
     * for means the same thing as blank.
     */
    private static function fill(string $text, array $values): string
    {
        foreach ($values as $key => $value) {
            $text = str_replace('{{' . $key . '}}', (string) $value, $text);
        }

        return preg_replace('~\{\{\s*[a-z0-9_]+\s*\}\}~i', '', $text) ?? $text;
    }
}
