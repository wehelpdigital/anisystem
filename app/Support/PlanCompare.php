<?php

namespace App\Support;

/**
 * The plan comparison table (/pricing/compare, 2026-10-01; inside the app on
 * My Subscription since 2026-10-18).
 *
 * Every cell is read off config/tiers.php, the same numbers the gates in
 * App\Support\Tier read, so the table can never promise a door the app
 * keeps shut. A row is [label, hint, reader, glossary key]: the reader gets
 * one tier's config and returns true (included), false (not included) or a
 * short text ("3", "Unlimited", "Today and tomorrow"); the glossary key puts
 * a Taglish pill after the label inside the app. A group may carry a note,
 * a line said once under its heading.
 *
 * 2026-10-18 (the owner: "update the pricing table comparison"): a row for
 * every module the dashboard's Global and Quick Tools open, grouped the way
 * a farmer meets them: the season, what Anee does, the field helpers and
 * calculators, the weather, the team, the records, the reports, the
 * community and news. No add-on any more: the field helpers come with
 * Libre + Anee and up (fieldHelpers), as do Satellite Weather (skyWeather)
 * and the NPK Plus calculator (npkPlus).
 */
class PlanCompare
{
    /** The plans in order, admin left out: [key => config]. */
    public static function plans(): array
    {
        return array_filter(config('tiers'), fn ($t, $k) => $k !== 'admin', ARRAY_FILTER_USE_BOTH);
    }

