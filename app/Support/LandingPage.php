<?php

namespace App\Support;

use App\Models\AsSiteSetting;

/**
 * The ads landing page (/{face}/start, 2026-09-29): what it says.
 *
 * Every word is editable from the mother app (AniSystem > Landing page),
 * which writes the whole page as JSON under `landing.page` on the site
 * settings shelf; what is not stored falls back to DEFAULTS here, section
 * by section and field by field. Testimonials are the page's own list,
 * written there too (name, role, place, words, photo); none are written in
 * code, because a testimonial must be a real farmer's, and with none the
 * section is not drawn at all. (The mother's Testimonials module belongs to
 * the AniSenso site and is not read here.)
 *
 * Tokens in any text: {farmers} (the region's word for its farmers),
 * {crops} (how many crops the catalogue keeps for the country), {pay}
 * (how paid plans are paid), {signupWays} (email, and Google when it is on), {libreAnee} and {solo} (their monthly prices),
 * so the page cannot drift from the app it sells.
 */
class LandingPage
{
    public const KEY = 'landing.page';

    /**
     * DEFAULTS, published on the shelf for the mother's editor to prefill its
     * form and to tell an edit from a default (it stores only what differs).
     * Re-published whenever the page is drawn and the code's defaults changed.
     */
    public const DEFAULTS_KEY = 'landing.defaults';

    /** The built-in pictures a pillar may show (files in public/images/site/lp). */
    public const SHOTS = ['board', 'growth', 'report-top', 'report-money', 'datediff', 'anee-chat-hand'];

