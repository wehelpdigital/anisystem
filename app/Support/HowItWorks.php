<?php

namespace App\Support;

/**
 * How anee.io works (2026-10-01): the cropping season told as seven steps
 * (six until 2026-10-02, when planning split into planning and building the
 * plan), each with the tools a grower reaches for at it and what Anee does
 * there.
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
 *
 * The words follow the site's style: short, plain, no dashes.
 */
class HowItWorks
{
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
                    self::item('when', 'When to Plant Analysis', 'The best planting window for your town', 'appointment.png', true,
                        "Anee reads your town's weather record, the outlook for the coming months and how your crop grows, then names the window that gives it the best start and the safest harvest.",
                        ['A best window and a second choice', 'The risk in each month, said plainly', 'Saved, so you can come back to it'],
                        'when-to-plant-analysis', 'wtp.page'),
                    self::item('what', 'What to Plant Analysis', 'The crop that fits your land this season', 'plant.png', true,
                        'Not sure what to grow? Anee weighs your climate, the forecast, your soil and your water, and ranks the crops that should do best on your land this season.',
                        ['Crops ranked for your place and season', 'Why each one fits, or does not', 'What each one will need from you'],
                        null, 'whatp.page'),
                    self::item('variety', 'Variety Research', 'Compare varieties before you buy seed', 'icons/biotechnology.png', true,
                        'Anee looks up the varieties of your crop and compares them for your area: yield, maturity, resistance and how they did in weather like yours.',
                        ['Varieties side by side', 'Strong and weak points for your area', 'The sources she used, to check yourself'],
                        null, 'vary.page'),
                    self::item('cropProtocol', 'Crop Protocol Analysis', 'Anee writes a protocol for your variety', 'icons/biostimulant.png', true,
                        'Pick your crop and variety, and Anee writes the season for your place by growth stage: the bags of fertilizer and when, the sprays, the water and what to watch for.',
                        ['Stage by stage, from land prep to harvest', 'Fertilizer, sprays and water on their days', 'Read against your weather, soil and water'],
                        null, 'proto.page'),
                    self::item('maps', 'Lot planning with Maps powered with GPS', 'Draw, measure and pin your fields', 'location-marker.png', false,
                        'Trace each field over a satellite view and the app gives you its area and the length of each side. Drop pins on the pump, the gate or the low spot that floods.',
                        ['The area and sides of every field', 'Pins with notes and photos', 'Attach a map to a lot, a task or a note'],
                        'farm-maps', 'maps.page'),
                    self::item('draw', 'Draw', 'Team planning: sketch the plan everyone follows', 'writting.png', false,
                        'Plan with your team on a drawing: where the seedbed goes, how the water moves, who works which lot. Sketch a layout, a plan or a flow, and everyone works from the same picture.',
                        ['Shapes, lines, text and colors', 'Draw over a photo of your field', 'Kept in your gallery for any season'],
                        null, 'draw.page'),
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
                'lede' => 'Turn the plan into a living cropping schedule. Each lot keeps its own day zero, so every task lands on the right date.',
                'say' => null,   // the owner took this step's line out (2026-10-01)
                'face' => 'salute',
                'pattern' => 'plant',
                'glyph' => 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z',
                'items' => [
                    self::item('schedule', 'Cropping schedule', 'The whole season on one board', 'icons/calendar.png', false,
                        "Start a season from your protocol or from scratch. Every task, irrigation, hired service and payroll day sits on one board, dated from each lot's own day zero.",
                        ['Drag a task to move it', 'Drafts and versions for the what ifs', 'Undo that survives a logout'],
                        'cropping-calendar', 'sm.index'),
                    self::item('lots', 'Lots', 'Each field with its own crop and count', 'treasure-map.png', false,
                        'Add each field as a lot with its crop, variety, size and planting date. Lots sown a week apart keep their own count, so the timing stays honest.',
                        ['Crop, variety, size and dates per lot', "Attach the lot's map", 'Two crops in one season if you grow them'],
                        null, 'sm.index'),
                    self::item('workers', 'Workers and payroll', 'The right hands on the right task', 'tractor.png', false,
                        "Keep a roster with each worker's rate, put people on each task, tick who came, and the labor cost adds itself up.",
                        ['Rates, attendance and payroll days', 'Whole days and half days', 'Labor cost counted as you go'],
                        'farm-workers-and-payroll', 'sm.index'),
                    self::item('access', 'Team logins', 'Your team sees what you allow', 'friends.png', false,
                        'Give a worker or a partner their own login, and decide module by module what they may see and what they may change.',
                        ['None, view or edit, per module', 'A diary of every change and who made it', 'One login for each person'],
                        null, 'sm.index'),
                    self::item('collab', 'Collab Room', 'Chat, whiteboard and calls for the team', 'speech-bubbles.png', true,
                        'Each season has a room for its team: chat with photos and voice notes, a shared whiteboard, calls, and Anee in the room to answer the whole team at once.',
                        ['A group chat for each season', 'A whiteboard everyone can draw on', 'Ask Anee together'],
                        null, 'sm.index'),
                    self::item('morning', 'Morning plan email', "Today's work in every inbox at 6 AM", 'time.png', false,
                        "Every morning at 6 AM your team gets the day's plan by email: what to do and on which lot.",
                        ['Sent on its own every day', 'The whole team starts on the same page', 'Nothing to remember to send'],
                        null, null),
                    self::item('stock', 'Stock that counts itself', 'Tick a task and the shed updates', 'icons/fertilizer.png', false,
                        'When you tick a fertilizer or spray task done, the shed takes it out of stock. Undo the tick and it goes back. The costs follow into your expenses.',
                        ['On hand is always the sum of the moves', 'Each move names who made it', 'Costs flow into the expenses report'],
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
                'items' => [
                    self::item('growth', 'Growth stages', 'Where every lot stands today', 'icons/soil-restoration.png', false,
                        'Pick any date and each lot shows its growth stage, what to do now and what to watch for. anee.io knows ' . ($ph ? '85 Philippine crops, from palay and mais to gulay and fruit trees.' : 'nearly a hundred crops, from rice and corn to vegetables and fruit trees.'),
                        ['Stage, do list and watch list per lot', 'Annual and perennial crops', 'Shown right on the board'],
                        'growth-stages-and-weather', 'sm.index'),
                    self::item('weather', 'Weather for your farm', 'The forecast where your field is', 'weather.png', false,
                        'See the forecast for your own farm beside your plan, so a spray is not wasted on the day before the rain.',
                        ['Rain, heat and wind for the week', 'Right beside the growth stages', 'Anee reads it before she answers'],
                        'growth-stages-and-weather', 'sm.index'),
                    self::item('realign', 'Realign by Anee', 'When a crop runs ahead or behind', 'user-refresh.png', true,
                        "Crops do not read calendars. Anee reads a lot's records, its weather and your notes, works out the stage it is truly in, and moves that lot's stage count to match.",
                        ['The real stage, with her reasons', 'What to do now and what to watch', 'Your calendar dates stay as they are'],
                        null, 'sm.index'),
                    self::item('board', 'Today on the board', 'Tick the work as it gets done', 'list.png', false,
                        'Open the day and see each task on its lot. Tick it done, mark who worked, and add a photo or a voice note. The board keeps the record for your reports.',
                        ['Done, moved or skipped, all on record', 'Workers and materials on each task', 'Photos, clips and voice on any task'],
                        'cropping-calendar', 'sm.index'),
                    self::item('capture', 'Notes from the field', 'Snap it, film it or just say it', 'voice-recorder.png', false,
                        'Quick Capture takes a photo, Quick Record takes a video and Quick Voice files what you say as a note. It all lands with the season, easy to find later.',
                        ['Notes per season, per day or for everything', 'A gallery of every photo and clip', 'Tags that tie it all together'],
                        'notes-photos-and-voice', 'notes.hub'),
                    self::item('offline', 'Works without signal', 'Keep going at the far lot', 'icons/offline.png', false,
                        'Turn on offline mode and keep ticking tasks and writing notes where there is no signal. anee.io keeps your changes on the phone and sends them once the signal comes back.',
                        ['Turn it on once for each phone', 'Your recent seasons stay open', 'Changes wait, then sync'],
                        null, null),
                    self::item('tip', "Anee's tip of the day", 'One useful thing to do today', 'idea.png', true,
                        'Each day Anee looks at your lots, their stages and the weather, and gives you one tip worth acting on today.',
                        ['Read on your dashboard each morning', 'Fitted to your lots and stages', 'Ask her more with one tap'],
                        null, null),
                ],
            ],
            [
                'key' => 'protect',
                'word' => 'Protect',
                'when' => 'When something looks wrong',
                'title' => 'Catch problems early',
                'lede' => 'Yellow leaves, holes in the leaves, a storm on the way. The sooner you know what it is, the less it costs.',
                'say' => null,   // the owner took every step's Anee line out (2026-10-02)
                'face' => 'concerned',
                'pattern' => 'protect',
                'glyph' => 'M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3zM9 12l2 2 4-4',
                'items' => array_values(array_filter([
                    self::item('chat', 'Chat with Anee', 'Ask anything, even with a photo', 'icons/chat.png', true,
                        'Ask in ' . ($ph ? 'Tagalog or English' : 'plain English') . ' and send a photo of the sick plant. Anee answers with your farm in mind: the lot, its stage, the weather and what you already applied.',
                        ['Photos, files and reports in the chat', 'Answers that know your season', 'Picks up where you left off'],
                        'ai-agricultural-technician', 'ai.index'),
                    self::item('sofar', 'Analyze So Far', 'A check up halfway through', 'pie-chart.png', true,
                        'Halfway through the season, ask Anee to read it so far: where the crop stands, the risks ahead and what to do next.',
                        ['Where each lot stands now', 'The risks in the coming weeks', 'Next steps, in order'],
                        'farm-reports', 'sm.index'),
                    $ph ? self::item('guides', 'Crop problem guides', 'Pests, diseases and weeds, explained', 'icons/tool-box.png', false,
                        'Free guides to the pests, diseases and weeds of Philippine crops: how to spot them, why they come and what to do about them.',
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
                        ['Albums you make on purpose', 'Every season in one Global Gallery', 'Attach any picture to a note'],
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
                        'Every worker day and the labor cost for the season, added up from the board. Nothing to type twice.',
                        ['Counted from the attendance you ticked', 'Saved on your report shelf', 'Copy it as text to share'],
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
        ]));

        // Hand placed on the owner's word (2026-10-02): where a tool should sit
        // a little off the spot the desktop burst gives it, in rem [right, down].
        $nudge = [
            'variety' => [0, -2.5],     // up a little
            // ('review' sat 7rem further left until it moved to Step 2, 2026-10-02.)
            'access' => [0, 5.6],       // Team logins, further down
            'board' => [0, 5.6],        // Today on the board, further down
            'season' => [-6.9, 6.25],   // Anee Season Report, further down and left
        ];
        foreach ($stages as &$st) {
            foreach ($st['items'] as &$it) {
                $it['nudge'] = $nudge[$it['key']] ?? null;
                $it['video'] = self::video($it['key']);
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