    public static function groups(): array
    {
        $count = fn (string $key) => fn (array $t) => $t[$key] === null ? 'Unlimited' : (string) $t[$key];
        $gate = fn (string $key) => fn (array $t) => (bool) ($t[$key] ?? false);
        $all = fn () => fn (array $t) => true;
        $paid = fn (array $t) => ! empty($t['price']);
        // Kept reading both wordings (label-style: stored text other code reads).
        $adFree = fn (array $t) => (bool) array_intersect(['No ads', 'Ad-free'], $t['features'] ?? []);
        $ph = Region::ph();

        return [
            ['Seasons and fields', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', [
                ['Active seasons', 'Seasons running at the same time', $count('schedulesActive')],
                ['Archived seasons', 'Finished seasons you keep to look back on', $count('schedulesArchived')],
                ['Lots per season', 'Fields, each with its own crop and day zero', $count('lotsPerSchedule')],
                ['Global Lots', 'Each field once: its place, size, soil and water, read by every analysis', $all(), 'global-lots'],
                ['Activities board, notes, tags', 'The whole season day by day', $all()],
                ['Growth stages', 'Where every lot stands on any date', $all(), 'growth-stage'],
                ['Protocol Builder', 'Write the season before day zero and make a season from it', $all(), 'protocol-builder'],
                ['Farm maps', 'Draw, measure and pin your fields', fn (array $t) => empty($t['maps']) ? false : ($t['mapsTotal'] === null ? 'Unlimited' : (string) $t['mapsTotal']), 'maps'],
                ['Drawings and photo capture', null, $all(), 'draw'],
            ]],
            ['Anee does it for you', 'M11 3.5l1.7 4.4 4.4 1.7-4.4 1.7L11 15.7l-1.7-4.4-4.4-1.7 4.4-1.7L11 3.5zM18 14l.9 2.3 2.3.9-2.3.9L18 20.4l-.9-2.3-2.3-.9 2.3-.9L18 14z', [
                ['Chat with Anee', 'Ask anything, send a photo', $gate('ai'), 'anee'],
                ['Planting analyses', 'When to Plant, What to Plant, Variety Ranking, Crop Protocol', $gate('aiAnalyses'), 'anee-does-it'],
                ['Satellite Analysis', 'Your field read from space: greenness, weak spots, radar through clouds', $gate('aiAnalyses'), 'satellite-analysis'],
                ['Land Prep Planner', 'Every step of getting your lots ready, dated back from planting', $gate('aiAnalyses'), 'land-prep-planner'],
                ['Anee Auto', 'Anee plans the whole season and keeps it on track', $gate('aiAnalyses'), 'anee-auto'],
                ['Anee reviews your protocol', 'Problems, strengths and weak spots before you commit', $gate('aiAnalyses'), 'anee-review'],
                ['Realign by Anee', 'The true growth stage of a lot', $gate('ai')],
                // What a paid month brings (ManualPay grants creditsMonthly for
                // every month bought); a plan with Anee but none buys packs.
                ['AI credits included', 'Added for every month you pay for', fn (array $t) => empty($t['ai']) ? false
                    : (! empty($t['creditsMonthly']) ? $t['creditsMonthly'] . ' a month' : 'Buy packs'), 'credits'],
            ], 'Anee works on AI credits, and every chat and analysis shows its price before it runs. She reads twenty years of your area\'s weather, and what she looks up about a variety or a product is kept, so the next look is quicker. Solo Farmer and Farm Owner add credits for every month you pay for, and any plan with Anee can buy more packs inside the app.'],
            ['Field helpers and calculators', 'M14.5 5.2a4.2 4.2 0 00-5.3 5.3L4 15.7 7.3 19l5.2-5.2a4.2 4.2 0 005.3-5.3l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6z', [
                ['Pest Finder and Disease Finder', 'From the damage or the signs you see to what to spray', $gate('fieldHelpers'), 'pest-finder'],
                ['Weed Control Helper', 'What works at your crop\'s age, group by group', $gate('fieldHelpers'), 'weed-control-helper'],
                ['Nutrient Deficiency Finder', 'From the leaves you see to the missing nutrient', $gate('fieldHelpers'), 'nutrient-deficiency-finder'],
                ['Variety Finder', 'Every registered variety, with days to harvest, yield and resistance', $gate('fieldHelpers'), 'variety-finder'],
                ['Compatibility Checker', 'Which sprays and fertilizers go on together, the mixing order and the strength. Over 7,000 products ' . ($ph ? 'sold in the Philippines' : 'in the book'), $gate('fieldHelpers'), 'compatibility-checker'],
                ['NPK Plus Calculator', 'Every nutrient in your fertilizer plan, against what your crop needs', $gate('npkPlus'), 'npk-plus'],
                // The chart is read on every plan; Anee reading photos of the leaves needs her.
                ['LCC Checker', 'Rice: is it time for nitrogen, and how much urea', fn (array $t) => ! empty($t['aiAnalyses']) ? 'Chart and photos' : 'Your own chart', 'lcc-checker'],
                ['Benta o Bilad Calculator', 'Sell the palay wet or dry it first: which leaves you more money', $gate('bentaBilad'), 'benta-o-bilad'],
                ['Hatian Calculator', 'The harvest shared fairly with your farm manager, in sacks and kilos', $gate('hatian'), 'hatian'],
                ['Seed Count and Distance Calculator', 'The seeds for your spacing, or the spacing for your seeds, with the stand and the yield', $gate('seedCount'), 'seed-count-distance'],
                ['How to Plant', 'How traditional farmers plant every crop, step by step, into your Protocol Builder', $gate('howToPlant'), 'how-to-plant'],
            ], 'The calculators and finders use no credits. Anee reading a plan, a photo or a product the book does not know uses a few.'],
            ['Weather', 'M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z', [
                ['Forecast', null, fn (array $t) => $t['weatherDays'] === null ? 'Full forecast' : 'Today and tomorrow', 'forecast'],
                ['Weather right now', 'Shown on your dashboard', $gate('weatherNow')],
                ['Satellite Weather', 'Clouds, rain radar and typhoon paths over your farm. Opened by a weekly pass in credits', $gate('skyWeather'), 'satellite-weather'],
            ]],
            ['Workers and team', 'M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z', [
                ['Workers and payroll', 'Rates, attendance and labor cost on every task', $gate('workers'), 'workers'],
                ['Global Workers', 'Your crew once, put on any season or land prep plan', $gate('workers')],
                ['Inventory', 'The shed: what you have, and stock that counts itself', $gate('inventory'), 'mod-inventory'],
                ['Worker logins', 'With access levels per module', $gate('workerLogins')],
                ['Collab Room', 'Team chat, whiteboard and calls', $gate('collab'), 'mod-collab-room'],
                ['Logs diary', 'Every change and who made it', $gate('auditLogs')],
            ]],
            ['Records and media', 'M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 14l2.5-3 2 2.5L15 10l3 4', [
                ['Global Notes and Gallery', 'Every note, photo and drawing from every season', $all(), 'global-notes'],
                ['Contact List', 'Workers, buyers, traders and suppliers', $all(), 'contact-list'],
                ['Voice notes', null, $gate('voiceFarm'), 'quick-voice'],
                ['Video recording', null, $gate('videoRecording'), 'quick-record'],
                ['Document uploads', null, $gate('docUploads'), 'mod-documentation'],
                ['Offline mode', 'Keep working where there is no signal', $gate('offline')],
                ['Storage', null, fn (array $t) => $t['storageGb'] . ' GB' . ($paid($t) ? ', expandable' : '')],
            ]],
            ['Reports', 'M4 19h16M7 16v-4m5 4V8m5 8v-6', [
                ['Labor report and observations', null, $all(), 'labor-report'],
                ['Expenses, profit and every other report', 'Anee Season Report, Analyze So Far, View as Protocol and Compare Reports included', $gate('reportsAll'), 'compare-reports'],
            ]],
            ['Community and news', 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-1.4-.6M15 4H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l4-4h4a2 2 0 002-2V6a2 2 0 00-2-2z', array_values(array_filter([
                ['Community feed', 'Post and read with photos', $all()],
                $ph ? ['Latest in Agriculture', 'The farm news every few days, with what it means for your farm in Taglish and English', $all()] : null,
                ['The Stash', 'Partners\' magazines and guides, read in the app', $all()],
                ['Video and voice posts', null, $gate('communityVideo')],
                ['Discussion rooms to join', null, fn (array $t) => $t['discussionJoin'] === null ? 'Unlimited' : (string) $t['discussionJoin']],
                ['Private rooms', 'Password and approval rooms', $gate('discussionPrivateJoin')],
                ['Create your own rooms', null, $gate('discussionCreate')],
            ]))],
            // Said as what the reader sees, not as a tick for "No ads": a
            // dash under "No ads" was a double negative on a phone.
            ['Ads', 'M3 11l18-5v12L3 14v-3zM11.6 16.8a3 3 0 11-5.8-1.6', [
                ['Ads in the app', 'They keep Libre free', fn (array $t) => $adFree($t) ? 'None' : 'A few'],
            ]],
        ];
    }
}
