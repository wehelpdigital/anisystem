<?php

namespace App\Support;

/**
 * How anee.io works (2026-10-01): the cropping season told as steps (six
 * until 2026-10-02, when planning split into planning and building the
 * plan; eight since 2026-10-13, when Anee Auto, the way to have Anee run all
 * seven, became the last), each with the tools a grower reaches for at it
 * and what Anee does there. Say the count with stepWord(), never by hand.
 *
 * One list drives both tellings: the public page (/how-it-works) and the
 * full screen tour behind the dashboard's card. Change a word here and both
 * change, so the site and the app can never tell the story two ways.
 *
 * An item:
 *   key, name, short       the chip: its name and one line under it
 *   icon                   a drawn picture under public/images
 *   anee                   true when Anee does the work (the chip says so)
 *   what, gets             what opens under the chip: a paragraph, then up
 *                          to three things you get
 *   page                   a /features/{slug} page that tells more (the
 *                          Philippine site's), or url for any other page
 *   app                    the route that opens the tool inside the app
 *   plan                   the plan it comes with, read off config/tiers.php
 *                          through GATES (planOf()), never written by hand
 *
 * The words follow the site's style: short, plain, no dashes.
 *
 * 2026-10-18 (the owner: "update the list of modules"): the tools of the
 * last weeks found their place in the season. Global Lots and the Variety
 * Finder before day zero, the Land Prep Planner when the field is readied,
 * the LCC Checker and the farm news while the crop grows, the Nutrient
 * Deficiency Finder and the Compatibility Checker beside the other field
 * helpers when something looks wrong. Still eight steps.
 */
class HowItWorks
{
    /** Each tool's own article page on the Philippine site, /features/{slug} (2026-10-05). */
    public const PAGES = [
        'when' => 'when-to-plant-analysis',
        'what' => 'what-to-plant-analysis',
        'variety' => 'variety-research',
        'cropProtocol' => 'crop-protocol-analysis',
        'maps' => 'farm-maps',
        'draw' => 'farm-drawing',
        'protocol' => 'protocol-builder',
        'inventory' => 'farm-inventory-and-expenses',
        'review' => 'protocol-review-by-anee',
        'schedule' => 'cropping-calendar',
        'lots' => 'farm-lots',
        'workers' => 'farm-workers-and-payroll',
        'access' => 'team-logins',
        'collab' => 'collab-room',
        'morning' => 'morning-plan-email',
        'stock' => 'automatic-stock-deduction',
        'growth' => 'growth-stages-and-weather',
        'weather' => 'farm-weather-forecast',
        'realign' => 'realign-by-anee',
        'board' => 'daily-farm-tasks',
        'capture' => 'notes-photos-and-voice',
        'offline' => 'offline-farm-app',
        'tip' => 'farm-tip-of-the-day',
        'chat' => 'ai-agricultural-technician',
        'sofar' => 'analyze-so-far',
        'guides' => 'crop-problem-guides',
        'community' => 'farmer-community',
        'observations' => 'harvest-records',
        'contacts' => 'farm-contact-list',
        'documentation' => 'farm-documentation',
        'gallery' => 'farm-photo-gallery',
        'labor' => 'labor-report',
        'expenses' => 'expenses-report',
        'profit' => 'profit-report',
        'season' => 'anee-season-report',
        'compare' => 'compare-reports',
        'asProtocol' => 'view-as-protocol',
        // The five tools of 2026-10-07.
        'satellite' => 'satellite-analysis',
        'skyWeather' => 'satellite-weather',
        'npk' => 'npk-plus-calculator',
        // The finders split in three (2026-10-13, the owner): the disease
        // finder shares the page (given on its item, so the page keeps the
        // pest finder's icon and film); the weed helper's page is /weeds.
        'pestFinder' => 'pest-and-disease-finders',
        'stash' => 'the-stash',
        // The tools placed in the season on 2026-10-18.
        'landprep' => 'land-preparation-planner',
        'lcc' => 'lcc-checker',
        'nutrientFinder' => 'nutrient-deficiency-finder',
    ];

    /**
     * What opens each tool, as config/tiers.php names it (2026-10-18): the
     * key the gates read, so the plan a tool is said to come with is the one
     * the app really opens it on. Null = every plan. A second pair is a part
     * of the tool that comes later (the LCC Checker reads your own chart on
     * every plan; Anee reading photos of the leaves needs her).
     */
    public const GATES = [
        'when' => 'aiAnalyses', 'what' => 'aiAnalyses', 'variety' => 'aiAnalyses', 'cropProtocol' => 'aiAnalyses',
        'satellite' => 'aiAnalyses', 'landprep' => 'aiAnalyses', 'review' => 'aiAnalyses', 'auto' => 'aiAnalyses',
        'chat' => 'ai', 'realign' => 'ai',
        'maps' => 'maps', 'skyWeather' => 'skyWeather', 'npk' => 'npkPlus', 'bilad' => 'bentaBilad', 'hatian' => 'hatian', 'market' => 'marketPrices', 'seedCount' => 'seedCount', 'howToPlant' => 'howToPlant',
        'varietyFinder' => 'fieldHelpers', 'pestFinder' => 'fieldHelpers', 'diseaseFinder' => 'fieldHelpers',
        'nutrientFinder' => 'fieldHelpers', 'weedHelper' => 'fieldHelpers', 'compat' => 'fieldHelpers',
        'inventory' => 'inventory', 'stock' => 'inventory',
        'workers' => 'workers', 'globalWorkers' => 'workers',
        'access' => 'workerLogins', 'collab' => 'collab',
        'sofar' => 'reportsAll', 'expenses' => 'reportsAll', 'profit' => 'reportsAll', 'season' => 'reportsAll',
        'compare' => 'reportsAll', 'asProtocol' => 'reportsAll',
        'weather' => [null, 'weatherDays', 'The full forecast'],
        'lcc' => [null, 'aiAnalyses', 'Anee reading your photos'],
        'capture' => [null, 'videoRecording', 'Quick Record video'],
        'documentation' => [null, 'docUploads', 'File uploads'],
        // The pest, disease and weed guides open in full with the field helpers' plan (ContentGate, 2026-10-17).
        'guides' => [null, 'fieldHelpers', 'The whole guide'],
    ];

