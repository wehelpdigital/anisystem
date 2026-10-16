<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPricing;
use App\Support\AiUsage;
use App\Support\CompatBook;
use App\Support\CompatCheck;
use App\Support\FertTraits;
use App\Support\FieldCatalogue;
use App\Support\ProductLibrary;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The Compatibility Checker (2026-10-18, the owner): can these sprays and
 * fertilizers go on together, in what order, how strong, and what to watch.
 * The sixth field helper (/app/field-helpers/compat, in FieldPack::TOOLS,
 * behind the same gate). The page is drawn by FieldHelperController with
 * field-helpers.partials.compat; this controller answers its calls:
 *
 *   find      the product book searched by brand, company or ingredient
 *   product   one product's sheet (its ingredients, label rates, mixing notes)
 *   check     the free reading (App\Support\CompatCheck), no AI, no charge
 *   mine      the farmer's own products (as_user_products, per farm owner)
 *   anee      Anee looks up online what the book lacks (AiPrices 'compat',
 *             priced by the engine per product to look up); what she finds
 *             is kept: a product with its ingredients becomes a row of the
 *             book (source 'anee'), her notes go on the research shelf.
 */
class CompatController extends Controller
{
    public const KIND = 'compat';

    public const WATER = ['soft' => 'Clear and soft', 'hard' => 'Hard water', 'muddy' => 'Muddy or brown'];

    public const WEATHER = ['cool' => 'Cool morning or late afternoon', 'hot' => 'Hot, above 32 C', 'rain' => 'Rain may come soon'];

    public const TYPES_OWN = ['insecticide', 'fungicide', 'herbicide', 'molluscicide', 'pgr', 'foliar', 'soluble', 'granular', 'amendment', 'biostimulant', 'biological', 'adjuvant', 'other'];

    public const FORMS_OWN = ['EC', 'SC', 'WP', 'WG', 'SL', 'SP', 'OD', 'EW', 'CS', 'ZC', 'GR', 'liquid', 'soluble', 'granular'];

    /** What the page needs at first paint (FieldHelperController passes it to the partial). */
    public static function book(?string $defaultCrop): array
    {
        $crops = FieldCatalogue::crops();
        $crop = FieldCatalogue::cropKey($defaultCrop);

        return [
            'crops' => $crops,
            'crop' => isset($crops[$crop]) ? $crop : 'rice',
            'counts' => ProductLibrary::counts(),
            'stages' => CompatCheck::STAGES,
            'water' => self::WATER,
            'weather' => self::WEATHER,
            'units' => array_keys(CompatCheck::UNITS),
            'types' => ProductLibrary::TYPES,
            'typesOwn' => self::TYPES_OWN,
            'formsOwn' => self::FORMS_OWN,
            'ingredients' => CompatBook::ingredientNames(),
            'mine' => self::mineList(),
        ];
    }

    /* ================================================================ the book */

    public function find(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $crop = FieldCatalogue::cropKey((string) $request->query('crop', '')) ?: null;
        $shelf = (string) $request->query('shelf', '');
        if (mb_strlen($q) < 2) {
            return $this->json(true, 'ok', ['items' => [], 'mine' => []]);
        }
        $rows = ProductLibrary::search($q, $shelf ?: null, $crop, 30);
        $mine = array_values(array_filter(self::mineList(), fn ($m) => str_contains(ProductLibrary::norm($m['b'] . ' ' . implode(' ', $m['ai'])), ProductLibrary::norm($q))));

        return $this->json(true, 'ok', ['items' => array_map(fn ($p) => ProductLibrary::card($p, $crop), $rows), 'mine' => array_slice($mine, 0, 10)]);
    }

    public function product(Request $request, int $id)
    {
        $crop = FieldCatalogue::cropKey((string) $request->query('crop', '')) ?: null;
        $p = ProductLibrary::many([$id])[$id] ?? null;
        if (! $p) {
            return $this->json(false, 'That product is not in the book.', [], 404);
        }
        $tr = CompatBook::traits($p, ProductLibrary::applied($p) === 'soil' ? 'soil' : 'spray');
        $crops = FieldCatalogue::crops();
        $uses = array_map(fn ($u) => ['crop' => $crops[$u[0]][1] ?? $u[0], 'key' => $u[0], 'for' => $u[1] ?? '', 'rate' => $u[2] ?? '', 'phi' => $u[4] ?? ''], array_slice((array) $p['uses'], 0, 30));
        if ($crop) {
            usort($uses, fn ($a, $b) => (int) ($b['key'] === $crop) <=> (int) ($a['key'] === $crop));
        }

        return $this->json(true, 'ok', ['p' => ProductLibrary::card($p, $crop) + [
            'known' => $tr['known'], 'unknownAi' => $tr['unknown'], 'uses' => $uses, 'mix' => array_values((array) ($p['mix'] ?? [])),
            'reg' => (string) ($p['reg'] ?? ''), 'mode' => (string) ($p['mode'] ?? ''), 'tox' => (string) ($p['tox'] ?? ''),
            'analysis' => (string) ($p['analysis'] ?? ''), 'crops' => array_values(array_map(fn ($k) => $crops[$k][1] ?? $k, (array) $p['crops'])),
            'other' => array_values((array) ($p['other'] ?? [])), 'aka' => array_values((array) ($p['aka'] ?? [])), 'chem' => $p['chem'] ?? null,
            'notes' => (string) ($p['notes'] ?? ''),
        ]]);
    }

