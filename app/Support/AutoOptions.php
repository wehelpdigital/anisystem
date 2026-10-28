<?php

namespace App\Support;

/**
 * How the farmer wants Anee Auto to run the season (2026-10-28, the owner: "ask the user if user wants to include
 * inventory and price ..., include land prep ..., irrigation schedules (if user says no for irrigation, just provide
 * it as a recommendation), and worker payments and based on the answers, will how the daily management of anee auto
 * be ... if land prep is included, ask the questions similar to the land prep analysis to know the capability of the
 * user to do land prep (machineries, external services, etc.) ... you can also do mixing sprays that are compatible
 * to save labor"; and "include recommendations in the schedule, etc. of these protections and preemptive approach
 * (let the user select)").
 *
 * Kept in the season's answers (answers.options), asked on the wizard's first step, before the lots and their
 * planting days, so the land preparation the farmer can do is known when the day is picked. The calendar prompt
 * (AutoBrain) and the planner (AutoPlanner) read them; the season page shows the tabs they turn on.
 */
final class AutoOptions
{
    /** The switches: key => [icon, title, line when on, line when off, default]. */
    public const SWITCHES = [
        'landPrep' => ['🚜', 'Land preparation', 'Anee plans the plowing, harrowing and leveling before planting, by your machines and crew.', 'You prepare the field your own way; Anee only checks it is ready on time.', true],
        'irrigation' => ['💧', 'Irrigation schedule', 'Each watering and draining goes on the calendar on its day.', 'Anee only advises on the water, with no watering tasks.', true],
        'inventory' => ['📦', 'Inventory and prices', 'Anee keeps the shed: what you have, what each task uses, what is left to buy and what it costs. You fill in what you buy.', 'No shed: the products are named on each task only.', true],
        'payments' => ['💵', 'Worker payments', 'Anee counts what each worker earns from the work they do, ready for payday.', 'No pay is counted.', false],
        'mixing' => ['🧪', 'Combine sprays', 'When two sprays fall close together and are safe to mix, Anee puts them in one trip to save labor.', 'Every spray goes on its own trip.', true],
    ];

    /** The protection approach: key => [icon, title, line, what the calendar does]. */
    public const PROTECTION = [
        'smart' => ['🎯', 'Smart, the seed first', 'What the seed guards against gets no spray. The rest is scouted every week and sprayed when it passes the threshold, with prevention only at the few stages where waiting costs the crop.',
            'Plan preventive sprays only at the stages where waiting costs the crop, never for what the seed covers; everything else is weekly scouting and an "only if you find" spray past the threshold.'],
        'preventive' => ['🛡️', 'Preventive', 'Sprays at the risky stages before anything shows, for the pests and diseases common here. More sprays and cost, less risk where they come every season.',
            'Plan preventive sprays at the crop book\'s risky stages for the pests and diseases common at this place and season (still none for what the seed covers), plus weekly scouting.'],
        'minimal' => ['🔍', 'Only when needed', 'No preventive sprays at all: you scout every week and Anee sprays only what you find. The fewest sprays; it needs careful scouting.',
            'Plan NO preventive sprays: a scouting walk every week in the critical stages, and a spray only "if you find" a pest or disease past its threshold.'],
    ];

    /** How soon a hired machine comes: key => words. */
    public const HIRE = ['quick' => 'Yes, within a few days', 'slow' => 'Yes, but it takes a week or more', 'no' => 'No hired service here'];

    /** The machines the land preparation asks about (LandPrepPlanner's equipment), the ones a farmer names first. */
    public const MACHINES = ['hand_tractor', 'tractor_rotavator', 'tractor_disc', 'carabao_plow', 'carabao_harrow', 'transplanter', 'drum_seeder'];

    public static function defaults(): array
    {
        return array_map(fn ($s) => $s[4], self::SWITCHES) + ['protection' => 'smart', 'fleet' => [], 'hire' => 'quick'];
    }

    /** The season's options, filled with the defaults. */
    public static function of(array $answers): array
    {
        return self::clean((array) ($answers['options'] ?? []));
    }

    public static function clean(array $in): array
    {
        $o = self::defaults();
        foreach (self::SWITCHES as $k => $_) {
            if (array_key_exists($k, $in)) {
                $o[$k] = filter_var($in[$k], FILTER_VALIDATE_BOOLEAN);
            }
        }
        if (isset(self::PROTECTION[$in['protection'] ?? ''])) {
            $o['protection'] = $in['protection'];
        }
        if (isset(self::HIRE[$in['hire'] ?? ''])) {
            $o['hire'] = $in['hire'];
        }
        $fleet = [];
        foreach ((array) ($in['fleet'] ?? []) as $f) {
            $t = (string) ($f['type'] ?? '');
            $own = max(0, min(20, (int) ($f['own'] ?? 0)));
            $hired = max(0, min(20, (int) ($f['hired'] ?? 0)));
            if (in_array($t, self::MACHINES, true) && $own + $hired > 0) {
                $fleet[$t] = ['type' => $t, 'own' => $own, 'hired' => $hired];
            }
        }
        $o['fleet'] = array_values($fleet);
        $o['set'] = ! empty($in['set']) || array_key_exists('protection', $in);

        return $o;
    }