    /**
     * The plan a tool comes with: ['tier' => key, 'name' => plan name,
     * 'says' => the line under the tool], plus 'more' for a part of it that
     * opens on a later plan. The first plan in config order whose value opens
     * the key wins (true, unlimited, or more than the free plan's number).
     */
    public static function planOf(string $key): array
    {
        $gate = self::GATES[$key] ?? null;
        [$main, $moreKey, $moreWhat] = is_array($gate) ? $gate + [null, null, null] : [$gate, null, null];
        $plans = array_filter(config('tiers'), fn ($t, $k) => $k !== 'admin', ARRAY_FILTER_USE_BOTH);
        $keys = array_keys($plans);
        $top = end($keys);
        $first = function (?string $cfg) use ($plans, $keys, $top) {
            if ($cfg === null) {
                return $keys[0];
            }
            // array_key_exists, not ??: a null here means unlimited, not missing.
            $base = array_key_exists($cfg, $plans[$keys[0]]) ? $plans[$keys[0]][$cfg] : false;
            foreach ($plans as $k => $t) {
                $v = array_key_exists($cfg, $t) ? $t[$cfg] : false;
                if ($v === true || ($v === null && $base !== null) || (is_numeric($v) && is_numeric($base) && $v > $base)) {
                    return $k;
                }
            }

            return $top;
        };
        $say = fn (string $tier) => $tier === $keys[0] ? 'On every plan, ' . $plans[$tier]['name'] . ' included'
            : 'Comes with ' . $plans[$tier]['name'] . ($tier === $top ? '' : ' and up');
        $tier = $first($main);
        $out = ['tier' => $tier, 'name' => $plans[$tier]['name'], 'says' => $say($tier), 'more' => null];
        if ($moreKey) {
            $later = $first($moreKey);
            $out['more'] = $moreWhat . ': ' . $plans[$later]['name'] . ($later === $top ? '' : ' and up');
        }

        return $out;
    }

    /**
     * The app's own icon for a feature page (images/...), the one its tool
     * wears in the app and in How It Works; the reports page, which no one
     * tool owns, wears the profit report's. Null for a page with no tool.
     */
    public static function iconOfPage(string $slug): ?string
    {
        static $map = null;
        if ($map === null) {
            $map = ['farm-reports' => 'icons/profit.png'];
            foreach (self::stages() as $st) {
                foreach ($st['items'] as $it) {
                    if ($it && ! empty(self::PAGES[$it['key']])) {
                        $map[self::PAGES[$it['key']]] = $it['icon'];
                    }
                }
            }
        }

        return $map[$slug] ?? null;
    }

