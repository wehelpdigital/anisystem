<?php

namespace App\Support;

/**
 * What an anee.io email looks like.
 *
 * Every template stores its whole body — the builder in the mother app edits
 * the stored row — so this is what BUILDS those bodies: the dark green
 * masthead with the wordmark and one of Anee's faces, the white card, the
 * buttons, the tidy footer. Change the house style here, re-run the seeder,
 * and every template nobody has rewritten by hand takes the new look.
 *
 * Written to the rules email clients actually keep rather than the ones the
 * web has:
 *  - tables for layout, inline styles on everything that matters, 600px wide
 *    and fluid below that, so a phone gets the same email as a desk;
 *  - the <style> block only ADDS (phone padding, dark-mode colours for the
 *    clients that honour prefers-color-scheme) — strip it, as some clients
 *    do, and the email still reads properly;
 *  - Outlook ignores border-radius and gradients: every gradient sits on a
 *    solid bgcolor, and the button is padded for Word's renderer too;
 *  - images are absolute URLs on the LIVE site, because a stored template is
 *    sent from wherever it is sent and a local address would be a broken
 *    image in every inbox. Every image has alt text and fixed dimensions, so
 *    a client that blocks images still lays the email out.
 *
 * Body text inside a template carries no colour of its own: it inherits from
 * the card, which is what lets a dark-mode client repaint it. Anything that
 * must carry a colour takes one of the ae-* classes so dark mode can find it.
 */
class EmailSkin
{
    /** Where the pictures live. Never APP_URL: that is anisystem.test here. */
    public const ASSET_BASE = 'https://anee.io';

    /* The house palette. DARK/GOLD/LEAF are the logo's own colours. */
    public const DARK = '#2B3A1C';
    public const GOLD = '#E8BE1C';
    public const LEAF = '#87B84C';

    /* Kept under their old names: controllers that build parts of an email
     * reach for these. */
    public const GREEN = '#3d6b22';
    public const DEEP = '#2B3A1C';
    public const INK = '#1f2a17';
    public const MUTED = '#5f6b55';
    public const LINE = '#e3eadb';
    public const PAPER = '#eef2e8';

    private const FONT = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

    /** An absolute address for a file in public/, tagged so caches refresh. */
    public static function asset(string $path): string
    {
        return self::ASSET_BASE . '/' . ltrim($path, '/') . '?v=anee';
    }

    /** One of Anee's faces (public/images/anee/emoji/{name}.png, 128px). */
    public static function face(string $name): string
    {
        $name = preg_replace('~[^a-z0-9\-]~', '', strtolower($name)) ?: 'happy';

        return self::asset('images/anee/emoji/' . $name . '.png');
    }