    public const DEFAULTS = [
        // The browser tab and the link preview; a blank description is the hero's sub.
        'meta' => [
            'title' => 'Plan your cropping season on your phone',
            'description' => '',
        ],
        'hero' => [
            'kicker' => 'For {farmers} · Free forever on Libre',
            'headline' => 'Never miss the right day to spray, fertilize or harvest again.',
            'sub' => 'anee.io is the cropping schedule app built for {farmers}: your whole season planned day by day, what the crop needs at every stage, and what the season really cost you. All on your phone.',
            'cta' => 'Create my free account',
            'note' => 'No credit card. Free forever on Libre. Works on any phone.',
            // An uploaded phone screenshot in place of the board's (blank: the board).
            'image' => '',
            // The two chips that float beside the phone (they describe its screenshot).
            'chips' => [
                ['icon' => '🌿', 'title' => 'Pre-emergence herbicide', 'sub' => 'Sat · DAT 3 · Lot A'],
                ['icon' => '🌾', 'title' => 'Early tillering', 'sub' => 'What to do now: first nitrogen'],
            ],
        ],
        'proof' => [
            'lead' => 'Made in the Philippines, used on real fields every day',
            'items' => ['{crops} crops with their own growth calendars', 'DAS, DAT and DAP day counts', 'Weather for your own field', 'Anee answers in Taglish'],
            // The live line under it ("430+ farm activities planned so far"): show or hide.
            'stats' => 'show',
        ],
        'problem' => [
            'kicker' => 'Sound familiar?',
            'headline' => 'Still running the season from a notebook and memory?',
            // An uploaded photo in place of the farmer among the sacks (blank: that one).
            'image' => '',
            'bullets' => [
                'A spray goes on a week late because nobody kept the day count.',
                'Payday comes and no one is sure who worked how many days.',
                'You only find out at harvest whether the season made money.',
                'The advice arrives after the damage is already done.',
            ],
            'solutionKicker' => 'There is a better way',
            'solutionHeadline' => 'anee.io keeps the season for you, in three steps',
            'steps' => [
                ['title' => 'Add your lot and your crop', 'text' => 'Pick from {crops} crops, set the sowing or transplant date. Two minutes.'],
                ['title' => 'Get the plan, day by day', 'text' => 'Every task on its day, with the day count and the growth stage it falls in.'],
                ['title' => 'Tick it done, see the money', 'text' => 'What was done, what it cost, and what is due next, on one screen.'],
            ],
        ],
        'pillars' => [
            [
                'kicker' => 'The season board',
                'title' => 'Every task lands on the right day',
                'text' => 'Your spray, top-dress and irrigation dates stop living in your head. The board lays the season out day by day, counts DAS and DAT for you, and shows the growth stage on every date.',
                'bullets' => ['Day counts that switch from DAS to DAT at transplant', 'Tick work done and the board keeps the record', 'Measure the days between any two sprays in one tap'],
                'image' => 'board', 'plan' => 'Free on Libre',
                'upload' => '', 'frame' => 'phone',
            ],
            [
                'kicker' => 'Growth stages',
                'title' => 'Know what the crop needs today',
                'text' => 'Open any lot and see the stage it is in right now, what to do in this stage, and what to watch for, before the problem shows.',
                'bullets' => ['What to do now, in plain words', 'The pests and weather that matter at this stage', 'When the next stage begins'],
                'image' => 'growth', 'plan' => 'Free on Libre',
                'upload' => '', 'frame' => 'phone',
            ],
            [
                'kicker' => 'Anee, your farm technician',
                'title' => 'Send a photo, get an answer in minutes',
                'text' => 'Snap the leaf, ask in Tagalog, Bisaya, Ilocano or Taglish. Anee reads your crop and your season and answers with what is accurate and scientifically based, not guesses.',
                'bullets' => ['Reads a photo of the leaf, the pest or the field', 'Knows your crop, variety and stage', 'Deep analyses: when to plant, what to plant, which variety'],
                'image' => 'anee-chat-hand', 'plan' => 'Libre + Anee, {libreAnee} a month',
                'upload' => '', 'frame' => 'phone',
            ],
            [
                'kicker' => 'Reports',
                'title' => 'See if the season paid, down to the peso',
                'text' => 'Wages, materials, services and every extra expense add up by themselves. At the end, see what came in, what went out, and what each lot kept.',
                'bullets' => ['Labor, expenses and profit reports', 'Cash to prepare for any stretch of days', 'A season report from Anee with what to change next time'],
                'image' => 'report-top', 'plan' => 'Full reports from {solo} a month',
                'upload' => '', 'frame' => 'phone',
            ],
        ],
        'more' => [
            'headline' => 'And the rest of the farm, too',
            'items' => [
                ['icon' => '🌦️', 'title' => 'Weather for your field', 'text' => 'The forecast for your lot, day by day.'],
                ['icon' => '📝', 'title' => 'Notes and photos', 'text' => 'Everything you saw, on the day you saw it.'],
                ['icon' => '👷', 'title' => 'Workers and pay', 'text' => 'Who worked, how long, and what each one is owed.'],
                ['icon' => '📦', 'title' => 'Inventory', 'text' => 'Fertilizer and chemicals in, used, and left.'],
                ['icon' => '🗺️', 'title' => 'Maps of your lots', 'text' => 'Draw each lot and pin where the trouble is.'],
                ['icon' => '👥', 'title' => 'A farmers community', 'text' => 'Ask other farmers and share what worked.'],
            ],
        ],
        'testimonials' => [
            'headline' => 'Farmers who plan with anee.io',
            // Each: name, role, location, quote, result (the measurable part,
            // e.g. "Saved 2 sprays this season"), rating (0-5), photo (a URL,
            // or a path on the mother app's disk). Written only in the mother app.
            'items' => [],
        ],
        'faq' => [
            'headline' => 'Questions before you start',
            'items' => [
                ['q' => 'Is it really free?', 'a' => 'Yes. Libre is free forever: one active season on one lot, the season board, growth stages, notes and photos, and weather for today and tomorrow. A few ads keep it free.'],
                ['q' => 'Do I need a credit card?', 'a' => 'No. You sign up with {signupWays}. Paid plans are paid by {pay}, only if and when you choose one.'],
                ['q' => 'Can I cancel any time?', 'a' => 'Nothing renews by itself. You pay for a month or a year at a time, and if you stop, your season and your records stay safe and readable.'],
                ['q' => 'Does it work on my phone, even in the field?', 'a' => 'Yes. anee.io runs in the browser of any phone, tablet or computer, and it is built for the phone first. Paid plans also keep working when the signal drops.'],
                ['q' => 'Which crops does it know?', 'a' => '{crops} crops grown in the Philippines, each with its own growth stages and day count: rice transplanted or direct seeded, corn, vegetables, fruit trees and more.'],
                ['q' => 'Can my workers use it too?', 'a' => 'Yes, on the Solo Farmer and Owner plans. You invite them, choose what each one may see or do, and they use it free on your plan.'],
                ['q' => 'Is my farm data private?', 'a' => 'Yes. Your seasons, notes and money are yours alone unless you choose to share something with the community.'],
            ],
        ],
        'closer' => [
            'headline' => 'Plan your next season tonight.',
            'sub' => 'Set up your first lot in two minutes, and let anee.io keep the days, the stages and the money for you.',
            'cta' => 'Create my free account',
            'risk' => 'Free forever on Libre · No credit card · Upgrade only when you want',
            // An uploaded background in place of the farmer in the palay (blank: that one).
            'image' => '',
        ],
    ];