    public static function stages(): array
    {
        $ph = Region::ph();
        $peso = $ph ? 'peso' : 'dollar';

        $stages = array_values(array_filter([
            [
                'key' => 'plan',
                'word' => 'Plan',
                'when' => 'Before day zero',
                'title' => 'Plan the season',
                'lede' => 'Decide when, what, where and how before a single seed goes in. A mistake costs the least while it is still on paper.',
                'say' => null,   // the owner took every step's Anee line out (2026-10-02)
                'face' => 'thinking',
                'pattern' => 'plan',
                'glyph' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                'items' => [
                    // Global Lots (2026-10-16): each field answered once, before any analysis asks.
                    self::item('globalLots', 'Global Lots', 'Each field once: its place, soil and water', 'map-pin.png', false,
                        'Add each field once: where it is, its size, its soil, its water and what grew there last. Every analysis reads it from there, so you answer those questions one time, and every six months anee.io asks you to check it is still right.',
                        ['Place, size, soil and water for each lot', 'Read by every analysis, never asked twice', 'A reminder to check it every six months'],
                        null, 'farmlots.index'),
                    self::item('when', 'When to Plant Analysis', 'The best planting window for your town', 'appointment.png', true,
                        "Anee reads twenty years of your town's weather, the outlook for the coming months and how your crop grows, then names the window that gives it the best start and the safest harvest.",
                        ['A best window and a second choice', 'The risk in each month, said plainly', 'Saved, so you can come back to it'],
                        'when-to-plant-analysis', 'wtp.page'),
                    self::item('what', 'What to Plant Analysis', 'The crop that fits your land this season', 'plant.png', true,
                        'Not sure what to grow? Anee weighs your climate, the forecast, your soil and your water, and ranks the crops that should do best on your land this season.',
                        ['Crops ranked for your place and season', 'Why each one fits, or does not', 'What each one will need from you'],
                        null, 'whatp.page'),
                    self::item('variety', 'Variety Ranking', 'Compare varieties before you buy seed', 'icons/biotechnology.png', true,
                        'Anee looks up the varieties of your crop and compares them for your area: yield, maturity, resistance and how they did in weather like yours.',
                        ['Varieties side by side', 'Strong and weak points for your area', 'The sources she used, to check yourself'],
                        null, 'vary.page'),
                    // A field helper (2026-10-13): the registered varieties, read before seed is bought.
                    self::item('varietyFinder', 'Variety Finder', 'Every registered variety of your crop', 'icons/rice-variety.svg', false,
                        'Every registered variety of rice, corn, vegetables, root crops and fruit trees, with its days to harvest, its yield and what it resists. Filter them by what your farm needs, then let Anee rank the few that fit your place.',
                        ['Days to harvest, yield and resistance', 'Rice, corn, vegetables, root crops and fruit trees', 'Filters for what your farm needs'],
                        null, 'fh.page:varieties', $ph ? '/rice-varieties' : null),
                    self::item('cropProtocol', 'Crop Protocol Analysis', 'Anee writes a protocol for your variety', 'icons/biostimulant.png', true,
                        'Pick your crop and variety, and Anee writes the season for your place by growth stage: the bags of fertilizer and when, the sprays, the water and what to watch for.',
                        ['Stage by stage, from land prep to harvest', 'Fertilizer, sprays and water on their days', 'Checked against your weather, soil and water'],
                        null, 'proto.page'),
                    self::item('satellite', 'Satellite Analysis', 'Your field, read from space', 'icons/satellite.svg', true,
                        'Draw your field on the map and Anee reads the newest Sentinel 2 picture and Sentinel 1 radar, which sees through typhoon clouds, then weighs the weather, ENSO and the soil of your area.',
                        ['Crop health and the weak spots in the field', 'Radar when clouds hide the field', 'Threats, soil and what to do next'],
                        'satellite-analysis', 'sat.page'),
                    self::item('maps', 'Lot planning with GPS maps', 'Draw, measure and pin your fields', 'location-marker.png', false,
                        'Trace each field over a satellite view and the app gives you its area and the length of each side. Drop pins on the pump, the gate or the low spot that floods.',
                        ['The area and sides of every field', 'Pins with notes and photos', 'Attach a map to a lot, a task or a note'],
                        'farm-maps', 'maps.page'),
                    self::item('draw', 'Draw', 'Team planning: sketch the plan everyone follows', 'writting.png', false,
                        'Plan with your team on a drawing: where the seedbed goes, how the water moves, who works which lot. Sketch a layout, a plan or a flow, and everyone works from the same picture.',
                        ['Shapes, lines, text and colors', 'Draw over a photo of your field', 'Kept in your gallery for any season'],
                        null, 'draw.page'),
                    // The Stash (2026-10-07): partners' resources, read before the season is planned.
                    self::item('stash', 'The Stash', "Partners' magazines and guides, in the app", 'icons/stash.svg', false,
                        "Resources shared by anee.io's partners, read right inside the app. It starts with every issue of the PhilRice Magazine: new varieties, field methods and the farmers who made them work.",
                        ['Every PhilRice Magazine issue, free to read', 'Search by year, title or topic', 'More partners to come'],
                        'the-stash', 'stash.shelf:philrice,e-magazines'),
                ],
            ],
            [
                'key' => 'build',
                'word' => 'Build',
                'when' => 'Ready for day zero',
                'title' => 'Build and finalize the plan',
                'lede' => 'Write the season task by task, count what it will use against what is in the shed, and let Anee check it before you commit.',
                'say' => null,
                'face' => 'thumbsup',
                'pattern' => 'build',
                'glyph' => 'M4 6h7v5H4zM13 6h7v5h-7zM4 13h4v5H4zM10 13h10v5H10z',
                'items' => [
                    self::item('protocol', 'Protocol Builder', 'Write the whole season before day zero', 'icons/bricks.png', false,
                        'Write every task of the season on its day: land prep, sowing, each fertilizer and spray with its rate, counted in days after sowing or transplanting. Keep one version for the wet season and one for the dry.',
                        ['Tasks on a day count (DAS, DAT, DAP)', 'Materials and rules in one place', 'Turn it into a cropping schedule'],
                        'protocol-builder', 'pb.page'),
                    self::item('inventory', 'Materials and inventory', 'Know what to buy before you need it', 'sack.png', false,
                        'Your protocol adds up what its tasks will use and sets it against what you have, so you see what is left to buy. Stock the shed with fertilizer, seed and chemicals, each in its own unit.',
                        ['What the plan needs against what you have', 'Counted in bags, liters or kilos', 'Every move in and out on record'],
                        'farm-inventory-and-expenses', 'pb.page'),
                    self::item('npk', 'NPK Plus Calculator', 'Every nutrient in your fertilizer plan', 'icons/npk.svg', false,
                        'Tap the fertilizers you plan to use and NPK Plus adds up every nutrient, as the element and as the oxide, checks it against what your crop needs and shows the yield it can feed. The count uses no credits, and Anee can read the plan against your place, soil and season.',
                        ['N, P and K per hectare and in all', 'Micronutrients and biofertilizers counted too', 'Short, right or too much for your crop'],
                        'npk-plus-calculator', 'npk.page'),
                    self::item('review', 'Anee reviews your protocol', 'A second pair of eyes on your plan', 'icons/technician-support.png', true,
                        'Before you commit, let Anee read your protocol. She points out the problems, the strengths and the weak spots: a rate that is too high, a spray too close to harvest, a stage with nothing planned.',
                        ['Problems, strengths and weak spots', 'Clear changes you can make', 'Ask her more about it in chat'],
                        null, 'pb.page'),
                ],
            ],
            [
                'key' => 'plant',
                'word' => 'Plant',
                'when' => 'Day zero',
                'title' => 'Set up and plant',
                'lede' => 'Get every field ready on time, then turn the plan into a living cropping schedule. Each lot keeps its own day zero, so every task lands on the right date.',
                'say' => null,   // the owner took this step's line out (2026-10-01)
                'face' => 'salute',
                'pattern' => 'plant',
                'glyph' => 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z',
                'items' => [
                    // The Land Prep Planner (2026-10-14; several lots, each its own crop, crew and machines since).
                    self::item('landprep', 'Land Prep Planner', 'Every lot ready on time for planting', 'icons/land-prep.svg', true,
                        'Pick your lots, each with its own crop and planting day. Anee dates every step of getting them ready, from clearing and plowing to harrowing, leveling and the seed, counted back from planting and fitted around your soil, the weather, your workers and your machines.',
                        ['Each lot with its own crop and planting day', 'Your workers and machines planned on every step', 'What it will all cost'],
                        'land-preparation-planner', 'landprep.page'),
                    self::item('seedCount', 'Seed Count and Distance Calculator', 'The seeds for your spacing, or the spacing for your seeds', 'icons/seed-count.svg', false,
                        'Set the distance between rows and between plants and see how many seeds or kilos your field needs, how many plants will stand at harvest and the yield they can carry, with a warning when the stand is thin or crowded. Or start from the seeds you have, or the stand you want, and see the spacing.',
                        ['Seeds and kilos for a hectare or your lot', 'The final stand after germination and thinning', 'Warnings for crowding and for seeds a hill'],
                        null, 'seedcalc.page'),
                    self::item('howToPlant', 'How to Plant', 'How traditional farmers plant every crop', 'icons/how-to-plant.svg', false,
                        'Pick a crop and see how traditional farmers here plant it, step by step from getting the field ready to the harvest, each step on its day, with a video where there is a good one. Then see what to change for a higher yield, and send it to your Protocol Builder to make it your own.',
                        ['Every crop and fruit tree, step by step with the days', 'What to change for a higher yield', 'One tap into your Protocol Builder'],
                        null, 'htp.page', '/how-to-plant'),
                    self::item('schedule', 'Cropping schedule', 'The whole season on one board', 'icons/calendar.png', false,
                        "Start a season from your protocol or from scratch. Every task, irrigation, hired service and payroll day sits on one board, dated from each lot's own day zero.",
                        ['Drag a task to move it', 'Drafts and versions to try other plans', 'Undo that still works after you log out'],
                        'cropping-calendar', 'sm.index'),
                    self::item('lots', 'Lots', 'Each field with its own crop and count', 'treasure-map.png', false,
                        'Add each field as a lot with its crop, variety, size and place. Each lot counts from its own day zero, so lots sown a week apart keep their own timing.',
                        ['Crop, variety, size and place per lot', "Attach the lot's map", 'A different crop on each lot if you grow them'],
                        null, 'sm.index'),
                    self::item('workers', 'Workers and payroll', 'The right hands on the right task', 'tractor.png', false,
                        "Keep a roster with each worker's rate, put people on each task, tick who came, and the labor cost adds itself up.",
                        ['Rates, attendance and payroll days', 'Whole days and half days', 'Labor cost counted as you go'],
                        'farm-workers-and-payroll', 'sm.index'),
                    // The tools the count left out until 2026-10-13 (the owner: "are you sure there's only 45?").
                    self::item('globalWorkers', 'Global Workers', 'Your crew in one list, for every season', 'united.png', false,
                        'Add each worker once, with their pay, skills and the weekdays they do not work. Put them on any season or land prep plan in a tap, and each season keeps its own record of their days and pay.',
                        ['One roster for every season', 'Skills and days off kept with each person', 'Changes reach every open season'],
                        null, 'workers.global'),
                    self::item('access', 'Team logins', 'Your team sees what you allow', 'friends.png', false,
                        'Give a worker or a partner their own login, and decide module by module what they may see and what they may change.',
                        ['None, view or edit, per module', 'A diary of every change and who made it', 'One login for each person'],
                        null, 'sm.index'),
                    self::item('collab', 'Collab Room', 'Chat, whiteboard and live cameras for the team', 'speech-bubbles.png', true,
                        'Each season has a room for its team: chat with photos and voice notes, a shared whiteboard, live cameras, and Anee in the room to answer the whole team at once.',
                        ['A group chat for each season', 'A whiteboard everyone can draw on', 'Ask Anee together'],
                        null, 'sm.index'),
                    self::item('morning', 'Morning plan email', "Today's work in every inbox at 6 AM", 'time.png', false,
                        "Every morning at 6 AM your team gets the day's plan by email: what to do and on which lot.",
                        ['Sent on its own every day', 'The whole team starts on the same page', 'Nothing to remember to send'],
                        null, null),
                    self::item('share', 'Share the plan', "A public link, or the day's plan by email", 'social-media.png', false,
                        "Share the whole plan, one day or one task as a public page anyone can open, no login needed. Post it on Facebook or WhatsApp, send it to a cofarmer, or email today's or tomorrow's work to your workers.",
                        ['A public link for the plan, a day or a task', "Today's or tomorrow's plan by email", 'Send it to a cofarmer in the app'],
                        null, 'sm.index'),
                    self::item('stock', 'Stock that counts itself', 'Tick a task and the shed updates', 'icons/fertilizer.png', false,
                        'When you tick a fertilizer or spray task done, the shed takes it out of stock. Undo the tick and it goes back. The costs follow into your expenses.',
                        ['Stock on hand always adds up from the moves', 'Each move names who made it', 'Costs flow into the expenses report'],
                        'farm-inventory-and-expenses', 'sm.index'),
                ],
            ],
            [
                'key' => 'grow',
                'word' => 'Grow',
                'when' => 'Day by day',
                'title' => 'Grow with the count',
                'lede' => 'Every day the app knows how old each lot is, what stage it is in and what the weather is about to do.',
                'say' => null,   // taken out on the owner's word (2026-10-02)
                'face' => 'happy',
                'pattern' => 'grow',
                'glyph' => 'M12 21c0-4 1-7 4-9M12 21c0-5-2-8-6-9m6 9V8m0 0c0-2.5 1.5-4 4-4 0 2.5-1.5 4-4 4zm0 0C12 5.5 10.5 4 6.5 4c0 2.5 1.5 4 5.5 4z',
                'items' => array_values(array_filter([
                    self::item('growth', 'Growth stages', 'Where every lot stands today', 'icons/soil-restoration.png', false,
                        'Pick any date and each lot shows its growth stage, what to do now and what to watch for. anee.io knows ' . ($ph ? '85 Philippine crops, from palay and mais to gulay and fruit trees.' : 'nearly a hundred crops, from rice and corn to vegetables and fruit trees.'),
                        ['Stage, do list and watch list per lot', 'Annual and perennial crops', 'Shown right on the board'],
                        'growth-stages-and-weather', 'sm.index'),
                    self::item('weather', 'Weather for your farm', 'The forecast where your field is', 'weather.png', false,
                        'See the forecast for your own farm beside your plan, so a spray is not wasted on the day before the rain.',
                        ['Rain and heat for the coming days', 'Right beside the growth stages', 'A reminder when rain falls on a spray day'],
                        'growth-stages-and-weather', 'sm.index'),
                    self::item('skyWeather', 'Satellite Weather', 'Clouds, rain and typhoons over your farm', 'icons/storm.svg', false,
                        'Watch the clouds and rain of the last hours over your farm, fast forward the forecast, and see every typhoon with its path, its cone and how far it is from your field.',
                        ['Satellite clouds and rain radar, played back', 'Typhoon tracks with the time at every forecast point', 'Where the rain clusters are headed, hour by hour'],
                        'satellite-weather', 'sky.page'),
                    // The LCC Checker (2026-10-14): is it time for nitrogen, from the leaves.
                    self::item('lcc', 'LCC Checker', 'Rice: is it time for nitrogen?', 'icons/leaf-color.svg', false,
                        'From the third week on, hold your leaves against the leaf color chart and tap the color each one matches, or send Anee photos of the leaves and she reads the color. The checker says if urea is due now and how many kilos your area needs.',
                        ['Your own chart, or photos read by Anee', 'Whether it is time for nitrogen', 'How much urea, for your area'],
                        'lcc-checker', 'lcc.page'),
                    self::item('realign', 'Realign by Anee', 'When a crop runs ahead or behind', 'user-refresh.png', true,
                        "Crops do not read calendars. Anee reads a lot's records, its weather and your notes, works out the stage it is truly in, and moves that lot's stage count to match.",
                        ['The real stage, with her reasons', 'What to do now and what to watch', 'Your calendar dates stay as they are'],
                        null, 'sm.index'),
                    self::item('board', 'Today on the board', 'Tick the work as it gets done', 'list.png', false,
                        'Open the day and see each task on its lot. Tick it done, mark who worked, and add a photo or a voice note. The board keeps the record for your reports.',
                        ['Done and moved, all on record', 'Workers and materials on each task', 'Photos, clips and voice on any task'],
                        'cropping-calendar', 'sm.index'),
                    self::item('cash', 'Cash to prepare', 'What a day, or a stretch of days, will cost', 'icons/cash.svg', false,
                        'Tap the money on any day and see the cash to prepare: wages, materials, stock to buy and extra expenses, line by line. Or pick two days and see the total for the whole stretch, so the money is ready before the work.',
                        ["Each day's cost, line by line", 'The total for any stretch of days', 'Wages counted from who works each task'],
                        null, 'sm.index'),
                    self::item('daycount', 'Count the days', 'Days between any two days or tasks', 'icons/daycount.svg', false,
                        "Tap one day, then another, and see how far apart they are, in days and in weeks, with each lot's DAS or DAT count at both ends. Handy for the days between two sprays, or before harvest.",
                        ['Days apart, in days and in weeks', "Each lot's day count at both ends", 'From any day or any task'],
                        null, 'sm.index'),
                    self::item('capture', 'Notes from the field', 'Snap it, film it or just say it', 'voice-recorder.png', false,
                        'Quick Capture takes a photo, Quick Record takes a video and Quick Voice saves what you say as a note. It all lands with the season, easy to find later.',
                        ['Notes per season, per day or for everything', 'A gallery of every photo and clip', 'Tags that tie it all together'],
                        'notes-photos-and-voice', 'notes.hub'),
                    self::item('tags', 'Tags', 'Tie tasks, notes and contacts together', 'label.png', false,
                        'Put your own tags on tasks, notes, photos and contacts, like Buyer, Irrigation or Lot 2. Then find everything with a tag in one tap, in one season or across all of them.',
                        ['Your own words, on anything', 'Filter the board by a tag', 'All your tags on one page'],
                        null, 'tags.global'),
                    self::item('offline', 'Works without signal', 'Keep going at the far lot', 'icons/offline.png', false,
                        'Offline mode is always on: keep ticking tasks and writing notes where there is no signal. anee.io keeps your changes on the phone and sends them once the signal comes back.',
                        ['Always on, every plan', 'Your recent seasons stay open', 'Changes wait, then sync'],
                        null, null),
                    self::item('tip', "Anee's tip of the day", 'One useful thing to do today', 'idea.png', true,
                        'Each day the dashboard gives you one tip for the stage your crop is in, from what anee.io knows about each crop and stage. Tap it to ask Anee more.',
                        ['Read on your dashboard each morning', 'Made for your crop and its stage', 'Ask her more with one tap'],
                        null, null),
                    // Latest in Agriculture (2026-10-07): the farm news, from the Philippines' newsrooms.
                    $ph ? self::item('news', 'Latest in Agriculture', 'Farm news, and what it means for you', 'newspaper.png', false,
                        'Every few days anee.io gathers the farm news from many newsrooms: prices, the weather, government programs and new ways to farm. Each story ends with what it means for your farm, in Taglish and in English.',
                        ['The farm news of the week in one place', 'What each story means for your farm', 'In Taglish and in English'],
                        null, 'community.blog', '/blog') : null,
                ])),
            ],
            [
                'key' => 'protect',
                'word' => 'Protect',
                'when' => 'When something looks wrong',
                'title' => 'Catch problems early',
                'lede' => 'Yellow leaves, holes in the leaves, weeds, a storm on the way. The sooner you know what it is and what to put on, the less it costs.',
                'say' => null,   // the owner took every step's Anee line out (2026-10-02)
                'face' => 'concerned',
                'pattern' => 'protect',
                'glyph' => 'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3zM9 12l2 2 4-4',
                'items' => array_values(array_filter([
                    self::item('chat', 'Chat with Anee', 'Ask anything, even with a photo', 'icons/chat.png', true,
                        'Ask in ' . ($ph ? 'Tagalog or English' : 'plain English') . ' and send a photo of the sick plant. Anee answers with your farm in mind: the lot, its stage, the weather and what you already applied.',
                        ['Photos, files and reports in the chat', 'Answers that know your season', 'Picks up where you left off'],
                        'ai-agricultural-technician', 'ai.index'),
                    self::item('sofar', 'Analyze So Far', 'A checkup halfway through', 'pie-chart.png', true,
                        'Halfway through the season, ask Anee to read it so far: where the crop stands, the risks ahead and what to do next.',
                        ['Where each lot stands now', 'The risks in the coming weeks', 'Next steps, in order'],
                        'farm-reports', 'sm.index'),
                    self::item('pestFinder', 'Pest Finder', 'From the damage you see to what to spray', 'icons/pest.svg', false,
                        'Pick your crop and where you see the damage: the leaves, the stems, the ' . ($ph ? 'panicles' : 'heads') . ' or the roots. The finder names the pests that fit, how to be sure, and the active ingredients that control them, with their groups.',
                        ['Any crop, from ' . ($ph ? 'palay to gulay' : 'rice to vegetables'), 'How to be sure before you spray', 'Active ingredients, their groups and products sold here'],
                        'pest-and-disease-finders', 'fh.page:pests'),
                    self::item('diseaseFinder', 'Disease Finder', 'From the signs you see to what to spray', 'icons/disease.svg', false,
                        'Spots, blight, rot or wilting? Pick your crop and the signs you see, and the finder names the diseases that fit, what to do first and the active ingredients that control them.',
                        ['Matched from the signs on your crop', 'What to do first, before any spray', 'Active ingredients, their groups and products sold here'],
                        'pest-and-disease-finders', 'fh.page:diseases'),
                    // The fifth field helper (2026-10-16).
                    self::item('nutrientFinder', 'Nutrient Deficiency Finder', 'From the leaves you see to the missing nutrient', 'icons/nutrient.svg', false,
                        'Yellow, purple or burnt leaves? Pick your crop, where the change started and what it looks like. The finder names the nutrient your crop is likely short of, how to be sure, and the fertilizers sold here that supply it, with photos to compare.',
                        ['Any crop, with photos to compare', 'How to be sure before you buy', 'The fertilizers sold here that fix it'],
                        'nutrient-deficiency-finder', 'fh.page:nutrients'),
                    self::item('weedHelper', 'Weed Control Helper', "What to do about weeds at your crop's age", 'icons/weed.svg', false,
                        'Pick your crop, how old it is and the weeds you see. The helper tells you what works at that age: the active ingredients for those weeds, group by group, or weeding by hand when no herbicide fits.',
                        ['By your crop and its age', 'Active ingredients by group, and products sold here', 'Hand weeding when no spray fits'],
                        'pest-and-disease-finders', 'fh.page:weeds'),
                    // The sixth field helper (2026-10-18): before the tank is filled.
                    self::item('compat', 'Compatibility Checker', 'Which sprays and fertilizers go on together', 'icons/compat.svg', false,
                        'Add the sprays and fertilizers you want to put on together, with how much of each. The checker says if they mix, the order to pour them in, how strong the spray is for your crop and its age, and what to watch. It knows over 7,000 products ' . ($ph ? 'sold in the Philippines' : 'registered for sale') . ', and Anee can look up one it does not know, once: what she finds is kept for next time.',
                        ['Mix or not, pair by pair', 'The mixing order and a jar test', 'How strong the spray is for your crop'],
                        null, 'fh.page:compat'),
                    $ph ? self::item('guides', 'Crop problem guides', 'Pests, diseases and weeds, explained', 'icons/tool-box.png', false,
                        'Guides to the pests, diseases and weeds of Philippine crops: how to spot them, why they come and what to do about them.',
                        ['Written for Philippine farms', 'What to look for, with pictures', 'What to do, step by step'],
                        null, null, '/problems') : null,
                    self::item('community', 'Farmer community', 'Ask growers who have seen it before', 'community-post.png', false,
                        'Post a photo to the feed or ask in a discussion room. Growers trade what worked for them and warn each other early.',
                        ['A news feed and discussion rooms', 'Messages with photos and voice', 'A ladder of 100 levels for helping out'],
                        'farmer-community', 'community.index'),
                ])),
            ],
            [
                'key' => 'harvest',
                'word' => 'Harvest',
                'when' => $ph ? 'Ani time' : 'Harvest time',
                'title' => 'Harvest and after',
                'lede' => 'Write down what came off the field and where it went, while you still remember it.',
                'say' => null,   // the owner took every step's Anee line out (2026-10-02)
                'face' => 'starstruck',
                'pattern' => 'harvest',
                'glyph' => 'M5 9h14l-1.5 10a2 2 0 01-2 1.7h-7a2 2 0 01-2-1.7L5 9zm3 0V7a4 4 0 018 0v2',
                'items' => [
                    self::item('observations', 'Observations', 'Yield, moisture, price and problems', 'pencil.png', false,
                        'After harvest, note your yield, moisture, price, buyer and any problems you saw. Next season you plan from real numbers, not memory.',
                        ['Yield and moisture per lot', 'Who bought it and at what price', 'Problems noted for next time'],
                        'farm-reports', 'sm.index'),
                    self::item('bilad', 'Benta o Bilad Calculator', 'Sell it wet, or dry it first?', 'icons/benta-bilad.svg', false,
                        'Put in your sacks, what the harvester takes, what the drying costs and the wet and dry prices, and see which way leaves you more money, and the dry price where drying starts to pay.',
                        ['Both ways side by side, after every cost', 'The weight drying takes out', 'The break even dry price'],
                        null, 'bilad.page'),
                    self::item('hatian', 'Hatian Calculator', 'Share the harvest fairly with your farm manager', 'icons/hatian.svg', false,
                        'Give your farm manager a percent, a number of sacks or kilos of the whole harvest, paid in sacks or in money, keep sacks for your home food, and see what each of you gets and how to hand it over.',
                        ['The share counted from the whole harvest', 'Home food out of your own share', 'Paid in sacks, or in money at your price'],
                        null, 'hatian.page'),
                    self::item('market', 'Market Price Forecast', 'When to sell, from the crop\'s own prices', 'icons/market-forecast.svg', false,
                        'Pick your crop and where you sell. See its farmgate price month by month since 1990, the forecast for this year and next with its likely range, the months it sells best, whether keeping the harvest pays, and the imports and bans that moved it.',
                        ['The best and the glut months', 'This year and next, with the likely range', 'Imports and bans, and what they did'],
                        null, 'market.page'),
                    self::item('contacts', 'Contact List', 'Buyers, traders and workers in one place', 'card-index.png', false,
                        'A phonebook for everyone around your farm: workers, traders, buyers and suppliers, with notes and tags.',
                        ['Phone numbers, notes and tags', 'Find a buyer in seconds', 'Yours, across every season'],
                        null, 'contacts.page'),
                    self::item('documentation', 'Documentation', "The season's papers in one place", 'document.png', false,
                        'Keep the season\'s references together: the protocol you followed, rules, receipts and any file, each with its own text and attachments.',
                        ['Text and files together', 'Kept with the season', 'Easy to find next year'],
                        null, 'sm.index'),
                    self::item('gallery', 'Season gallery', 'Every photo from planting to harvest', 'gallery.png', false,
                        'Every photo, clip and drawing the season made, with albums you put together yourself, like the harvest or the lot that did best.',
                        ['Albums you make yourself', 'Every season in one Global Gallery', 'Pick a saved picture for a task or a record'],
                        'notes-photos-and-voice', 'gallery.hub'),
                ],
            ],
            [
                'key' => 'reports',
                'word' => 'Learn',
                'when' => 'Look back',
                'title' => 'Reports and analysis',
                'lede' => 'The records you kept all season add up on their own. See what it cost, what it earned and what to change.',
                'say' => null,   // the owner took every step's Anee line out (2026-10-02)
                'face' => 'delighted',
                'pattern' => 'reports',
                'glyph' => 'M4 19h16M7 16v-4m5 4V8m5 8v-6',
                'items' => [
                    self::item('labor', 'Labor Report', 'Worker days and labor cost', 'icons/tea.png', false,
                        'Every worker day and the labor cost for the season, added up from the workers you put on each task. Nothing to type twice.',
                        ['Each worker at their own rate', 'Kept on your Saved Reports tab', 'Your busiest months, easy to see'],
                        'farm-reports', 'sm.index'),
                    self::item('expenses', 'Expenses Report', "Every {$peso} spent this season", 'icons/money-bag.png', false,
                        "Materials, services and labor: every {$peso} the season cost, in one report built from your records.",
                        ['Built from the board and the shed', 'Grouped so you see where it went', 'Saved for the next season'],
                        'farm-inventory-and-expenses', 'sm.index'),
                    self::item('profit', 'Profit Report', 'What the harvest earned against the cost', 'icons/profit.png', false,
                        'Your harvest sales against everything you spent, so you see the real profit of the season.',
                        ['Sales from your observations', 'Costs from your records', 'The real profit, not a guess'],
                        'farm-reports', 'sm.index'),
                    self::item('season', 'Anee Season Report', 'What went well and what went wrong', 'anee/avatar-160.jpg', true,
                        'Anee reads your finished season, from the plan to the harvest, and shows what went well, what went wrong and what to improve.',
                        ['A plain story of your season', 'What to keep and what to change', 'Ask her more about any part'],
                        'farm-reports', 'sm.index'),
                    self::item('compare', 'Compare Reports', 'This season against the last', 'icons/ab-testing.png', true,
                        'Put two reports of the same type side by side, see the difference, and let Anee explain what changed and why.',
                        ['Any two seasons side by side', 'The difference, line by line', 'Anee explains what changed'],
                        null, 'compare.page'),
                    self::item('asProtocol', 'View as Protocol', "Your best lot as next season's recipe", 'icons/checklist.png', false,
                        "One lot's finished work written as a step by step recipe: what was done, with what and on which day. A ready start for your next protocol.",
                        ['What worked, in order', 'Rates and days from your real records', 'A starting point for the next season'],
                        null, 'sm.index'),
                ],
            ],
            // Step 8 (2026-10-13, the owner): Anee Auto, for the farmer who
            // would rather not do steps 1 to 7 at all.
            [
                'key' => 'auto',
                'word' => 'Auto',
                'when' => 'Or let Anee do it all',
                'title' => 'Anee Auto',
                'lede' => 'Rather not plan it all yourself? Tell Anee about your farm once. She analyzes everything, plans the whole season, tells you what to do each day, and moves the work herself when the rain comes or a worker cannot. You just follow.',
                'say' => null,
                'face' => 'wink',
                'pattern' => 'auto',
                'glyph' => 'M12 3l1.8 4.9L19 9.7l-5.2 1.8L12 16.4l-1.8-4.9L5 9.7l5.2-1.8L12 3zm6 11l.9 2.3 2.3.9-2.3.9L18 20.4l-.9-2.3-2.3-.9 2.3-.9L18 14z',
                'items' => [
                    self::item('auto', 'Anee Auto', 'Anee plans the season, you just follow', 'anee/avatar-160.jpg', true,
                        'Answer a few questions once: your lots, the crop and variety, your soil, your water, your fertilizer and your workers. Anee looks up your variety, reads the forecast and the season ahead, and writes every task from land preparation to harvest. Then she keeps it on track every day.',
                        ["Today's work every morning, by email too, for you and each worker", "Can't today? She moves it to the best day near it", 'Send a photo, a video or a voice note and she adjusts the plan'],
                        null, 'auto.index'),
                ],
            ],
        ]));

        // Hand placed on the owner's word (2026-10-02): where a tool should sit
        // a little off the spot the desktop burst gives it, in rem [right, down].
        // Since 2026-10-05 every line in a step is one length, which is what
        // the old nudges were reaching for by hand (Team logins, Today on the
        // board and Anee Season Report further down; Variety up a little);
        // kept on, they would make those lines the odd ones out again.
        $nudge = [];
        foreach ($stages as &$st) {
            foreach ($st['items'] as &$it) {
                $it['nudge'] = $nudge[$it['key']] ?? null;
                // Every tool opens onto its own article page.
                if (isset(self::PAGES[$it['key']])) {
                    $it['page'] = self::PAGES[$it['key']];
                    $it['url'] = null;
                }
                $it['video'] = self::video($it['key']);
                $it['plan'] = self::planOf($it['key']);
            }
            unset($it);
        }
        unset($st);

        return $stages;
    }