    /**
     * Wrap a template's words in the house shell.
     *
     * @param  string  $inner  the template's own HTML
     * @param  string  $title  the headline in the masthead
     * @param  array{face?:string|null, eyebrow?:string, preheader?:string, why?:string}  $opt
     *         face: which of Anee's faces sits in the masthead (null for none);
     *         eyebrow: the small gold line above the headline;
     *         preheader: the line an inbox shows beside the subject;
     *         why: one sentence in the footer saying why this came.
     */
    public static function wrap(string $inner, string $title = '', array $opt = []): string
    {
        $font = self::FONT;
        $logo = self::asset('images/logo-white.png');
        $mark = self::asset('images/logo-mark.png');
        $home = self::ASSET_BASE;

        $face = array_key_exists('face', $opt) ? $opt['face'] : 'happy';
        $eyebrow = trim((string) ($opt['eyebrow'] ?? ''));
        $pre = trim((string) ($opt['preheader'] ?? ''));
        $why = trim((string) ($opt['why'] ?? '')) ?: 'You are getting this because of your anee.io account. If it was not meant for you, you can ignore it.';

        // The preview line, padded so the inbox does not fill the rest of it
        // with whatever text comes next ("anee.io anee.io Hi Juan…").
        $preheader = $pre === '' ? '' :
            '<div class="ae-pre" style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'
            . $pre . str_repeat('&#8199;&#65279;&#847; ', 40) . '</div>';

        $eyebrowHtml = $eyebrow === '' ? '' :
            '<div style="margin:0 0 6px;font-family:' . $font . ';font-size:12px;line-height:1.4;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#E8BE1C;">'
            . $eyebrow . '</div>';

        $titleHtml = $title === '' ? '' :
            '<h1 class="ae-h1" style="margin:0;font-family:' . $font . ';font-size:26px;line-height:1.25;font-weight:800;letter-spacing:-.2px;color:#ffffff;">'
            . $title . '</h1>';

        $faceCell = $face ? '<td class="ae-face" width="76" align="right" valign="bottom" style="padding:0 0 0 12px;">'
            . '<img src="' . self::face($face) . '" width="76" height="76" alt="Anee" '
            . 'style="display:block;width:76px;height:76px;border:0;outline:none;text-decoration:none;border-radius:18px;">'
            . '</td>' : '';

        return <<<HTML
<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="format-detection" content="telephone=no,address=no,email=no,date=no">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>anee.io</title>
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
<style>
:root{color-scheme:light dark;supported-color-schemes:light dark;}
body{margin:0;padding:0;width:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}
table{border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;}
img{-ms-interpolation-mode:bicubic;}
.ae-body p{margin:0 0 16px;}
.ae-body a{color:#3d6b22;}
@media only screen and (max-width:620px){
  .ae-outer{padding:12px 8px 20px !important;}
  .ae-pad{padding-left:22px !important;padding-right:22px !important;}
  .ae-head{padding:22px 22px 20px !important;}
  .ae-h1{font-size:22px !important;}
  .ae-face img{width:60px !important;height:60px !important;}
}
@media only screen and (max-width:480px){
  .ae-btn,.ae-btn tbody,.ae-btn tr,.ae-btn td,.ae-btn a{display:block !important;width:100% !important;box-sizing:border-box;text-align:center !important;}
}
@media (prefers-color-scheme:dark){
  .ae-bg{background:#0f150b !important;}
  .ae-card{background:#1a2413 !important;border-color:#2e3d22 !important;}
  .ae-body,.ae-body p,.ae-body li,.ae-body td{color:#e6ecdd !important;}
  .ae-body a{color:#bfe08f !important;}
  .ae-body .ae-btn a,.ae-body .ae-btn span{color:#2B3A1C !important;}
  .ae-muted,.ae-body .ae-muted{color:#a8b59a !important;}
  .ae-ink,.ae-body .ae-ink{color:#eef3e7 !important;}
  .ae-panel{background:#233119 !important;border-color:#3a4d2c !important;}
  .ae-panel-gold{background:#2e2a12 !important;border-color:#5b4e17 !important;}
  .ae-panel-alert{background:#321c17 !important;border-color:#5e3228 !important;}
  .ae-line{border-color:#2e3d22 !important;}
  .ae-task{border-left-color:#6f9a3e !important;}
  .ae-foot,.ae-foot a{color:#94a386 !important;}
}
[data-ogsc] .ae-card{background:#1a2413 !important;}
[data-ogsc] .ae-body,[data-ogsc] .ae-body p{color:#e6ecdd !important;}
</style>
</head>
<body class="ae-bg" style="margin:0;padding:0;background:#eef2e8;">
{$preheader}
<table role="presentation" class="ae-bg" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#eef2e8" style="background:#eef2e8;">
<tr><td class="ae-outer" align="center" style="padding:28px 12px 32px;">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

  <!-- masthead -->
  <tr><td class="ae-head" bgcolor="#2B3A1C" style="background-color:#2B3A1C;background-image:linear-gradient(135deg,#1c2712 0%,#2B3A1C 48%,#44632a 100%);border-radius:18px 18px 0 0;padding:26px 32px 24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr><td colspan="2" style="padding:0 0 18px;">
        <a href="{$home}" style="text-decoration:none;"><img src="{$logo}" width="138" height="21" alt="anee.io" style="display:block;width:138px;height:21px;border:0;outline:none;color:#ffffff;font-family:{$font};font-size:18px;font-weight:800;"></a>
      </td></tr>
      <tr>
        <td valign="bottom">{$eyebrowHtml}{$titleHtml}</td>
        {$faceCell}
      </tr>
    </table>
  </td></tr>
  <tr><td height="4" bgcolor="#E8BE1C" style="height:4px;line-height:4px;font-size:0;background-color:#E8BE1C;background-image:linear-gradient(90deg,#E8BE1C 0%,#87B84C 100%);">&nbsp;</td></tr>

  <!-- the words -->
  <tr><td class="ae-card ae-body ae-pad" bgcolor="#ffffff" style="background:#ffffff;padding:30px 32px 8px;font-family:{$font};font-size:16px;line-height:1.6;color:#1f2a17;">
<!-- ===== The words start here. Everything above and below is the anee.io frame. ===== -->
{$inner}
<!-- ===== The words end here. ===== -->
  </td></tr>

  <!-- sign-off -->
  <tr><td class="ae-card ae-pad" bgcolor="#ffffff" style="background:#ffffff;border-radius:0 0 18px 18px;padding:18px 32px 26px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr><td class="ae-line" style="border-top:1px solid #e3eadb;padding-top:18px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
          <td valign="middle" style="padding-right:12px;"><img src="{$mark}" width="40" height="23" alt="" style="display:block;width:40px;height:23px;border:0;"></td>
          <td valign="middle" class="ae-muted" style="font-family:{$font};font-size:13px;line-height:1.5;color:#5f6b55;">
            <strong class="ae-ink" style="color:#2B3A1C;">anee.io</strong> &middot; your season, planned with Anee
          </td>
        </tr></table>
      </td></tr>
    </table>
  </td></tr>

  <!-- footer -->
  <tr><td class="ae-foot" align="center" style="padding:20px 24px 0;font-family:{$font};font-size:12px;line-height:1.7;color:#6f7a64;">
    <a href="{$home}" style="color:#4d5a41;text-decoration:underline;">anee.io</a>
    &nbsp;&middot;&nbsp; <a href="{$home}/contact" style="color:#4d5a41;text-decoration:underline;">Contact us</a>
    &nbsp;&middot;&nbsp; <a href="{$home}/legal/privacy" style="color:#4d5a41;text-decoration:underline;">Privacy</a><br>
    Questions? Write to <a href="mailto:support@anee.io" style="color:#4d5a41;text-decoration:underline;">support@anee.io</a><br>
    <span style="display:inline-block;margin-top:6px;">{$why}</span>
  </td></tr>

</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
HTML;
    }

    /**
     * The one button shape every email uses: a gold pill with dark words.
     * Padded twice — once for real clients, once (in the mso comments) for
     * Outlook's Word renderer, which ignores padding on a link.
     */
    public static function button(string $label, string $url, bool $withLink = true): string
    {
        $l = e($label);
        $u = e($url);
        $font = self::FONT;

        $html = '<table role="presentation" class="ae-btn" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px;">'
            . '<tr><td align="center" bgcolor="#E8BE1C" style="background:#E8BE1C;border-radius:999px;mso-padding-alt:0;">'
            . '<a href="' . $u . '" target="_blank" style="display:inline-block;padding:15px 30px;font-family:' . $font . ';'
            . 'font-size:16px;line-height:20px;font-weight:800;color:#2B3A1C;text-decoration:none;border-radius:999px;mso-padding-alt:0;">'
            . '<!--[if mso]><i style="letter-spacing:30px;mso-font-width:-100%;mso-text-raise:22pt;">&nbsp;</i><![endif]-->'
            . '<span style="mso-text-raise:11pt;color:#2B3A1C;">' . $l . '</span>'
            . '<!--[if mso]><i style="letter-spacing:30px;mso-font-width:-100%;">&nbsp;</i><![endif]-->'
            . '</a></td></tr></table>';

        if ($withLink) {
            $html .= self::linkFallback($url);
        }

        return $html;
    }

    /** The button's address as words, for the clients that flatten buttons. */
    public static function linkFallback(string $url): string
    {
        $u = e($url);

        return '<p class="ae-muted" style="margin:10px 0 18px;font-size:12.5px;line-height:1.5;color:#5f6b55;word-break:break-all;">'
            . 'If the button does not open, copy this link into your browser:<br>'
            . '<a href="' . $u . '" style="color:#3d6b22;text-decoration:underline;">' . $u . '</a></p>';
    }

    /**
     * A panel for what is worth setting apart — a date, an order, a note.
     *
     * @param  string  $tone  leaf (green), gold (heads-up) or alert (a problem)
     */
    public static function panel(string $inner, string $tone = 'leaf'): string
    {
        [$bg, $line, $cls] = match ($tone) {
            'gold' => ['#fdf6dc', '#efd98a', 'ae-panel ae-panel-gold'],
            'alert' => ['#fdf0ec', '#f1c6b9', 'ae-panel ae-panel-alert'],
            default => ['#f3f8ec', '#d6e6c2', 'ae-panel'],
        };

        return '<table role="presentation" class="' . $cls . '" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="' . $bg . '" '
            . 'style="background:' . $bg . ';border:1px solid ' . $line . ';border-radius:14px;border-collapse:separate;margin:18px 0;">'
            . '<tr><td style="padding:16px 18px;font-size:15px;line-height:1.6;">' . $inner . '</td></tr>'
            . '</table>';
    }

    /**
     * Label / value lines — an order, a plan, a job's facts.
     *
     * @param  array<string, string>  $rows  label => value (value is HTML)
     */
    public static function facts(array $rows, string $tone = 'leaf'): string
    {
        $out = '';
        $n = 0;
        foreach ($rows as $label => $value) {
            $top = $n++ ? 'border-top:1px solid #dfe9d2;' : '';
            $out .= '<tr>'
                . '<td class="ae-muted ae-line" width="112" valign="top" style="' . $top . 'width:112px;padding:9px 12px 9px 0;font-size:13px;line-height:1.5;color:#5f6b55;">' . e($label) . '</td>'
                . '<td class="ae-ink ae-line" valign="top" style="' . $top . 'padding:9px 0;font-size:15px;line-height:1.5;font-weight:700;color:#1f2a17;">' . $value . '</td>'
                . '</tr>';
        }

        return self::panel('<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $out . '</table>', $tone);
    }

    /** A small quiet line — the "if you did not expect this" kind. */
    public static function note(string $html): string
    {
        return '<p class="ae-muted" style="margin:18px 0 16px;font-size:13.5px;line-height:1.55;color:#5f6b55;">' . $html . '</p>';
    }

    /** A small uppercase label over a section. */
    public static function label(string $text): string
    {
        return '<div class="ae-muted" style="margin:0 0 4px;font-size:11.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#5f6b55;">'
            . e($text) . '</div>';
    }

    /**
     * One job in a list of work: title, a line of facts, a short description.
     * Everything passed in is already escaped by the caller.
     */
    public static function taskRow(string $title, string $meta = '', string $desc = '', string $when = ''): string
    {
        $badge = $when === '' ? '' :
            '<span style="display:inline-block;margin:0 8px 4px 0;padding:2px 9px;border-radius:999px;background:#E8BE1C;'
            . 'font-size:11px;line-height:16px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#2B3A1C;">' . $when . '</span>';

        return '<tr><td class="ae-line ae-task" style="padding:14px 0 14px 14px;border-bottom:1px solid #e3eadb;border-left:3px solid #87B84C;">'
            . $badge
            . '<div class="ae-ink" style="font-size:16px;line-height:1.4;font-weight:700;color:#1f2a17;">' . $title . '</div>'
            . ($meta !== '' ? '<div class="ae-muted" style="margin-top:4px;font-size:13px;line-height:1.5;color:#5f6b55;">' . $meta . '</div>' : '')
            . ($desc !== '' ? '<div style="margin-top:6px;font-size:14px;line-height:1.55;">' . $desc . '</div>' : '')
            . '</td></tr>';
    }

    /** Rows from taskRow() wrapped in their table. */
    public static function taskList(string $rows): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 18px;">'
            . $rows . '</table>';
    }

    /**
     * The plain-text twin of an email, for the multipart/alternative part.
     *
     * strip_tags() on the new shell would hand a reader the stylesheet, the
     * preview padding and the Outlook comments as prose, so this takes those
     * out first and keeps what a person needs from the rest: the words, the
     * breaks between them, and every link written out in full.
     */
    public static function toText(string $html): string
    {
        $t = $html;
        $t = preg_replace('~<(head|style|script|title)\b[^>]*>.*?</\1>~is', '', $t) ?? $t;
        $t = preg_replace('~<div class="ae-pre".*?</div>~is', '', $t) ?? $t;
        $t = preg_replace('~<!--.*?-->~s', '', $t) ?? $t;

        // Links keep their address: "Set my password (https://…)".
        $t = preg_replace_callback('~<a\b[^>]*href="([^"]*)"[^>]*>(.*?)</a>~is', function ($m) {
            $href = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $label = trim(preg_replace('~\s+~', ' ', strip_tags($m[2])) ?? '');
            // A link around a picture only (the logo) says nothing in text.
            if ($label === '' || $label === $href || str_starts_with($href, 'mailto:') && $label === substr($href, 7)) {
                return $label;
            }

            return $label . ' (' . $href . ')';
        }, $t) ?? $t;

        $t = preg_replace('~<img\b[^>]*>~i', '', $t) ?? $t;
        $t = preg_replace('~<br\s*/?>~i', "\n", $t) ?? $t;
        $t = preg_replace('~<li\b[^>]*>~i', "\n- ", $t) ?? $t;
        $t = preg_replace('~</(p|h[1-6]|table|ul|ol|blockquote)>~i', "\n\n", $t) ?? $t;
        $t = preg_replace('~</(div|tr)>~i', "\n", $t) ?? $t;
        $t = preg_replace('~</td>~i', ' ', $t) ?? $t;
        $t = strip_tags($t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = str_replace(["\u{00A0}", "\u{2007}", "\u{FEFF}", "\u{034F}"], [' ', '', '', ''], $t);

        $lines = array_map(fn ($l) => trim(preg_replace('~[ \t]+~', ' ', $l) ?? $l), explode("\n", str_replace("\r", '', $t)));
        $t = implode("\n", $lines);
        $t = preg_replace("~\n{3,}~", "\n\n", $t) ?? $t;

        return trim($t) . "\n";
    }
}
