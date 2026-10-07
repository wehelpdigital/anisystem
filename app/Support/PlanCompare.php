<?php

namespace App\Support;

/**
 * The plan comparison table (/pricing/compare, 2026-10-01).
 *
 * Every cell is read off config/tiers.php, the same numbers the gates in
 * App\Support\Tier read, so the table can never promise a door the app
 * keeps shut. A row is [label, hint, reader]: the reader gets one tier's
 * config and returns true (included), false (not included) or a short
 * text ("3", "Unlimited", "Today and tomorrow"). A group may carry a note,
 * a line said once under its heading.
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
        $adFree = fn (array $t) => (bool) array_intersect(['No ads', 'Ad-free'], $t['features'] ?? []);

        return [
            ['Seasons and fields', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z', [
                ['Active seasons', 'Seasons running at the same time', $count('schedulesActive')],
                ['Archived seasons', 'Finished seasons you keep to look back on', $count('schedulesArchived')],
                ['Lots per season', 'Fields, each with its own crop and day zero', $count('lotsPerSchedule')],
                ['Activities board, notes, tags', 'The whole season day by day', $all()],
                ['Growth stages', 'Where every lot stands on any date', $all()],
                ['Farm maps', 'Draw, measure and pin your fields', $count('mapsTotal')],
                ['Drawings and photo capture', null, $all()],
            ]],
            ['Weather', 'M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z', [
                ['Forecast', null, fn (array $t) => $t['weatherDays'] === null ? 'Full forecast' : 'Today and tomorrow'],
                ['Weather right now', 'Shown on your dashboard', $gate('weatherNow')],
            ]],
            ['Anee, the smart farm technician', 'M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5', [
                ['Chat with Anee', 'Ask anything, send a photo', $gate('ai')],
                ['AI analyses', 'When to Plant, What to Plant, Variety Research, Crop Protocol', $gate('aiAnalyses')],
                ['Realign by Anee', 'The true growth stage of a lot', $gate('ai')],
            ], 'Anee works on AI credits, bought as packs inside the app. Every chat and analysis shows its price in credits before it runs.'],
            ['Workers and team', 'M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z', [
                ['Workers and payroll', 'Roster, rates, attendance', $gate('workers')],
                ['Inventory', 'The shed: what you have and what it cost', $gate('inventory')],
                ['Worker logins', 'With access levels per module', $gate('workerLogins')],
                ['Collab Room', 'Team chat, whiteboard and calls', $gate('collab')],
                ['Logs diary', 'Every change and who made it', $gate('auditLogs')],
            ]],
            ['Records and media', 'M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 14l2.5-3 2 2.5L15 10l3 4', [
                ['Voice notes', null, $gate('voiceFarm')],
                ['Video recording', null, $gate('videoRecording')],
                ['Document uploads', null, $gate('docUploads')],
                ['Offline mode', 'Keep working where there is no signal', $gate('offline')],
                ['Storage', null, fn (array $t) => $t['storageGb'] . ' GB' . ($paid($t) ? ', expandable' : '')],
            ]],
            ['Reports', 'M4 19h16M7 16v-4m5 4V8m5 8v-6', [
                ['Labor report and observations', null, $all()],
                ['Expenses, profit and every other report', 'Compare Reports included', $gate('reportsAll')],
            ]],
            ['Community', 'M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-1.4-.6M15 4H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l4-4h4a2 2 0 002-2V6a2 2 0 00-2-2z', [
                ['Community feed', 'Post and read with photos', $all()],
                ['Video and voice posts', null, $gate('communityVideo')],
                ['Discussion rooms to join', null, fn (array $t) => $t['discussionJoin'] === null ? 'Unlimited' : (string) $t['discussionJoin']],
                ['Private rooms', 'Password and approval rooms', $gate('discussionPrivateJoin')],
                ['Create your own rooms', null, $gate('discussionCreate')],
            ]],
            ['The rest', 'M5 13l4 4L19 7', [
                ['No ads', null, $adFree],
            ]],
        ];
    }
}
