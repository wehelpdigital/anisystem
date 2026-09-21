<?php

namespace App\Support;

/**
 * A saved map drawn small, from its own shapes.
 *
 * The map module's thumbnail used to come from the Static Maps API, and
 * when the key was short, the quota gone or the network unwilling the
 * shelf turned into a column of broken frames. This draws the plan itself
 * -- areas, paths, arrows, pins, labels -- on a soft field with GD, so a
 * saved map always has a picture, whatever the map service says. It is
 * the fallback under ScheduleMapController::thumb(); the filed photo and
 * the satellite render are still preferred when they can be had.
 */
final class MapThumb
{
    /** A PNG of the shapes, or null when there is nothing to draw or GD is missing. */
    public static function png(array $objects, int $w = 400, int $h = 300): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }
        $pts = [];
        foreach ($objects as $o) {
            foreach ((array) ($o['points'] ?? []) as $p) {
                if (is_array($p) && isset($p[0], $p[1]) && is_numeric($p[0]) && is_numeric($p[1])) {
                    $pts[] = [(float) $p[0], (float) $p[1]];
                }
            }
        }
        if (! $pts) {
            return null;
        }

        // The frame: every point, with air around it, kept in proportion.
        $lats = array_column($pts, 0);
        $lngs = array_column($pts, 1);
        $minLat = min($lats); $maxLat = max($lats);
        $minLng = min($lngs); $maxLng = max($lngs);
        $midLat = ($minLat + $maxLat) / 2;
        $cos = max(0.2, cos(deg2rad($midLat)));
        $spanLat = max($maxLat - $minLat, 0.0004);
        $spanLng = max(($maxLng - $minLng) * $cos, 0.0004);
        $pad = 0.14;
        $scale = min(($w * (1 - 2 * $pad)) / $spanLng, ($h * (1 - 2 * $pad)) / $spanLat);
        $drawW = $spanLng * $scale;
        $drawH = $spanLat * $scale;
        $ox = ($w - $drawW) / 2;
        $oy = ($h - $drawH) / 2;
        $project = function (array $p) use ($minLat, $minLng, $cos, $scale, $ox, $oy, $drawH, $spanLat, $spanLng) {
            $x = $ox + (($p[1] - $minLng) * $cos) * $scale;
            $y = $oy + $drawH - (($p[0] - $minLat) * $scale);

            return [(int) round($x), (int) round($y)];
        };

        $img = imagecreatetruecolor($w, $h);
        imageantialias($img, true);
        // A field, not a blank: two greens and a faint grid read as ground.
        $bg = imagecolorallocate($img, 226, 236, 214);
        imagefill($img, 0, 0, $bg);
        $grid = imagecolorallocatealpha($img, 90, 120, 60, 116);
        for ($x = 0; $x < $w; $x += 24) {
            imageline($img, $x, 0, $x, $h, $grid);
        }
        for ($y = 0; $y < $h; $y += 24) {
            imageline($img, 0, $y, $w, $y, $grid);
        }

        $rgb = function (?string $hex, int $alpha = 0) use ($img) {
            $hex = ltrim((string) $hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
                $hex = 'f5c518';
            }

            return imagecolorallocatealpha($img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $alpha);
        };
        $ink = imagecolorallocate($img, 47, 82, 25);

        // Areas first, so lines and pins sit on top of them.
        $order = ['area' => 0, 'path' => 1, 'line' => 1, 'arrow' => 1, 'pen' => 1, 'pin' => 2, 'text' => 3];
        usort($objects, fn ($a, $b) => ($order[$a['kind'] ?? 'pen'] ?? 1) <=> ($order[$b['kind'] ?? 'pen'] ?? 1));

        foreach ($objects as $o) {
            $kind = (string) ($o['kind'] ?? 'pen');
            $raw = array_values(array_filter((array) ($o['points'] ?? []), fn ($p) => is_array($p) && isset($p[0], $p[1])));
            if (! $raw) {
                continue;
            }
            $xy = array_map($project, $raw);
            $width = max(2, min(6, (int) round(((int) ($o['width'] ?? 3)) * 0.9)));
            imagesetthickness($img, $width);
            $stroke = $rgb($o['color'] ?? null);

            if ($kind === 'area' && count($xy) >= 3) {
                $flat = [];
                foreach ($xy as [$x, $y]) { $flat[] = $x; $flat[] = $y; }
                imagefilledpolygon($img, $flat, $rgb($o['color'] ?? null, 82));
                imagepolygon($img, $flat, $ink);
            } elseif ($kind === 'pin' || (count($xy) === 1 && $kind !== 'text')) {
                [$x, $y] = $xy[0];
                imagesetthickness($img, 2);
                imagefilledellipse($img, $x, $y, 16, 16, $rgb($o['color'] ?? null));
                imageellipse($img, $x, $y, 16, 16, $ink);
                imagefilledellipse($img, $x, $y, 6, 6, $ink);
            } elseif ($kind === 'text') {
                [$x, $y] = $xy[0];
                $label = trim((string) ($o['label'] ?? ''));
                if ($label !== '') {
                    $label = mb_strimwidth($label, 0, 18, '…');
                    $tw = imagefontwidth(3) * strlen($label);
                    imagefilledrectangle($img, $x - 4, $y - 9, $x + $tw + 4, $y + 8, imagecolorallocatealpha($img, 255, 255, 255, 30));
                    imagestring($img, 3, $x, $y - 7, $label, $ink);
                }
            } elseif (count($xy) >= 2) {
                for ($i = 1; $i < count($xy); $i++) {
                    imageline($img, $xy[$i - 1][0], $xy[$i - 1][1], $xy[$i][0], $xy[$i][1], $stroke);
                }
                if ($kind === 'arrow') {
                    // The head: two short strokes back from the last point.
                    [$x1, $y1] = $xy[count($xy) - 2];
                    [$x2, $y2] = $xy[count($xy) - 1];
                    $ang = atan2($y2 - $y1, $x2 - $x1);
                    foreach ([0.8, -0.8] as $turn) {
                        $hx = (int) round($x2 - 12 * cos($ang + $turn));
                        $hy = (int) round($y2 - 12 * sin($ang + $turn));
                        imageline($img, $x2, $y2, $hx, $hy, $stroke);
                    }
                }
            }
            // A named shape says its name, small, at its first point.
            $label = trim((string) ($o['label'] ?? ''));
            if ($label !== '' && $kind !== 'text') {
                [$x, $y] = $xy[0];
                imagestring($img, 2, min($w - 60, $x + 10), max(2, $y - 16), mb_strimwidth($label, 0, 16, '…'), $ink);
            }
        }
        imagesetthickness($img, 1);

        ob_start();
        imagepng($img, null, 6);
        $out = (string) ob_get_clean();
        imagedestroy($img);

        return $out !== '' ? $out : null;
    }
}
