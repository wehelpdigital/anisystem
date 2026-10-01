<?php

namespace App\Http\Controllers;

use App\Models\AsSitePage;
use App\Support\SitePages;
use Illuminate\Http\Request;

/**
 * The public site's guides, blog and feature pages, their hubs, the sitemap,
 * and the preview the mother app's builder draws (2026-10-01).
 * See App\Support\SitePages.
 */
class SitePageController extends Controller
{
    /*
     * Route parameters are read by name: Laravel hands them to a controller
     * by position, and these routes carry defaults (face, section) beside the
     * slug, in an order that is not the method's.
     */
    public function hub(Request $request)
    {
        $section = (string) $request->route('section');
        abort_unless(isset(SitePages::SECTIONS[$section]) && $section !== 'features', 404);

        return view('public.site.hub', [
            'section' => $section,
            'meta' => SitePages::SECTIONS[$section],
            'pages' => SitePages::inSection($section),
        ]);
    }

    public function show(Request $request)
    {
        $section = (string) $request->route('section');
        $slug = (string) $request->route('slug');
        $page = SitePages::find($section, $slug);
        abort_unless($page, 404);

        return $this->draw($page, false);
    }

    /**
     * A page as the builder has it now, drawn exactly as the live one is.
     *
     * GET /site-preview/{id}?e=&t= draws a saved page (drafts included); POST
     * /site-preview with a `page` JSON draws what is on the builder's screen
     * before it is saved. Either way the address is signed with the secret
     * the two apps share (mother.media_token) and an expiry, and the answer
     * tells search engines to stay out.
     */
    public function preview(Request $request)
    {
        $id = (int) $request->route('id');
        $expires = (int) $request->input('e');
        $token = (string) $request->input('t');
        $secret = (string) config('mother.media_token');
        abort_if($secret === '' || $expires < time() || ! hash_equals(hash_hmac('sha256', 'site-preview:' . $expires, $secret), $token), 403);

        if ($request->isMethod('post')) {
            $data = json_decode((string) $request->input('page'), true);
            abort_unless(is_array($data), 422);
            $page = new AsSitePage(SitePages::fieldsFromSeed(array_merge(['section' => 'blog', 'slug' => 'preview'], $data)));
            $page->id = (int) ($data['id'] ?? 0);
            $page->updated_at = now();
        } else {
            $page = AsSitePage::where('deleteStatus', 1)->findOrFail((int) $id);
        }

        return $this->draw($page, true)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function sitemap()
    {
        $static = ['/', '/how-it-works', '/features', '/pricing', '/about', '/tutorial', '/contact', '/crops', '/problems', '/blog', '/ask-anee', '/questions'];
        $urls = array_map(fn ($p) => ['loc' => url($p), 'lastmod' => null], $static);
        try {
            foreach (AsSitePage::live()->orderBy('section')->orderBy('sortOrder')->get(['section', 'slug', 'updated_at']) as $p) {
                $urls[] = ['loc' => SitePages::url($p->section, $p->slug), 'lastmod' => $p->updated_at?->toAtomString()];
            }
        } catch (\Throwable $e) {
            // no pages table yet: the site's own pages still go out
        }

        return response()->view('public.site.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function draw(AsSitePage $page, bool $preview)
    {
        $blocks = is_array($page->blocks) ? $page->blocks : [];

        return response()->view('public.site.page', [
            'page' => $page,
            'blocks' => $blocks,
            'meta' => SitePages::SECTIONS[$page->section] ?? SitePages::SECTIONS['blog'],
            'toc' => SitePages::toc($blocks),
            'faq' => SitePages::faq($blocks),
            'related' => $page->exists ? SitePages::related($page, 4) : SitePages::inSection($page->section)->take(4),
            'minutes' => SitePages::readMinutes($page),
            'preview' => $preview,
            'canonical' => SitePages::url($page->section, $page->slug),
        ]);
    }
}
