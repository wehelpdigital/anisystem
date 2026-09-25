<?php

namespace App\Http\Controllers;

use App\Models\AsLegalPage;
use App\Support\CommunityText;
use Illuminate\Support\Str;

/**
 * Public legal / info pages (Privacy, Terms, Cookies, About) — content is
 * managed from the mother app and rendered here read-only.
 */
class LegalController extends Controller
{
    /** The face comes first on the address (/ph/legal/privacy); scalar route parameters arrive by position. */
    public function show(string $face, string $slug)
    {
        // One read serves the page and the switcher above it: every published
        // page, the owner's own additions included, in the mother's order.
        $pages = AsLegalPage::active()
            ->published()
            ->orderBy('sortOrder')
            ->orderBy('id')
            ->get();

        $page = $pages->firstWhere('slug', $slug);

        if (! $page) {
            // A core address with no row at all (a fresh database) wears the
            // standard wording. A row that exists but is a draft or retired
            // is the owner's decision, and stays a 404.
            $page = AsLegalPage::where('slug', $slug)->exists() ? null : AsLegalPage::fromDefault($slug);
            if (! $page) {
                abort(404);
            }
            $pages = $pages->push($page)->sortBy('sortOrder')->values();
        }
        // Written for the home market; on the international face the
        // identity words widen (the text in the database is untouched).
        // Country names stay -- a governing-law clause must keep its country.
        if (\App\Support\Region::englishOnly()) {
            $page->body = str_replace(['Filipino farmers', 'Filipino farmer', 'Filipino farms'], ['farmers', 'farmer', 'farms'], (string) $page->body);
        }

        [$html, $toc] = $this->withAnchors(CommunityText::safeHtml($page->body));

        // The pages link to each other and to the public pages by their old,
        // face-less addresses ("/legal/cookies", "/pricing"), which answer
        // with a redirect that picks a face for the visitor. Point them at
        // the face being read instead, so an /en reader stays on /en.
        $html = preg_replace(
            '~href="/(legal/[a-z0-9\-]+|pricing|contact|about|features|tutorial)(?=["#?])~',
            'href="/' . $face . '/$1',
            $html
        ) ?? $html;

        return view('legal.show', [
            'page' => $page,
            'html' => $html,
            'toc' => $toc,
            'pages' => $pages,
        ]);
    }

    /**
     * Give every section heading an id, and list them for "On this page".
     *
     * Runs AFTER the sanitiser, which allows no attributes on headings, so
     * the ids are ours and cannot be smuggled in through the editor. A
     * leading "3." becomes the list's number rather than part of its words.
     *
     * @return array{0:string,1:array<int,array{id:string,num:?string,text:string}>}
     */
    private function withAnchors(string $html): array
    {
        $toc = [];
        $used = [];
        $html = preg_replace_callback('~<h3>(.*?)</h3>~s', function ($m) use (&$toc, &$used) {
            $text = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text === '') {
                return $m[0];
            }
            $num = null;
            $words = $text;
            if (preg_match('/^(\d{1,2})[.)]\s+(.+)$/u', $text, $n)) {
                [$num, $words] = [$n[1], $n[2]];
            }
            $id = Str::slug($words) ?: 'section';
            $base = $id;
            for ($i = 2; isset($used[$id]); $i++) {
                $id = $base . '-' . $i;
            }
            $used[$id] = true;
            $toc[] = ['id' => $id, 'num' => $num, 'text' => $words];

            return '<h3 id="' . e($id) . '">' . $m[1] . '</h3>';
        }, $html) ?? $html;

        return [$html, $toc];
    }
}
