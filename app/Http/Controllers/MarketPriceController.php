<?php

namespace App\Http\Controllers;

use App\Support\MarketBook;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Market Price Forecast (2026-10-27, the owner: "select the crop, and will
 * give the potential forecast of the upcoming price for the year and next
 * year based on the historical data ... the main goal here is to show when
 * to sell or trade the output of crops based on its market price").
 *
 * No credits: the forecast is arithmetic on the kept price records
 * (App\Support\MarketBook, App\Support\MarketForecast), the import and
 * policy events were read once and kept. A forecast the farmer saves keeps
 * what it said that day (as_plant_analyses, kind 'market', on the Saved tab
 * and into the Trash when deleted).
 */
class MarketPriceController extends Controller
{
    public const KIND = 'market';

    public function page()
    {
        return view('market-prices.index', ['catalog' => MarketBook::ready() ? MarketBook::catalog() : []]);
    }

    /** The places an item has prices for. */
    public function places(Request $request)
    {
        $item = (string) $request->query('item');
        if (! isset(MarketBook::items()[$item])) {
            return $this->json(false, 'Pick a crop first.', [], 422);
        }

        return $this->json(true, 'ok', ['places' => MarketBook::places($item)]);
    }

    /** One item at one place, with the sell plan when a harvest month is given. */
    public function outlook(Request $request)
    {
        $c = $this->clean($request->all());
        if (! $c) {
            return $this->json(false, 'Pick a crop first.', [], 422);
        }
        $o = MarketBook::outlook($c['item'], $c['place']);
        if (! $o || ! $o['forecast'] || ! $o['seasonal']) {
            return $this->json(false, 'There are too few recent prices of this one to forecast it. Try another kind of it, or the whole country.', [], 404);
        }

        return $this->json(true, 'ok', ['outlook' => $o, 'plan' => $this->plan($o, $c), 'params' => $c]);
    }

    public function save(Request $request)
    {
        $c = $this->clean($request->all());
        if (! $c) {
            return $this->json(false, 'Pick a crop first.', [], 422);
        }
        $o = MarketBook::outlook($c['item'], $c['place']);
        if (! $o || ! $o['forecast'] || ! $o['seasonal']) {
            return $this->json(false, 'There are too few recent prices of this one to forecast it.', [], 404);
        }
        $title = mb_substr($o['item']['name'] . ' · ' . ucfirst($o['place']['name']) . ($c['harvest'] ? ' · harvest ' . Carbon::parse($c['harvest'] . '-01')->format('M Y') : ''), 0, 190);
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => $title,
            'params' => json_encode($c), 'report' => json_encode(['outlook' => $o, 'plan' => $this->plan($o, $c)]), 'credits' => 0, 'status' => 'ready', 'deleteStatus' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->json(true, 'Saved.', ['id' => $id, 'title' => $title] + $this->shelf(1));
    }

    /** A saved forecast, as it was that day. */
    public function one(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)
            ->where('status', 'ready')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That forecast is gone.', [], 404);
        }
        $rep = json_decode((string) $r->report, true) ?: [];

        return $this->json(true, 'ok', ['id' => (int) $r->id, 'title' => $r->title, 'params' => json_decode((string) $r->params, true),
            'outlook' => $rep['outlook'] ?? null, 'plan' => $rep['plan'] ?? null, 'savedAt' => Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, Y')]);
    }

    public function list(Request $request)
    {
        return $this->json(true, 'ok', $this->shelf(max(1, (int) $request->query('page', 1))));
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Moved to the Trash. Restore it from the Trash for 30 days.', $this->shelf(1));
    }

    /**
     * Palay's going price lately near the farmer, wet and dry (MarketBook::palayNow), for the price a kilo
     * Benta o Bilad and Hatian ask (2026-10-27). Served on their own routes (bilad.palay, hatian.palay), so a
     * worker given either tool reads it. The province asked, else the farmer's newest farm lot's, else the
     * profile's, else the whole country.
     */
    public function palay(Request $request)
    {
        $asked = $request->has('province') ? (string) $request->query('province') : MarketBook::homeProvince((int) Auth::id());
        $r = MarketBook::palayNow(mb_substr(trim($asked), 0, 80));
        if (! $r || (! $r['wet'] && ! $r['dry'])) {
            return $this->json(false, 'No palay prices are kept yet.', [], 404);
        }
        $key = $r['province'] ? MarketBook::provinceKey($r['province']) : null;

        return $this->json(true, 'ok', $r + ['url' => route('market.page') . '?' . http_build_query(['crop' => 'rice'] + ($key && $r['tip'] && $r['tip']['where'] === $r['province'] ? ['place' => $key, 'placeName' => $r['province']] : []))]);
    }

    /** The inputs held to what is kept: an item, a place it has, a harvest month, an amount. */
    private function clean(array $in): ?array
    {
        $item = (string) ($in['item'] ?? '');
        if (! isset(MarketBook::items()[$item])) {
            return null;
        }
        $place = (string) ($in['place'] ?? 'PH');
        $place = preg_match('/^[a-z0-9_]{2,60}$/i', $place) ? $place : 'PH';
        $harvest = (string) ($in['harvest'] ?? '');
        $harvest = preg_match('/^(20\d\d)-(0[1-9]|1[0-2])$/', $harvest) ? $harvest : null;
        $qty = is_numeric($in['qty'] ?? null) ? max(0, min(10000000, (float) $in['qty'])) : null;
        // How the farmer counted it (2026-10-27): kilos, tons, cavans or cavans a hectare, kept to put the page back as it was.
        $n = fn ($k, float $hi) => is_numeric($in[$k] ?? null) && (float) $in[$k] > 0 ? min($hi, (float) $in[$k]) : null;

        return ['item' => $item, 'place' => $place, 'harvest' => $harvest, 'qty' => $qty ?: null,
            'qtyUnit' => in_array($in['qtyUnit'] ?? '', ['kg', 't', 'cav', 'cavha', 'unit'], true) ? $in['qtyUnit'] : 'kg',
            'qtyRaw' => $n('qtyRaw', 10000000), 'ha' => $n('ha', 100000), 'cavLo' => $n('cavLo', 150), 'cavHi' => $n('cavHi', 150)];
    }

    private function plan(array $o, array $c): ?array
    {
        return $c['harvest'] ? MarketBook::sellPlan($o, MarketBook::idx($c['harvest']), $c['qty']) : null;
    }

    /** The farmer's saved forecasts, newest first, twelve to a page. */
    private function shelf(int $page): array
    {
        $per = 12;
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'ready')->where('deleteStatus', 1)
            ->orderByDesc('id')->skip(($page - 1) * $per)->take($per + 1)->get(['id', 'title', 'params', 'report', 'created_at']);

        return [
            'history' => $rows->take($per)->map(function ($r) {
                $rep = json_decode((string) $r->report, true) ?: [];
                $o = $rep['outlook'] ?? [];
                $at = Carbon::parse($r->created_at)->timezone('Asia/Manila');

                return ['id' => (int) $r->id, 'title' => (string) $r->title, 'icon' => \App\Support\CropStages::icon($o['item']['crop'] ?? null),
                    'latest' => $o['latest']['p'] ?? null, 'next' => $o['years']['next']['avg'] ?? null, 'unit' => $o['item']['unit'] ?? 'kg',
                    'best' => $o['best'] ?? [], 'day' => $at->format('j'), 'month' => $at->format('M'), 'at' => $at->format('M j, Y g:i A')];
            })->values()->all(),
            'hasMore' => $rows->count() > $per,
            'page' => $page,
        ];
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
