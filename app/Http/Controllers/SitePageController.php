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

        // The weeds have their own front page: a catalogue to filter, the
        // weed control helper by rice age, and the guides.
        if ($section === 'weeds') {
            return view('public.site.weeds-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'pages' => SitePages::inSection($section),
                'control' => \App\Support\WeedControl::TABLE,
            ]);
        }
        // Pests and diseases: catalogues like the weeds (2026-10-07), with a
        // finder in place of the weed control helper.
        if ($section === 'pests' || $section === 'diseases') {
            return view('public.site.catalogue-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'pages' => SitePages::inSection($section),
            ]);
        }
        // /problems: the door to pests, diseases and weeds.
        if ($section === 'problems') {
            return view('public.site.problems-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'groups' => collect(['pests', 'diseases', 'weeds'])->mapWithKeys(fn ($s) => [$s => SitePages::inSection($s)]),
            ]);
        }

        return view('public.site.hub', [
            'section' => $section,
            'meta' => SitePages::SECTIONS[$section],
            'pages' => SitePages::inSection($section),
        ]);
    }

    /** An old /problems/{slug} address: to the page's new home, for good. */
    public function movedProblem(Request $request)
    {
        $page = SitePages::movedProblem((string) $request->route('slug'));
        abort_unless($page, 404);

        return redirect(SitePages::pageUrl($page), 301);
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
        $static = ['/', '/how-it-works', '/features', '/pricing', '/pricing/compare', '/about', '/tutorial', '/contact', '/crops', '/problems', '/pests', '/diseases', '/weeds', '/land-preparation', '/blog', '/ask-anee', '/questions', '/legal'];
        $urls = array_map(fn ($p) => ['loc' => url($p), 'lastmod' => null], $static);
        try {
            foreach (AsSitePage::live()->orderBy('section')->orderBy('sortOrder')->get(['section', 'slug', 'updated_at']) as $p) {
                $urls[] = ['loc' => SitePages::url($p->section, $p->slug), 'lastmod' => $p->updated_at?->toAtomString()];
            }
        } catch (\Throwable $e) {
            // no pages table yet: the site's own pages still go out
        }
        try {
            foreach (\App\Models\AsLegalPage::active()->published()->orderBy('sortOrder')->get(['slug', 'updated_at']) as $p) {
                $urls[] = ['loc' => url('/legal/' . $p->slug), 'lastmod' => $p->updated_at?->toAtomString()];
            }
        } catch (\Throwable $e) {
            // the legal pages' table is the mother app's; without it, /legal alone
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
