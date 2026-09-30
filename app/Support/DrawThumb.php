<?php

namespace App\Support;

/**
 * A drawing redrawn from its own strokes, on the server (2026-09-30).
 *
 * A drawing that kept its strokes carries everything needed to paint it
 * again, and some pictures did not survive the moves between hosts (the old
 * Railway disk, a mother bucket that lost older files). Rather than a
 * "Picture missing" tile, the Draw page, the Gallery and every chip get the
 * drawing back: painted here with GD from the same objects the pad paints --
 * path, line, arrow, rect, ellipse, text, and a placeholder frame for a
 * pasted image whose own file is not at hand.
 *
 * The pad's sheet is 1280 wide (W0); a drawing is framed to the extent of
 * what is on it, with the sheet's width as the floor, so a small sketch is
 * not blown up to fill the tile.
 */
class DrawThumb
{
    private const SHEET_W = 1280;

    private const SHEET_H = 900;

    /** A PNG of the first page (or the page that was open), or null. */
    public static function png($strokes, int $maxW = 480, int $maxH = 360): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $objects = self::pageObjects($strokes);
        if (! $objects) {
            return null;
        }

        // The sheet the drawing was made on, grown to hold anything beyond it.
        $x1 = 0.0; $y1 = 0.0; $x2 = (float) self::SHEET_W; $y2 = (float) self::SHEET_H;
        foreach ($objects as $o) {
            foreach (self::extent($o) as [$x, $y]) {
                $x1 = min($x1, $x); $y1 = min($y1, $y); $x2 = max($x2, $x); $y2 = max($y2, $y);
            }
        }
        $pad = 24;
        $x1 -= $pad; $y1 -= $pad; $x2 += $pad; $y2 += $pad;
        $scale = min($maxW / max(1, $x2 - $x1), $maxH / max(1, $y2 - $y1));
        $w = max(1, (int) round(($x2 - $x1) * $scale));
        $h = max(1, (int) round(($y2 - $y1) * $scale));

        // Drawn at twice the size and shrunk: GD's thick lines have no
        // anti-aliasing of their own, and the resample smooths them.
        $ss = 2;
        $im = imagecreatetruecolor($w * $ss, $h * $ss);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        $tx = fn ($x) => (int) round(($x - $x1) * $scale * $ss);
        $ty = fn ($y) => (int) round(($y - $y1) * $scale * $ss);

        foreach ($objects as $o) {
            $type = (string) ($o['type'] ?? '');
            $color = self::color($im, (string) ($o['color'] ?? '#111827'));
            $thick = max(1, (int) round(((float) ($o['width'] ?? 2)) * $scale * $ss));
            imagesetthickness($im, $thick);
            switch ($type) {
                case 'path':
                    $pts = array_values(array_filter((array) ($o['points'] ?? []), fn ($p) => isset($p['x'], $p['y'])));
                    if (count($pts) === 1) {
                        self::dot($im, $tx($pts[0]['x']), $ty($pts[0]['y']), $thick, $color);
                    }
                    for ($i = 1; $i < count($pts); $i++) {
                        imageline($im, $tx($pts[$i - 1]['x']), $ty($pts[$i - 1]['y']), $tx($pts[$i]['x']), $ty($pts[$i]['y']), $color);
                        // Round joins, so a thick stroke does not break into dashes.
                        if ($thick > 3) {
                            self::dot($im, $tx($pts[$i]['x']), $ty($pts[$i]['y']), $thick, $color);
                        }
                    }
                    break;
                case 'line':
                case 'arrow':
                    $ax = $tx($o['x1'] ?? 0); $ay = $ty($o['y1'] ?? 0); $bx = $tx($o['x2'] ?? 0); $by = $ty($o['y2'] ?? 0);
                    imageline($im, $ax, $ay, $bx, $by, $color);
                    if ($type === 'arrow') {
                        $a = atan2($by - $ay, $bx - $ax);
                        $len = (10 + ((float) ($o['width'] ?? 2)) * 2.2) * $scale * $ss;
                        imageline($im, $bx, $by, (int) ($bx - $len * cos($a - 0.4)), (int) ($by - $len * sin($a - 0.4)), $color);
                        imageline($im, $bx, $by, (int) ($bx - $len * cos($a + 0.4)), (int) ($by - $len * sin($a + 0.4)), $color);
                    }
                    break;
                case 'rect':
                    [$rx, $ry, $rw, $rh] = self::box($o);
                    imagerectangle($im, $tx($rx), $ty($ry), $tx($rx + $rw), $ty($ry + $rh), $color);
                    break;
                case 'ellipse':
                    [$rx, $ry, $rw, $rh] = self::box($o);
                    // GD's ellipse outline is one pixel; rings of it make the width.
                    $cx = $tx($rx + $rw / 2); $cy = $ty($ry + $rh / 2);
                    $ew = max(2, (int) round($rw * $scale * $ss)); $eh = max(2, (int) round($rh * $scale * $ss));
                    imagesetthickness($im, 1);
                    for ($k = -intdiv($thick, 2); $k <= intdiv($thick, 2); $k++) {
                        imageellipse($im, $cx, $cy, max(1, $ew + 2 * $k), max(1, $eh + 2 * $k), $color);
                    }
                    break;
                case 'text':
                    $size = max(1, min(5, (int) round(((float) ($o['size'] ?? 24)) * $scale * $ss / 8)));
                    $fontH = imagefontheight($size);
                    imagestring($im, $size, $tx($o['x'] ?? 0), $ty($o['y'] ?? 0) - $fontH, (string) ($o['text'] ?? ''), $color);
                    break;
                case 'image':
                    [$rx, $ry, $rw, $rh] = self::box($o);
                    $placed = self::pasteImage($im, (string) ($o['src'] ?? ''), $tx($rx), $ty($ry), (int) round($rw * $scale * $ss), (int) round($rh * $scale * $ss));
                    if (! $placed) {
                        imagesetthickness($im, max(1, $ss));
                        imagefilledrectangle($im, $tx($rx), $ty($ry), $tx($rx + $rw), $ty($ry + $rh), imagecolorallocate($im, 236, 238, 241));
                        imagerectangle($im, $tx($rx), $ty($ry), $tx($rx + $rw), $ty($ry + $rh), imagecolorallocate($im, 170, 176, 186));
                    }
                    break;
            }
        }

