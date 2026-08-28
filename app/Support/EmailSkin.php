<?php

namespace App\Support;

/**
 * What an anee.io email looks like.
 *
 * Every template in the mother app stores only its MIDDLE — the greeting, the
 * sentences, the button, the table of the day's work. This wraps that in the
 * masthead, the card and the footer, so a message written in a builder cannot
 * accidentally arrive looking like a plain-text note, and so changing the
 * house style once changes every email.
 *
 * Written to the rules email clients actually keep rather than the ones the
 * web has: tables for layout, inline styles only, no flexbox, no external
 * stylesheet, and a width that survives a phone. Outlook ignores border-radius
 * and background-image; nothing here needs them to be legible.
 */
class EmailSkin
{
    public const GREEN = '#4a7c2a';
    public const DEEP = '#2f5219';
    public const INK = '#1f2937';
    public const MUTED = '#6b7280';
    public const LINE = '#e5e7eb';
    public const PAPER = '#f4f7f0';

    /**
     * Wrap a template's body in the house shell.
     *
     * @param  string  $inner  the template's own HTML
     * @param  string  $title  what stands in the masthead under the name
     */
    public static function wrap(string $inner, string $title = ''): string
    {
        $app = e(config('app.name', 'anee.io'));
        $year = date('Y');
        $sub = $title !== '' ? '<div style="margin-top:4px;font-size:13px;color:#d6e9bd;">' . e($title) . '</div>' : '';

        return <<<HTML
<!doctype html>
<html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$app}</title></head>
<body style="margin:0;padding:0;background:#f4f7f0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f7f0;padding:24px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <tr><td style="background:#2f5219;padding:20px 26px;">
    <div style="font-size:19px;font-weight:800;color:#ffffff;letter-spacing:-.2px;">🌱 {$app}</div>
    {$sub}
  </td></tr>
  <tr><td style="padding:26px;font-size:15px;line-height:1.62;color:#1f2937;">
    {$inner}
  </td></tr>
  <tr><td style="padding:16px 26px 22px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
    Sent by {$app}. If this was not meant for you, you can ignore it.<br>
    &copy; {$year} {$app}
  </td></tr>
</table>
</td></tr></table>
</body></html>
HTML;
    }

    /** The one button shape every email uses. */
    public static function button(string $label, string $url): string
    {
        $l = e($label);
        $u = e($url);

        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;">'
            . '<tr><td style="background:#4a7c2a;border-radius:9px;">'
            . '<a href="' . $u . '" style="display:inline-block;padding:12px 22px;font-size:15px;font-weight:700;'
            . 'color:#ffffff;text-decoration:none;">' . $l . '</a>'
            . '</td></tr></table>'
            . '<div style="font-size:12px;color:#6b7280;word-break:break-all;">Or open this link:<br>' . $u . '</div>';
    }

    /**
     * A quiet panel for a fact worth setting apart — a date, a lot, a total.
     */
    public static function panel(string $inner): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" '
            . 'style="background:#f4f7f0;border:1px solid #dbe7cd;border-radius:10px;margin:16px 0;">'
            . '<tr><td style="padding:14px 16px;font-size:14px;line-height:1.6;color:#1f2937;">' . $inner . '</td></tr>'
            . '</table>';
    }
}
