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
        // weed control helper by crop age, and the guides.
        if ($section === 'weeds') {
            return view('public.site.weeds-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'pages' => SitePages::inSection($section),
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
        // How to Plant (2026-10-19): every crop's traditional planting guide, shelf by shelf.
        if ($section === 'how-to-plant') {
            return view('public.site.how-to-plant-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'pages' => SitePages::inSection($section),
            ]);
        }
        // Nutrient deficiencies (2026-10-16): the catalogue of every crop's, the crop and nutrient pages.
        if ($section === 'nutrient-deficiencies') {
            return view('public.site.deficiency-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'pages' => SitePages::inSection($section),
            ]);
        }
        // Rice varieties: the finder over every variety (2026-10-13).
        if ($section === 'rice-varieties') {
            return view('public.site.varieties-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'varieties' => \App\Support\RiceVarieties::all(),
            ]);
        }
        // Crop varieties: the crops, each to its own finder (2026-10-13).
        if ($section === 'crop-varieties') {
            return view('public.site.crop-varieties-hub', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
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
        // Rice is a crop variety too; its finder lives at /rice-varieties (2026-10-14).
        if ($section === 'crop-varieties' && $slug === 'rice') {
            return redirect(SitePages::url('rice-varieties'), 301);
        }
        // A page taken off the site: to the guide that covers its subject, for good (2026-10-17).
        if ($to = SitePages::movedTo($section, $slug)) {
            return redirect($to, 301);
        }
        // A crop's own variety finder: /crop-varieties/corn (2026-10-13).
        if ($section === 'crop-varieties' && ($crop = \App\Support\CropVarieties::cropBySlug($slug))) {
            return view('public.site.crop-varieties-crop', [
                'section' => $section,
                'meta' => SitePages::SECTIONS[$section],
                'crop' => $crop,
                'varieties' => \App\Support\CropVarieties::forCrop($crop['key']),
            ]);
        }
        $page = SitePages::find($section, $slug);
        if (! $page && ($sheet = $this->factSheet($request, $section, $slug))) {
            return $sheet;
        }
        abort_unless($page, 404);

        return $this->draw($page, false);
    }

    /**
     * A pest, disease or weed of the field catalogue with no profile page
     * written yet (2026-10-08): its short fact sheet, from its row. Kept out
     * of search engines (thin beside a profile) until a page replaces it.
     */
    private function factSheet(Request $request, string $section, string $slug)
    {
        $F = \App\Support\FieldCatalogue::class;
        $e = match ($section) {
            'pests', 'diseases' => $F::problem($section, $slug),
            'weeds' => $F::weed($slug),
            default => null,
        };
        if (! $e) {
            return null;
        }
        $request->attributes->set('robots', 'noindex, follow');
        // Others of its first crop, the profiles and sheets alike.
        $first = $e['crops'][0] ?? null;
        $all = $section === 'weeds' ? $F::weeds() : $F::problems($section);
        $more = collect($all)->filter(fn ($o, $s) => $s !== $slug && $first && in_array($first, $o['crops'], true))
            ->take(8)->map(fn ($o, $s) => ['name' => $o['name'], 'url' => SitePages::url($section, $s)])->values();

        return response()->view('public.site.fact-sheet', [
            'section' => $section, 'slug' => $slug, 'e' => $e, 'more' => $more,
            'meta' => SitePages::SECTIONS[$section],
        ]);
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
        $static = ['/', '/how-it-works', '/how-it-works/why-anee', '/how-it-works/anee-vs-now', '/features', '/pricing', '/pricing/compare', '/about', '/tutorial', '/contact', '/crops', '/how-to-plant', '/problems', '/pests', '/diseases', '/weeds', '/nutrient-deficiencies', '/land-preparation', '/rice-varieties', '/crop-varieties', '/blog', '/ask-anee', '/questions', '/legal'];
        // Each crop's variety finder (2026-10-13).
        foreach (\App\Support\CropVarieties::crops() as $c) {
            if ($c['hasHub']) {
                $static[] = '/crop-varieties/' . $c['slug'];
            }
        }
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

    /**
     * /llms.txt (2026-10-13): the site in plain Markdown for AI assistants
     * (the llmstxt.org convention): what anee.io is, its plans, its tools,
     * and every live guide and post with its one line answer. Built from the
     * same lists the pages use, so it never says what the site does not.
     */
    public function llms()
    {
        $line = fn (string $s) => trim(preg_replace('/\s+/u', ' ', strip_tags($s)));
        $out = ['# anee.io', '',
            '> anee.io is a farm management app for Filipino farmers. It plans and runs the cropping season (every task dated from each lot\'s own day zero), tracks workers, inventory, costs and harvests, reads the weather and the satellites for each lot, and includes Anee, a smart farm technician who answers in Tagalog, Bisaya, Ilocano or English and reads the farm\'s own records and photos before she answers. It runs in the browser of any phone, works without signal, and has a free plan.', '',
            '## Plans', ''];
        foreach (['libre', 'libreAnee', 'solo', 'owner'] as $t) {
            $c = config('tiers.' . $t);
            if (! $c) {
                continue;
            }
            $price = $c['price'] ? 'PHP ' . $c['price'] . ' a month' : 'free';
            $out[] = '- ' . $c['name'] . ' (' . $price . '): ' . implode('; ', $c['features'] ?? []);
        }
        $out[] = '- Prices and the full comparison: ' . url('/pricing');
        $out[] = '';
        $out[] = '## How the season works, step by step (' . \App\Support\HowItWorks::stepWord() . ' steps)';
        $out[] = '';
        foreach (\App\Support\HowItWorks::stages() as $i => $st) {
            $out[] = '### ' . ($i + 1) . '. ' . $st['title'];
            foreach ($st['items'] as $it) {
                $page = ! empty($it['page']) ? ' (' . SitePages::url('features', $it['page']) . ')' : '';
                $out[] = '- ' . $it['name'] . $page . ': ' . $line($it['what']);
            }
            $out[] = '';
        }
        $out[] = '## Why Anee';
        $out[] = '';
        $out[] = '- [Why Anee answers better than a general AI chat app](' . url('/how-it-works/why-anee') . '): before she answers, Anee reads the farm\'s own lots, crop, variety and planting date, its field profile and the usual soil of its barangay, the forecast for its spot, the ENSO outlook, twenty years of local rain and heat, every typhoon, flood and drought in its town since 2005, the records of over 1,600 varieties and over 7,000 fertilizers and pesticides registered in the Philippines. A general chat app only has what it learned online and what the farmer types.';
        $out[] = '- [Anee vs Now](' . url('/how-it-works/anee-vs-now') . '): nine jobs of a farming season done the usual way and with Anee side by side, what guessing can cost a hectare beside a year of a plan, and the cost of each plan counted in sacks of palay.';
        $out[] = '';
        $names = ['blog' => 'Answers and articles', 'crops' => 'Crop guides', 'how-to-plant' => 'How to plant, the traditional way, crop by crop', 'land-preparation' => 'Land preparation', 'pests' => 'Pests', 'diseases' => 'Diseases', 'weeds' => 'Weeds', 'nutrient-deficiencies' => 'Nutrient deficiencies by crop', 'rice-varieties' => 'Rice varieties', 'crop-varieties' => 'Crop varieties', 'features' => 'Feature pages'];
        try {
            $pages = AsSitePage::live()->orderBy('section')->orderBy('sortOrder')->get(['section', 'slug', 'title', 'excerpt']);
            foreach ($names as $section => $name) {
                $rows = $pages->where('section', $section);
                if ($rows->isEmpty()) {
                    continue;
                }
                $out[] = '## ' . $name;
                $out[] = '';
                foreach ($rows as $p) {
                    $out[] = '- [' . $line($p->title) . '](' . SitePages::url($p->section, $p->slug) . '): ' . \Illuminate\Support\Str::limit($line((string) $p->excerpt), 280);
                }
                $out[] = '';
            }
        } catch (\Throwable $e) {
            // no pages table yet: the app's own story still goes out
        }
        $out[] = '## Contact';
        $out[] = '';
        $out[] = '- Sign up free: ' . url('/signup');
        $out[] = '- Contact: ' . url('/contact');

        return response(implode("\n", $out) . "\n", 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
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
