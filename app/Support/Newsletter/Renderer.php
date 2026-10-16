<?php

namespace App\Support\Newsletter;

use App\Support\EmailSkin;

/**
 * An email's blocks, drawn in the one design (Theme) as HTML every inbox
 * reads (2026-10-17). The rules are EmailSkin's: tables for layout, inline
 * styles on everything that matters, 600px wide and fluid below, a <style>
 * block that only adds (phones, dark mode), every image an absolute address
 * with alt text.
 *
 * Three modes:
 *   live     what Acumbamail sends: its own tags fill the unsubscribe and
 *            browser links, and links to anee.io carry the email's utm tags;
 *   test     what a test send gets: the tags become plain anee.io links;
 *   preview  the builder's view: every block answers a click (it tells the
 *            builder which block to open), and links do not navigate.
 *
 * The content (as_nl_emails.blocks):
 *   {kicker, headline, face, hero: {src, alt, link}, blocks: [{type, ...}]}
 */
final class Renderer
{
    public const BLOCKS = ['heading', 'text', 'image', 'video', 'button', 'callout', 'anee', 'story', 'pair', 'quote', 'list', 'divider', 'spacer', 'social', 'html'];

    public const FACES = ['happy', 'smile', 'grin', 'wave', 'cheer', 'delighted', 'idea', 'thinking', 'thumbsup', 'salute', 'love', 'heart', 'wink', 'starstruck', 'laughing', 'calm', 'content', 'relieved', 'concerned', 'worried', 'alarmed', 'serious', 'whisper', 'leaf', 'flower', 'yes', 'oops', 'shy'];

    private array $t;

    private string $font;

    private string $mode;

    private string $utm;

    /** The preview's inbox: light, dark, or (a real send) whatever the reader's phone says. */
    private string $scheme = 'auto';

    private function __construct(array $theme, string $mode, string $utm)
    {
        $this->t = $theme;
        $this->font = Theme::fontStack($theme);
        $this->mode = $mode;
        $this->utm = $utm;
    }

    /**
     * The whole email.
     *
     * @param  array{subject?: string, preheader?: string|null, content?: array|null}  $email
     */
    public static function html(array $email, ?array $theme = null, string $mode = 'live', string $utm = '', string $scheme = 'auto'): string
    {
        $r = new self($theme ?? Theme::get(), $mode, $utm);
        // Only the builder's preview picks: a sent email follows the reader's inbox.
        $r->scheme = $mode === 'preview' && in_array($scheme, ['light', 'dark'], true) ? $scheme : 'auto';

        return $r->page($email);
    }

    /** The plain text twin, for the multipart part and the text preview. */
    public static function text(string $html): string
    {
        return EmailSkin::toText($html);
    }

    /** A clean content array from whatever the builder sent. */
    public static function clean($content): array
    {
        $c = is_array($content) ? $content : (json_decode((string) $content, true) ?: []);
        $hero = is_array($c['hero'] ?? null) ? $c['hero'] : [];
        $blocks = [];
        foreach (array_slice((array) ($c['blocks'] ?? []), 0, 80) as $b) {
            if (! is_array($b) || ! in_array($b['type'] ?? '', self::BLOCKS, true)) {
                continue;
            }
            $b['id'] = preg_replace('/[^a-z0-9]/i', '', (string) ($b['id'] ?? '')) ?: substr(md5(uniqid('', true)), 0, 8);
            $blocks[] = self::cleanBlock($b);
        }
        $face = (string) ($c['face'] ?? '');

        return [
            'kicker' => self::plain($c['kicker'] ?? '', 120),
            'headline' => self::plain($c['headline'] ?? '', 200),
            'face' => in_array($face, self::FACES, true) ? $face : '',
            'hero' => ['src' => self::url($hero['src'] ?? ''), 'alt' => self::plain($hero['alt'] ?? '', 200), 'link' => self::url($hero['link'] ?? '')],
            'blocks' => $blocks,
        ];
    }