    /**
     * The phone in the hero: someone logs in, and the real dashboard comes up
     * as it looks inside the app (the greeting, the three tiles, the tip of the
     * day, today's work on a season with its weather, the news feed).
     */
    public static function phone(): array
    {
        $ph = Region::ph();
        // The season's crop as the app's crop tables know it, and the count
        // it runs on (transplanted rice counts days after transplanting).
        $cropKey = $ph ? 'rice' : 'corn_sweet';
        $counter = $ph ? 'DAT' : 'DAP';
        $stage = CropStages::stageFor($cropKey, 28, $counter, null);

        return [
            'name' => $ph ? 'Juan' : 'Sam',
            'initials' => $ph ? 'JD' : 'SR',
            'email' => $ph ? 'juan@bukid.ph' : 'sam@greenacre.farm',
            'hello' => $ph ? 'Magandang umaga' : 'Good morning',
            'plan' => 'Solo Farmer',
            'daysLeft' => 28,
            'tipTitle' => 'Spraying',
            'tip' => 'Spray before 3 PM today. The rain after that would wash it off.',
            'season' => $ph ? 'Wet season palay 2026' : 'Spring corn 2026',
            'crops' => $ph ? '🌾' : '🌽',
            'tasks' => [
                ['Fertilizer', 'high', 'Apply urea, 1 bag per hectare', 'Lot 2', 3, 'Half day'],
                ['Irrigation', 'medium', $ph ? 'Irrigate the paddy' : 'Irrigate the north block', 'Lot 1', 1, 'Half day'],
                ['Scouting', 'low', 'Scout for armyworm', 'Lot 2', 1, null],
            ],
            'place' => $ph ? 'Cabanatuan, Nueva Ecija' : 'Fresno, California',
            'days' => [['Today', '⛅', 31, 24], ['Fri', '🌧️', 29, 24], ['Sat', '🌦️', 30, 24], ['Sun', '☀️', 32, 25], ['Mon', '⛅', 31, 24]],
            'advice' => 'Rain after 3 PM today. Spray in the morning.',
            'post' => $ph
                ? ['Rosa Santos', 'RS', 20, '2h', 'Ang ganda ng tubo ng mais ko ngayong linggo! 🌽', 24, 6]
                : ['Rosa Santos', 'RS', 20, '2h', 'My corn is looking great this week! 🌽', 24, 6],
            // After the dashboard the film opens the season: the Cropping
            // Schedules list, the season's modules, then its activities board
            // with today's tasks (the same three as on the dashboard).
            'board' => [
                'desc' => $ph ? 'Riverside and the upper field' : 'The north and south blocks',
                'crop' => $ph ? 'Rice, transplanted (Palay)' : 'Sweet corn',
                'cropKey' => $cropKey,
                'counter' => $counter,
                'day' => 28,
                'stage' => self::plain($stage['label'] ?? ($ph ? 'Active tillering' : 'Rapid growth')),
                'length' => 105,
                'lots' => 2,
                'workers' => 3,
                'activities' => 36,
                // Lot => its day count, for the lot chip on each card.
                'das' => ['Lot 2' => 28, 'Lot 1' => 30],
                // Per task above: the type as the board names it, a water
                // badge (irrigation only) and the card's note.
                'cards' => [
                    ['Fertilizer (Granular)', null, 'Broadcast evenly before the rain this afternoon.'],
                    ['Irrigation', 'Irrigate', 'Keep 3 to 5 cm of water in the field.'],
                    ['Monitoring', null, $ph ? 'Start with the leaves near the levee.' : 'Start with the rows nearest the road.'],
                ],
                // Yesterday's one task, already done.
                'yesterday' => ['Water check after the rain', 'Lot 1'],
            ],
        ];
    }