    /* ================================================================ the free check */

    public function check(Request $request)
    {
        [$ctx, $items, $err] = $this->readCase($request);
        if ($err) {
            return $err;
        }

        return $this->json(true, 'ok', ['result' => (new CompatCheck($ctx))->run($items)]);
    }

    /** The field and the items, validated and resolved to products. */
    private function readCase(Request $request): array
    {
        $v = Validator::make($request->all(), [
            'crop' => 'nullable|string|max:40',
            'stage' => 'nullable|in:,' . implode(',', array_keys(CompatCheck::STAGES)),
            'water' => 'nullable|in:,' . implode(',', array_keys(self::WATER)),
            'weather' => 'nullable|in:,' . implode(',', array_keys(self::WEATHER)),
            'tankL' => 'nullable|numeric|min:1|max:1000',
            'waterHa' => 'nullable|numeric|min:20|max:2000',
            'items' => 'required|array|min:1|max:10',
            'items.*.ref' => 'required|string|max:40',
            'items.*.name' => 'nullable|string|max:120',
            'items.*.applied' => 'nullable|in:spray,soil',
            'items.*.amount' => 'nullable|numeric|min:0|max:1000000',
            'items.*.unit' => 'nullable|in:' . implode(',', array_keys(CompatCheck::UNITS)),
            'items.*.per' => 'nullable|in:' . implode(',', array_keys(CompatCheck::PERS)),
        ]);
        if ($v->fails()) {
            return [null, null, $this->json(false, $v->errors()->first(), [], 422)];
        }
        $crop = FieldCatalogue::cropKey((string) $request->input('crop', '')) ?: null;
        $ctx = ['crop' => isset(FieldCatalogue::crops()[$crop]) ? $crop : null, 'stage' => (string) $request->input('stage', ''), 'water' => (string) $request->input('water', ''),
            'weather' => (string) $request->input('weather', ''), 'tankL' => (float) ($request->input('tankL') ?: 16), 'waterHa' => (float) ($request->input('waterHa') ?: 200)];
        $raw = array_values((array) $request->input('items', []));
        $pids = [];
        $mids = [];
        foreach ($raw as $r) {
            if (preg_match('/^p:(\d+)$/', (string) $r['ref'], $m)) {
                $pids[] = (int) $m[1];
            } elseif (preg_match('/^m:(\d+)$/', (string) $r['ref'], $m)) {
                $mids[] = (int) $m[1];
            }
        }
        $prods = ProductLibrary::many($pids);
        $mine = $mids ? ProductLibrary::db()->table('as_user_products')->whereIn('id', $mids)->where('ownerId', WorkerContext::effectiveOwnerId())->where('deleteStatus', 1)->get()->keyBy('id') : collect();
        $items = [];
        foreach ($raw as $r) {
            $ref = (string) $r['ref'];
            $name = trim((string) ($r['name'] ?? ''));
            $p = null;
            if (preg_match('/^p:(\d+)$/', $ref, $m)) {
                $p = $prods[(int) $m[1]] ?? null;
            } elseif (preg_match('/^m:(\d+)$/', $ref, $m) && isset($mine[(int) $m[1]])) {
                $p = self::ownProduct($mine[(int) $m[1]]);
            }
            if (! $p) {
                // Only a name: the book's own row of that exact name, or nothing known.
                $p = $name !== '' ? $this->byName($name) : null;
                $p = $p ?? ['k' => 'typed', 'b' => $name ?: 'Product', 'c' => '', 't' => 'other', 'ai' => [], 'n' => [], 'tr' => [], 'crops' => [], 'uses' => []];
                $ref = isset($p['id']) ? 'p:' . $p['id'] : 'x';
            }
            $items[] = ['ref' => $ref, 'name' => $name !== '' ? $name : (string) $p['b'], 'product' => $p, 'applied' => $r['applied'] ?? null,
                'amount' => $r['amount'] ?? null, 'unit' => $r['unit'] ?? null, 'per' => $r['per'] ?? null];
        }

        return [$ctx, $items, null];
    }

