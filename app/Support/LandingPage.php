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
 * Wrap words of a headline in *stars* to mark them (LandingPage::marked).
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
    public const SHOTS = ['board', 'growth', 'weather', 'hub', 'report-top', 'report-money', 'datediff', 'anee-chat-hand'];

    /** The built-in photos a cost row may show (files in public/images/site/lp). */
    public const PHOTOS = ['tractor', 'sacks', 'storm', 'palay-phone', 'anee-chat-hand'];

    /*
     * The argument, top to bottom (the owner, 2026-09-29/30): a bigger yield
     * despite weather nobody can predict (and the pests it brings), fuel and
     * fertilizer that keep climbing; precision agriculture as the answer; and
     * every tool a farm needs, in one app.
     * Wrap words of a headline in *stars* to mark them.
     */
    public const DEFAULTS = [
        // The browser tab and the link preview; a blank description is the hero's sub.
        'meta' => [
            'title' => 'Precision agriculture on your phone',
            'description' => '',
        ],
        'hero' => [
            'kicker' => 'Precision agriculture for {farmers}',
            'headline' => '*Increase your yield*, even with unpredictable weather and costly fertilizer and fuel.',
            'sub' => 'Precision agriculture on your phone: the right spray, fertilizer and water on the right day, planned around your field\'s weather. Less waste, accurate application, fewer losses, more harvest, higher income.',
            'cta' => 'Create my free account',
            'note' => 'No credit card. Free forever on Libre. Works on any phone.',
            // Where the hero's words sit beside the phone on a wide screen: left
            // or right (against the phone). On a phone they always read left.
            'align' => 'right',
            // An uploaded phone screenshot in place of the board's (blank: the board).
            'image' => '',
            // The two chips that float beside the phone.
            'chips' => [
                ['icon' => '🌿', 'title' => 'Pre-emergence herbicide', 'sub' => 'Sat · DAT 3 · Lot A'],
                ['icon' => '🌧️', 'title' => 'Rain likely from 1 PM', 'sub' => 'The forecast for Lot A'],
            ],
        ],
        // The first thing no farmer controls: the weather, and the pests and
        // diseases it brings (the owner, 2026-09-30). Photo left, words right.
        'problem' => [
            'kicker' => 'The weather changed',
            'headline' => 'Weather you cannot predict brings pests and diseases you did not plan for',
            // An uploaded photo in place of the paddies under a grey sky (blank: that one).
            'image' => '',
            'bullets' => [
                'Rain the day after you spray washes the chemical, and the money, off the field.',
                'Warm, wet weeks bring blast, sheath blight and bacterial leaf blight faster than you can react.',
                'A long dry spell, then the first heavy rains, and the armyworms arrive all at once.',
                'El Niño and La Niña move the planting window, and habit misses it.',
            ],
            // What anee.io does about it (a green tick each), under the costs' label.
            // One short line each, about 36 characters: they never wrap.
            'fixes' => [
                'Hourly forecast for each lot',
                'Pests to watch at every stage',
                'Anee checks a sick leaf in minutes',
            ],
            'solutionKicker' => 'How it works',
            'solutionHeadline' => 'Start in two minutes, in three steps',
            'steps' => [
                ['title' => 'Add your lot and your crop', 'text' => 'Pick from {crops} crops and set the sowing or transplant date. Two minutes.'],
                ['title' => 'Follow the plan, day by day', 'text' => 'Every task on its day, with the stage it falls in and the weather for your field.'],
                ['title' => 'Record it, and see what paid', 'text' => 'Tick the work done; the costs, the harvest and the profit add up by themselves.'],
            ],
        ],
        // The two costs that keep climbing, one row each after the weather:
        // the first with its picture on the right, the next on the left.
        'costs' => [
            'helpsLabel' => 'How anee.io helps',
            'items' => [
                [
                    'kicker' => 'Fuel keeps going up',
                    'headline' => 'Every trip to the field burns more money than last season',
                    'text' => 'The hand tractor, the water pump, the sprayer and the ride to town all run on fuel, and fuel keeps getting dearer. A job done twice, or on the wrong day, burns it twice.',
                    'fixes' => [
                        'Each job on the right day, done once',
                        'Water only where the crop needs it',
                        'Fuel bought and used, tracked',
                    ],
                    'image' => 'tractor', 'upload' => '',
                ],
                [
                    'kicker' => 'Fertilizer keeps going up',
                    'headline' => 'A sack costs more every season. Make every sack count.',
                    'text' => 'Urea and complete fertilizer cost more each planting. Put on at the wrong stage, a sack is paid for in full and only partly taken up by the crop.',
                    'fixes' => [
                        'Apply at the stage it is taken up',
                        'Exact rates per lot, from your stock',
                        'Anee checks a leaf before you buy',
                    ],
                    'image' => 'sacks', 'upload' => '',
                ],
            ],
        ],
        // What guessing costs a hectare: the home page's own "up to" figures.
        'losses' => [
            'headline' => 'What guessing costs a hectare',
            'items' => [
                ['n' => 40, 'title' => 'lost to pests and diseases', 'text' => 'When the spray comes late, or never comes at all.'],
                ['n' => 25, 'title' => 'lost to fertilizer at the wrong time', 'text' => 'The right sack on the wrong week feeds the field a fraction of what it paid for.'],
                ['n' => 25, 'title' => 'lost to water at the wrong time', 'text' => 'Dry at flowering, flooded at ripening: the stage the water missed never comes back.'],
                ['n' => 20, 'title' => 'lost to planting outside the window', 'text' => 'A season started on habit instead of the climate\'s calendar pays for it at harvest.'],
            ],
            'note' => 'Of a hectare\'s harvest. Ranges drawn from FAO crop-loss and Philippine rice research estimates; your own farm\'s numbers vary. Most of it can be avoided, and that is the point.',
        ],
        // The answer, in the four words precision agriculture is built on.
        'precision' => [
            'kicker' => 'The answer: precision agriculture',
            'headline' => 'The right input, the right amount, at the right time, in the right place',
            'sub' => 'It is how the biggest farms protect their yield and cut their costs. anee.io brings it to yours, with no sensors, no drones and no big budget: just your phone.',
            'items' => [
                ['icon' => '⏱️', 'title' => 'The right time', 'text' => 'Each task lands on its day by DAS or DAT and the growth stage, never too early, never too late, and never into the rain.'],
                ['icon' => '⚖️', 'title' => 'The right amount', 'text' => 'Materials and rates set per activity and per lot, drawn from your inventory, so you buy and apply only what the field needs.'],
                ['icon' => '🧪', 'title' => 'The right input', 'text' => 'Anee reads a photo of the leaf or the pest and says what it is and what works, before you pay for the wrong product.'],
                ['icon' => '📍', 'title' => 'The right place', 'text' => 'Every lot keeps its own plan, its own forecast and its own map, with pins where the trouble is.'],
            ],
        ],
        'pillars' => [
            [
                'kicker' => 'Precision timing',
                'title' => 'Hit every stage on the right day',
                'text' => 'A harvest is decided at a few critical stages. The board counts DAS and DAT for you and says what the crop needs at each one, so the fertilizer, the spray and the water arrive when they do the most good.',
                'bullets' => ['Day counts that switch from DAS to DAT at transplant', 'What to do now, and what to watch for, at every stage', 'The days between any two sprays, measured in one tap'],
                'image' => 'growth', 'plan' => 'Free on Libre',
                'upload' => '', 'frame' => 'phone',
            ],
            [
                'kicker' => 'Weather-smart farming',
                'title' => 'Plan around the rain, not after it',
                'text' => 'See the forecast for your own lot, by the day and by the hour, beside your plan. Spray before the rain instead of into it, and let the rain do the irrigating.',
                'bullets' => ['A forecast for each lot\'s own town', 'The chance of rain hour by hour, and what it means today', 'When to Plant reads the El Niño and La Niña outlook'],
                'image' => 'weather', 'plan' => 'Today and tomorrow free; the full forecast from {solo} a month',
                'upload' => '', 'frame' => 'phone',
            ],
            [
                'kicker' => 'Anee, your AI farm technician',
                'title' => 'Know what is wrong before you spend on it',
                'text' => 'Snap the leaf, the pest or the field and ask in Tagalog, Bisaya, Ilocano or Taglish. Anee reads your crop, its stage and your season, and answers with what is accurate and scientifically based, so the fix you buy is the right one.',
                'bullets' => ['Reads a photo of the leaf, the pest or the field', 'Knows your crop, variety, stage and weather', 'Deep analyses: when to plant, what to plant, which variety'],
                'image' => 'anee-chat-hand', 'plan' => 'Libre + Anee, {libreAnee} a month',
                'upload' => '', 'frame' => 'phone',
            ],
            [
                'kicker' => 'Cost control',
                'title' => 'Cut the costs you can, down to the peso',
                'text' => 'Wages, fertilizer, chemicals, services and every extra expense add up by themselves, lot by lot. See what each sack cost to grow, where the money went, and what the season kept.',
                'bullets' => ['Labor, expense and profit reports', 'Inventory: what you bought, used and have left', 'Cash to prepare for any stretch of days'],
                'image' => 'report-top', 'plan' => 'Full reports from {solo} a month',
                'upload' => '', 'frame' => 'phone',
            ],
        ],
        // Every tool, grouped: the whole farm in one app.
        'more' => [
            'headline' => 'Everything you need to manage your farm, in one app',
            'sub' => 'From the first plan to the last sack: one app instead of a notebook, a calculator, a group chat and a guess. Some tools come with the paid plans.',
            // An uploaded phone screenshot in place of the season's modules (blank: those).
            'image' => '',
            'items' => [
                ['group' => 'Plan', 'icon' => '📅', 'title' => 'Season board', 'text' => 'Every task on its day, with its DAS or DAT count.'],
                ['group' => 'Plan', 'icon' => '🌱', 'title' => 'Growth stages', 'text' => 'What the crop needs now, and what comes next.'],
                ['group' => 'Plan', 'icon' => '📋', 'title' => 'Protocol Builder', 'text' => 'Write your crop program once, use it every season.'],
                ['group' => 'Plan', 'icon' => '🧭', 'title' => 'When and what to plant', 'text' => 'Analyses of the window, the crop and the variety.'],
                ['group' => 'Grow', 'icon' => '🌦️', 'title' => 'Weather', 'text' => 'The forecast for each lot, by the day and the hour.'],
                ['group' => 'Grow', 'icon' => '💬', 'title' => 'Chat Anee', 'text' => 'Photo checks and answers, in your own language.'],
                ['group' => 'Grow', 'icon' => '🗺️', 'title' => 'Maps and drawing', 'text' => 'Draw each lot and pin where the trouble is.'],
                ['group' => 'Grow', 'icon' => '📝', 'title' => 'Notes, photos and voice', 'text' => 'Everything you saw, on the day you saw it.'],
                ['group' => 'Manage', 'icon' => '👷', 'title' => 'Workers and attendance', 'text' => 'Who worked, how long, and what each one is owed.'],
                ['group' => 'Manage', 'icon' => '📦', 'title' => 'Inventory', 'text' => 'Fertilizer and chemicals in, used, and left.'],
                ['group' => 'Manage', 'icon' => '💸', 'title' => 'Expenses and income', 'text' => 'Every peso in and out, by the day and by the lot.'],
                ['group' => 'Manage', 'icon' => '🤝', 'title' => 'Collab Room', 'text' => 'Team chat and a shared whiteboard for the crew.'],
                ['group' => 'Measure', 'icon' => '📊', 'title' => 'Reports', 'text' => 'Labor, expenses, profit, and the protocol followed.'],
                ['group' => 'Measure', 'icon' => '✨', 'title' => 'Season report by Anee', 'text' => 'What went right, and what to change next time.'],
                ['group' => 'Measure', 'icon' => '⚖️', 'title' => 'Compare seasons', 'text' => 'Two seasons side by side, lot by lot.'],
                ['group' => 'Measure', 'icon' => '📴', 'title' => 'Works without signal', 'text' => 'Keep recording in the field; it syncs when you are back.'],
                ['group' => 'Measure', 'icon' => '👥', 'title' => 'A farmers community', 'text' => 'Ask other farmers, and share what worked.'],
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
                ['q' => 'Do I need sensors, drones or special equipment?', 'a' => 'No. Precision agriculture in anee.io runs on what you already have: your phone, your crop\'s growth calendar, the forecast for your field, and your own records.'],
                ['q' => 'Will it really increase my harvest?', 'a' => 'No app can promise a number, because the weather and the market decide part of it. What anee.io does is take away the losses that come from late, early or wrong applications and from costs nobody tracked, which is where most avoidable losses are.'],
                ['q' => 'Do I need a credit card?', 'a' => 'No. You sign up with {signupWays}. Paid plans are paid by {pay}, only if and when you choose one.'],
                ['q' => 'Can I cancel any time?', 'a' => 'Nothing renews by itself. You pay for a month or a year at a time, and if you stop, your season and your records stay safe and readable.'],
                ['q' => 'Does it work on my phone, even in the field?', 'a' => 'Yes. anee.io runs in the browser of any phone, tablet or computer, and it is built for the phone first. Paid plans also keep working when the signal drops.'],
                ['q' => 'Which crops does it know?', 'a' => '{crops} crops grown in the Philippines, each with its own growth stages and day count: rice transplanted or direct seeded, corn, vegetables, fruit trees and more.'],
                ['q' => 'Can my workers use it too?', 'a' => 'Yes. On Solo Farmer you keep their days and their pay; on Farm Owner they log in themselves, and you choose what each one may see or do.'],
                ['q' => 'Is my farm data private?', 'a' => 'Yes. Your seasons, notes and money are yours alone unless you choose to share something with the community.'],
            ],
        ],
        'closer' => [
            'headline' => 'Make this your *most precise season* yet.',
            'sub' => 'Set up your first lot in two minutes. anee.io keeps the days, the weather and the money, so you can keep your eyes on the harvest.',
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

    /** A headline, escaped, with its *starred* words marked. */
    public static function marked(string $text): string
    {
        return preg_replace('/\*([^*]+)\*/u', '<em>$1</em>', e($text));
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