    /**
     * The phone between steps 3 and 4: the same season's activities board.
     * A note goes on today, a task is dragged to tomorrow and one is ticked
     * done, then the Modules menu opens Growth Stages. Each lot's stage, its
     * tips and its timeline come from CropStages and CropStageTips, so the
     * film reads the crop exactly the way the app does.
     */
    public static function board(): array
    {
        $ph = Region::ph();
        $b = self::phone()['board'];
        $lots = [];
        foreach ($b['das'] as $name => $day) {
            $st = CropStages::stageFor($b['cropKey'], $day, $b['counter'], null);
            $tips = $st ? CropStageTips::for($b['cropKey'], $st['index'], $b['counter']) : ['do' => [], 'watch' => []];
            $lots[$name] = [
                'day' => $day,
                'stage' => self::plain($st['label'] ?? ''),
                'what' => self::plain($st['what'] ?? ''),
                'needs' => self::plain($st['needs'] ?? ''),
                'progress' => isset($st['progress']) ? (int) round($st['progress'] * 100) : null,
                'dayIn' => ($st['dayInStage'] ?? 0) + 1,
                'next' => isset($st['next']) ? [self::plain($st['next']['label']), $st['next']['inDays']] : null,
                'do' => array_map([self::class, 'plain'], array_slice($tips['do'] ?? [], 0, 2)),
                'watch' => array_map([self::class, 'plain'], array_slice($tips['watch'] ?? [], 0, 2)),
                'timeline' => array_map(fn ($t) => [self::plain($t['label']), $t['from'], $t['isNow'], $t['isPast']],
                    CropStages::timeline($b['cropKey'], $day, $b['counter'], null)),
            ];
        }

        return [
            'note' => [$ph ? 'East side, after the urea' : 'North block, after the urea', 'Leaves look greener already. Half a bag left in the shed.'],
            'mode' => self::plain(\App\Http\Controllers\Manager\GrowthStageController::counterSays($b['counter'])),
            'lots' => $lots,
            'notes' => [
                ['📝', 'Add a note', 'Words, photos or your voice'],
                ['↕️', 'Drag it to another day', 'Rain coming? Move the task'],
                ['✅', 'Tick it done', 'The shed and the costs follow'],
                ['🌱', 'Check the growth stage', 'Where each lot stands today'],
            ],
        ];
    }