    /** A typed name the book holds by that very brand. */
    private function byName(string $name): ?array
    {
        if (! ProductLibrary::ready()) {
            return null;
        }
        $r = ProductLibrary::db()->table(ProductLibrary::TABLE)->where('status', 1)->whereRaw('lower(brand) = ?', [mb_strtolower($name)])->orderByRaw("source = 'file' desc")->first();

        return $r ? ProductLibrary::fromRow($r) : null;
    }

    /* ================================================================ the farmer's own products */

    public static function ownProduct(object $r): array
    {
        $ai = json_decode((string) $r->ai, true) ?: [];
        $n = array_filter(array_map('floatval', json_decode((string) $r->nutrients, true) ?: []));
        $type = (string) $r->type;
        $form = (string) $r->form;
        $tr = in_array($type, CompatBook::FERT_TYPES, true) || $n ? FertTraits::infer((string) $r->name, $n, $form) : [];

        return ['id' => null, 'k' => 'mine-' . $r->id, 'b' => (string) $r->name, 'c' => 'Your own', 't' => $type, 'f' => in_array($form, ['liquid', 'soluble', 'granular'], true) ? '' : $form,
            'form' => in_array($form, ['liquid', 'soluble', 'granular'], true) ? $form : '', 'ai' => array_map(fn ($a) => array_filter(['n' => (string) ($a['n'] ?? ''), 'a' => isset($a['a']) && $a['a'] !== '' ? (float) $a['a'] : null, 'u' => $a['u'] ?? null], fn ($x) => $x !== null), $ai),
            'n' => $n, 'tr' => $tr, 'crops' => [], 'uses' => [], 'applied' => (string) $r->applied === 'soil' ? 'soil' : 'spray', 'notes' => (string) $r->notes, 'source' => 'mine'];
    }

    public static function mineList(): array
    {
        try {
            if (! Auth::check() || ! \Illuminate\Support\Facades\Schema::connection(ProductLibrary::conn())->hasTable('as_user_products')) {
                return [];
            }
            $rows = ProductLibrary::db()->table('as_user_products')->where('ownerId', WorkerContext::effectiveOwnerId())->where('deleteStatus', 1)->orderByDesc('updated_at')->limit(200)->get();
        } catch (\Throwable $e) {
            return [];
        }

        return $rows->map(function ($r) {
            $p = self::ownProduct($r);

            return ['id' => (int) $r->id, 'ref' => 'm:' . $r->id, 'b' => $p['b'], 'c' => 'Your own', 't' => $p['t'], 'type' => ProductLibrary::TYPES[$p['t']] ?? 'Product',
                'f' => $p['f'] ?: $p['form'], 'applied' => $p['applied'], 'ai' => array_map(fn ($a) => trim(($a['n'] ?? '') . (isset($a['a']) ? ' ' . ProductLibrary::num((float) $a['a']) . ' ' . ($a['u'] ?? '') : '')), $p['ai']),
                'nut' => $p['n'] ?: null, 'rate' => $r->rateAmount !== null ? ['amount' => (float) $r->rateAmount, 'unit' => $r->rateUnit, 'per' => $r->ratePer] : null,
                'raw' => ['type' => $p['t'], 'form' => (string) $r->form, 'applied' => $p['applied'], 'ai' => json_decode((string) $r->ai, true) ?: [], 'n' => json_decode((string) $r->nutrients, true) ?: (object) [], 'notes' => (string) $r->notes]];
        })->all();
    }