        $out = imagecreatetruecolor($w, $h);
        imagecopyresampled($out, $im, 0, 0, 0, 0, $w, $h, $w * $ss, $h * $ss);
        imagedestroy($im);
        ob_start();
        imagepng($out, null, 6);
        imagedestroy($out);

        return ob_get_clean() ?: null;
    }

    /** The objects of the page a paged drawing was left on, or the flat list. */
    private static function pageObjects($strokes): array
    {
        if (! is_array($strokes) || ! $strokes) {
            return [];
        }
        if (DrawStrokes::isPaged($strokes)) {
            $page = collect($strokes)->first(fn ($p) => ! empty($p['current'])) ?? $strokes[0];

            return array_values(array_filter((array) ($page['objects'] ?? []), 'is_array'));
        }

        return array_values(array_filter($strokes, 'is_array'));
    }

    /** The corners an object reaches, in sheet coordinates. */
    private static function extent(array $o): array
    {
        return match ((string) ($o['type'] ?? '')) {
            'path' => array_map(fn ($p) => [(float) ($p['x'] ?? 0), (float) ($p['y'] ?? 0)], array_filter((array) ($o['points'] ?? []), 'is_array')),
            'line', 'arrow' => [[(float) ($o['x1'] ?? 0), (float) ($o['y1'] ?? 0)], [(float) ($o['x2'] ?? 0), (float) ($o['y2'] ?? 0)]],
            'text' => [[(float) ($o['x'] ?? 0), (float) ($o['y'] ?? 0) - (float) ($o['size'] ?? 24)],
                [(float) ($o['x'] ?? 0) + mb_strlen((string) ($o['text'] ?? '')) * (float) ($o['size'] ?? 24) * .6, (float) ($o['y'] ?? 0)]],
            default => (function () use ($o) {
                [$x, $y, $w, $h] = self::box($o);

                return [[$x, $y], [$x + $w, $y + $h]];
            })(),
        };
    }

    /** x, y, w, h with a negative width or height turned the right way up. */
    private static function box(array $o): array
    {
        $x = (float) ($o['x'] ?? 0); $y = (float) ($o['y'] ?? 0);
        $w = (float) ($o['w'] ?? 0); $h = (float) ($o['h'] ?? 0);

        return [min($x, $x + $w), min($y, $y + $h), abs($w), abs($h)];
    }

    private static function color($im, string $hex): int
    {
        if (preg_match('/^#?([0-9a-f]{3})$/i', $hex, $m)) {
            $hex = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        }
        if (! preg_match('/^#?([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i', $hex, $m)) {
            return imagecolorallocate($im, 17, 24, 39);
        }

        return imagecolorallocate($im, hexdec($m[1]), hexdec($m[2]), hexdec($m[3]));
    }

    private static function dot($im, int $x, int $y, int $d, int $color): void
    {
        imagefilledellipse($im, $x, $y, max(2, $d), max(2, $d), $color);
    }

    /** A pasted picture carried inline (a data URL) goes back where it was. */
    private static function pasteImage($im, string $src, int $x, int $y, int $w, int $h): bool
    {
        if ($w < 2 || $h < 2 || ! preg_match('~^data:image/[a-z+]+;base64,~i', $src)) {
            return false;
        }
        $bin = base64_decode(substr($src, strpos($src, ',') + 1), true);
        $pic = $bin ? @imagecreatefromstring($bin) : false;
        if (! $pic) {
            return false;
        }
        imagecopyresampled($im, $pic, $x, $y, 0, 0, $w, $h, imagesx($pic), imagesy($pic));
        imagedestroy($pic);

        return true;
    }
}