    private static function cleanBlock(array $b): array
    {
        $o = ['id' => $b['id'], 'type' => $b['type']];
        $rich = fn ($k) => self::rich((string) ($b[$k] ?? ''));
        switch ($b['type']) {
            case 'heading':
                $o += ['text' => self::plain($b['text'] ?? '', 200), 'size' => ($b['size'] ?? '') === 'md' ? 'md' : 'lg', 'align' => self::align($b['align'] ?? '')];
                break;
            case 'text':
                $o += ['html' => $rich('html'), 'align' => self::align($b['align'] ?? ''), 'size' => in_array($b['size'] ?? '', ['sm', 'lg'], true) ? $b['size'] : 'md'];
                break;
            case 'image':
                $o += ['src' => self::url($b['src'] ?? ''), 'alt' => self::plain($b['alt'] ?? '', 200), 'link' => self::url($b['link'] ?? ''),
                    'caption' => self::plain($b['caption'] ?? '', 300), 'width' => in_array($b['width'] ?? '', ['full', 'wide', 'half'], true) ? $b['width'] : 'full'];
                break;
            case 'video':
                $o += ['url' => self::url($b['url'] ?? ''), 'thumb' => self::url($b['thumb'] ?? ''), 'caption' => self::plain($b['caption'] ?? '', 300),
                    'label' => self::plain($b['label'] ?? '', 60) ?: 'Panoorin ang video'];
                break;
            case 'button':
                $o += ['label' => self::plain($b['label'] ?? '', 80) ?: 'Buksan', 'url' => self::url($b['url'] ?? ''),
                    'align' => self::align($b['align'] ?? 'left'), 'style' => ($b['style'] ?? '') === 'outline' ? 'outline' : 'solid'];
                break;
            case 'callout':
                $o += ['title' => self::plain($b['title'] ?? '', 160), 'html' => $rich('html'), 'tone' => in_array($b['tone'] ?? '', ['leaf', 'gold', 'alert'], true) ? $b['tone'] : 'leaf'];
                break;
            case 'anee':
                $o += ['face' => in_array($b['face'] ?? '', self::FACES, true) ? $b['face'] : 'happy', 'html' => $rich('html')];
                break;
            case 'story':
                $o += ['src' => self::url($b['src'] ?? ''), 'title' => self::plain($b['title'] ?? '', 200), 'html' => $rich('html'),
                    'source' => self::plain($b['source'] ?? '', 120), 'url' => self::url($b['url'] ?? ''), 'label' => self::plain($b['label'] ?? '', 60) ?: 'Basahin'];
                break;
            case 'pair':
                $items = [];
                foreach (array_slice((array) ($b['items'] ?? []), 0, 2) as $it) {
                    $it = is_array($it) ? $it : [];
                    $items[] = ['src' => self::url($it['src'] ?? ''), 'title' => self::plain($it['title'] ?? '', 160), 'html' => self::rich((string) ($it['html'] ?? '')),
                        'url' => self::url($it['url'] ?? ''), 'label' => self::plain($it['label'] ?? '', 60)];
                }
                while (count($items) < 2) {
                    $items[] = ['src' => '', 'title' => '', 'html' => '', 'url' => '', 'label' => ''];
                }
                $o += ['items' => $items];
                break;
            case 'quote':
                $o += ['html' => $rich('html'), 'by' => self::plain($b['by'] ?? '', 120)];
                break;
            case 'list':
                $items = array_values(array_filter(array_map(fn ($x) => self::rich((string) $x, true), array_slice((array) ($b['items'] ?? []), 0, 30)), fn ($x) => $x !== ''));
                $o += ['items' => $items, 'style' => in_array($b['style'] ?? '', ['check', 'number', 'dot'], true) ? $b['style'] : 'check'];
                break;
            case 'divider':
                $o += ['style' => in_array($b['style'] ?? '', ['line', 'dots', 'leaf'], true) ? $b['style'] : 'line'];
                break;
            case 'spacer':
                $o += ['size' => max(8, min(64, (int) ($b['size'] ?? 20)))];
                break;
            case 'social':
                $o += ['title' => self::plain($b['title'] ?? '', 120)];
                break;
            case 'html':
                $o += ['html' => self::rawHtml((string) ($b['html'] ?? ''))];
                break;
        }

        return $o;
    }

    /* ================================================================ page */