    public function saveMine(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'name' => 'required|string|max:120',
            'type' => 'required|in:' . implode(',', self::TYPES_OWN),
            'form' => 'nullable|in:,' . implode(',', self::FORMS_OWN),
            'applied' => 'required|in:spray,soil',
            'ai' => 'nullable|array|max:6',
            'ai.*.n' => 'required_with:ai|string|max:80',
            'ai.*.a' => 'nullable|numeric|min:0|max:1000000',
            'ai.*.u' => 'nullable|in:g/L,g/kg,%,mL/L,CFU/g,CFU/mL',
            'n' => 'nullable|array',
            'n.*' => 'nullable|numeric|min:0|max:100',
            'rateAmount' => 'nullable|numeric|min:0|max:1000000',
            'rateUnit' => 'nullable|in:' . implode(',', array_keys(CompatCheck::UNITS)),
            'ratePer' => 'nullable|in:' . implode(',', array_keys(CompatCheck::PERS)),
            'notes' => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $nut = [];
        foreach ((array) $request->input('n', []) as $k => $val) {
            if (in_array($k, ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'SiO2'], true) && is_numeric($val) && (float) $val > 0) {
                $nut[$k] = round((float) $val, 3);
            }
        }
        $ai = [];
        foreach ((array) $request->input('ai', []) as $a) {
            $n = trim((string) ($a['n'] ?? ''));
            if ($n !== '') {
                $ai[] = array_filter(['n' => $n, 'a' => isset($a['a']) && $a['a'] !== '' ? (float) $a['a'] : null, 'u' => $a['u'] ?? null], fn ($x) => $x !== null);
            }
        }
        $row = ['name' => trim((string) $request->input('name')), 'type' => $request->input('type'), 'form' => (string) $request->input('form', ''), 'applied' => $request->input('applied'),
            'ai' => json_encode($ai, JSON_UNESCAPED_UNICODE), 'nutrients' => json_encode((object) $nut), 'rateAmount' => $request->input('rateAmount') !== null && $request->input('rateAmount') !== '' ? (float) $request->input('rateAmount') : null,
            'rateUnit' => $request->input('rateUnit') ?: null, 'ratePer' => $request->input('ratePer') ?: null, 'notes' => mb_substr((string) $request->input('notes', ''), 0, 500), 'updated_at' => now()];
        $owner = WorkerContext::effectiveOwnerId();
        $id = (int) $request->input('id');
        if ($id) {
            $n = ProductLibrary::db()->table('as_user_products')->where('id', $id)->where('ownerId', $owner)->where('deleteStatus', 1)->update($row);
            if (! $n) {
                return $this->json(false, 'That product is gone.', [], 404);
            }
        } else {
            if (ProductLibrary::db()->table('as_user_products')->where('ownerId', $owner)->where('deleteStatus', 1)->count() >= 200) {
                return $this->json(false, 'You have 200 products of your own. Remove one you no longer use first.', [], 422);
            }
            $id = ProductLibrary::db()->table('as_user_products')->insertGetId($row + ['ownerId' => $owner, 'userId' => Auth::id(), 'deleteStatus' => 1, 'created_at' => now()]);
        }
        $mine = self::mineList();

