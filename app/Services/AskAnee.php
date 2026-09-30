<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\AsAskQuestion;
use App\Models\AsSitePage;
use App\Support\AiUsage;
use App\Support\AneeEmoji;
use App\Support\CropCatalog;
use App\Support\SeoKeywords;
use App\Support\SitePages;
use Illuminate\Support\Str;

/**
 * Try and Ask Anee's two thinking steps (2026-10-01).
 *
 * classify()  the moment a question is typed: is it about farming, what does
 *             Anee say back (in the visitor's language, with her faces), and
 *             does an article on the site already answer it.
 * answer()    when the emailed link is opened: the question becomes a page
 *             in the "questions" section (/question/{slug}), written by
 *             PageWriter with research first, keywords from as_seo_keywords
 *             and a word about anee.io for this exact topic.
 */
class AskAnee
{
    public function __construct(private AiClient $ai, private PageWriter $writer) {}

    /** @return array{ok: bool, agri: bool, reply: string, topic: string, crop: ?string, lang: string, match: ?int, error: ?string} */
    public function classify(string $question): array
    {
        $settings = AiSetting::current()->forField('PH');
        $candidates = $this->candidates($question);
        $list = $candidates->map(fn ($p) => $p->id . ': ' . $p->title)->implode("\n");
        $crops = collect(CropCatalog::CROPS)->map(fn ($c, $k) => $k . ' = ' . CropCatalog::label($k))->implode('; ');

        $prompt = "A visitor on anee.io's public website typed one free question into \"Try and Ask Anee\". They will give a few farm details next, and the full answer is sent to their email.\n\n"
            . "Their question:\n\"\"\"" . $question . "\"\"\"\n\n"
            . "Questions this site has already answered (id: title):\n" . ($list !== '' ? $list : '(none yet)') . "\n\n"
            . "Crop keys: {$crops}\n\n"
            . "Decide, and return ONLY a JSON object:\n"
            . "{\"agri\": true or false, \"reply\": \"...\", \"topic\": \"...\", \"crop\": \"a crop key or null\", \"lang\": \"en or tl\", \"match\": an id or null}\n\n"
            . "agri: true when it is about agriculture and crops: planting, seeds and varieties, soil, water, fertilizer, pests, diseases, weeds, crop growth, harvest and post harvest, crop prices and selling, farm costs, farm tools and machines, farm planning. False for anything else (other subjects, greetings with no question, requests unrelated to farming).\n"
            . "reply: what you say to them now, 1 to 3 short sentences in the visitor's own language (Tagalog, Taglish or English), warm, with one or two of your faces (:anee-NAME:).\n"
            . "  If agri is false: say sorry, you only answer questions about agriculture and crops, and invite them to ask one. Do not answer their question.\n"
            . "  If agri is true: do NOT answer yet. Say it is a good question, and that to analyze it properly you need a few details about their farm: the farm size, the crop, and where the farm is.\n"
            . "topic: 3 to 8 plain English words naming the subject, e.g. \"yellow leaves on rice after transplanting\".\n"
            . "crop: the crop key when the question clearly names a crop, else null.\n"
            . "lang: \"tl\" when the question is mostly Tagalog or Taglish, else \"en\".\n"
            . "match: the id of a question above ONLY when that page already fully answers this same question (same crop, same problem, same need). Otherwise null.\n\n"
            . AneeEmoji::promptLine();

        $parse = function (string $t): ?array {
            $t = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($t));
            $s = strpos($t, '{');
            $e = strrpos($t, '}');
            $d = $s !== false && $e !== false ? json_decode(substr($t, $s, $e - $s + 1), true) : null;

            return is_array($d) && array_key_exists('agri', $d) && trim((string) ($d['reply'] ?? '')) !== '' ? $d : null;
        };
        $r = $this->ai->ask($settings, [], $prompt, null, 700, ['timeout' => 45]);
        $d = ($r['ok'] ?? false) ? $parse((string) $r['text']) : null;
        if ($d === null && ($r['ok'] ?? false)) {
            $r2 = $this->ai->ask($settings, [], $prompt . "\n\nReturn ONLY the JSON object.", null, 700, ['timeout' => 45]);
            $d = ($r2['ok'] ?? false) ? $parse((string) $r2['text']) : null;
            $r['tokensIn'] = (int) ($r['tokensIn'] ?? 0) + (int) ($r2['tokensIn'] ?? 0);
            $r['tokensOut'] = (int) ($r['tokensOut'] ?? 0) + (int) ($r2['tokensOut'] ?? 0);
        }
        AiUsage::record('ask-classify', 0, 0, null, $settings, $r, 0);
        if ($d === null) {
            return ['ok' => false, 'agri' => false, 'reply' => '', 'topic' => '', 'crop' => null, 'lang' => 'en', 'match' => null,
                'error' => $r['error'] ?? 'Anee could not read that just now. Please try again.'];
        }
        $match = (int) ($d['match'] ?? 0);

