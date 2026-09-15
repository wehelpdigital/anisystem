<?php

namespace App\Http\Controllers;

use App\Models\AsAdUnit;

/**
 * The door an advertisement's picture opens: counts the click, then sends
 * the browser on to wherever the unit points. Public, since a guest on
 * the pricing page sees the same slots.
 */
class AdsController extends Controller
{
    public function go(int $id)
    {
        $unit = AsAdUnit::active()->find($id);
        $to = trim((string) $unit?->linkUrl);
        if (! $unit || ! preg_match('#^https?://#i', $to)) {
            abort(404);
        }
        try {
            AsAdUnit::where('id', $unit->id)->increment('clicks');
        } catch (\Throwable $e) {
            // Counting is not worth a failed click.
        }

        return redirect()->away($to);
    }
}