        return $this->json(true, 'Saved.', ['id' => $id, 'item' => collect($mine)->firstWhere('id', $id), 'mine' => $mine]);
    }

    public function deleteMine(int $id)
    {
        ProductLibrary::db()->table('as_user_products')->where('id', $id)->where('ownerId', WorkerContext::effectiveOwnerId())->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Removed.', ['mine' => self::mineList()]);
    }

    /* ================================================================ Anee's online check */

    /** The price and whether Anee can run here (the page asks only when the book lacks something). */
    public function aneeOptions(Request $request)
    {
        $payer = $this->payer();
        $credits = app(AiCreditService::class);
        $settings = AiSetting::current();
        return $this->json(true, 'ok', [
            // One price for any number of products to look up (the owner, 2026-10-18).
            'price' => AiPricing::credits(self::KIND),
            'balance' => round((float) $credits->balance($payer->id), 2),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'canUse' => Tier::farmCan('aiAnalyses') && $payer->canUseAi() && $settings->isUsable(),
            'tierLocked' => ! Tier::farmCan('aiAnalyses'),
            'whyNot' => ! Tier::farmCan('aiAnalyses') ? Tier::farmUnlocksAt('aiAnalyses') === 'libreAnee' ? 'Anee checking online comes with Libre + Anee and every plan above it. The checker itself stays yours.' : 'Anee checking online is not on this plan.'
                : (! $payer->canUseAi() ? Tier::aneeNeeds($payer, 'The online check') : (! $settings->isUsable() ? 'Anee is not switched on yet. Please check back soon.' : null)),
        ]);
    }

    public function anee(Request $request)
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'Anee checking products online comes with {plan}, and with every plan above it. The checker itself stays yours.');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'The online check'), [], 403);
        }
        [$ctx, $items, $err] = $this->readCase($request);
        if ($err) {
            return $err;
        }
        $before = (new CompatCheck($ctx))->run($items);
        $look = $before['unknown']['items'];
        foreach ($before['unknown']['pairs'] as [$a, $b]) {
            foreach ([$a, $b] as $i) {
                if (! in_array($i, $look, true) && ! $before['items'][$i]['known']) {
                    $look[] = $i;
                }
            }
        }
        if (! $look) {
            return $this->json(false, 'Our book already knows every product here, so there is nothing for Anee to look up.', [], 422);
        }
        $units = min(6, count($look));
        $quote = AiPricing::quote(self::KIND);
        $agreed = AiPricing::agreed($request, $quote, (int) $quote['credits']);
        if ($agreed === null) {
            return $this->json(false, 'The online check now costs ' . $quote['credits'] . ' credits. Please check again.', ['quote' => $quote['credits']], 409);
        }
        $price = (float) $agreed;
        $credits = app(AiCreditService::class);
        if ($credits->balance($payer->id) < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->json(false, 'You need ' . ceil($price) . ' credits for this check and have ' . number_format((int) floor($credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(8))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }
        $case = ['ctx' => $ctx, 'items' => array_map(fn ($it) => array_diff_key($it, ['product' => 1]), $items), 'look' => array_slice($look, 0, 6)];
        $title = 'Mix check · ' . implode(' + ', array_slice(array_map(fn ($it) => $it['name'], $items), 0, 3)) . (count($items) > 3 ? ' and more' : '');
        $rid = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr($title, 0, 190), 'params' => json_encode($case, JSON_UNESCAPED_UNICODE),
            'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $rid]])->send();
            fastcgi_finish_request();
            $this->run($rid, (int) $payer->id, $settings, $request->all(), $before, array_slice($look, 0, 6), $price, $units);
            exit;
        }
        @set_time_limit(300);
        $this->run($rid, (int) $payer->id, $settings, $request->all(), $before, array_slice($look, 0, 6), $price, $units);

        return $this->job($rid);
    }

    private function run(int $id, int $payerId, AiSetting $settings, array $input, array $before, array $look, float $price, int $units): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('research');
            $names = array_map(fn ($i) => $before['items'][$i], $look);
            $brief = $this->researchBrief($names);
            $result = app(AiClient::class)->researchThenJson($settings->forField('PH'), $brief, fn (string $notes) => $this->jsonPrompt($before, $names, $notes), 6000,
                fn (string $t) => $this->parse($t), $beat, 3500, ['kind' => self::KIND, 'scope' => implode(' + ', array_column($names, 'name'))]);
            if (($result['data'] ?? null) === null) {
                Log::warning('compat: unparsable answer', ['head' => mb_substr((string) ($result['text'] ?? ''), 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'Anee\'s answer could not be read. Nothing was charged. Please try again.');
            }
            $anee = $result['data'];
            // What she found becomes part of the book, and the check runs again with it.
            $added = $this->keep($anee, $names, $payerId, (array) ($result['sources'] ?? []));
            $request = Request::create('/', 'POST', $input);
            $items = (array) $request->input('items', []);
            foreach ($added as $i => $pid) {
                if (isset($items[$i])) {
                    $items[$i]['ref'] = 'p:' . $pid;
                }
            }
            $request->merge(['items' => $items]);
            [$ctx, $resolved] = $this->readCase($request);
            $after = (new CompatCheck($ctx))->run($resolved);
            $after = $this->merge($after, $anee);
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $price);
            app(AiCreditService::class)->chargeAllowingNegative($payerId, $price, mb_substr('Compatibility Checker, online check: ' . $units . ' ' . ($units === 1 ? 'product' : 'products') . $note, 0, 250));
            AiPricing::audit(self::KIND, $result, $price, [], $settings);
            $report = ['result' => $after, 'anee' => ['summary' => $anee['summary'] ?? '', 'notes' => $anee['notes'] ?? [], 'products' => $anee['products'] ?? []],
                'added' => array_values($added), 'refs' => array_map(fn ($it) => $it['ref'] ?? 'x', $items), 'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            DB::table('as_plant_analyses')->where('id', $id)->update(['report' => json_encode($report, JSON_UNESCAPED_UNICODE), 'credits' => round($price, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    /** The search brief: only the products (never the farm), so the notes help the next farmer who asks about them too. */
    private function researchBrief(array $items): string
    {
        $lines = [];
        foreach ($items as $x) {
            $what = $x['name'] . ($x['company'] && $x['company'] !== 'Your own' ? ' (' . $x['company'] . ')' : '');
            $ai = array_filter(array_merge(array_column($x['ai'], 'n'), $x['unknownAi']));
            $lines[] = '- ' . $what . ($ai ? '; the farmer says it contains: ' . implode(', ', $ai) : '') . ($x['f'] ? '; formulation ' . $x['f'] : '');
        }
        sort($lines);

        return "Look up these agricultural products sold in the Philippines and write research notes from what the pages say (product labels, the company's Philippine pages, the FPA lists of registered pesticides and fertilizers, distributor listings, and for ingredients, extension and pesticide property databases):\n"
            . implode("\n", $lines) . "\n\nFor EACH product: who makes or registers it in the Philippines; its type (insecticide, fungicide, herbicide, foliar fertilizer, granular fertilizer, adjuvant...); its active ingredients with their strength (g/L, g/kg or %), or for a fertilizer its guaranteed analysis; its formulation (EC, SC, WP, WG, SL...); what its label says about mixing (compatible with, do not mix with, pH, alkaline products, oils, copper, the mixing order); the label rate per 16 L knapsack or per hectare for common crops; the pre harvest interval. For any active ingredient that is unusual, its chemical class, its IRAC, FRAC or HRAC group, and whether it breaks down in alkaline water. Then any published notes on mixing these products together. Say plainly when a product cannot be found. Write the notes in English; keep the page names you read.";
    }

    /** The answer's prompt: what the book already knows, her notes, and the JSON wanted. */
    private function jsonPrompt(array $before, array $look, string $notes): string
    {
        $flags = implode(', ', ['op', 'carb', 'pyr', 'neo', 'diamide', 'aver', 'spino', 'nereis', 'fipr', 'igr', 'dtc', 'copper', 'sulfur', 'limesulfur', 'bordeaux', 'captan', 'chlorothalonil', 'strobi', 'dmi',
            'benzim', 'phenylamide', 'phosphonate', 'cymoxanil', 'antibiotic', 'sdhi', 'accase', 'su', 'su_corn', 'als', 'auxin', 'auxin_salt', 'propanil', 'glyphosate', 'glufosinate', 'bipyridyl',
            'triazine', 'psii_urea', 'bentazon', 'vlcfa', 'ppo', 'hppd', 'oil', 'soap', 'organosilicone', 'surfactant', 'sticker', 'acidifier', 'conditioner', 'living_fungus', 'living_bact', 'bt',
            'botanical', 'oxidizer', 'pgr', 'ethephon', 'alkaline', 'acidic', 'hard', 'mud', 'heat', 'alk_fast', 'alk_slow', 'acid_sens']);
        $items = [];
        foreach ($before['items'] as $x) {
            $items[] = '- ' . $x['name'] . ($x['company'] ? ' (' . $x['company'] . ')' : '') . ': ' . $x['typeWord'] . ($x['f'] ? ', ' . $x['f'] : '') . ', ' . ($x['applied'] === 'soil' ? 'spread on the soil' : 'sprayed')
                . ($x['ai'] ? '; ingredients: ' . implode(', ', array_map(fn ($a) => $a['n'] . ' (' . $a['cls'] . ($a['grp'] ? ', ' . $a['grp'] : '') . ')', $x['ai'])) : '')
                . ($x['known'] ? '' : '; NOT KNOWN TO OUR BOOK');
        }
        $pairs = [];
        foreach ($before['pairs'] as $p) {
            $pairs[] = '- ' . $before['items'][$p['a']]['name'] . ' + ' . $before['items'][$p['b']]['name'] . ': ' . $p['level']
                . ($p['reasons'] ? ' (' . implode(' ', array_column($p['reasons'], 'why')) . ')' : '');
        }

        return "You are Anee, the smart farm technician of anee.io, helping a Filipino farmer check whether products can be applied together (one tank, or spread together). "
            . 'Crop: ' . ($before['cropName'] ?? 'not given') . ($before['stageWord'] ? ', ' . $before['stageWord'] : '') . ".\n\nTHE ITEMS:\n" . implode("\n", $items)
            . "\n\nWHAT OUR RULES ALREADY SAY ABOUT EACH PAIR (unknown = our book could not tell):\n" . ($pairs ? implode("\n", $pairs) : '- none')
            . "\n\nLOOK UP: " . implode(', ', array_column($look, 'name')) . "\n\n"
            . ($notes !== '' ? "RESEARCH NOTES (found on the web just now; rely on them over memory, and say when they are silent):\n" . $notes . "\n\n" : '')
            . "Answer with ONLY a JSON object, no other text:\n"
            . '{"products":[{"name":"exactly as in LOOK UP","found":true,"brand":"","company":"","type":"insecticide|fungicide|herbicide|molluscicide|pgr|adjuvant|foliar|soluble|granular|amendment|biostimulant|biological|other",'
            . '"formulation":"EC","ai":[{"name":"","amount":0,"unit":"g/L"}],"nutrients":{"N":0},"flags":["from the list below only"],"labelRate":"","phi":"","mixing":["what its label says about mixing, in your own words"],'
            . '"confidence":"high|medium|low","source":"the page names you used"}],'
            . '"pairs":[{"a":"item name","b":"item name","level":"ok|care|no","why":"","fix":""}],"notes":["anything else the farmer must watch"],"summary":"two sentences for the farmer"}'
            . "\n\nRULES: flags may only come from this list: " . $flags . ". Use found=false and confidence low when a product cannot be confirmed; never invent an ingredient, a strength or a rate. "
            . 'Give a pair only when the research says something about it or its ingredients; "no" only with a clear reason. Plain English a farmer reads, short sentences, no dashes between words (write "to" for ranges). '
            . 'The product label always wins: say so when the label is the only way to be sure.';
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
        $clean = fn ($s, int $max = 400) => mb_substr(trim((string) preg_replace(['/\s+[-–—]\s+/u', '/[–—]/u'], [', ', ' to '], (string) $s)), 0, $max);
        $types = array_merge(self::TYPES_OWN, ['nematicide', 'acaricide', 'rodenticide']);
        $allowed = array_flip(['op', 'carb', 'pyr', 'neo', 'diamide', 'aver', 'spino', 'nereis', 'fipr', 'igr', 'dtc', 'copper', 'sulfur', 'limesulfur', 'bordeaux', 'captan', 'chlorothalonil', 'strobi', 'dmi',
            'benzim', 'phenylamide', 'phosphonate', 'cymoxanil', 'antibiotic', 'sdhi', 'accase', 'su', 'su_corn', 'als', 'auxin', 'auxin_salt', 'propanil', 'glyphosate', 'glufosinate', 'bipyridyl',
            'triazine', 'psii_urea', 'bentazon', 'vlcfa', 'ppo', 'hppd', 'oil', 'soap', 'organosilicone', 'surfactant', 'sticker', 'acidifier', 'conditioner', 'living_fungus', 'living_bact', 'bt',
            'botanical', 'oxidizer', 'pgr', 'ethephon', 'alkaline', 'acidic', 'hard', 'mud', 'heat', 'alk_fast', 'alk_slow', 'acid_sens']);
        $products = [];
        foreach (array_slice((array) ($d['products'] ?? []), 0, 6) as $p) {
            if (! is_array($p) || trim((string) ($p['name'] ?? '')) === '') {
                continue;
            }
            $ai = [];
            foreach (array_slice((array) ($p['ai'] ?? []), 0, 6) as $a) {
                $n = $clean($a['name'] ?? '', 80);
                if ($n !== '') {
                    $ai[] = array_filter(['n' => $n, 'a' => is_numeric($a['amount'] ?? null) && (float) $a['amount'] > 0 ? (float) $a['amount'] : null,
                        'u' => in_array($a['unit'] ?? '', ['g/L', 'g/kg', '%', 'mL/L', 'CFU/g', 'CFU/mL'], true) ? $a['unit'] : null], fn ($x) => $x !== null);
                }
            }
            $nut = [];
            foreach ((array) ($p['nutrients'] ?? []) as $k => $v) {
                if (in_array($k, ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'SiO2'], true) && is_numeric($v) && (float) $v > 0 && (float) $v <= 100) {
                    $nut[$k] = (float) $v;
                }
            }
            $products[] = ['name' => $clean($p['name'], 120), 'found' => (bool) ($p['found'] ?? false), 'brand' => $clean($p['brand'] ?? '', 120), 'company' => $clean($p['company'] ?? '', 120),
                'type' => in_array($p['type'] ?? '', $types, true) ? $p['type'] : 'other', 'formulation' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) ($p['formulation'] ?? '')), 0, 4)),
                'ai' => $ai, 'nutrients' => $nut, 'flags' => array_values(array_filter((array) ($p['flags'] ?? []), fn ($f) => is_string($f) && isset($allowed[$f]))),
                'labelRate' => $clean($p['labelRate'] ?? '', 200), 'phi' => $clean($p['phi'] ?? '', 80), 'mixing' => array_values(array_filter(array_map(fn ($x) => $clean($x, 220), array_slice((array) ($p['mixing'] ?? []), 0, 6)))),
                'confidence' => in_array($p['confidence'] ?? '', ['high', 'medium', 'low'], true) ? $p['confidence'] : 'low', 'source' => $clean($p['source'] ?? '', 250)];
        }
        $pairs = [];
        foreach (array_slice((array) ($d['pairs'] ?? []), 0, 20) as $p) {
            if (is_array($p) && in_array($p['level'] ?? '', ['ok', 'care', 'no'], true)) {
                $pairs[] = ['a' => $clean($p['a'] ?? '', 120), 'b' => $clean($p['b'] ?? '', 120), 'level' => $p['level'], 'why' => $clean($p['why'] ?? '', 300), 'fix' => $clean($p['fix'] ?? '', 300)];
            }
        }

        return ['products' => $products, 'pairs' => $pairs, 'notes' => array_values(array_filter(array_map(fn ($x) => $clean($x, 300), array_slice((array) ($d['notes'] ?? []), 0, 6)))),
            'summary' => $clean($d['summary'] ?? '', 500)];
    }

    /**
     * A product Anee found with its ingredients (or its analysis), confirmed
     * well enough, becomes a row of the book for every farmer after. Returns
     * item index => the new (or matching) product id.
     */
    private function keep(array $anee, array $looked, int $payerId, array $sources): array
    {
        $out = [];
        foreach ($looked as $x) {
            $hit = null;
            foreach ($anee['products'] as $p) {
                if (mb_strtolower($p['name']) === mb_strtolower($x['name']) || mb_strtolower($p['brand']) === mb_strtolower($x['name'])) {
                    $hit = $p;
                    break;
                }
            }
            if (! $hit || ! $hit['found'] || $hit['confidence'] === 'low' || (! $hit['ai'] && ! $hit['nutrients'])) {
                continue;
            }
            $brand = $hit['brand'] !== '' ? $hit['brand'] : $x['name'];
            // Learnt once (the owner, 2026-10-18): a row of the book from now on, or an alias of the row the files already hold.
            $id = ProductLibrary::learn([
                'brand' => $brand, 'company' => $hit['company'], 'type' => $hit['type'], 'formulation' => $hit['formulation'], 'ai' => $hit['ai'], 'nutrients' => $hit['nutrients'],
                'rates' => $hit['labelRate'] !== '' ? [['crop' => '', 'text' => $hit['labelRate'], 'phi' => $hit['phi']]] : [], 'mixing' => $hit['mixing'], 'flags' => $hit['flags'],
                'aliases' => mb_strtolower($brand) !== mb_strtolower($x['name']) ? [$x['name']] : [], 'confidence' => $hit['confidence'],
                'sources' => array_merge($hit['source'] !== '' ? [['title' => $hit['source'], 'url' => '']] : [], array_slice($sources, 0, 8)), 'ownerId' => $payerId,
            ], 'compat');
            if ($id) {
                $out[$x['i']] = $id;
            }
        }

        return $out;
    }

    /** Anee's pair readings laid over the rules': the rules' level stands, hers fills what they could not tell, and the worse of two wins. */
    private function merge(array $res, array $anee): array
    {
        $idx = [];
        foreach ($res['items'] as $x) {
            $idx[mb_strtolower($x['name'])] = $x['i'];
        }
        foreach ($anee['pairs'] as $ap) {
            $a = $idx[mb_strtolower($ap['a'])] ?? null;
            $b = $idx[mb_strtolower($ap['b'])] ?? null;
            if ($a === null || $b === null || $a === $b) {
                continue;
            }
            foreach ($res['pairs'] as &$p) {
                if (($p['a'] === $a && $p['b'] === $b) || ($p['a'] === $b && $p['b'] === $a)) {
                    $p['anee'] = $ap['level'];
                    if (CompatCheck::RANK[$ap['level']] > CompatCheck::RANK[$p['level']] || $p['level'] === 'unknown') {
                        $p['level'] = $ap['level'];
                    }
                    $p['reasons'][] = ['id' => 'anee', 'level' => $ap['level'], 'why' => $ap['why'], 'fix' => $ap['fix'], 'gap' => null, 'anee' => true];
                }
            }
            unset($p);
        }
        $worst = 'ok';
        foreach (array_merge(array_column($res['pairs'], 'level'), array_column($res['tank'], 'level')) as $l) {
            if (CompatCheck::RANK[$l] > CompatCheck::RANK[$worst]) {
                $worst = $l;
            }
        }
        if ($res['unknown']['items'] && $worst === 'ok') {
            $worst = 'unknown';
        }
        $res['verdict'] = $worst;
        $res['aneeChecked'] = true;

        return $res;
    }

    public function job(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That check is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            $beatAt = Carbon::parse($r->updated_at ?: $r->created_at);
            if ($beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || Carbon::parse($r->created_at)->lt(now()->subMinutes(12))) {
                $why = 'The online check was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $rep = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => $rep['phase'] ?? null, 'try' => $rep['try'] ?? null,
                'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The online check failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Ready.', ['status' => 'ready', 'id' => (int) $r->id, 'report' => json_decode((string) $r->report, true), 'charged' => (float) $r->credits,
            'balance' => round((float) app(AiCreditService::class)->balance($this->payer()->id), 2)]);
    }

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