    /** The page's words: what the mother stored, over the defaults, tokens filled. */
    public static function content(): array
    {
        $rows = [];
        try {
            $rows = AsSiteSetting::query()->whereIn('key', [self::KEY, self::DEFAULTS_KEY])->pluck('value', 'key')->all();
        } catch (\Throwable $e) {
            // No shelf yet: the defaults alone.
        }
        self::publishDefaults($rows[self::DEFAULTS_KEY] ?? null);
        $stored = json_decode((string) ($rows[self::KEY] ?? ''), true);
        $page = self::merge(self::DEFAULTS, is_array($stored) ? $stored : []);

        return self::fill($page, self::tokens());
    }

    /** Put the code's defaults on the shelf when what is there is not them. */
    public static function publishDefaults(?string $onShelf = null): void
    {
        $json = json_encode(self::DEFAULTS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($onShelf === $json) {
            return;
        }
        try {
            AsSiteSetting::put(self::DEFAULTS_KEY, $json);
        } catch (\Throwable $e) {
            // Read-only or missing shelf: the editor waits for the next draw.
        }
    }

    /** An uploaded picture's address, or the built-in file's when none was uploaded. */
    public static function imageUrl(?string $upload, string $builtIn): string
    {
        return self::photoUrl($upload) ?? asset('images/site/' . $builtIn);
    }

    /**
     * A testimonial photo's address: as given, or on the mother app's public
     * disk, where its Landing page editor puts an upload (the ads' way).
     */
    public static function photoUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return MediaStore::url(MediaStore::REMOTE_PREFIX . ltrim($path, '/'));
    }

    private static function tokens(): array
    {
        $crops = 0;
        foreach (CropCatalog::CROPS as $c) {
            if (empty($c['intl'])) {
                $crops++;
            }
        }

        return [
            '{farmers}' => Region::t('farmersOf'),
            '{crops}' => (string) $crops,
            '{pay}' => Region::payMethod(),
            // Google only where its sign-in is switched on.
            '{signupWays}' => filled(config('services.google.client_id')) ? 'your email or your Google account' : 'just your email',
            '{libreAnee}' => Region::priceTag(Region::tierPrice('libreAnee', 'month')),
            '{solo}' => Region::priceTag(Region::tierPrice('solo', 'month')),
        ];
    }

    /**
     * Stored over defaults. An associative array merges key by key; a list
     * (bullets, steps, pillars, questions) is taken whole from what was
     * stored when it holds anything, so the editor can remove an item. Each
     * stored item is given every field its default kind has (blank where it
     * had none), so the page never reads a field that is not there.
     */
    private static function merge(array $base, array $over): array
    {
        foreach ($over as $k => $v) {
            if (! array_key_exists($k, $base)) {
                continue;
            }
            if (is_array($base[$k]) && is_array($v)) {
                if (! array_is_list($base[$k])) {
                    $base[$k] = self::merge($base[$k], $v);
                    continue;
                }
                $first = $base[$k][0] ?? null;
                if (is_string($first)) {
                    $v = array_values(array_filter($v, fn ($x) => is_string($x) && trim($x) !== ''));
                } elseif (is_array($first)) {
                    $blank = self::shape($first);
                    $v = array_values(array_map(fn ($x) => array_replace($blank, $x), array_filter($v, 'is_array')));
                } else {
                    // A list the page may leave empty (testimonials): items as stored.
                    $v = array_values(array_filter($v, 'is_array'));
                }
                if ($v !== [] || $base[$k] === []) {
                    $base[$k] = $v;
                }
            } elseif (is_string($base[$k]) && is_string($v) && trim($v) !== '') {
                $base[$k] = $v;
            }
        }

        return $base;
    }

    /** An item's fields with nothing in them: strings blank, lists empty. */
    private static function shape(array $item): array
    {
        return array_map(fn ($x) => is_array($x) ? (array_is_list($x) ? [] : self::shape($x)) : (is_string($x) ? '' : $x), $item);
    }

    private static function fill(mixed $v, array $tokens): mixed
    {
        if (is_array($v)) {
            return array_map(fn ($x) => self::fill($x, $tokens), $v);
        }

        return is_string($v) ? strtr($v, $tokens) : $v;
    }
}
