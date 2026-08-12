<?php

namespace App\Http\Controllers;

use App\Models\AsTutorialPage;
use Illuminate\Http\Request;

/**
 * "How to use" — the page behind the question mark in every module header.
 *
 * Which page you get depends on what you are holding: the same module is
 * driven differently on a phone and at a desk, and instructions that describe
 * the wrong one are worse than none.
 */
class HelpController extends Controller
{
    public function show(Request $request, string $module)
    {
        $module = strtolower(trim($module));
        if (! array_key_exists($module, AsTutorialPage::MODULES)) {
            abort(404);
        }

        $device = $this->device($request);
        $page = $this->pageFor($module, $device);

        return view('help.show', [
            'moduleKey' => $module,
            'moduleLabel' => AsTutorialPage::label($module),
            'device' => $device,
            'page' => $page,
            // Where the reader came from, so the way back is the way in.
            'back' => $this->back($request),
            'others' => AsTutorialPage::active()
                ->where('moduleKey', '!=', $module)
                ->where('device', $device)
                ->orderBy('moduleKey')
                ->get(['moduleKey', 'title']),
        ]);
    }

    /**
     * A page written for this device, or the nearest one that exists — a
     * tablet reads the desktop page sooner than it reads nothing, and every
     * device would rather have the mobile page than an empty screen.
     */
    private function pageFor(string $module, string $device): ?AsTutorialPage
    {
        $order = match ($device) {
            'mobile' => ['mobile', 'tablet', 'desktop'],
            'tablet' => ['tablet', 'desktop', 'mobile'],
            default => ['desktop', 'tablet', 'mobile'],
        };

        $rows = AsTutorialPage::active()->where('moduleKey', $module)->get()->keyBy('device');
        foreach ($order as $d) {
            if ($rows->has($d)) {
                return $rows->get($d);
            }
        }

        return null;
    }

    /**
     * The client measures itself and says so (?device=), because a user agent
     * cannot be trusted about width and a 13" tablet in a keyboard case is a
     * desktop for our purposes. The header link fills it in; a bare URL falls
     * back to a reasonable guess from the agent string.
     */
    /**
     * Which page to serve. The reader is holding the device the instructions
     * are about, so the browser's own word is taken for it — no picker, and
     * no ?device= to get bookmarked and then be wrong forever. The mother app
     * still writes one page per device; ?device= is honoured only there, where
     * previewing another device is the whole job.
     */
    private function device(Request $request): string
    {
        $asked = strtolower((string) $request->query('device'));
        if ($request->boolean('preview') && in_array($asked, AsTutorialPage::DEVICES, true)) {
            return $asked;
        }

        $ua = (string) $request->userAgent();
        if (preg_match('~iPad|Tablet|Nexus 7|SM-T~i', $ua)) {
            return 'tablet';
        }

        return preg_match('~Mobi|Android|iPhone~i', $ua) ? 'mobile' : 'desktop';
    }

    private function back(Request $request): ?string
    {
        $from = (string) $request->query('from');
        // Same-origin paths only: this ends up in an href.
        return (str_starts_with($from, '/') && ! str_starts_with($from, '//')) ? $from : null;
    }
}