        return [
            'ok' => true,
            'agri' => (bool) $d['agri'],
            'reply' => trim((string) $d['reply']),
            'topic' => Str::limit(trim((string) ($d['topic'] ?? '')), 180, ''),
            'crop' => isset(CropCatalog::CROPS[(string) ($d['crop'] ?? '')]) ? (string) $d['crop'] : null,
            'lang' => ($d['lang'] ?? '') === 'tl' ? 'tl' : 'en',
            'match' => $match > 0 && $candidates->contains('id', $match) ? $match : null,
            'error' => null,
        ];
    }

    /**
     * The question pages worth showing the model: all of them while there
     * are few, then the ones sharing the most words with the question.
     */
    private function candidates(string $question)
    {
        $all = AsSitePage::live()->where('section', 'questions')->orderByDesc('id')->limit(400)->get(['id', 'title', 'focusKeyword', 'excerpt']);
        if ($all->count() <= 40) {
            return $all;
        }
        $words = SeoKeywords::words($question);

        return $all->map(function ($p) use ($words) {
            $p->hit = count(array_intersect($words, SeoKeywords::words($p->title . ' ' . $p->focusKeyword . ' ' . $p->excerpt)));

            return $p;
        })->filter(fn ($p) => $p->hit > 0)->sortByDesc('hit')->take(25)->values();
    }

    /** The article, written and saved. Throws when the writer fails. */
    public function answer(AsAskQuestion $q, ?callable $beat = null): AsSitePage
    {
        $crop = \App\Http\Controllers\AskAneeController::plain((string) ($q->cropLabel ?: ($q->crop ? CropCatalog::label($q->crop) : '')));
        $farm = trim(implode(' ', array_filter([
            $q->farmWords() ? $q->farmWords() . ' of' : '',
            $crop ?: 'crops',
            $q->placeWords() ? 'in ' . $q->placeWords() : '',
            $q->country && $q->country !== 'PH' ? '(' . (\App\Support\Region::name($q->country) ?? $q->country) . ')' : '',
        ])));
        $keywords = SeoKeywords::candidates($q->question . ' ' . $q->topic . ' ' . $crop, 25)
            ->map(fn ($k) => $k->keyword . ' (' . number_format((int) $k->volume) . ' searches)')->all();

        $done = $this->writer->write([
            'section' => 'questions',
            'topic' => (string) $q->topic,
            'question' => (string) $q->question,
            'farm' => $farm,
            'keywords' => $keywords,
            'lang' => $q->lang ?: 'en',
            'research' => true,
            'country' => $q->country ?: 'PH',
        ], $beat);
        AiUsage::record('ask-article', 0, 0, $q->id, AiSetting::current(), $done['result'], 0);
        if (! $done['ok']) {
            throw new \RuntimeException($done['error'] ?: 'The answer could not be written.');
        }
        $f = $done['page'];

        // The question itself opens the page: what was asked, on what farm.
        $asked = [
            'type' => 'callout',
            'tone' => 'info',
            'title' => $f['lang'] === 'tl' ? 'Ang tanong' : 'The question',
            'text' => '"' . \App\Support\ArticleStyle::clean(Str::limit($q->question, 600)) . '"'
                . ($farm !== '' && $farm !== 'crops' ? ($f['lang'] === 'tl' ? ' Tanong ng isang magsasaka na may ' : ' Asked by a farmer with ') . $farm . '.' : ''),
        ];
        $blocks = array_merge([$asked], $f['blocks']);

        $slug = $f['slug'] ?: Str::slug($q->topic ?: $f['title']);
        $base = $slug;
        for ($i = 2; AsSitePage::where('section', 'questions')->where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        $page = AsSitePage::create([
            'section' => 'questions',
            'slug' => $slug,
            'lang' => $f['lang'],
            'category' => $f['category'] ?: ($crop ? Str::before($crop, ' (') : 'Farming'),
            'title' => $f['title'],
            'metaTitle' => $f['metaTitle'],
            'metaDescription' => $f['metaDescription'],
            'focusKeyword' => $f['focusKeyword'],
            'keywords' => $f['keywords'],
            'excerpt' => $f['excerpt'],
            'heroImage' => $this->hero($q->crop ?: $f['crop'], $f['title']),
            'blocks' => $blocks,
            'status' => 'published',
            'sortOrder' => 0,
            'publishedAt' => now(),
            'deleteStatus' => 1,
        ]);
        SeoKeywords::markUsed($f['title'] . ' ' . $f['excerpt'] . ' ' . json_encode($blocks, JSON_UNESCAPED_UNICODE),
            array_merge([$f['focusKeyword']], $f['keywords'], SeoKeywords::candidates($q->question . ' ' . $q->topic, 25)->pluck('keyword')->all()));
        SitePages::forgetCaches();

        return $page;
    }

    /**
     * A picture for the page: the crop guide's own photo when the crop has
     * one, the site's farm photos otherwise. Never invented.
     */
    public function hero(?string $crop, string $title): array
    {
        $family = $crop ? (string) strtok((string) (\App\Support\CropStages::normalize($crop) ?? $crop), '_') : '';
        $guide = [
            'rice' => 'palay', 'corn' => 'corn-seeds', 'coconut' => 'coconut-fertilizer', 'banana' => 'banana-farming-philippines',
        ][$family] ?? null;
        $group = $crop ? (CropCatalog::CROPS[$crop]['group'] ?? '') : '';
        if (! $guide && str_contains($group, 'vegetable')) {
            $guide = 'vegetables-philippines';
        } elseif (! $guide && $group === 'Fruit trees') {
            $guide = 'pagtatanim-ng-puno';
        }
        if ($guide) {
            $h = SitePages::find('crops', $guide)?->heroImage;
            if (is_array($h) && ! empty($h['src'])) {
                return $h;
            }
        }
        $pool = [
            ['/images/site/photos/inspect.jpg', 'A farmer checking the leaves of a crop in the field'],
            ['/images/site/fields-aerial.jpg', 'Farm fields seen from above'],
            ['/images/site/harvest-hands.jpg', 'Farm workers harvesting by hand'],
            ['/images/site/photos/farmer-hijab.jpg', 'A farmer standing in her field'],
            ['/images/site/photos/hero-planting.jpg', 'Planting seedlings by hand'],
        ];
        [$src, $alt] = $pool[abs(crc32($title)) % count($pool)];

        return ['src' => $src, 'alt' => $alt, 'credit' => ''];
    }
}
