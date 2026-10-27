<?php

namespace App\Services;

use App\Models\AsAutoLot;
use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use App\Support\AutoBook;
use App\Support\AutoGoal;
use App\Support\CropCatalog;
use App\Support\CropStages;
use App\Support\EnsoOutlook;
use App\Support\FieldCatalogue;
use App\Support\FieldContext;
use App\Support\FieldProfile;
use App\Support\SoilConditions;
use App\Support\VarietyBook;
use App\Support\VarietyLearned;
use App\Support\WorkDays;
use Illuminate\Support\Carbon;

/**
 * What Anee Auto asks the model (2026-10-12), and how it reads the answers.
 *
 *   reading()     the first reading, after the questions: the variety
 *                 (searched on the web), the soil from its signs and the
 *                 place, the season (ENSO, the weather, the same weeks in
 *                 past years), a realistic goal and the fertilizer focus.
 *   calendar()    one lot's season as tasks, each held to an anchor day
 *                 with a window, fertilizer split at the critical moments
 *                 from the farmer's own plan, prevention first, every spray
 *                 by active ingredient with its safety.
 *   observation() a photo, a video's frames, a voice note or words from the
 *                 field: what it likely is, what to do, new tasks.
 *   answer()      a question in the chat, answered with the season in mind.
 *
 * Every brief carries the same season facts (facts()), so the four agree.
 */
class AutoBrain
{
    public function __construct(private WeatherService $weather, private AutoPlanner $planner)
    {
    }

