<?php

namespace App\Http\Controllers\Manager;

use App\Models\AiSetting;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPricing;
use App\Support\AiUsage;
use App\Support\CompatBook;
use App\Support\FieldCatalogue;
use App\Support\FieldPack;
use App\Support\ProductLibrary;
use App\Support\RangeEffect;
use App\Support\RangeEffectBook;
use App\Support\Tier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The Range Effect calendar on the Activities board (2026-10-29, the owner). The engine is App\Support\RangeEffect,
 * its book App\Support\RangeEffectBook; this answers the full screen sheet (sm.partials.range-effect):
 *
 *   options   the season's lots (crop, count, the pests and diseases its crop can carry), what can be checked, the
 *             saved readings, and what a look up would cost
 *   run       one lot, one check, and the products the farmer is thinking of (tries), read into the calendar
 *   products  the product book searched (brand, company or ingredient) for a try
 *   save      a reading kept as it stands (as_range_effects), to open again later; saved, delete
 *   lookup    Anee looks up a product (or an ingredient) the book lacks, paid (AiPrices 'effect'); what she finds is
 *             kept in the product book and the effect book, so it is asked once (job polls it)
 *
 * Free to read on Libre + Anee and every plan above (the field helpers' door, FieldPack); a look up also needs Anee.
 */
class RangeEffectController extends BaseScheduleController
{
    public const KIND = 'effect';

    public const SAVED = 'as_range_effects';

    public const MAX_TRIES = 8;

    private function gate()
    {
        if (FieldPack::lockedHere()) {
            abort(response()->json(['success' => false, 'packLock' => true,
                'message' => 'The Range Effect calendar comes with Libre + Anee and every plan above it. Upgrade to see how long every product you apply keeps working.'], 403));
        }
    }

    public function options(Request $request)
    {
        $this->gate();
        $schedule = $this->schedule($request->query('scheduleId'));
        $schedule->loadMissing(['lots', 'activities.lots']);
        $nutrients = [];
        foreach (RangeEffect::NUTRIENTS as $k => [$el, $name]) {
            $nutrients[] = ['key' => 'n:' . $k, 'short' => $k, 'name' => $name, 'micro' => in_array($k, RangeEffect::MICROS, true)];
        }
        $threats = [];
        foreach (RangeEffect::THREATS as $k => [$name, $icon]) {
            $threats[] = ['key' => 't:' . $k, 'name' => $name, 'icon' => $icon];
        }

        return $this->jsonOk('ok', ['data' => [
            'lots' => RangeEffect::lots($schedule), 'nutrients' => $nutrients, 'threats' => $threats,
            'saved' => $this->savedList((int) $schedule->id), 'lookup' => $this->lookupInfo(),
        ]]);
    }

    public function run(Request $request)
    {
        $this->gate();
        $schedule = $this->schedule($request->input('scheduleId'));
        try {
            $res = RangeEffect::run($schedule, (int) $request->input('lotId'), (string) $request->input('check', 'n:N'), $this->tries($request), $this->also($request), $this->logic($request));
        } catch (\InvalidArgumentException $e) {
            return $this->jsonFail($e->getMessage(), 422);
        }

        return $this->jsonOk('ok', ['data' => $res]);
    }

    /** The parts of the deeper reading the farmer switched on (RangeEffectLogic::OPTS). */
    private function logic(Request $request): array
    {
        $in = (array) $request->input('logic', []);

        return array_intersect_key(array_map(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN), $in), array_flip(\App\Support\RangeEffectLogic::OPTS));
    }

    /** The other checks read beside the main one. */
    private function also(Request $request): array
    {
        return array_values(array_filter(array_map(fn ($k) => mb_substr((string) $k, 0, 80), (array) $request->input('also', [])), fn ($k) => $k !== ''));
    }

    /** The tries as the engine reads them: a book product (by id) or a bare ingredient, a date, an amount. */
    private function tries(Request $request): array
    {
        $out = [];
        foreach (array_slice((array) $request->input('tries', []), 0, self::MAX_TRIES) as $t) {
            if (! is_array($t)) {
                continue;
            }
            $p = null;
            if (! empty($t['productId'])) {
                $p = RangeEffect::productOf((int) $t['productId']);
            } elseif (trim((string) ($t['ai'] ?? '')) !== '') {
                $d = CompatBook::ingredient((string) $t['ai']);
                $name = $d['name'] ?? trim((string) $t['ai']);
                $p = ['b' => $name, 'c' => '', 't' => (string) ($d['kind'] ?? 'other'), 'ai' => [['n' => $name]], 'n' => []];
            }
            if (! $p) {
                continue;
            }
            $out[] = ['product' => $p, 'date' => (string) ($t['date'] ?? ''), 'qty' => max(0, (float) ($t['qty'] ?? 0)), 'unit' => mb_substr((string) ($t['unit'] ?? ''), 0, 20),
                'method' => in_array($t['method'] ?? null, ['granular', 'foliar'], true) ? $t['method'] : null];
        }

        return $out;
    }

    public function products(Request $request)
    {
        $this->gate();
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return $this->jsonOk('ok', ['data' => ['items' => []]]);
        }
        $crop = FieldCatalogue::cropKey((string) $request->query('crop', ''));
        $items = array_map(fn ($p) => ProductLibrary::card($p, $crop), ProductLibrary::search($q, null, $crop, 20));
        // An ingredient typed on its own can be tried as it is.
        $ing = CompatBook::ingredient($q);

        return $this->jsonOk('ok', ['data' => ['items' => $items, 'ingredient' => $ing ? ['name' => $ing['name'], 'kind' => $ing['kind'] ?? '', 'known' => (bool) RangeEffectBook::ingredient($ing['name'])] : null]]);
    }

    /* =============================================================== saved readings */

    private function savedReady(): bool
    {
        try {
            return Schema::hasTable(self::SAVED);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function savedList(int $scheduleId): array
    {
        if (! $this->savedReady()) {
            return [];
        }

        return DB::table(self::SAVED)->where('croppingScheduleId', $scheduleId)->where('userId', Auth::id())->where('deleteStatus', 1)
            ->orderByDesc('updated_at')->orderByDesc('id')->limit(30)->get(['id', 'title', 'lotId', 'created_at', 'updated_at'])
            ->map(fn ($r) => ['id' => (int) $r->id, 'title' => (string) $r->title, 'lotId' => (int) $r->lotId,
                'at' => Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A'),
                'edited' => $r->updated_at && $r->updated_at !== $r->created_at ? Carbon::parse($r->updated_at)->timezone('Asia/Manila')->format('M j, Y g:i A') : null])->all();
    }

    public function save(Request $request)
    {
        $this->gate();
        if (! $this->savedReady()) {
            return $this->jsonFail('Saving is being set up. Please try again in a few minutes.', 422);
        }
        $schedule = $this->schedule($request->input('scheduleId'));
        try {
            $res = RangeEffect::run($schedule, (int) $request->input('lotId'), (string) $request->input('check', 'n:N'), $this->tries($request), $this->also($request), $this->logic($request));
        } catch (\InvalidArgumentException $e) {
            return $this->jsonFail($e->getMessage(), 422);
        }
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            $title = $res['check']['label'] . ' · ' . $res['lot']['name'];
        }
        $params = ['check' => $res['check']['key'], 'also' => array_map(fn ($sr) => $sr['check']['key'], $res['series']), 'logic' => $res['logic']['opts'] ?? [], 'tries' => array_values(array_map(fn ($t) => array_intersect_key((array) $t, array_flip(['productId', 'ai', 'date', 'qty', 'unit', 'name', 'method'])),
            array_slice((array) $request->input('tries', []), 0, self::MAX_TRIES)))];
        $row = ['lotId' => (int) $res['lot']['id'], 'title' => mb_substr($title, 0, 190),
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE), 'result' => json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()];
        // A saved reading opened and changed is saved over itself (the owner, 2026-10-30: "if you are editing a saved
        // file, clicking save should update it, not save a new one"); "Save as new" sends no id.
        $id = (int) $request->input('id', 0);
        $have = $id ? DB::table(self::SAVED)->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('userId', Auth::id())->where('deleteStatus', 1)->first() : null;
        if ($have) {
            DB::table(self::SAVED)->where('id', $have->id)->update($row);
            $id = (int) $have->id;
        } else {
            $id = DB::table(self::SAVED)->insertGetId($row + ['userId' => Auth::id(), 'croppingScheduleId' => (int) $schedule->id, 'deleteStatus' => 1, 'created_at' => now()]);
        }

        return $this->jsonOk($have ? 'Updated.' : 'Saved.', ['data' => ['id' => $id, 'title' => $row['title'], 'updated' => (bool) $have, 'saved' => $this->savedList((int) $schedule->id)]]);
    }

    /**
     * A lot with no answers takes an answered lot's, from the soil note ("Use Masin 1's answers"): both the season
     * owner's farm lots, the one taking them still unanswered.
     */
    public function copyAnswers(Request $request)
    {
        $this->gate();
        $schedule = $this->schedule($request->input('scheduleId'));
        $owner = \App\Support\LotSync::ownerOf($schedule);
        $to = \App\Support\FarmLots::one($owner, (int) $request->input('to'));
        $from = \App\Support\FarmLots::one($owner, (int) $request->input('from'));
        if (! $to || ! $from || empty($from['answers'])) {
            return $this->jsonFail('Those lots could not be found.', 404);
        }
        if (! empty($to['answers'])) {
            return $this->jsonFail($to['name'] . ' has answers of its own already. Change them in Global Lots.', 422);
        }
        \App\Support\FarmLots::save($owner, ['answers' => (array) $from['answers']], (int) $to['id']);

        return $this->jsonOk($to['name'] . ' now has ' . $from['name'] . '\'s answers. Change any of them in Global Lots.');
    }

    /** A saved reading's name, changed. */
    public function rename(Request $request, int $id)
    {
        $this->gate();
        $schedule = $this->schedule($request->input('scheduleId'));
        $title = mb_substr(trim((string) $request->input('title', '')), 0, 190);
        if ($title === '') {
            return $this->jsonFail('Give it a name.', 422);
        }
        $n = $this->savedReady() ? DB::table(self::SAVED)->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('userId', Auth::id())->where('deleteStatus', 1)
            ->update(['title' => $title]) : 0;
        if (! $n && ! DB::table(self::SAVED)->where('id', $id)->where('userId', Auth::id())->where('deleteStatus', 1)->exists()) {
            return $this->jsonFail('That saved reading is gone.', 404);
        }

        return $this->jsonOk('Renamed.', ['data' => ['id' => $id, 'title' => $title, 'saved' => $this->savedList((int) $schedule->id)]]);
    }

    public function saved(Request $request, int $id)
    {
        $this->gate();
        $schedule = $this->schedule($request->query('scheduleId'));
        $r = $this->savedReady() ? DB::table(self::SAVED)->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('userId', Auth::id())->where('deleteStatus', 1)->first() : null;
        if (! $r) {
            return $this->jsonFail('That saved reading is gone.', 404);
        }

        return $this->jsonOk('ok', ['data' => ['id' => (int) $r->id, 'title' => (string) $r->title, 'params' => json_decode((string) $r->params, true) ?: [],
            'result' => json_decode((string) $r->result, true) ?: [], 'at' => Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A'),
            'current' => RangeEffect::VERSION]]);
    }

    public function delete(Request $request, int $id)
    {
        $this->gate();
        $schedule = $this->schedule($request->query('scheduleId'));
        if ($this->savedReady()) {
            DB::table(self::SAVED)->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('userId', Auth::id())->update(['deleteStatus' => 0, 'updated_at' => now()]);
        }

        return $this->jsonOk('Removed.', ['data' => ['saved' => $this->savedList((int) $schedule->id)]]);
    }

    /* =============================================================== Anee looks it up */

    private function lookupInfo(): array
    {
        $payer = $this->payer();
        $credits = app(AiCreditService::class);
        $settings = AiSetting::current();
        $price = 0;
        try {
            $price = AiPricing::credits(self::KIND);
        } catch (\Throwable $e) {
            report($e);
        }
        $can = Tier::farmCan('aiAnalyses') && $payer->canUseAi() && $settings && $settings->isUsable();

        return ['price' => $price, 'balance' => round((float) $credits->balance($payer->id), 2), 'unlimited' => $credits->unlimited((int) $payer->id), 'canUse' => $can,
            'whyNot' => $can ? null : (! Tier::farmCan('aiAnalyses') ? 'Anee looking a product up comes with Libre + Anee and every plan above it.'
                : (! $payer->canUseAi() ? Tier::aneeNeeds($payer, 'Looking a product up') : 'Anee is not switched on yet. Please check back soon.'))];
    }

    public function lookup(Request $request)
    {
        $this->gate();
        $schedule = $this->schedule($request->input('scheduleId'));
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'Anee looking up a product comes with {plan}, and with every plan above it. The Range Effect calendar itself stays yours.');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings || ! $settings->isUsable()) {
            return $this->jsonFail($payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'Looking a product up'), 403);
        }
        $name = mb_substr(trim((string) $request->input('name', '')), 0, 120);
        $ai = mb_substr(trim((string) $request->input('ai', '')), 0, 160);
        if ($name === '' && $ai === '') {
            return $this->jsonFail('Type the product\'s name, its active ingredient, or both.', 422);
        }
        // Nothing to pay for when the book already knows it.
        if ($name !== '' && ($p = ProductLibrary::find($name)) && (! empty($p['ai']) || ! empty($p['n']))) {
            return $this->jsonOk('Already in the book.', ['data' => ['known' => true, 'product' => ProductLibrary::card($p)]]);
        }
        if ($name === '' && ($d = CompatBook::ingredient($ai)) && RangeEffectBook::ingredient($d['name'])) {
            return $this->jsonOk('Already in the book.', ['data' => ['known' => true, 'ingredient' => ['name' => $d['name'], 'kind' => $d['kind'] ?? '', 'known' => true]]]);
        }
        $quote = AiPricing::quote(self::KIND);
        $agreed = AiPricing::agreed($request, $quote, (int) $quote['credits']);
        if ($agreed === null) {
            return $this->jsonFail('A look up now costs ' . $quote['credits'] . ' credits. Please try again.', 409, ['quote' => $quote['credits']]);
        }
        $price = (float) $agreed;
        $credits = app(AiCreditService::class);
        if ($credits->balance($payer->id) < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->jsonFail('You need ' . ceil($price) . ' credits for this look up and have ' . number_format((int) floor($credits->balance($payer->id))) . '.', 402, ['outOfCredits' => true]);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(8))->orderByDesc('id')->first();
        if ($standing) {
            return $this->jsonOk('Already working on it.', ['data' => ['pending' => true, 'id' => (int) $standing->id]]);
        }
        $case = ['name' => $name, 'ai' => $ai, 'scheduleId' => (int) $schedule->id];
        $rid = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr('Range Effect look up · ' . ($name !== '' ? $name : $ai), 0, 190),
            'params' => json_encode($case, JSON_UNESCAPED_UNICODE), 'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $rid]])->send();
            fastcgi_finish_request();
            $this->work($rid, (int) $payer->id, $settings, $name, $ai, $price);
            exit;
        }
        @set_time_limit(300);
        $this->work($rid, (int) $payer->id, $settings, $name, $ai, $price);

        return $this->job($request, $rid);
    }

    private function work(int $id, int $payerId, AiSetting $settings, string $name, string $ai, float $price): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('research');
            $what = trim(($name !== '' ? $name : '') . ($ai !== '' ? ($name !== '' ? '; the farmer says it contains ' : 'the active ingredient ') . $ai : ''));
            $brief = 'Look up this agricultural product or active ingredient used in the Philippines and write research notes from what the pages say (the product label, '
                . 'the company\'s Philippine pages, the registered pesticide and fertilizer lists, distributor listings, and for an ingredient, pesticide property databases '
                . 'and field studies): ' . $what . "\n\nNotes on: who makes or registers it in the Philippines; its type (insecticide, fungicide, herbicide, foliar fertilizer, "
                . 'granular fertilizer...); its active ingredients with their strength, or for a fertilizer its guaranteed analysis; its formulation; the pests, diseases or '
                . 'weeds its label names; the label rate; and above all, for each active ingredient, how many days ONE application keeps protecting a crop in hot, rainy '
                . 'tropical field conditions (residual activity), whether it is contact, systemic, translaminar, a stomach poison, a pre emergence or post emergence herbicide, '
                . 'and how fast it starts to work. For a fertilizer, how long one application keeps feeding the crop. Say plainly when something cannot be found. Write in English.';
            $result = app(AiClient::class)->researchThenJson($settings->forField('PH'), $brief, fn (string $notes) => $this->jsonPrompt($name, $ai, $notes), 3000,
                fn (string $t) => $this->parse($t), $beat, 2500, ['kind' => self::KIND, 'scope' => $name !== '' ? $name : $ai]);
            if (($result['data'] ?? null) === null) {
                Log::warning('range effect: unparsable answer', ['head' => mb_substr((string) ($result['text'] ?? ''), 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'Anee\'s answer could not be read. Nothing was charged. Please try again.');
            }
            $d = $result['data'];
            $kept = $this->keep($d, $name, $ai, $payerId, (array) ($result['sources'] ?? []));
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $price);
            app(AiCreditService::class)->chargeAllowingNegative($payerId, $price, mb_substr('Range Effect, product look up: ' . ($name !== '' ? $name : $ai) . $note, 0, 250));
            AiPricing::audit(self::KIND, $result, $price, [], $settings);
            $report = ['found' => $d['found'], 'summary' => $d['summary'], 'product' => $kept['product'], 'ingredients' => $kept['ingredients'], 'learned' => $kept['learned'],
                'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            DB::table('as_plant_analyses')->where('id', $id)->update(['report' => json_encode($report, JSON_UNESCAPED_UNICODE), 'credits' => round($price, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    private function jsonPrompt(string $name, string $ai, string $notes): string
    {
        return "You are Anee, the smart farm technician of anee.io, helping a Filipino farmer see how long a product keeps working on the crop.\n\nLOOK UP: "
            . ($name !== '' ? 'the product "' . $name . '"' : 'the active ingredient') . ($ai !== '' ? ' (active ingredient given by the farmer: ' . $ai . ')' : '') . "\n\n"
            . ($notes !== '' ? "RESEARCH NOTES (found on the web just now; rely on them over memory, and say when they are silent):\n" . $notes . "\n\n" : '')
            . "Answer with ONLY a JSON object, no other text:\n"
            . '{"found":true,"brand":"the product name as sold, or empty for an ingredient on its own","company":"","type":"insecticide|fungicide|herbicide|bactericide|molluscicide|rodenticide|nematicide|acaricide|pgr|foliar|soluble|granular|biostimulant|biological|other",'
            . '"formulation":"EC","ai":[{"name":"","amount":0,"unit":"g/L"}],"nutrients":{"N":0},"labelRate":"","phi":"",'
            . '"effects":[{"ai":"the ingredient name","targets":["insect|mite|fungus|bacteria|weed|snail|rat|nematode|growth"],"move":"contact|systemic|translaminar|stomach|soil|pre|post|pre_post|fumigant|bait|biological|protectant|curative","onset":0,"days":[5,10,14],"fade":"sharp|gradual","note":"one short sentence a farmer can use"}],'
            . '"feedDays":[0,0,0],"confidence":"high|medium|low","source":"the page names you used","summary":"two sentences for the farmer"}'
            . "\n\nRULES: days are [shortest, usual, longest] days ONE application at the label rate keeps working in hot, rainy tropical fields; for a fertilizer give feedDays the same way and leave effects empty. "
            . 'Use found=false and confidence low when the product cannot be confirmed; never invent an ingredient, a strength or a rate. Plain English, short sentences, no dashes between words (write "to" for ranges).';
    }

    private function parse(string $text): ?array
    {
        $t = trim($text);
        if (preg_match('/\{.*\}/s', $t, $m)) {
            $t = $m[0];
        }
        $d = json_decode($t, true);
        if (! is_array($d)) {
            return null;
        }
        $clean = fn ($s, int $max = 300) => mb_substr(trim((string) preg_replace(['/\s+[-–—]\s+/u', '/[–—]/u'], [', ', ' to '], (string) $s)), 0, $max);
        $ai = [];
        foreach (array_slice((array) ($d['ai'] ?? []), 0, 6) as $a) {
            $n = $clean($a['name'] ?? '', 80);
            if ($n !== '') {
                $ai[] = array_filter(['n' => $n, 'a' => is_numeric($a['amount'] ?? null) && (float) $a['amount'] > 0 ? (float) $a['amount'] : null,
                    'u' => in_array($a['unit'] ?? '', ['g/L', 'g/kg', '%', 'mL/L', 'CFU/g', 'CFU/mL'], true) ? $a['unit'] : null], fn ($x) => $x !== null);
            }
        }
        $nut = [];
        foreach ((array) ($d['nutrients'] ?? []) as $k => $v) {
            if (in_array($k, ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'SiO2'], true) && is_numeric($v) && (float) $v > 0 && (float) $v <= 100) {
                $nut[$k] = (float) $v;
            }
        }
        $effects = [];
        foreach (array_slice((array) ($d['effects'] ?? []), 0, 6) as $e) {
            if (! is_array($e) || trim((string) ($e['ai'] ?? '')) === '') {
                continue;
            }
            $days = array_map('intval', array_slice((array) ($e['days'] ?? []), 0, 3));
            if (count($days) !== 3 || max($days) < 1 || max($days) > 120) {
                continue;
            }
            $effects[] = ['ai' => $clean($e['ai'], 80), 'targets' => array_values(array_intersect((array) ($e['targets'] ?? []), ['insect', 'mite', 'fungus', 'bacteria', 'weed', 'snail', 'rat', 'nematode', 'growth'])),
                'move' => (string) ($e['move'] ?? 'contact'), 'onset' => (int) ($e['onset'] ?? 0), 'days' => $days, 'fade' => ($e['fade'] ?? '') === 'sharp' ? 'sharp' : 'gradual', 'note' => $clean($e['note'] ?? '', 200)];
        }
        $types = array_keys(ProductLibrary::TYPES);

        return ['found' => (bool) ($d['found'] ?? false), 'brand' => $clean($d['brand'] ?? '', 120), 'company' => $clean($d['company'] ?? '', 120),
            'type' => in_array($d['type'] ?? '', array_merge($types, ['bactericide']), true) ? $d['type'] : 'other', 'formulation' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) ($d['formulation'] ?? '')), 0, 4)),
            'ai' => $ai, 'nutrients' => $nut, 'labelRate' => $clean($d['labelRate'] ?? '', 200), 'phi' => $clean($d['phi'] ?? '', 80), 'effects' => $effects,
            'confidence' => in_array($d['confidence'] ?? '', ['high', 'medium', 'low'], true) ? $d['confidence'] : 'low', 'source' => $clean($d['source'] ?? '', 250), 'summary' => $clean($d['summary'] ?? '', 500)];
    }

    /** What Anee found, kept: the product in the book (learnt once), each ingredient's days in the effect book. */
    private function keep(array $d, string $name, string $ai, int $payerId, array $sources): array
    {
        $learned = [];
        $ings = [];
        foreach ($d['effects'] as $e) {
            $dict = CompatBook::ingredient($e['ai']);
            $key = $dict['name'] ?? $e['ai'];
            if ($d['confidence'] !== 'low' && RangeEffectBook::learnIngredient($key, $e + ['conf' => $d['confidence'] === 'high' ? 'high' : 'med',
                'src' => array_slice(array_column($sources, 'url'), 0, 6)])) {
                $learned[] = $key;
            }
            $ings[] = ['name' => $key, 'kind' => $dict['kind'] ?? '', 'known' => (bool) RangeEffectBook::ingredient($key)];
        }
        $product = null;
        $brand = $d['brand'] !== '' ? $d['brand'] : $name;
        if ($brand !== '' && $d['found'] && $d['confidence'] !== 'low' && ($d['ai'] || $d['nutrients'])) {
            $pid = ProductLibrary::learn([
                'brand' => $brand, 'company' => $d['company'], 'type' => $d['type'] === 'bactericide' ? 'other' : $d['type'], 'formulation' => $d['formulation'], 'ai' => $d['ai'],
                'nutrients' => $d['nutrients'], 'rates' => $d['labelRate'] !== '' ? [['crop' => '', 'text' => $d['labelRate'], 'phi' => $d['phi']]] : [], 'mixing' => [], 'flags' => [],
                'aliases' => $name !== '' && mb_strtolower($brand) !== mb_strtolower($name) ? [$name] : [], 'confidence' => $d['confidence'],
                'sources' => array_merge($d['source'] !== '' ? [['title' => $d['source'], 'url' => '']] : [], array_slice($sources, 0, 8)), 'ownerId' => $payerId,
            ], 'effect');
            if ($pid && ($p = RangeEffect::productOf($pid))) {
                $product = ProductLibrary::card($p);
            }
        }
        if (! $product && ! $ings && $ai !== '') {
            $dict = CompatBook::ingredient($ai);
            $ings[] = ['name' => $dict['name'] ?? $ai, 'kind' => $dict['kind'] ?? '', 'known' => (bool) RangeEffectBook::ingredient($dict['name'] ?? $ai)];
        }

        return ['product' => $product, 'ingredients' => $ings, 'learned' => $learned];
    }

    public function job(Request $request, int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->jsonFail('That look up is gone.', 404);
        }
        if ($r->status === 'pending') {
            $beatAt = Carbon::parse($r->updated_at ?: $r->created_at);
            if ($beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || Carbon::parse($r->created_at)->lt(now()->subMinutes(12))) {
                $why = 'The look up was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->jsonFail($why, 422, ['status' => 'failed']);
            }
            $rep = json_decode((string) $r->report, true) ?: [];

            return $this->jsonOk('Working…', ['data' => ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => $rep['phase'] ?? null, 'try' => $rep['try'] ?? null,
                'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->jsonFail($r->error ?: 'The look up failed and nothing was charged. Please try again.', 422, ['status' => 'failed']);
        }

        return $this->jsonOk('Ready.', ['data' => ['status' => 'ready', 'id' => (int) $r->id, 'report' => json_decode((string) $r->report, true), 'charged' => (float) $r->credits,
            'balance' => round((float) app(AiCreditService::class)->balance($this->payer()->id), 2)]]);
    }

    private function payer(): \App\Models\User
    {
        return \App\Models\User::findOrFail((int) \App\Support\WorkerContext::effectiveOwnerId());
    }
}
