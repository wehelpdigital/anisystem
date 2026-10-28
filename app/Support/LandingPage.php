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
 * {tools} (how many tools How It Works lists for the country), {freeTools} (how many of them every plan has),
 * {cropResearch} and {pesoBasis} (the losses band's source note, by country),
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

    /** The built-in photos a cost row may show (files in public/images/site/lp). */
    public const PHOTOS = ['tractor', 'sacks', 'storm', 'palay-phone', 'anee-chat-hand'];

    /** The photos a loss card may wear (files in public/images/site/lp/loss). */
    public const LOSS_PHOTOS = ['palay-heads', 'sacks', 'palay-phone', 'sacks-shed', 'transplant', 'storm-paddies', 'farmer-hijab', 'hero-planting'];

    /*
     * The argument, top to bottom (the owner, 2026-09-29/30): a bigger yield
     * despite weather nobody can predict (and the pests it brings), fuel and
     * fertilizer that keep climbing; precision agriculture as the answer; and
     * every tool a farm needs, in one app. Since 2026-10-28 the page also shows
     * why advice from strangers fails (the Facebook post), the newest tools,
     * and every tool in folds that open onto how it helps; the four feature
     * pillars are gone (the page was 22,000 px on a phone).
     * Wrap words of a headline in *stars* to mark them.
     */
    public const DEFAULTS = [
        // The browser tab and the link preview; a blank description is the hero's sub.
        'meta' => [
            'title' => 'Start Precision Farming on Your Phone, Free',
            'description' => 'Know the right spray, fertilizer and water for each day, planned around your own field\'s weather. Waste less, lose less and harvest more. Free to start.',
        ],
        'hero' => [
            'kicker' => 'Made for {farmers}',
            'headline' => '*Increase your yield*, even when the weather turns on you and fertilizer and fuel keep going up.',
            'sub' => 'Know the right spray, fertilizer and water for each day, planned around your own field\'s weather. Waste less, lose less, and harvest more.',
            'cta' => 'Create my free account',
            'note' => 'Takes 2 minutes. No credit card. Free forever on Libre.',
            // Where the hero's words sit beside the phone on a wide screen: left
            // or right (against the phone). On a phone they always read left.
            'align' => 'right',
            // An uploaded phone screenshot in place of the board's (blank: the board).
            'image' => '',
            // The two chips that float beside the phone.
            'chips' => [
                ['icon' => '🌿', 'title' => 'Preemergence herbicide', 'sub' => 'Sat · DAT 3 · Lot A'],
                ['icon' => '🌧️', 'title' => 'Rain likely from 1 PM', 'sub' => 'The forecast for Lot A'],
            ],
        ],
        // The first thing no farmer controls: the weather, and the pests and
        // diseases it brings (the owner, 2026-09-30). Photo left, words right.
        'problem' => [
            // The centered title over all three problem rows (weather, fuel,
            // fertilizer): the small green line, the title and the line under it
            // (the owner, 2026-09-30).
            'sectionKicker' => 'Sound familiar?',
            'sectionTitle' => 'Filipino Farmers Are Slowly Becoming Bankrupt',
            'sectionSub' => 'Everything costs more every season. The weather no longer keeps its calendar. And when something goes wrong, help comes late. Too many farmers end the season with a loan they can\'t pay.',
            'kicker' => 'The weather changed',
            'headline' => 'Weather you can\'t count on, and the pests, diseases and lodging it brings',
            // An uploaded photo in place of the paddies under a grey sky (blank: that one).
            'image' => '',
            'bullets' => [
                'You spray, and it rains the next day. The chemical, and your money, wash away.',
                'Warm, wet weeks bring blast, sheath blight and leaf blight before you can act.',
                'A long dry spell, then heavy rain, and the armyworms come all at once.',
            ],
            // What anee.io does about it (a green tick each), under the costs' label.
            // One short line each, about 36 characters: they never wrap.
            'fixes' => [
                'Know the best time to plant',
                'Ask Anee what stops the pest',
                'Anee reads a sick leaf from a photo',
            ],
            'solutionKicker' => 'Getting started is easy',
            'solutionHeadline' => 'Three steps. Two minutes.',
            'steps' => [
                ['title' => 'Add your lot and your crop', 'text' => 'Pick from {crops} crops and set the day you plant. That\'s it.'],
                ['title' => 'Follow the plan, day by day', 'text' => 'Every task on its day, with your crop\'s stage and your field\'s weather.'],
                ['title' => 'Tick it done, and see what paid', 'text' => 'Tick the work done. The costs, the harvest and the profit add up on their own.'],
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
                    'text' => 'The hand tractor, the pump, the sprayer and the ride to town all run on fuel, and fuel keeps going up. A job done twice, or on the wrong day, burns it twice.',
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
                    'text' => 'Urea and complete cost more each planting. Put on at the wrong stage, you pay for the whole sack, but the crop takes only part of it.',
                    'fixes' => [
                        'Apply at the stage it is taken up',
                        'Exact rates per lot, from your stock',
                        'Anee checks a leaf before you buy',
                    ],
                    'image' => 'sacks', 'upload' => '',
                ],
            ],
        ],
        // What guessing costs a hectare: the home page's eight "up to" leaks
        // ("What a Season Loses Without Intervention"), each with its photo
        // (lp/loss/<image>.webp, or an upload) and what it takes from a
        // hectare in pesos (shown on the Philippine page; the other page shows
        // the percentage of a hectare's gross instead).
        'losses' => [
            'headline' => 'Here\'s what guessing can cost you',
            'sub' => 'When you can\'t be sure, you guess. And every guess has a price. This is the most each one can take from one hectare.',
            'items' => [
                ['n' => 40, 'title' => 'Yield lost to pests and diseases', 'text' => 'When the fix comes late, or never comes at all.', 'peso' => '₱24,000 to ₱40,000', 'image' => 'palay-heads', 'upload' => ''],
                ['n' => 30, 'title' => 'Wasted on the wrong solution', 'text' => 'The wrong product, at full price, while the real problem keeps eating.', 'peso' => '₱18,000 to ₱30,000', 'image' => 'sacks', 'upload' => ''],
                ['n' => 30, 'title' => 'Yield lost to trying myths that are not true', 'text' => 'Old beliefs and hearsay, tried on a whole field before anyone checked.', 'peso' => '₱18,000 to ₱30,000', 'image' => 'palay-phone', 'upload' => ''],
                ['n' => 30, 'title' => 'Profit lost to untracked spending', 'text' => 'Costs you remember instead of write down add up all season, and only show at the end.', 'peso' => '₱18,000 to ₱30,000', 'image' => 'sacks-shed', 'upload' => ''],
                ['n' => 25, 'title' => 'Yield lost to mistimed fertilizer', 'text' => 'The right sack in the wrong week feeds the crop only part of what you paid for.', 'peso' => '₱15,000 to ₱25,000', 'image' => 'transplant', 'upload' => ''],
                ['n' => 25, 'title' => 'Yield lost to water at the wrong time', 'text' => 'Dry at flowering, flooded at ripening: the stage the water missed never comes back.', 'peso' => '₱15,000 to ₱25,000', 'image' => 'storm-paddies', 'upload' => ''],
                ['n' => 20, 'title' => 'Yield lost to delayed decisions', 'text' => 'Days waiting for an answer, or for the technician\'s next visit, while the problem keeps growing.', 'peso' => '₱12,000 to ₱20,000', 'image' => 'farmer-hijab', 'upload' => ''],
                ['n' => 20, 'title' => 'Yield lost to planting outside the window', 'text' => 'Planting by habit instead of by the weather shows at harvest.', 'peso' => '₱12,000 to ₱20,000', 'image' => 'hero-planting', 'upload' => ''],
            ],
            'note' => 'Percentage ranges drawn from published {cropResearch} loss estimates{pesoBasis}. Your farm\'s exact numbers vary, which is the point.',
        ],
        // The answer, in the four words precision agriculture is built on.
        'precision' => [
            'kicker' => 'So what\'s the fix?',
            'headline' => 'The right input, the right amount, at the right time, in the right place',
            'sub' => 'That\'s all precision farming is. Big farms do it with sensors and drones. You can do it with your phone.',
            'items' => [
                ['icon' => '⏱️', 'title' => 'The right time', 'text' => 'Each task lands on its day, counted from planting and the crop\'s stage. Never too early, never too late, never into the rain.'],
                ['icon' => '⚖️', 'title' => 'The right amount', 'text' => 'The rate for each task and each lot, taken from what\'s in your shed, so you buy and use only what the field needs.'],
                ['icon' => '🧪', 'title' => 'The right input', 'text' => 'Send Anee a photo of the leaf or the pest. She tells you what it is and what works, before you pay for the wrong product.'],
                ['icon' => '📍', 'title' => 'The right place', 'text' => 'Every lot keeps its own plan, its own forecast and its own map, with pins where the trouble is.'],
            ],
        ],
        // Anee Auto (2026-10-13, the owner: "complete the /start"): the
        // homepage's section (public.partials.anee-auto-section), its five
        // steps and its phone; these are the words around them. No price.
        'auto' => [
            'kicker' => 'The easiest way of all',
            'headline' => 'Rather not plan it all yourself? *Just follow Anee.*',
            'sub' => 'Answer a few questions about your farm once. Anee plans the whole season, tells you what to do each day, and moves the work herself when it rains or a worker can\'t come.',
            'cta' => 'Let Anee plan my season',
        ],
        // Try it (2026-10-07): the visitor picks a crop and a planting day
        // and watches a sample season land on its days. The tasks are the
        // view's own (common practice); these are the words around them.
        'demo' => [
            'kicker' => 'Try it now',
            'headline' => 'Your season, *planned in ten seconds*',
            'sub' => 'Pick your crop and the day you plant. Here\'s a sample of the calendar anee.io builds for every lot, each task on its own day.',
            'cta' => 'Get my full season plan free',
            'note' => 'A sample from common practice. Your own plan follows your variety, your field and your weather, and moves when they do.',
        ],
        // New this season (2026-10-07; the newest tools since 2026-10-28, the owner: "based on all these new changes,
        // new features ... make this landing page for ads better, persuasive"). Each item's image is a tool's key
        // (How It Works), whose phone screen of the app as it is now shows on the card, with the plan it comes with.
        'space' => [
            'kicker' => 'New this season',
            'headline' => 'Plant at the right time. *Sell at the right price.*',
            'sub' => 'The newest tools follow your crop all the way to the money: when to plant, a plan for every lot, your field from space, the price ahead, whether to dry before you sell, and a fair share for your farm manager.',
            'items' => [
                ['title' => 'Market Price Forecast', 'text' => 'The months your crop sells highest and the low season to stay away from, read from its farmgate prices of the last years.', 'image' => 'market'],
                ['title' => 'Benta o Bilad Calculator', 'text' => 'Sell it wet, or dry it first? Put in your sacks and the prices and see which way leaves more money in your hand.', 'image' => 'bilad'],
                ['title' => 'Hatian Calculator', 'text' => 'Your farm manager\'s share from the whole harvest, with your home food set aside, counted the same fair way every time.', 'image' => 'hatian'],
                ['title' => 'When to Plant Analysis', 'text' => 'Twenty years of your town\'s weather and the El Niño or La Niña outlook, read into the weeks with the safest start and the best price at harvest.', 'image' => 'when'],
                ['title' => 'Crop Protocol Analysis', 'text' => 'Anee writes the season for your variety and your lots, stage by stage: the fertilizer, the sprays and the water each lot needs.', 'image' => 'cropProtocol'],
                ['title' => 'Satellite Analysis', 'text' => 'Draw your field and see its health from space, the weak corner to walk first, and radar that sees through typhoon clouds.', 'image' => 'satellite'],
            ],
        ],
        // Every tool, by the step of the season it serves. The tools
        // themselves are How It Works' own list (HowItWorks::stages(), with
        // the icons they wear in the app), so the page can never name a tool
        // the app does not have, or miss a new one (2026-10-13).
        'more' => [
            'headline' => '{tools} tools for your whole season, *in one app*',
            'sub' => 'No more notebook, calculator, group chat and guessing. {freeTools} of them are free forever on Libre. Tap any tool to see how it helps.',
            // An uploaded phone screenshot in place of the season's modules (blank: those).
            'image' => '',
        ],
        // Anee's lines (2026-10-28, the owner: "landing pages should be somewhat conversational right?"): a speech
        // bubble with her face opens each section, so the page reads like a talk with her. Short, plain words; a blank
        // line is not drawn. Wrap words in *stars* to color them.
        'says' => [
            'hero' => 'Hi, I\'m Anee, your smart farm technician! Tell me about your farm once, and I\'ll tell you *what to do each day*.',
            'problem' => 'Let me ask you something. Does any of this sound like *your last season*?',
            'losses' => 'This part hurts, I know. But it helps to *see where the money goes*.',
            'social' => 'And when something goes wrong, where do you ask? *This happened in a real farmers\' group.*',
            'precision' => 'Here\'s the good news: you don\'t need to guess. *You need the right day.*',
            'auto' => 'Too busy to plan it all? *Leave it to me.* You just follow.',
            'demo' => 'Want to see it for yourself? *Try it now*, no sign up needed.',
            'space' => 'I keep learning. *These came out this season.*',
            'more' => 'Everything is in one app. *Tap any tool* and I\'ll tell you what it does.',
            'faq' => 'Still thinking it over? That\'s fine. *Here\'s what farmers ask me most.*',
            'closer' => 'I\'m ready when you are. *Let\'s make this season your best.*',
        ],
        'testimonials' => [
            'headline' => 'Farmers who plan with anee.io',
            // Each: name, role, location, quote, result (the measurable part,
            // e.g. "Saved 2 sprays this season"), rating (0-5), photo (a URL,
            // or a path on the mother app's disk). Written only in the mother app.
            'items' => [],
        ],
        'faq' => [
            'headline' => 'You might be wondering',
            'items' => [
                ['q' => 'Is it really free?', 'a' => 'Yes. Libre is free forever, with {freeTools} of the {tools} tools: one active season on one lot, the season board, growth stages, notes and photos, weather for today and tomorrow, the Market Price Forecast, Benta o Bilad and the Hatian Calculator among them. A few ads keep it free.'],
                ['q' => 'Who is Anee?', 'a' => 'Anee is the smart farm technician inside anee.io. Send her a photo or ask in Tagalog, Bisaya, Ilocano, Taglish or English, and she answers in seconds, knowing your lot, its stage, your weather and what you already applied. She comes with Libre + Anee, from {libreAnee} a month, and with every paid plan.'],
                ['q' => 'How is Anee different from ChatGPT or Gemini?', 'a' => 'Anee runs on a top language model too. The difference is what she reads before she answers: your lots and their soil, your crop\'s day count, the forecast for your farm and the El Niño or La Niña outlook, twenty years of your town\'s weather, and the varieties and products registered here. A general chat app answers about farming in general. Anee answers about your field.'],
                ['q' => 'Why not just ask in a Facebook group?', 'a' => 'People there mean well, but they cannot see your field. Under one real post, twenty comments gave seven different causes for the same palay, and four said to spray right away. Anee reads your lot first, asks for the one sign that tells the causes apart, and gives one answer with what to do.'],
                ['q' => 'What is Anee Auto?', 'a' => 'The easiest way to use anee.io. Answer a few questions about your farm once, and Anee plans the whole season, tells you each morning what to do, and moves the work herself when the rain comes or a worker cannot. You just follow.'],
                ['q' => 'Is it hard to use?', 'a' => 'No. If you can send a photo in a chat, you can use anee.io. Add your lot and your crop, and your season plan is there, day by day. The main screens each have a short video that shows how they work.'],
                ['q' => 'Do I need sensors, drones or special equipment?', 'a' => 'No. Precision agriculture in anee.io runs on what you already have: your phone, your crop\'s growth calendar, the forecast for your field, and your own records.'],
                ['q' => 'Will it really increase my harvest?', 'a' => 'No app can promise a number, because the weather and the market decide part of it. What anee.io does is take away the losses that come from late, early or wrong applications and from costs nobody tracked, which is where most avoidable losses are.'],
                ['q' => 'Do I need a credit card?', 'a' => 'No. You sign up with {signupWays}. Paid plans are paid through {pay}, only if and when you choose one.'],
                ['q' => 'Can I cancel any time?', 'a' => 'Nothing renews by itself. You pay for a month or a year at a time, and if you stop, your season and your records stay safe and readable.'],
                ['q' => 'Does it work on my phone, even in the field?', 'a' => 'Yes. anee.io runs in the browser of any phone, tablet or computer, and it is built for the phone first. Solo Farmer and Farm Owner also keep working when the signal drops.'],
                ['q' => 'Which crops does it know?', 'a' => '{crops} crops grown in the Philippines, each with its own growth stages and day count: rice transplanted or direct seeded, corn, vegetables, fruit trees and more.'],
                ['q' => 'Can my workers use it too?', 'a' => 'Yes. On Solo Farmer you keep their days and their pay; on Farm Owner they log in themselves, and you choose what each one may see or do.'],
                ['q' => 'Is my farm data private?', 'a' => 'Yes. Your seasons, notes and money are yours alone unless you choose to share something with the community.'],
            ],
        ],
        'closer' => [
            'headline' => 'Ready to stop guessing? *Let\'s plan your season.*',
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

        // Every tool How It Works lists, and the plan every account has (the first in config/tiers).
        $tools = collect(HowItWorks::stages())->flatMap(fn ($st) => $st['items']);
        $free = array_key_first(array_filter(config('tiers'), fn ($t, $k) => $k !== 'admin', ARRAY_FILTER_USE_BOTH));

        return [
            '{farmers}' => Region::t('farmersOf'),
            '{crops}' => (string) $crops,
            '{pay}' => Region::payMethod(),
            // Google only where its sign-in is switched on.
            '{signupWays}' => filled(config('services.google.client_id')) ? 'your email or your Google account' : 'just your email',
            '{libreAnee}' => Region::priceTag(Region::tierPrice('libreAnee', 'month')),
            // The losses band's source note, said for the page's own country.
            '{cropResearch}' => Region::ph() ? 'Philippine rice' : 'crop',
            '{pesoBasis}' => Region::ph() ? '; peso ranges assume a typical palay hectare grossing ₱60,000 to ₱100,000' : '',
            '{solo}' => Region::priceTag(Region::tierPrice('solo', 'month')),
            '{tools}' => (string) $tools->count(),
            '{freeTools}' => (string) $tools->filter(fn ($it) => ($it['plan']['tier'] ?? null) === $free)->count(),
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