    /** The season in facts, for every brief. */
    public function facts(AsAutoSeason $auto, bool $withWeather = true): array
    {
        $s = AsCroppingSchedule::find($auto->croppingScheduleId);
        $a = (array) $auto->answers;
        $lots = [];
        $hzSeen = [];
        foreach (AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get() as $al) {
            $lot = AsScheduleLot::find($al->lotId);
            if (! $lot || (int) $lot->deleteStatus !== 1) {
                continue;
            }
            $at = $this->planner->placeOf($lot);
            // Learn once (2026-10-18): the variety's known facts, the record's or Anee's earlier lookup's, so nothing is looked up again.
            $rec = $lot->variety ? self::varietyOf($lot->crop, $lot->variety) : null;
            $row = [
                'lotId' => (int) $lot->id, 'name' => $lot->lotName, 'crop' => $lot->crop ? CropCatalog::label($lot->crop) : null, 'cropKey' => $lot->crop,
                'variety' => $lot->variety ?: null, 'method' => $al->method, 'counter' => $al->method ? AutoBook::counterOf($al->method) : null,
                'areaHa' => $lot->lotSize ? round((float) $lot->lotSize * match ($lot->lotSizeUnit) { 'sqm' => 0.0001, 'acre' => 0.404686, default => 1 }, 4) : null,
                'fieldDate' => $al->fieldDate?->toDateString(), 'sowDate' => $al->sowDate?->toDateString(), 'treeAgeMonths' => $al->treeMonths,
                'place' => trim(implode(', ', array_filter([$lot->locBarangay, $lot->locTown, $lot->locProvince]))) ?: null,
                'pin' => $lot->pinLat ? [round((float) $lot->pinLat, 5), round((float) $lot->pinLng, 5)] : null,
                // The lot's own days, else its variety's within the crop's range, else the crop's (AsScheduleLot::maturityDays,
                // 2026-10-19): the rule the season's feed and every module use, so the reading and the calendar agree with them.
                'maturityDays' => $lot->maturityDays() ?: CropCatalog::maturity($lot->crop),
            ];
            if ($rec) {
                $row['varietyKnown'] = ltrim(VarietyBook::promptLines([$rec]), '- ');
            } elseif (! $lot->variety && $lot->crop) {
                // No variety named: the records that fit the crop, so the reading picks from them and nothing is searched.
                $opts = rescue(fn () => VarietyBook::namedThenFit((string) $lot->crop, [], [], 5), [], false);
                if ($opts) {
                    $row['varietiesOnRecord'] = VarietyBook::promptLines($opts);
                }
            }
            // The area's typical soil from samples taken there (the soil atlas, 2026-10-18): never a test of this lot.
            $aw = \App\Support\SoilAtlas::words(\App\Support\SoilAtlas::for($lot->locProvince, $lot->locTown, $lot->locBarangay, $lot->crop), 'short');
            if ($aw !== '') {
                $row['areaSoil'] = preg_replace('/^[^\n]*\n/', '', $aw);
            }
            if ($at && $withWeather) {
                $row['soilGrids'] = $this->soilGrids($at[0], $at[1]);
                $row['forecast'] = $this->forecastWords($at[0], $at[1]);
            }
            // The lot's goal, the app's count (AutoGoal, 2026-10-28): the variety's best, the farmer's aim, the stand they
            // plant, the water, salt and sodium, and what it needs fed. Every brief reads the same number.
            $goal = rescue(fn () => AutoGoal::forLot($auto, $lot, $al, []), null, false);
            if ($goal && $goal['tPerHa']) {
                $row['goal'] = ['tPerHa' => $goal['tPerHa'], 'aim' => AutoGoal::AIMS[$goal['aim']][0], 'howCounted' => AutoGoal::words($goal)]
                    + (! empty($goal['inputs']['target']) ? ['farmerTarget' => $goal['inputs']['target']] : []);
            }
            if ($al->fert) {
                $row['fertilizerPlan'] = $this->fertWords((array) $al->fert);
            }
            if ($al->imbalance) {
                $row['farmerKeptAnUnbalancedPlan'] = (AutoBook::IMBALANCE[$al->imbalanceReason] ?? $al->imbalanceReason) . ($al->imbalanceNote ? ': ' . $al->imbalanceNote : '');
            }
            // The town's twenty years of storms, floods, dry spells and heat (the hazard book, 2026-10-18), the months this lot stands in the field marked; said once per town.
            $hzWhere = trim(implode(', ', array_filter([$lot->locTown, $lot->locProvince])));
            if ($hzWhere !== '' && ! isset($hzSeen[$hzWhere])) {
                $hzSeen[$hzWhere] = $row['name'];
                $hz = self::placeHistory($lot, $row['fieldDate'], (int) ($row['maturityDays'] ?? 120));
                if ($hz !== '') {
                    $row['placeHistory'] = $hz;
                }
            } elseif ($hzWhere !== '') {
                $row['placeHistory'] = 'Same town as ' . $hzSeen[$hzWhere] . ': see its placeHistory.';
            }
            $lots[] = $row;
        }
        $soil = (array) ($a['soilRead'] ?? []);
        $people = AutoPlanner::people($auto);

        return [
            'season' => $s?->title, 'about' => $s?->description, 'today' => now('Asia/Manila')->format('l, F j, Y'),
            'lots' => $lots,
            'soil' => [
                'signsTheFarmerSaw' => array_map(fn ($k) => AutoBook::SOIL_SIGNS[$k][0] ?? $k, (array) ($soil['signs'] ?? [])),
                'phTheFarmerSays' => AutoBook::PH[$a['soil']['ph'] ?? 'unsure'][0] ?? 'I do not know',
                'testedPh' => $soil['tested'] ?? null,
                'readFromSigns' => ['texture' => $soil['texture'] ?? 'unsure', 'conditions' => $soil['conditions'] ?? [], 'ph' => $soil['ph'] ?? 'unsure'],
                'guidance' => SoilConditions::guidance((array) ($soil['conditions'] ?? [])),
            ],
            // The field in detail (App\Support\FieldProfile, 2026-10-15), when the farmer gave it.
            'fieldInDetail' => ! empty($a['fieldProfile']) ? FieldProfile::words((array) $a['fieldProfile']) : null,
            'water' => AutoBook::WATER[$a['water'] ?? '']['0'] ?? 'not said',
            'waterNote' => $a['waterNote'] ?? null,
            'pastProblems' => array_values(array_filter(array_map(fn ($k) => AutoBook::PROBLEMS[$k][0] ?? null, (array) ($a['problems'] ?? [])))),
            'pastPestsAndDiseases' => array_values((array) ($a['cropProblemNames'] ?? [])),
            'notes' => $a['notes'] ?? null,
            'workers' => $people->map(fn ($p) => ['name' => $p['name'], 'offWeekdays' => array_map(fn ($d) => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][$d] ?? $d, $p['off']),
                'halfDays' => WorkDays::words($p['parts'] ?? []) ?: null, 'skills' => $p['skills']])->values()->all(),
            'enso' => EnsoOutlook::forPrompt(),
        ];
    }

    /** The months a lot stands in the field: its field day's month through its harvest (its days), at most twelve. */
    public static function standMonths(?string $fieldDate, int $days): array
    {
        $m0 = $fieldDate ? (int) substr($fieldDate, 5, 2) : (int) now('Asia/Manila')->month;
        $span = min(12, (int) ceil(max(30, $days) / 30) + 1);

        return array_map(fn ($i) => ($m0 - 1 + $i) % 12 + 1, range(0, $span - 1));
    }

    /** The town's twenty years of storms, floods, dry spells and heat, this lot's months marked (the hazard book). */
    public static function placeHistory(AsScheduleLot $lot, ?string $fieldDate, int $days): string
    {
        return (string) \App\Support\HazardBook::words($lot->locProvince, $lot->locTown, self::standMonths($fieldDate, $days), 1500);
    }

    private function soilGrids(float $lat, float $lng): array
    {
        $raw = FieldContext::soil($lat, $lng);
        $out = [];
        foreach (['phh2o' => 'pH (water)', 'clay' => 'clay', 'sand' => 'sand', 'soc' => 'organic carbon', 'cec' => 'CEC', 'nitrogen' => 'nitrogen'] as $k => $label) {
            if (isset($raw[$k]['top30'])) {
                $out[$label] = $raw[$k]['top30'] . ' ' . ($raw[$k]['unit'] ?? '');
            }
        }

        return $out ? $out + ['note' => 'ISRIC SoilGrids, 250 m model of the top 30 cm, not a test of this field'] : [];
    }

    private function forecastWords(float $lat, float $lng, int $count = 16): string
    {
        // From today on: a reading kept since last night still holds yesterday (2026-10-19).
        $today = now('Asia/Manila')->toDateString();
        $days = array_filter($this->planner->skyAt($lat, $lng), fn ($d) => $d >= $today, ARRAY_FILTER_USE_KEY);
        $out = [];
        foreach (array_slice($days, 0, $count, true) as $d => $x) {
            $out[] = $d . ': rain chance ' . $x['pop'] . '%' . ($x['mm'] !== null ? ', ' . $x['mm'] . ' mm' : '') . ($x['wind'] !== null ? ', wind up to ' . $x['wind'] . ' km/h' : '');
        }

        return implode('; ', $out);
    }

    private function fertWords(array $fert): array
    {
        return [
            'products' => array_map(fn ($l) => ($l['name'] ?? '?') . ($l['grade'] ? ' (' . $l['grade'] . ')' : '') . ': ' . ($l['amount'] ?? '?') . ' ' . ($l['unit'] ?? '') . ' = ' . ($l['kg'] ?? '?') . ' kg for the lot', (array) ($fert['lines'] ?? [])),
            'npkPerHa' => $fert['npkPerHa'] ?? null,
            'needs' => $fert['needs'] ?? null,
            'microsShort' => array_keys(array_filter((array) ($fert['micros'] ?? []), fn ($m) => ($m['state'] ?? '') === 'short')),
            'barrelReachPercentOfGoal' => $fert['reach'] ?? null,
            'goalTPerHa' => $fert['goal']['tPerHa'] ?? null,
        ];
    }

    /* ------------------------------------------------------- the first reading */

    public function readingPrompts(AsAutoSeason $auto): array
    {
        $f = $this->facts($auto);
        $f['climate'] = null;
        // The field's twenty years for the months the crops stand (the climate shelf), not only this month and next (2026-10-19).
        $months = [];
        foreach ($f['lots'] as $l) {
            $months = array_merge($months, self::standMonths($l['fieldDate'] ?? null, (int) ($l['maturityDays'] ?? 120)));
        }
        if ($first = collect($f['lots'])->first(fn ($l) => ! empty($l['pin']) || ! empty($l['place']))) {
            $f['climate'] = \App\Support\ClimateStore::wordsFor((string) ($first['place'] ?? ''), $first['pin'][0] ?? null, $first['pin'][1] ?? null, array_values(array_unique($months)), true) ?: null;
        }
        $book = collect($f['lots'])->pluck('cropKey')->filter()->unique()->mapWithKeys(fn ($k) => [CropCatalog::label($k) => AutoBook::forPrompt($k)])->all();
        // Each crop's stages on its count (the Growth Stages table), so the critical moments are the app's own, not searched.
        $stages = collect($f['lots'])->filter(fn ($l) => ! empty($l['cropKey']))->unique('cropKey')->mapWithKeys(fn ($l) => [CropCatalog::label($l['cropKey']) => collect(CropStages::stagesFor($l['cropKey'], $l['counter'] ?? null, $l['maturityDays'] ?? null))
            ->map(fn ($st) => (is_array($st) ? ($st[1] ?? $st['label'] ?? 'stage') : 'stage') . ' from day ' . (int) (is_array($st) ? ($st[0] ?? $st['from'] ?? 0) : 0))->implode('; ')])->filter()->all();
        $json = json_encode($f, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Learn once (2026-10-18): a variety the records or an earlier lookup know is in THE FARM (varietyKnown) and is not looked up again.
        $unknown = self::unknownVarieties($f['lots']);
        $blocks = implode('', array_map(fn ($crop, $names) => VarietyLearned::askBlock($names, $crop), array_keys($unknown), $unknown));
        $soilKnown = collect($f['lots'])->every(fn ($l) => ! empty($l['areaSoil']));
        /* Only what the app does not keep is searched (2026-10-19): a named variety neither the records nor an earlier lookup
         * know, and the soil of a lot the soil atlas has no samples for. The season's outlook is read from the weather record,
         * each town's twenty years and the ENSO outlook; the crop's critical moments from its stages and the crop book; a lot
         * with no variety picks from the records that fit it. Nothing left: no search at all. The brief carries the case, not
         * the farm (no forecast, no workers), so the same case reads the kept notes (ResearchShelf 'auto'). */
        $asks = [];
        if ($unknown) {
            $asks[] = 'THE VARIETIES: ' . collect($unknown)->map(fn ($names, $crop) => implode(', ', $names) . ' (' . CropCatalog::label((string) $crop) . ')')->implode('; ')
                . '. For each find: who bred or released it (a seed company, or a public breeder), its maturity in days, yield potential and average yield in t/ha, the season it was bred for, its tolerances and weaknesses (lodging, blast, tungro, BLB, drought, submergence, salinity, heat), and anything a grower is warned about.';
        }
        if (! $soilKnown) {
            $asks[] = 'THE SOIL of ' . collect($f['lots'])->filter(fn ($l) => empty($l['areaSoil']) && ! empty($l['place']))->pluck('place')->unique()->implode('; ')
                . ': soil surveys or studies of the area (pH, sodic, saline, acid sulfate, texture, known deficiencies).';
        }
        $research = $asks ? "You are researching before a whole season is planned for a Filipino farm. Use the web search tool. Write research NOTES (prose, not JSON) with the pages you read, at most 700 words.\n\n"
            . implode("\n", array_map(fn ($a, $i) => ($i + 1) . '. ' . $a, $asks, array_keys($asks)))
            . "\n\nThe app keeps the farm's weather record, each town's storms and floods of twenty years and the ENSO outlook: do not look those up." . $blocks
            : '';

        $doc = "You are Anee, a smart farm technician for Filipino farmers. You are reading a farm before planning its whole season. Return ONE JSON object, nothing else. Plain words a farmer understands. Never invent a number the facts or the research do not support; where you are unsure, say so in dataGaps.\n\n"
            . "THE FARM:\n" . $json . "\n\nTHE CROP BOOK (land preparation, seedbed, preventive sprays, safety):\n" . json_encode($book, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"
            . ($stages ? "EACH CROP'S STAGES (day counts from the field day, the app's own table):\n" . json_encode($stages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n" : '')
            . "RULES: Read the soil from the farmer's signs and pH answer FIRST, then the lot's areaSoil (soil samples taken in that area, typical for the area and not a test of this lot), then the SoilGrids model; if they disagree, trust the farmer's signs, then the samples, and say so. Call the samples \"typical for your area\" and never say where they came from. THE GOAL is the app's own count in each lot's goal (tPerHa and howCounted: the variety's best, the farmer's aim, the stand they plant, the water, salt and sodium): use that tPerHa as the lot's goal everywhere, never another number, and in goal.why say in two plain sentences what it rests on and what would raise it. Fertilizer focus is about WHICH nutrients and WHEN matter most on this soil, not amounts; the farmer chooses products next.\n\n"
            . "THE SEASON: no outlook was searched. Read season.outlook, rain, typhoon and heat from THE FARM's climate (the field's weather record for these months, with what El Nino and La Nina did there), each lot's placeHistory and the ENSO outlook. The fertilizer's critical moments follow EACH CROP'S STAGES and the crop book. A lot with varietiesOnRecord and no variety named: name the one that fits it best in its variety block, from that list. A lot's areaSoil and its soil signs: say how that soil locks up or frees nutrients (sodic and alkaline soils lock phosphorus, zinc and iron; ammonium sulfate and elemental sulfur acidify around the roots; gypsum's calcium pushes sodium out).\n\n"
            . "HISTORY: each lot's placeHistory is its town's twenty years of storms, floods, dry spells and heat, kept by the app. Count season.typhoon, rain and heat and the lots' risks from it (how many of the years a storm or flood hit the months this crop stands in the field, the worst one by name and year), and let it move the risks' prevent advice. Never say where it comes from.\n\n"
            . 'Return exactly: {"headline":"one line","season":{"outlook":"","enso":"","rain":"","typhoon":"","heat":"","watch":["",""]},'
            . '"lots":[{"lotId":0,"variety":{"found":true,"name":"","by":"","maturityDays":0,"yieldPotentialTPerHa":0,"averageTPerHa":0,"season":"","traits":[""],"cautions":[""],"source":""},'
            . '"soil":{"reading":"two or three sentences","texture":"clay|loam|sandy|unsure","ph":"acidic|neutral|alkaline|unsure","conditions":["sodic|saline|acid_sulfate|low_om|waterlogged"],"lockouts":[""],"unlocks":[""]},'
            . '"goal":{"tPerHa":0,"why":""},"fertilizer":{"focus":"","critical":[{"stage":"","why":""}],"goodSources":[""],"avoid":[""],"micros":[""]},'
            . '"risks":[{"risk":"","why":"","prevent":""}],"landPrep":"advice for preparing this lot"}],'
            . '"water":"what their water means for the plan","workforce":"what their workers mean for the plan","confidence":"low|medium|high","dataGaps":[""]}';

        return [$research, $doc];
    }

    /** The record of a lot's variety: the curated one, or one Anee looked up before (learn once). Null when it must be looked up. */
    public static function varietyOf(?string $crop, ?string $variety): ?array
    {
        try {
            return VarietyBook::find((string) $crop, (string) $variety);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** The named varieties no book knows, by crop: [cropKey => [names]], from facts() lots (or any rows with cropKey, variety and varietyKnown). */
    public static function unknownVarieties(array $lots): array
    {
        $out = [];
        foreach ($lots as $l) {
            $v = trim((string) ($l['variety'] ?? ''));
            if ($v !== '' && empty($l['varietyKnown']) && ! empty($l['cropKey']) && ! in_array($v, $out[$l['cropKey']] ?? [], true)) {
                $out[$l['cropKey']][] = $v;
            }
        }

        return $out;
    }

    /** The same from the season's lots, light (no weather, no soil): for keeping what the reading found. */
    public static function unknownVarietiesOf(AsAutoSeason $auto): array
    {
        $lots = [];
        foreach (AsAutoLot::where('autoId', $auto->id)->orderBy('id')->get() as $al) {
            $lot = AsScheduleLot::find($al->lotId);
            if ($lot && (int) $lot->deleteStatus === 1 && $lot->variety) {
                $lots[] = ['cropKey' => $lot->crop, 'variety' => $lot->variety, 'varietyKnown' => self::varietyOf($lot->crop, $lot->variety) ? 1 : null];
            }
        }

        return self::unknownVarieties($lots);
    }

    public static function parseObject(string $text, string $must): ?array
    {
        $t = preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? $text;
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a === false || $b === false) {
            return null;
        }
        $d = json_decode(substr($t, $a, $b - $a + 1), true);

        return is_array($d) && array_key_exists($must, $d) ? $d : null;
    }

    /* ------------------------------------------------------------ the calendar */

    public function calendarPrompt(AsAutoSeason $auto, int $lotId): string
    {
        $f = $this->facts($auto);
        $lot = collect($f['lots'])->firstWhere('lotId', $lotId) ?? [];
        // The reading says a town's history once ("Same town as Lot 1"); the calendar of ONE lot needs it whole, for its own months.
        if (str_starts_with((string) ($lot['placeHistory'] ?? ''), 'Same town as') && ($sl = AsScheduleLot::find($lotId))) {
            $lot['placeHistory'] = self::placeHistory($sl, $lot['fieldDate'] ?? null, (int) ($lot['maturityDays'] ?? 120)) ?: $lot['placeHistory'];
        }
        $reading = collect((array) ($auto->analysis['lots'] ?? []))->firstWhere('lotId', $lotId) ?? [];
        $others = collect($f['lots'])->where('lotId', '!=', $lotId)->map(fn ($l) => ['name' => $l['name'], 'crop' => $l['crop'], 'fieldDate' => $l['fieldDate']])->values();
        $crop = $lot['cropKey'] ?? null;
        $stages = $crop ? CropStages::stagesFor($crop, $lot['counter'] ?? null, $lot['maturityDays'] ?? null) : [];
        $problems = $this->cropProblemsForPrompt($crop);
        $tree = ($lot['method'] ?? null) === 'tree';
        $market = $tree ? '' : self::marketFor($lot);
        $anchors = $tree
            ? "This is a standing orchard. Every task's anchor is \"start\" (today, day 0) and day counts from today; plan the next 12 months."
            : ('Anchors: "field" = the day the crop goes into the field (' . ($lot['counter'] ?? 'DAP') . ' 0, ' . ($lot['fieldDate'] ?? '?') . '), with negative days for land preparation before it'
                . (($lot['method'] ?? '') === 'transplanted' ? '; "sow" = the seedbed sowing day (' . ($lot['sowDate'] ?? '?') . '), for seedbed tasks' : '')
                . '. Today is ' . now('Asia/Manila')->toDateString() . ': nothing may be planned before today, so land preparation that should have started already is squeezed into the days left, said plainly.');

        return "You are Anee, a smart farm technician, writing the whole season's calendar for ONE lot of a Filipino farm. The farmer follows it day by day; you will move tasks yourself when weather or workers change, so give each task its ideal day and how far it may move. Return ONE JSON object, nothing else.\n\n"
            . "THE LOT:\n" . json_encode($lot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"
            . "YOUR FIRST READING OF IT:\n" . json_encode($reading, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"
            // The season as the reading read it from the weather record, the town's history and ENSO (2026-10-19): the long view the 16 day forecast cannot give.
            . (! empty($auto->analysis['season']) ? "THE SEASON, AS YOUR READING SAID IT:\n" . json_encode($auto->analysis['season'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n" : '')
            . "THE REST OF THE FARM (other lots share the same workers): " . json_encode($others, JSON_UNESCAPED_UNICODE) . "\n"
            . 'SOIL: ' . json_encode($f['soil'], JSON_UNESCAPED_UNICODE) . ($f['fieldInDetail'] ? "\nFIELD IN DETAIL (the farmer's own answers): " . $f['fieldInDetail'] : '') . "\nWATER: " . $f['water'] . ($f['waterNote'] ? ' (' . $f['waterNote'] . ')' : '') . "\nPAST PROBLEMS: " . json_encode(array_merge($f['pastProblems'], $f['pastPestsAndDiseases']), JSON_UNESCAPED_UNICODE)
            . "\nWORKERS: " . json_encode($f['workers'], JSON_UNESCAPED_UNICODE) . "\nENSO: " . $f['enso'] . "\n\n"
            . "GROWTH STAGES (day counts from the field day):\n" . json_encode($stages, JSON_UNESCAPED_UNICODE) . "\n\n"
            . "THE CROP BOOK:\n" . AutoBook::forPrompt($crop) . "\n\n"
            . "THIS CROP'S WORST PESTS AND DISEASES IN THE PHILIPPINES, with active ingredients:\n" . $problems . "\n\n"
            . ($market !== '' ? "THE MARKET (the app's own farmgate price records at this lot's place; never say where they come from):\n" . $market . "\n" : '')
            . $anchors . "\n\n"
            . "RULES:\n"
            . "1. Cover the whole season, in order: land preparation (each pass its own task), seedbed, sowing or transplanting or planting, water management (when to flood, keep moist, drain, last irrigation before harvest; fit it to their water source), every fertilizer application, weed control, scouting walks (about weekly in the critical weeks), prevention, harvest and right after harvest. 30 to 50 tasks; keep each summary to one sentence, each why to two, and at most five steps.\n"
            . "2. FERTILIZER: use ONLY the farmer's own plan (fertilizerPlan.products) and split those exact products and totals across the critical moments (basal, active tillering or early vegetative, panicle or flower initiation, and so on, for this crop). Put the amount in bags or kg FOR THIS LOT'S AREA on each task, so the totals match the plan. If the plan is short of something the reading flagged (micros, sulfur, zinc, calcium), add a foliar or a correction task and say it is a suggestion to buy. Time fertilizer for moist soil and not before heavy rain.\n"
            . "3. PREVENTION FIRST: preventive sprays only at the stages the crop book and the risks call for, and threshold based ones say \"only if you find\" in the summary. Name products by ACTIVE INGREDIENT with its IRAC, FRAC or HRAC group, a label rate range as NUMBERS (per 16 L knapsack and per ha), and never a brand. Every product, soil amendment and fertilizer gets a number: a rate per hectare and the amount for this lot (never \"as per label\" or \"as recommended\"). Rotate modes of action.\n"
            . "4. SAFETY on every spray: the protective clothing, the re-entry time, the days before harvest (pre harvest interval) to respect, not near water or bees where it applies, and tank mix cautions (never propanil within 14 days of carbamates or organophosphates on rice; copper and sulfur not with oils; nothing alkaline with most insecticides). Put the farm's whole list in safetyChecks and the mixes to avoid in compatibility.\n"
            . "5. Each task: a short title a farmer understands, a one line summary of what to do, and why (the agronomy, in plain words). critical = true only where a few days late costs yield (transplanting, basal and panicle fertilizer, the first weed control, harvest at maturity).\n"
            . "6. weather: \"spray\" for any spraying, \"fert\" for granular fertilizer, \"dry\" for land preparation, sowing in the field and harvest, \"any\" otherwise. crew: workers needed (1 to 6). early/late: days the task may move before or after its day. time: \"morning\" or \"afternoon\" when the work is best done then, \"any\" otherwise; a worker with halfDays comes only that half of that weekday.\n"
            . "7. No dashes in the words you write. Plain English. Use the farmer's crop and lot names.\n"
            . ($market !== '' ? "8. THE SALE: among the after harvest tasks, one that plans the sale from THE MARKET: the months this crop usually sells best here, and whether drying and storing it pays more than selling at harvest (the sell or keep line). A guide, never a promised price.\n" : '')
            . "\n"
            . 'Return exactly: {"strategy":"two or three sentences: how this lot will be run and what matters most","harvestDay":0,'
            . '"tasks":[{"kind":"' . implode('|', array_keys(AutoBook::KINDS)) . '","title":"","summary":"","why":"","anchor":"field|sow|start","day":0,"early":0,"late":2,"critical":false,"weather":"dry|spray|fert|any","crew":1,"time":"morning|afternoon|any",'
            . '"steps":[""],"products":[{"name":"what it is, by active ingredient or the farmer\'s product","group":"IRAC or FRAC or HRAC group, or empty","rate":"label rate","amount":"for this lot"}],"safety":[""],"bring":["what to have ready"]}],'
            . '"safetyChecks":[""],"compatibility":[""]}';
    }

    /**
     * The market for one lot's calendar (2026-10-27): the crop's farmgate price at the lot's place, the months of its
     * year, the forecast at the harvest month, and whether keeping the harvest pays (MarketBook). '' without prices.
     */
    private static function marketFor(array $lot): string
    {
        $crop = $lot['cropKey'] ?? null;
        $days = (int) ($lot['maturityDays'] ?? 0);
        if (! $crop || empty($lot['fieldDate']) || $days <= 0) {
            return '';
        }
        $h = \Illuminate\Support\Carbon::parse((string) $lot['fieldDate'])->addDays($days);
        $idx = $h->year * 12 + $h->month - 1;
        $where = (string) ($lot['place'] ?? '');
        $line = \App\Support\MarketBook::forPrompt($crop, $where, [$idx], false);

        return $line === '' ? '' : $line . \App\Support\MarketBook::sellLine($crop, $where, $idx);
    }

    private function cropProblemsForPrompt(?string $crop): string
    {
        if (! $crop) {
            return 'none on file';
        }
        $rows = [];
        foreach (AutoBook::cropProblems([$crop], 12) as $p) {
            [$section, $slug] = explode(':', $p['key'], 2);
            $e = FieldCatalogue::problem($section, $slug) ?? [];
            $ai = collect((array) ($e['ai'] ?? []))->map(fn ($x) => is_array($x) ? trim(($x[0] ?? '') . ' (' . ($x[1] ?? '') . ')') : (string) $x)->implode(', ');
            $rows[] = $p['name'] . ' (' . $p['kind'] . ')' . ($ai ? ': ' . $ai : '') . (! empty($e['hint']) ? '. Sign: ' . $e['hint'] : '');
        }

        return $rows ? implode("\n", $rows) : 'none on file';
    }

    /**
     * Turn the model's tasks into rows: kinds and anchors checked, days
     * bounded, the farmer's crop words kept. Unknown kinds become "other".
     *
     * @return array<int, array> rows for as_auto_tasks
     */
    public static function taskRows(array $tasks, int $autoId, ?int $lotId, string $origin = 'plan', int $orderFrom = 0): array
    {
        $rows = [];
        foreach (array_values($tasks) as $i => $t) {
            if (! is_array($t) || trim((string) ($t['title'] ?? '')) === '') {
                continue;
            }
            $kind = isset(AutoBook::KINDS[$t['kind'] ?? '']) ? $t['kind'] : 'other';
            $anchor = in_array($t['anchor'] ?? '', ['field', 'sow', 'start'], true) ? $t['anchor'] : 'field';
            $products = array_values(array_filter(array_map(fn ($p) => is_array($p) ? array_filter([
                'name' => mb_substr(trim((string) ($p['name'] ?? '')), 0, 160), 'group' => mb_substr(trim((string) ($p['group'] ?? '')), 0, 40),
                'rate' => mb_substr(trim((string) ($p['rate'] ?? '')), 0, 160), 'amount' => mb_substr(trim((string) ($p['amount'] ?? '')), 0, 120),
            ]) : null, (array) ($t['products'] ?? []))));
            $rows[] = [
                'autoId' => $autoId, 'lotId' => $lotId, 'kind' => $kind,
                'title' => mb_substr(trim((string) $t['title']), 0, 190),
                'summary' => mb_substr(trim((string) ($t['summary'] ?? '')), 0, 2000) ?: null,
                'why' => mb_substr(trim((string) ($t['why'] ?? '')), 0, 3000) ?: null,
                'detail' => [
                    'steps' => array_values(array_slice(array_filter(array_map(fn ($x) => mb_substr(trim((string) $x), 0, 400), (array) ($t['steps'] ?? []))), 0, 10)),
                    'products' => array_slice($products, 0, 6),
                    'safety' => array_values(array_slice(array_filter(array_map(fn ($x) => mb_substr(trim((string) $x), 0, 400), (array) ($t['safety'] ?? []))), 0, 8)),
                    'bring' => array_values(array_slice(array_filter(array_map(fn ($x) => mb_substr(trim((string) $x), 0, 200), (array) ($t['bring'] ?? []))), 0, 8)),
                ],
                'anchor' => $anchor,
                'offsetDays' => max(-120, min(800, (int) ($t['day'] ?? 0))),
                'windowBefore' => max(0, min(14, (int) ($t['early'] ?? 0))),
                'windowAfter' => max(0, min(21, (int) ($t['late'] ?? 2))),
                'timeOfDay' => in_array($t['time'] ?? '', ['morning', 'afternoon'], true) ? $t['time'] : null,
                'critical' => ! empty($t['critical']) ? 1 : 0,
                'weather' => in_array($t['weather'] ?? '', ['dry', 'spray', 'fert', 'wet', 'any'], true) ? $t['weather'] : (AutoBook::KINDS[$kind][2] ?? 'any'),
                'crew' => max(1, min(10, (int) ($t['crew'] ?? 1))),
                'status' => 'planned', 'origin' => $origin, 'sortOrder' => $orderFrom + $i, 'deleteStatus' => 1,
            ];
        }

        return $rows;
    }

    /** Whether a question is about the weather, a spray, fertilizer or timing (it then gets the forecast). */
    public static function skyQuestion(string $q): bool
    {
        return \App\Support\SkyLines::question($q);
    }

    /* ----------------------------------------------------- from the field */

    public function observationPrompt(AsAutoSeason $auto, ?int $lotId, string $words, int $pictures, bool $video, bool $voice = false): string
    {
        $f = $this->facts($auto, false);
        $lot = $lotId ? collect($f['lots'])->firstWhere('lotId', $lotId) : null;
        $start = AutoPlanner::anchorDate($auto, null, 'start');
        $soon = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')->whereNotNull('plannedDate')
            ->where('plannedDate', '<=', now('Asia/Manila')->addDays(14)->toDateString())->when($lotId, fn ($q) => $q->where('lotId', $lotId))
            ->orderBy('plannedDate')->limit(20)->get(['id', 'title', 'kind', 'plannedDate'])->map(fn ($t) => $t->plannedDate->toDateString() . ' ' . $t->title)->implode('; ');
        $day = $lot && ! empty($lot['fieldDate']) ? (int) Carbon::parse($lot['fieldDate'], 'Asia/Manila')->diffInDays(now('Asia/Manila')->startOfDay(), false) : null;
        // What is seen today is read without the town's twenty years (2026-10-19: "Same town as" pointed at nothing here),
        // and with the week ahead, so a spray or a new task is timed by the rain.
        $next = '';
        if ($lot) {
            unset($lot['placeHistory']);
            $sl = AsScheduleLot::find((int) $lot['lotId']);
            $at = $sl ? $this->planner->placeOf($sl) : null;
            $next = $at ? $this->forecastWords($at[0], $at[1], 7) : '';
        }

        return "You are Anee, a smart farm technician, on an Anee Auto season you plan for this farmer. They sent an observation from the field"
            . ($pictures ? ' with ' . $pictures . ' picture' . ($pictures > 1 ? 's' : '') . ($video ? ' (frames from a video)' : '') : '')
            . ($voice ? ' and a voice note (attached as audio; it may be in Tagalog, a Visayan language or English: listen to it and treat what they say as their words)' : '') . ". Read it, say what it likely is and what to do, and add tasks to the calendar if something must be done. Return ONE JSON object, nothing else.\n\n"
            . 'THE LOT: ' . json_encode($lot, JSON_UNESCAPED_UNICODE) . ($day !== null ? "\nDay in the field today: " . $day . ' (' . ($lot['counter'] ?? '') . ')' : '') . "\n"
            . 'SOIL: ' . json_encode($f['soil']['readFromSigns'], JSON_UNESCAPED_UNICODE) . "\nWATER: " . $f['water'] . "\n"
            . "THE NEXT TWO WEEKS ON THE CALENDAR: " . ($soon ?: 'nothing') . "\n"
            . ($next !== '' ? 'THE NEXT 7 DAYS AT THE LOT (forecast): ' . $next . "\n" : '')
            . "THE FARMER SAYS: " . ($words !== '' ? $words : '(nothing, only pictures)') . "\n\n"
            . "RULES: Be honest about how sure you are from a phone picture. Prevention and thresholds first; a spray only when the signs call for it, by ACTIVE INGREDIENT with its IRAC, FRAC or HRAC group and a label rate range, never a brand, with its safety. A new task's inDays is days from today (0 = today). No dashes in your words.\n\n"
            . 'Return exactly: {"what":"likely cause, or \'nothing wrong\'","confidence":"low|medium|high","severity":"none|low|medium|high","reading":"what you see and why you think so",'
            . '"actions":["what to do now"],"watch":"what to look for in the next days",'
            . '"newTasks":[{"kind":"' . implode('|', array_keys(AutoBook::KINDS)) . '","title":"","summary":"","why":"","inDays":0,"late":2,"critical":false,"weather":"spray|fert|dry|any","crew":1,"steps":[""],"products":[{"name":"","group":"","rate":"","amount":""}],"safety":[""]}],'
            . '"reply":"what you say to the farmer in the chat: warm, short, plain words; you may use one or two of :anee-thinking: :anee-concerned: :anee-thumbsup: :anee-idea: :anee-worried: :anee-happy:"}'
            . "\nToday: " . now('Asia/Manila')->format('l, F j, Y') . '. The plan started ' . $start->toDateString() . '.';
    }

    public function askPrompt(AsAutoSeason $auto, string $question, array $recent): string
    {
        $f = $this->facts($auto, false);
        $today = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->whereIn('status', ['planned'])->whereNotNull('plannedDate')
            ->where('plannedDate', '<=', now('Asia/Manila')->addDays(7)->toDateString())->orderBy('plannedDate')->limit(25)->get()
            ->map(fn ($t) => $t->plannedDate->toDateString() . ' · ' . $t->title . ($t->summary ? ': ' . $t->summary : ''))->implode("\n");

        // A question about the weather, a spray, fertilizer or timing gets the forecast data the chat rules need (2026-10-19),
        // the week ahead at each of the farm's places (at most three) and the season the reading said; other questions go without.
        $sky = '';
        if (self::skyQuestion($question)) {
            $seen = [];
            foreach ($f['lots'] as $l) {
                $sl = count($seen) < 3 ? AsScheduleLot::find((int) $l['lotId']) : null;
                $at = $sl ? $this->planner->placeOf($sl) : null;
                $key = $at ? sprintf('%.2f,%.2f', $at[0], $at[1]) : null;
                if ($key && ! isset($seen[$key]) && ($w = $this->forecastWords($at[0], $at[1], 7)) !== '') {
                    $seen[$key] = true;
                    $sky .= 'THE NEXT 7 DAYS AT ' . mb_strtoupper((string) ($l['place'] ?: $l['name'])) . ' (forecast data): ' . $w . "\n";
                }
            }
            if (! empty($auto->analysis['season'])) {
                $sky .= 'THE SEASON, AS YOUR READING SAID IT: ' . json_encode($auto->analysis['season'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            }
        }

        return "You are Anee, a smart farm technician running this farmer's Anee Auto season: you plan their days and move tasks when weather or workers change. Answer their question in the chat: warm, short, plain words, practical. Use the season below. Never name a pesticide brand; active ingredients only. No dashes. You may use one Anee face like :anee-happy: or :anee-thinking:.\n\n"
            . 'THE SEASON: ' . json_encode(['lots' => array_map(fn ($l) => array_intersect_key($l, array_flip(['name', 'crop', 'variety', 'method', 'fieldDate', 'areaHa', 'place'])), $f['lots']), 'soil' => $f['soil']['readFromSigns'], 'water' => $f['water']], JSON_UNESCAPED_UNICODE)
            . "\nTHIS WEEK ON THE CALENDAR:\n" . ($today ?: 'nothing planned') . "\n" . ($sky !== '' ? $sky : '') . "\nTHE LAST FEW LINES OF THE CHAT:\n" . implode("\n", $recent) . "\n\nTODAY: " . now('Asia/Manila')->format('l, F j, Y') . "\n\nTHE FARMER ASKS: " . $question;
    }
}