    /** What the page draws the step from: the switches, the approaches, the machines with their names and pictures. */
    public static function forPage(): array
    {
        $items = (array) (LandPrepPlanner::equipmentBook()['items'] ?? []);
        $machines = [];
        foreach (self::MACHINES as $k) {
            if (isset($items[$k])) {
                $e = $items[$k];
                $machines[] = ['key' => $k, 'name' => $e['name'], 'local' => $e['local'] ?? '', 'photo' => ! empty($e['photo']['file']) ? asset(ltrim($e['photo']['file'], '/')) : null];
            }
        }

        return ['switches' => array_map(fn ($s) => ['icon' => $s[0], 'title' => $s[1], 'on' => $s[2], 'off' => $s[3]], self::SWITCHES),
            'protection' => array_map(fn ($p) => ['icon' => $p[0], 'title' => $p[1], 'line' => $p[2]], self::PROTECTION),
            'hire' => self::HIRE, 'machines' => $machines];
    }

    /** The machines in words: "1 hand tractor and a hired four wheel tractor with a rotavator", '' for none. */
    public static function fleetWords(array $o): string
    {
        $parts = [];
        foreach ((array) $o['fleet'] as $f) {
            $w = LandPrepPlanner::machineWord($f['type']);
            if ($f['own'] > 0) {
                $parts[] = $f['own'] . ' ' . $w . ' of their own';
            }
            if ($f['hired'] > 0) {
                $parts[] = 'a hired ' . $w;
            }
        }

        return count($parts) > 1 ? implode(', ', array_slice($parts, 0, -1)) . ' and ' . end($parts) : (string) ($parts[0] ?? '');
    }

    /** The rules the calendar prompt adds for these options. */
    public static function promptRules(array $o): string
    {
        $r = ['PROTECTION (the farmer chose "' . self::PROTECTION[$o['protection']][1] . '"): ' . self::PROTECTION[$o['protection']][3]];
        if ($o['landPrep']) {
            $fw = self::fleetWords($o);
            $r[] = 'LAND PREPARATION: plan each pass as its own task, sized to the farmer\'s own means: ' . ($fw !== '' ? $fw : 'no machine named, so carabao or hand work, or a hired machine') . '; a hired machine: ' . mb_strtolower(self::HIRE[$o['hire']])
                . '. When a hired machine is needed, add a "book the machine" task early enough for that wait.';
        } else {
            $r[] = 'LAND PREPARATION: the farmer prepares the field their own way. Plan no plowing or harrowing passes, only one "Field ready?" check a few days before planting, saying what ready means for this crop.';
        }
        $r[] = $o['irrigation']
            ? 'WATER: put each watering, flooding and draining on the calendar on its day, fitted to their water source.'
            : 'WATER: the farmer does not want watering tasks. Plan none; say the water plan for the season in the strategy and in the planting task\'s why, as advice.';
        if ($o['mixing']) {
            $r[] = 'COMBINED SPRAYS: when two sprays for this lot fall within each other\'s early and late days and are safe to mix (no mix rule 4 forbids, same water volume, the label allows it), plan them as ONE task with both products, the order to add them to the tank (water, then powders, then liquids, then oils), and a jar test first. Never combine more than two products, and never copper or sulfur with an oil.';
        }
        if ($o['inventory']) {
            $r[] = 'THE SHED: every product of every task carries its amount for this lot as a number and a unit (like "2 bags of 50 kg", "1.2 L", "150 g"), so the shed can count what the season uses.';
        }

        return implode("\n", array_map(fn ($x, $i) => '- ' . $x, $r, array_keys($r)));
    }

    /** The options in a few words, for the facts Anee reads and the review. */
    public static function words(array $o): string
    {
        $on = [];
        foreach (self::SWITCHES as $k => $s) {
            if ($o[$k]) {
                $on[] = mb_strtolower($s[1]);
            }
        }

        return 'Protection: ' . self::PROTECTION[$o['protection']][1] . '. Anee runs: ' . ($on ? implode(', ', $on) : 'none of the extras') . '.'
            . ($o['landPrep'] && self::fleetWords($o) !== '' ? ' Land preparation with ' . self::fleetWords($o) . '.' : '');
    }
}