    private function page(array $email): string
    {
        $t = $this->t;
        $c = self::clean($email['content'] ?? []);
        $font = $this->font;
        $pre = trim((string) ($email['preheader'] ?? ''));
        $preview = $this->mode === 'preview';

        $preheader = $pre === '' ? '' :
            '<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">'
            . e($pre) . str_repeat('&#8199;&#65279;&#847; ', 40) . '</div>';

        $body = '';
        foreach ($c['blocks'] as $i => $b) {
            $one = $this->block($b);
            $body .= $preview ? '<div data-nlb="' . e($b['id']) . '">' . $one . '</div>' : $one;
        }
        if (trim($t['signature']) !== '') {
            $body .= '<p style="margin:22px 0 4px;font-family:' . $font . ';font-size:16px;line-height:1.6;color:' . $t['text'] . ';">'
                . nl2br(e($t['signature'])) . '</p>';
        }

        $fontLink = $t['font'] === 'nunito'
            ? '<!--[if !mso]><!--><link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700;800&display=swap" rel="stylesheet"><!--<![endif]-->'
            : '';
        $previewKit = $preview ? $this->previewKit() : '';
        // A light preview drops the dark rules; a dark one applies them always.
        [$darkOpen, $darkClose] = match ($this->scheme) {
            'light' => ['@media not all{', '}'],
            'dark' => ['@media all{', '}'],
            default => ['@media (prefers-color-scheme:dark){', '}'],
        };
        $head = $this->head($c);
        $hero = $this->hero($c);
        $foot = $this->footer();
        $mark = EmailSkin::asset('images/logo-mark.png');
        $r = (int) $t['radius'];
        $title = e($email['subject'] ?? 'anee.io');

        return <<<HTML
<!doctype html>
<html lang="tl" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="format-detection" content="telephone=no,address=no,email=no,date=no">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>{$title}</title>
{$fontLink}
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
<style>
:root{color-scheme:light dark;supported-color-schemes:light dark;}
body{margin:0;padding:0;width:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;}
table{border-collapse:collapse;mso-table-lspace:0pt;mso-table-rspace:0pt;}
img{-ms-interpolation-mode:bicubic;border:0;outline:none;text-decoration:none;}
.nl-body p{margin:0 0 16px;}
.nl-body a{color:{$t['link']};}
@media only screen and (max-width:620px){
  .nl-outer{padding:10px 6px 20px !important;}
  .nl-pad{padding-left:20px !important;padding-right:20px !important;}
  .nl-head{padding:22px 20px 20px !important;}
  .nl-h1{font-size:23px !important;}
  .nl-face img{width:58px !important;height:58px !important;}
  .nl-img-half{width:100% !important;}
}
@media only screen and (max-width:480px){
  .nl-btn,.nl-btn tbody,.nl-btn tr,.nl-btn td,.nl-btn a{display:block !important;width:100% !important;box-sizing:border-box;text-align:center !important;}
}
{$darkOpen}
  .nl-bg{background:#0f150b !important;}
  .nl-card{background:#1a2413 !important;border-color:#2e3d22 !important;}
  .nl-body,.nl-body p,.nl-body li,.nl-body td{color:#e6ecdd !important;}
  .nl-body a{color:#bfe08f !important;}
  .nl-body .nl-btn a,.nl-body .nl-btn span{color:{$t['buttonText']} !important;}
  .nl-muted,.nl-body .nl-muted{color:#a8b59a !important;}
  .nl-ink,.nl-body .nl-ink{color:#eef3e7 !important;}
  .nl-panel{background:#233119 !important;border-color:#3a4d2c !important;}
  .nl-panel-gold{background:#2e2a12 !important;border-color:#5b4e17 !important;}
  .nl-panel-alert{background:#321c17 !important;border-color:#5e3228 !important;}
  .nl-line{border-color:#2e3d22 !important;}
  .nl-foot,.nl-foot a{color:#94a386 !important;}
{$darkClose}
[data-ogsc] .nl-card{background:#1a2413 !important;}
[data-ogsc] .nl-body,[data-ogsc] .nl-body p{color:#e6ecdd !important;}
</style>
{$previewKit}
</head>
<body class="nl-bg" style="margin:0;padding:0;background:{$t['paper']};">
{$preheader}
<table role="presentation" class="nl-bg" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{$t['paper']}" style="background:{$t['paper']};">
<tr><td class="nl-outer" align="center" style="padding:28px 12px 32px;">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">
{$head}
{$hero}
  <tr><td class="nl-card nl-body nl-pad" bgcolor="{$t['card']}" style="background:{$t['card']};padding:30px 32px 8px;font-family:{$font};font-size:16px;line-height:1.65;color:{$t['text']};">
{$body}
  </td></tr>
  <tr><td class="nl-card nl-pad" bgcolor="{$t['card']}" style="background:{$t['card']};border-radius:0 0 {$r}px {$r}px;padding:18px 32px 26px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
      <tr><td class="nl-line" style="border-top:1px solid {$t['line']};padding-top:18px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
          <td valign="middle" style="padding-right:12px;"><img src="{$mark}" width="40" height="23" alt="" style="display:block;width:40px;height:23px;"></td>
          <td valign="middle" class="nl-muted" style="font-family:{$font};font-size:13px;line-height:1.5;color:{$t['muted']};"><strong class="nl-ink" style="color:{$t['header']};">anee.io</strong> &middot; {$this->esc($t['tagline'])}</td>
        </tr></table>
      </td></tr>
    </table>
  </td></tr>
{$foot}
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function head(array $c): string
    {
        $t = $this->t;
        $font = $this->font;
        $r = (int) $t['radius'];
        $grad = $t['headerTo'] !== '' ? 'background-image:linear-gradient(135deg,' . $t['header'] . ' 0%,' . $t['headerTo'] . ' 100%);' : '';
        $logo = e($t['logo']);
        $lw = (int) $t['logoWidth'];
        $site = e($this->link($t['website'] ?: 'https://anee.io'));
        $kicker = $c['kicker'] === '' ? '' :
            '<div style="margin:0 0 6px;font-family:' . $font . ';font-size:12px;line-height:1.4;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:' . $t['accent'] . ';">' . e($c['kicker']) . '</div>';
        $h1 = $c['headline'] === '' ? '' :
            '<h1 class="nl-h1" style="margin:0;font-family:' . $font . ';font-size:27px;line-height:1.22;font-weight:800;letter-spacing:-.2px;color:#ffffff;">' . e($c['headline']) . '</h1>';
        $face = $c['face'] === '' ? '' :
            '<td class="nl-face" width="76" align="right" valign="bottom" style="padding:0 0 0 12px;"><img src="' . EmailSkin::face($c['face']) . '" width="76" height="76" alt="Anee" style="display:block;width:76px;height:76px;border-radius:18px;"></td>';
        $words = $kicker . $h1;
        $wordsRow = $words === '' && $face === '' ? '' : '<tr><td valign="bottom">' . $words . '</td>' . $face . '</tr>';
        $logoPad = $wordsRow === '' ? '0' : '0 0 18px';
        $open = $this->mode === 'preview' ? '<tr data-nlb="head">' : '<tr>';

        return $open . '<td class="nl-head" bgcolor="' . $t['header'] . '" style="background-color:' . $t['header'] . ';' . $grad . 'border-radius:' . $r . 'px ' . $r . 'px 0 0;padding:26px 32px 24px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
            . '<tr><td colspan="2" style="padding:' . $logoPad . ';"><a href="' . $site . '" style="text-decoration:none;"><img src="' . $logo . '" width="' . $lw . '" alt="anee.io" style="display:block;width:' . $lw . 'px;max-width:100%;height:auto;color:#ffffff;font-family:' . $font . ';font-size:18px;font-weight:800;"></a></td></tr>'
            . $wordsRow . '</table></td></tr>'
            . '<tr><td height="4" bgcolor="' . $t['accent'] . '" style="height:4px;line-height:4px;font-size:0;background-color:' . $t['accent'] . ';background-image:linear-gradient(90deg,' . $t['accent'] . ' 0%,' . $t['accentTo'] . ' 100%);">&nbsp;</td></tr>';
    }

    private function hero(array $c): string
    {
        if ($c['hero']['src'] === '') {
            return '';
        }
        $img = '<img src="' . e($c['hero']['src']) . '" width="600" alt="' . e($c['hero']['alt']) . '" style="display:block;width:100%;max-width:600px;height:auto;">';
        if ($c['hero']['link'] !== '') {
            $img = '<a href="' . e($this->link($c['hero']['link'])) . '" target="_blank">' . $img . '</a>';
        }
        $open = $this->mode === 'preview' ? '<tr data-nlb="hero">' : '<tr>';

        return $open . '<td class="nl-card" bgcolor="' . $this->t['card'] . '" style="background:' . $this->t['card'] . ';padding:0;font-size:0;line-height:0;">' . $img . '</td></tr>';
    }

    private function footer(): string
    {
        $t = $this->t;
        $font = $this->font;
        $a = 'style="color:' . $t['muted'] . ';text-decoration:underline;"';
        $social = [];
        foreach (['facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $k => $label) {
            if ($t[$k] !== '') {
                $social[] = '<a href="' . e($this->link($t[$k])) . '" ' . $a . '>' . $label . '</a>';
            }
        }
        [$unsub, $web] = match ($this->mode) {
            'live' => ['*|UNSUBSCRIBE_URL|*', '*|BROWSER_VIEW|*'],
            default => ['https://anee.io', 'https://anee.io'],
        };
        $site = e($this->link($t['website'] ?: 'https://anee.io'));

        return '<tr><td class="nl-foot" align="center" style="padding:20px 24px 0;font-family:' . $font . ';font-size:12px;line-height:1.75;color:' . $t['muted'] . ';">'
            . ($social ? implode(' &nbsp;&middot;&nbsp; ', $social) . '<br>' : '')
            . '<a href="' . $site . '" ' . $a . '>anee.io</a> &nbsp;&middot;&nbsp; Questions? <a href="mailto:support@anee.io" ' . $a . '>support@anee.io</a><br>'
            . ($t['footer'] !== '' ? '<span style="display:inline-block;margin-top:6px;">' . e($t['footer']) . '</span><br>' : '')
            . '<a href="' . $web . '" ' . $a . '>View in your browser</a> &nbsp;&middot;&nbsp; <a href="' . $unsub . '" ' . $a . '>Unsubscribe</a>'
            . '</td></tr>';
    }

    /* ============================================================== blocks */

    private function block(array $b): string
    {
        $t = $this->t;
        $font = $this->font;
        $text = 'font-family:' . $font . ';color:' . $t['text'] . ';';
        $rad = min(16, (int) $t['radius']);

        switch ($b['type']) {
            case 'heading':
                $size = $b['size'] === 'md' ? '19px' : '23px';

                return '<h2 class="nl-ink" style="margin:6px 0 12px;' . $text . 'font-size:' . $size . ';line-height:1.3;font-weight:800;color:' . $t['header'] . ';text-align:' . $b['align'] . ';">' . e($b['text']) . '</h2>';

            case 'text':
                if ($b['html'] === '') {
                    return '';
                }
                $fs = ['sm' => '14px', 'md' => '16px', 'lg' => '18px'][$b['size']];

                return '<div style="' . $text . 'font-size:' . $fs . ';line-height:1.65;text-align:' . $b['align'] . ';">' . $this->styleRich($b['html']) . '</div>';

            case 'image':
                if ($b['src'] === '') {
                    return $this->placeholder('Picture');
                }
                $w = ['full' => 536, 'wide' => 420, 'half' => 268][$b['width']];
                $img = '<img class="' . ($b['width'] === 'half' ? 'nl-img-half' : '') . '" src="' . e($b['src']) . '" width="' . $w . '" alt="' . e($b['alt']) . '" style="display:block;width:100%;max-width:' . $w . 'px;height:auto;border-radius:' . $rad . 'px;margin:0 auto;">';
                if ($b['link'] !== '') {
                    $img = '<a href="' . e($this->link($b['link'])) . '" target="_blank" style="text-decoration:none;">' . $img . '</a>';
                }

                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 18px;"><tr><td align="center">' . $img
                    . ($b['caption'] !== '' ? '<div class="nl-muted" style="margin-top:8px;font-family:' . $font . ';font-size:13px;line-height:1.5;color:' . $t['muted'] . ';text-align:center;">' . e($b['caption']) . '</div>' : '')
                    . '</td></tr></table>';

            case 'video':
                $thumb = $b['thumb'] !== '' ? $b['thumb'] : self::videoThumb($b['url']);
                $href = e($this->link($b['url'] ?: 'https://anee.io'));
                $pic = $thumb !== ''
                    ? '<a href="' . $href . '" target="_blank" style="text-decoration:none;"><img src="' . e($thumb) . '" width="536" alt="' . e($b['caption'] ?: 'Video') . '" style="display:block;width:100%;max-width:536px;height:auto;border-radius:' . $rad . 'px ' . $rad . 'px 0 0;"></a>'
                    : $this->placeholder('Video');

                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px;border-collapse:separate;">'
                    . '<tr><td style="font-size:0;line-height:0;">' . $pic . '</td></tr>'
                    . '<tr><td bgcolor="' . $t['header'] . '" style="background:' . $t['header'] . ';border-radius:0 0 ' . $rad . 'px ' . $rad . 'px;padding:12px 16px;">'
                    . '<a href="' . $href . '" target="_blank" style="font-family:' . $font . ';font-size:15px;font-weight:800;color:#ffffff;text-decoration:none;">'
                    . '<span style="display:inline-block;width:26px;height:26px;line-height:26px;border-radius:999px;background:' . $t['accent'] . ';color:' . $t['buttonText'] . ';text-align:center;font-size:12px;margin-right:8px;">&#9654;</span>'
                    . e($b['label']) . '</a>'
                    . ($b['caption'] !== '' ? '<div style="margin-top:4px;font-family:' . $font . ';font-size:13px;line-height:1.5;color:#dfe8d3;">' . e($b['caption']) . '</div>' : '')
                    . '</td></tr></table>';

            case 'button':
                return $this->button($b['label'], $b['url'], $b['align'], $b['style']);

            case 'callout':
                [$bg, $line, $cls] = match ($b['tone']) {
                    'gold' => ['#fdf6dc', '#efd98a', 'nl-panel nl-panel-gold'],
                    'alert' => ['#fdf0ec', '#f1c6b9', 'nl-panel nl-panel-alert'],
                    default => [$t['panel'], '#d6e6c2', 'nl-panel'],
                };

                return '<table role="presentation" class="' . $cls . '" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="' . $bg . '" style="background:' . $bg . ';border:1px solid ' . $line . ';border-radius:' . $rad . 'px;border-collapse:separate;margin:10px 0 20px;">'
                    . '<tr><td style="padding:16px 18px;' . $text . 'font-size:15px;line-height:1.6;">'
                    . ($b['title'] !== '' ? '<div class="nl-ink" style="margin:0 0 6px;font-weight:800;font-size:16px;color:' . $t['header'] . ';">' . e($b['title']) . '</div>' : '')
                    . $this->styleRich($b['html'], true) . '</td></tr></table>';

            case 'anee':
                // One column (2026-10-16, the owner): her face and name on top, her words under them.
                return '<table role="presentation" class="nl-panel" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="' . $t['panel'] . '" style="background:' . $t['panel'] . ';border:1px solid #d6e6c2;border-radius:' . $rad . 'px;border-collapse:separate;margin:10px 0 20px;">'
                    . '<tr><td style="padding:14px 16px 4px;">'
                    . '<img src="' . EmailSkin::face($b['face']) . '" width="40" height="40" alt="" style="display:inline-block;vertical-align:middle;width:40px;height:40px;border-radius:12px;">'
                    . '<span style="display:inline-block;vertical-align:middle;margin-left:10px;font-family:' . $font . ';font-size:13px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:' . $t['link'] . ';">Sabi ni Anee</span>'
                    . '</td></tr><tr><td style="padding:6px 16px 14px;' . $text . 'font-size:15px;line-height:1.6;">'
                    . $this->styleRich($b['html'], true) . '</td></tr></table>';

            case 'story':
                $href = $b['url'] !== '' ? e($this->link($b['url'])) : '';
                $pic = $b['src'] === '' ? '' : '<tr><td style="padding:0 0 12px;font-size:0;line-height:0;">'
                    . ($href ? '<a href="' . $href . '" target="_blank">' : '')
                    . '<img src="' . e($b['src']) . '" width="536" alt="' . e($b['title']) . '" style="display:block;width:100%;max-width:536px;height:auto;border-radius:' . $rad . 'px;">'
                    . ($href ? '</a>' : '') . '</td></tr>';

                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 22px;">' . $pic
                    . '<tr><td style="' . $text . '">'
                    . ($b['source'] !== '' ? '<div class="nl-muted" style="font-size:11.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:' . $t['muted'] . ';margin:0 0 4px;">' . e($b['source']) . '</div>' : '')
                    . '<div class="nl-ink" style="font-size:19px;line-height:1.3;font-weight:800;color:' . $t['header'] . ';margin:0 0 6px;">' . ($href ? '<a href="' . $href . '" target="_blank" style="color:' . $t['header'] . ';text-decoration:none;">' . e($b['title']) . '</a>' : e($b['title'])) . '</div>'
                    . '<div style="font-size:15px;line-height:1.6;">' . $this->styleRich($b['html'], true) . '</div>'
                    . ($href ? '<div style="margin-top:8px;"><a href="' . $href . '" target="_blank" style="font-size:14px;font-weight:800;color:' . $t['link'] . ';text-decoration:none;">' . e($b['label']) . ' &rarr;</a></div>' : '')
                    . '</td></tr></table>';

            case 'pair':
                // One column (2026-10-16, the owner: "do not do multi columns in
                // content"): each story is a full width card, a line between them.
                $out = '';
                $items = array_values(array_filter($b['items'], fn ($it) => $it['src'] !== '' || $it['title'] !== '' || $it['html'] !== ''));
                foreach ($items as $i => $it) {
                    if ($i > 0) {
                        $out .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 20px;"><tr><td class="nl-line" style="border-top:1px solid ' . $t['line'] . ';font-size:0;line-height:0;">&nbsp;</td></tr></table>';
                    }
                    $out .= $this->block(['type' => 'story', 'src' => $it['src'], 'title' => $it['title'], 'html' => $it['html'], 'source' => '', 'url' => $it['url'], 'label' => $it['label'] ?: 'Tingnan']);
                }

                return $out !== '' ? $out : $this->placeholder('Two stories');

            case 'quote':
                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px;"><tr>'
                    . '<td style="border-left:4px solid ' . $t['accent'] . ';padding:4px 0 4px 16px;' . $text . 'font-size:17px;line-height:1.55;font-style:italic;">'
                    . $this->styleRich($b['html'], true)
                    . ($b['by'] !== '' ? '<div class="nl-muted" style="margin-top:6px;font-style:normal;font-size:13px;font-weight:700;color:' . $t['muted'] . ';">' . e($b['by']) . '</div>' : '')
                    . '</td></tr></table>';

            case 'list':
                $rows = '';
                foreach ($b['items'] as $i => $it) {
                    $mark = match ($b['style']) {
                        'number' => '<span style="display:inline-block;width:24px;height:24px;line-height:24px;border-radius:999px;background:' . $t['header'] . ';color:#ffffff;text-align:center;font-size:12px;font-weight:800;">' . ($i + 1) . '</span>',
                        'dot' => '<span style="display:inline-block;width:8px;height:8px;border-radius:999px;background:' . $t['accentTo'] . ';margin-top:8px;"></span>',
                        default => '<span style="display:inline-block;width:22px;height:22px;line-height:22px;border-radius:999px;background:' . $t['accentTo'] . ';color:#ffffff;text-align:center;font-size:13px;font-weight:800;">&#10003;</span>',
                    };
                    $rows .= '<tr><td width="34" valign="top" style="width:34px;padding:2px 0 10px;">' . $mark . '</td>'
                        . '<td valign="top" style="padding:0 0 10px;' . $text . 'font-size:16px;line-height:1.55;">' . $this->styleRich($it, true) . '</td></tr>';
                }

                return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 14px;">' . $rows . '</table>';

            case 'divider':
                return match ($b['style']) {
                    'dots' => '<div style="margin:14px 0 22px;text-align:center;font-family:' . $font . ';font-size:20px;letter-spacing:10px;color:' . $t['accentTo'] . ';">&bull;&bull;&bull;</div>',
                    'leaf' => '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:14px 0 22px;"><tr>'
                        . '<td class="nl-line" style="border-top:1px solid ' . $t['line'] . ';">&nbsp;</td>'
                        . '<td width="52" align="center" style="width:52px;"><img src="' . EmailSkin::asset('images/logo-mark.png') . '" width="34" height="20" alt="" style="display:block;width:34px;height:20px;margin:0 auto;"></td>'
                        . '<td class="nl-line" style="border-top:1px solid ' . $t['line'] . ';">&nbsp;</td></tr></table>',
                    default => '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:10px 0 22px;"><tr><td class="nl-line" style="border-top:1px solid ' . $t['line'] . ';font-size:0;line-height:0;">&nbsp;</td></tr></table>',
                };

            case 'spacer':
                return '<div style="height:' . $b['size'] . 'px;line-height:' . $b['size'] . 'px;font-size:0;">&nbsp;</div>';

            case 'social':
                $links = [];
                foreach (['facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'website' => 'anee.io'] as $k => $label) {
                    if ($t[$k] !== '') {
                        $links[] = '<td style="padding:0 4px 8px;"><a href="' . e($this->link($t[$k])) . '" target="_blank" style="display:inline-block;padding:9px 16px;border-radius:999px;border:1.5px solid ' . $t['line'] . ';font-family:' . $font . ';font-size:14px;font-weight:800;color:' . $t['header'] . ';text-decoration:none;">' . $label . '</a></td>';
                    }
                }
                if (! $links) {
                    return $this->mode === 'preview' ? $this->placeholder('Social links: add them in the theme') : '';
                }

                return ($b['title'] !== '' ? '<div style="margin:6px 0 10px;text-align:center;' . $text . 'font-size:15px;font-weight:800;">' . e($b['title']) . '</div>' : '')
                    . '<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 14px;"><tr>' . implode('', $links) . '</tr></table>';

            case 'html':
                return $b['html'];
        }

        return '';
    }

    private function button(string $label, string $url, string $align, string $style): string
    {
        $t = $this->t;
        $href = e($this->link($url ?: ($t['website'] ?: 'https://anee.io')));
        $r = (int) $t['buttonRadius'];
        $solid = $style !== 'outline';
        $bg = $solid ? $t['accent'] : $t['card'];
        $color = $solid ? $t['buttonText'] : $t['header'];
        $border = $solid ? '' : 'border:2px solid ' . $t['accent'] . ';';

        return '<table role="presentation" class="nl-btn" align="' . ($align === 'center' ? 'center' : 'left') . '" cellpadding="0" cellspacing="0" border="0" style="margin:14px ' . ($align === 'center' ? 'auto' : '0') . ' 22px;border-collapse:separate;">'
            . '<tr><td align="center" bgcolor="' . $bg . '" style="background:' . $bg . ';border-radius:' . $r . 'px;' . $border . 'mso-padding-alt:0;">'
            . '<a href="' . $href . '" target="_blank" style="display:inline-block;padding:15px 30px;font-family:' . $this->font . ';font-size:16px;line-height:20px;font-weight:800;color:' . $color . ';text-decoration:none;border-radius:' . $r . 'px;mso-padding-alt:0;">'
            . '<!--[if mso]><i style="letter-spacing:30px;mso-font-width:-100%;mso-text-raise:22pt;">&nbsp;</i><![endif]-->'
            . '<span style="mso-text-raise:11pt;color:' . $color . ';">' . e($label) . '</span>'
            . '<!--[if mso]><i style="letter-spacing:30px;mso-font-width:-100%;">&nbsp;</i><![endif]-->'
            . '</a></td></tr></table>'
            . '<div style="clear:both;font-size:0;line-height:0;">&nbsp;</div>';
    }

    private function placeholder(string $what): string
    {
        if ($this->mode !== 'preview') {
            return '';
        }

        return '<div style="margin:8px 0 18px;padding:26px 12px;border:2px dashed ' . $this->t['line'] . ';border-radius:14px;text-align:center;font-family:' . $this->font . ';font-size:14px;font-weight:700;color:' . $this->t['muted'] . ';">' . e($what) . '</div>';
    }

    /** The builder's click to select, inside the preview frame. */
    private function previewKit(): string
    {
        return '<style>[data-nlb]{cursor:pointer;outline:2px dashed transparent;outline-offset:3px;border-radius:6px;transition:outline-color .2s ease}'
            . '[data-nlb]:hover{outline-color:rgba(232,190,28,.75)}[data-nlb].nlb-on{outline:2px solid #E8BE1C}</style>'
            . '<script>(function(){document.addEventListener("click",function(e){var a=e.target.closest("a");if(a)e.preventDefault();'
            . 'var t=e.target.closest("[data-nlb]");if(t)parent.postMessage({nlb:t.getAttribute("data-nlb")},"*");});'
            . 'window.addEventListener("message",function(e){var d=e.data||{};if(d.nlbOn===undefined)return;'
            . 'document.querySelectorAll(".nlb-on").forEach(function(n){n.classList.remove("nlb-on")});'
            . 'var t=document.querySelector("[data-nlb=\"" + d.nlbOn + "\"]");if(t){t.classList.add("nlb-on");if(d.scroll)t.scrollIntoView({block:"center",behavior:"smooth"});}});'
            . 'function h(){parent.postMessage({nlbHeight:document.documentElement.scrollHeight},"*")}window.addEventListener("load",h);setTimeout(h,400);})();</script>';
    }

    /* ============================================================== words */

    /**
     * Rich text as the builder writes it (p, br, b, i, u, a, ul, ol, li),
     * with the inline styles email needs.
     */
    private function styleRich(string $html, bool $tight = false): string
    {
        $t = $this->t;
        $html = preg_replace_callback('~<a\s+href="([^"]*)"[^>]*>~i', fn ($m) => '<a href="' . e($this->link(html_entity_decode($m[1]))) . '" target="_blank" style="color:' . $t['link'] . ';text-decoration:underline;font-weight:700;">', $html) ?? $html;
        $html = preg_replace('~<p>~i', '<p style="margin:0 0 ' . ($tight ? '10' : '16') . 'px;">', $html) ?? $html;
        $html = preg_replace('~<(ul|ol)>~i', '<$1 style="margin:0 0 16px;padding:0 0 0 22px;">', $html) ?? $html;
        $html = preg_replace('~<li>~i', '<li style="margin:0 0 6px;">', $html) ?? $html;
        $html = preg_replace('~<(b|strong)>~i', '<strong style="font-weight:800;">', $html) ?? $html;
        $html = preg_replace('~</b>~i', '</strong>', $html) ?? $html;
        if ($tight) {
            // The last paragraph of a panel or a cell needs no space under it.
            $html = preg_replace('~<p style="margin:0 0 10px;">((?:(?!<p).)*)</p>\s*$~is', '<p style="margin:0;">$1</p>', $html) ?? $html;
        }

        return $html;
    }

    /** An address with the email's utm tags when it goes to anee.io (live sends only). */
    private function link(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $this->mode !== 'live' || $this->utm === '' || str_starts_with($url, '*|')) {
            return $url;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host !== 'anee.io' && ! str_ends_with($host, '.anee.io')) {
            return $url;
        }
        if (str_contains($url, 'utm_source=')) {
            return $url;
        }
        [$base, $frag] = array_pad(explode('#', $url, 2), 2, null);

        return $base . (str_contains($base, '?') ? '&' : '?') . 'utm_source=newsletter&utm_medium=email&utm_campaign=' . rawurlencode($this->utm) . ($frag !== null ? '#' . $frag : '');
    }

    private function esc(string $s): string
    {
        return e($s);
    }

    /** A YouTube video's own picture; empty for anything else (upload a picture for those). */
    public static function videoThumb(string $url): string
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
        }

        return '';
    }

    /* ============================================================ cleaning */

    private static function plain($s, int $max): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $s)) ?? ''), 0, $max);
    }

    private static function align($a): string
    {
        return in_array($a, ['left', 'center'], true) ? $a : 'left';
    }

    /** http(s), mailto, or Acumbamail's own tags; nothing else becomes a link. */
    private static function url($u): string
    {
        $u = trim((string) $u);
        if ($u === '') {
            return '';
        }
        if (str_starts_with($u, '/')) {
            $u = 'https://anee.io' . $u;
        }

        return preg_match('~^(https?://|mailto:|\*\|[A-Z_]+\|\*$)~i', $u) ? mb_substr($u, 0, 1000) : '';
    }

    /**
     * The builder's rich text, kept to the few tags an email needs: no
     * attributes but a link's address, no scripts, no styles of its own.
     */
    public static function rich(string $html, bool $inline = false): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $html = preg_replace('~<(script|style|head|title)\b[^>]*>.*?</\1>~is', '', $html) ?? '';
        $html = str_ireplace(['<div>', '</div>'], ['<p>', '</p>'], $html);
        $allowed = $inline ? '<b><strong><i><em><u><a><br>' : '<p><br><b><strong><i><em><u><a><ul><ol><li>';
        $html = strip_tags($html, $allowed);
        // Every tag loses its attributes, but a link keeps a safe address.
        $html = preg_replace_callback('~<(/?)([a-z]+)([^>]*)>~i', function ($m) {
            $tag = strtolower($m[2]);
            if ($m[1] === '/') {
                return '</' . $tag . '>';
            }
            if ($tag === 'a') {
                $href = preg_match('~href\s*=\s*("([^"]*)"|\'([^\']*)\')~i', $m[3], $h) ? html_entity_decode(($h[2] ?? '') !== '' ? $h[2] : ($h[3] ?? '')) : '';
                $href = self::url($href);

                return $href === '' ? '<a>' : '<a href="' . e($href) . '">';
            }

            return '<' . $tag . '>';
        }, $html) ?? '';
        $html = preg_replace('~<a>(.*?)</a>~is', '$1', $html) ?? $html;
        $html = preg_replace('~<p>\s*(<br>)?\s*</p>~i', '', $html) ?? $html;
        // Words typed with no paragraph around them get one.
        if (! $inline && ! preg_match('~^\s*<(p|ul|ol)>~i', $html)) {
            $html = '<p>' . $html . '</p>';
        }

        return mb_substr($html, 0, 20000);
    }

    /** The HTML block: the admin's own markup, minus anything that runs. */
    private static function rawHtml(string $html): string
    {
        $html = preg_replace('~<(script|iframe|object|embed|form)\b[^>]*>.*?</\1>~is', '', $html) ?? '';
        $html = preg_replace('~<(script|iframe|object|embed|form|input|button)\b[^>]*/?>~i', '', $html) ?? '';
        $html = preg_replace('~\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)~i', '', $html) ?? '';
        $html = preg_replace('~(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2~i', '$1="#"', $html) ?? '';

        return mb_substr($html, 0, 30000);
    }
}