    /**
     * A tool's film: a phone recording of the real app in use, played in the
     * modal a tool opens (public/videos/how/{key}.mp4 and its .webp poster,
     * recorded on the test owner's account). Null until one is recorded; the
     * modal then shows the tool's words alone. The file's time busts caches.
     */
    /** How many steps, said as a word for a sentence ("Eight steps, ..."). */
    public static function stepWord(bool $capital = false): string
    {
        $n = count(self::stages());
        $w = [5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten'][$n] ?? (string) $n;

        return $capital ? ucfirst($w) : $w;
    }

    public static function video(string $key): ?array
    {
        $mp4 = public_path('videos/how/' . $key . '.mp4');
        if (! is_file($mp4)) {
            return null;
        }
        $poster = public_path('videos/how/' . $key . '.webp');

        return [
            asset('videos/how/' . $key . '.mp4') . '?v=' . filemtime($mp4),
            is_file($poster) ? asset('videos/how/' . $key . '.webp') . '?v=' . filemtime($poster) : '',
        ];
    }

    /** The crop tables' words, said without dashes, the way the site writes. */
    public static function plain(?string $s): string
    {
        return str_replace([' — ', '—', ' – ', '–'], [', ', ', ', ' to ', ' to '], (string) $s);
    }

    /**
     * The phone between steps 4 and 5: a grower asks Anee about a crop in
     * the real chat, with a photo, and Anee answers knowing the lot, its
     * stage and the weather. The notes say what she read first.
     */
    public static function chat(): array
    {
        $ph = Region::ph();

        return [
            'initials' => $ph ? 'JD' : 'SR',
            // A leaf with a real problem on it: sheath blight's bleached,
            // brown-edged blotches (the crop problem guide's own photo).
            'photo' => 'images/site/guides/problems-sheath-blight.webp',
            'question' => $ph
                ? 'May ganitong mantsa ang dahon ng palay ko at kumakalat. Ano ito at ano ang dapat kong gawin?'
                : 'These patches are spreading on my rice leaves. What is it and what should I do?',
            // What Anee does before she answers, in order.
            'reading' => ['Deeply analyzing your photo', 'Checking related data', 'Providing your answer'],
            'lead' => $ph ? 'Mukhang sheath blight ito, isang sakit na dulot ng fungus.' : 'This looks like sheath blight, a disease caused by a fungus.',
            'body' => $ph
                ? 'Ang malapad na maputlang mantsa na may kayumangging gilid ang karaniwang tanda nito, lalo na kung maalinsangan at siksik ang tanim.'
                : 'Wide pale blotches with brown edges are its usual sign, especially in humid weather and a dense crop.',
            'steps' => $ph
                ? ['<b>Huwag munang dagdagan ang urea.</b> Pinapalala ng sobrang nitrogen ang sakit.', 'Mag-spray ng <b>fungicide na rehistrado para sa sheath blight</b>, nakatutok sa ibabang bahagi ng puno.', 'Pagkatapos ng ani, linisin ang dayami at damo para hindi na ito bumalik.']
                : ['<b>Hold off on more urea for now.</b> Too much nitrogen makes it worse.', 'Spray a <b>fungicide registered for sheath blight</b>, aimed at the lower stems.', 'After harvest, clear the straw and weeds so it does not come back.'],
            'cost' => 7,
            'balance' => 120,
            'notes' => [
                ['🔍', 'Deeply analyzing the photo', 'Leaf color, spots and pattern'],
                ['📊', 'Checking related data', 'Your season, weather and records'],
                ['✍️', 'Providing the answer', 'What it is and what to do'],
            ],
        ];
    }

    /** The notes that float around the phone, one for each card on its dashboard. */
    public static function pings(): array
    {
        return [
            ['📋', "Today's activities", '3 tasks on 2 lots'],
            ['🌤️', 'Weather today', '31°C, rain after 3 PM'],
            ['💡', "Anee's tip for today", 'Spray before the rain'],
            ['💬', 'Today in the community', '5 new posts near you'],
        ];
    }

    private static function item(string $key, string $name, string $short, string $icon, bool $anee, string $what, array $gets, ?string $page, ?string $app, ?string $url = null): array
    {
        return compact('key', 'name', 'short', 'icon', 'anee', 'what', 'gets', 'page', 'app', 'url');
    }
}
