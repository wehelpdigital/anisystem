<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use App\Models\User;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Services\FieldHealth;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\CropCatalog;
use App\Support\CropStages;
use App\Support\EnsoOutlook;
use App\Support\FieldContext;
use App\Support\StormTracks;
use App\Support\Tier;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satellite Weather (2026-10-07): the sky over the farm, the way Zoom Earth
 * shows it, and Anee's reading of it against one of the farmer's lots.
 *
 * Free to look at:
 *   - live cloud and rain tiles from OpenWeatherMap (through our server, so
 *     the key stays here), with an opacity slider;
 *   - the last three hours of Himawari-9 infrared cloud (NASA GIBS), and the
 *     last two hours of rain radar (RainViewer), to play back;
 *   - active typhoons (the field health service, or GDACS directly): the
 *     track, the cone of uncertainty, the eye now and the forecast points,
 *     with the distance to the farm and a warning inside 300 km;
 *   - the hourly forecast for the farm, to fast forward through.
 * Paid (AiPrices 'skyweather'): Anee reads all of it with the lot's crop,
 * age and stage, and says what to do before, during and after.
 */
class SkyWeatherController extends Controller
{
    public const KIND = 'skyweather';

    /** OpenWeatherMap tile layers we pass through. */
    public const LAYERS = ['clouds_new', 'precipitation_new', 'wind_new', 'temp_new', 'pressure_new'];

    public function __construct(private AiCreditService $credits, private AiClient $ai, private FieldHealth $field)
    {
    }

    public function page()
    {
        return view('sky-weather.index');
    }

    public function options()
    {
        $payer = $this->payer();
        $settings = AiSetting::current();
        $canUse = $payer->canUseAi() && $settings->isUsable() && Tier::farmCan('aiAnalyses');

        return $this->json(true, 'ok', [
            'mapsKey' => (string) config('services.google_maps.key'),
            'owm' => (string) config('services.openweather.key') !== '',
            'quote' => AiPrices::of(self::KIND),
            'balance' => round($this->credits->balance($payer->id), 2),
            'unlimited' => $this->credits->unlimited((int) $payer->id),
            'canUse' => $canUse,
            'whyNot' => $canUse ? null : (! Tier::farmCan('aiAnalyses') ? 'Anee\'s weather reading comes with ' . Tier::withPlan(Tier::farmUnlocksAt('aiAnalyses')) . '.' : Tier::aneeNeeds($payer)),
            'lots' => $this->lots(),
        ]);
    }

    /** The farmer's lots, with where they are and how old the crop is. */
    private function lots(): array
    {
        $owner = WorkerContext::effectiveOwnerId();
        $seasons = AsCroppingSchedule::active()->forClient($owner)->where('deleteStatus', 1)->orderByDesc('id')->limit(40)->get(['id', 'title', 'cropType', 'status']);
        if ($seasons->isEmpty()) {
            return [];
        }
        $lots = AsScheduleLot::whereIn('croppingScheduleId', $seasons->pluck('id'))->where('deleteStatus', 1)->orderBy('id')->limit(200)->get();
        $out = [];
        foreach ($lots as $lot) {
            $s = $seasons->firstWhere('id', $lot->croppingScheduleId);
            $f = $this->lotFacts($lot, $s);
            $out[] = [
                'id' => $lot->id, 'season' => $s?->title, 'name' => $lot->lotName, 'crop' => $f['crop'], 'stage' => $f['stage'], 'day' => $f['day'],
                'lat' => $lot->pinLat, 'lng' => $lot->pinLng, 'place' => trim(implode(', ', array_filter([$lot->locBarangay, $lot->locTown, $lot->locProvince]))),
            ];
        }

        return $out;
    }

    private function lotFacts(AsScheduleLot $lot, ?AsCroppingSchedule $s): array
    {
        $crop = $lot->crop ?: ($s?->cropType ?? null);
        $day = $lot->dayZeroDate ? (int) \Illuminate\Support\Carbon::parse($lot->dayZeroDate)->startOfDay()->diffInDays(now('Asia/Manila')->startOfDay(), false) + (int) ($lot->growthShiftDays ?? 0) : null;
        $stage = null;
        try {
            $stage = $day !== null && $day >= 0 ? CropStages::stageFor($crop, $day, $lot->dayType ?: null, $lot->maturityDays()) : null;
        } catch (\Throwable $e) {
            $stage = null;
        }

        return [
            'crop' => $crop ? CropCatalog::label($crop) : 'Crop not set',
            'variety' => $lot->variety,
            'day' => $day,
            'counter' => $lot->dayType ?: 'DAS',
            'stage' => $stage['label'] ?? null,
            'stageNeeds' => $stage['needs'] ?? null,
            'next' => $stage['next'] ?? null,
            'maturity' => $lot->maturityDays(),
            'size' => $lot->lotSize ? rtrim(rtrim((string) $lot->lotSize, '0'), '.') . ' ' . ($lot->lotSizeUnit ?: 'ha') : null,
        ];
    }

    /** OpenWeatherMap's tiles, through our server so the key stays here. */
    public function tile(string $layer, int $z, int $x, int $y)
    {
        $key = (string) config('services.openweather.key');
        abort_unless($key !== '' && in_array($layer, self::LAYERS, true) && $z >= 0 && $z <= 12, 404);
        $png = Cache::remember("owm:$layer:$z:$x:$y", 600, function () use ($layer, $z, $x, $y, $key) {
            try {
                $res = Http::timeout(10)->get("https://tile.openweathermap.org/map/$layer/$z/$x/$y.png", ['appid' => $key]);

                return $res->ok() ? base64_encode($res->body()) : '';
            } catch (\Throwable $e) {
                return '';
            }
        });
        abort_if($png === '', 404);

        return response(base64_decode($png), 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=600']);
    }

    /**
     * The frames to play back: Himawari-9 infrared every 10 minutes for the
     * last three hours (NASA GIBS), and RainViewer's rain radar.
     */
    public function frames()
    {
        $data = Cache::remember('sky:frames', 300, function () {
            $him = [];
            $latest = $this->himawariLatest();
            if ($latest) {
                for ($k = 17; $k >= 0; $k--) {
                    $t = $latest->copy()->subMinutes(10 * $k);
                    $him[] = ['utc' => $t->format('Y-m-d\TH:i:s\Z'), 'ph' => $t->copy()->timezone('Asia/Manila')->format('M j, g:i A'),
                        'url' => 'https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/Himawari_AHI_Band13_Clean_Infrared/default/' . $t->format('Y-m-d\TH:i:s\Z') . '/GoogleMapsCompatible_Level6/{z}/{y}/{x}.png'];
                }
            }
            $radar = [];
            try {
                $rv = Http::timeout(10)->get('https://api.rainviewer.com/public/weather-maps.json')->json();
                foreach ((array) ($rv['radar']['past'] ?? []) as $f) {
                    $t = \Illuminate\Support\Carbon::createFromTimestampUTC((int) $f['time']);
                    $radar[] = ['utc' => $t->format('Y-m-d\TH:i:s\Z'), 'ph' => $t->copy()->timezone('Asia/Manila')->format('M j, g:i A'),
                        'url' => rtrim((string) ($rv['host'] ?? 'https://tilecache.rainviewer.com'), '/') . $f['path'] . '/256/{z}/{x}/{y}/2/1_1.png'];
                }
            } catch (\Throwable $e) {
            }

            return ['clouds' => $him, 'radar' => $radar];
        });

        return $this->json(true, 'ok', $data);
    }

    /** The newest Himawari picture GIBS has: tried from 20 minutes ago backwards. */
    private function himawariLatest(): ?\Illuminate\Support\Carbon
    {
        $now = now('UTC');
        $t = $now->copy()->minute((int) (floor($now->minute / 10) * 10))->second(0)->subMinutes(20);
        for ($i = 0; $i < 12; $i++, $t->subMinutes(10)) {
            try {
                $res = Http::timeout(8)->get('https://gibs.earthdata.nasa.gov/wmts/epsg3857/best/Himawari_AHI_Band13_Clean_Infrared/default/'
                    . $t->format('Y-m-d\TH:i:s\Z') . '/GoogleMapsCompatible_Level6/3/3/6.png');
                if ($res->ok() && strlen($res->body()) > 1200) {
                    return $t->copy();
                }
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    /** Active typhoons near a point: the service when connected, GDACS directly when not. */
    public function storms(Request $request)
    {
        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        return $this->json(true, 'ok', $this->stormsNear($lat, $lng));
    }

    private function stormsNear(?float $lat, ?float $lng): array
    {
        if ($this->field->configured()) {
            $res = $this->field->storms($lat, $lng);
            if ($res['ok']) {
                return $res['data'];
            }
        }

        return StormTracks::near($lat, $lng);
    }

    /** The farm's hourly forecast for five days and daily for ten. */
    public function forecast(Request $request)
    {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        abort_unless($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && ($lat || $lng), 422);

        return $this->json(true, 'ok', $this->hourly($lat, $lng));
    }

    private function hourly(float $lat, float $lng): array
    {
        return Cache::remember(sprintf('sky:fc:%.2f,%.2f', $lat, $lng), 1800, function () use ($lat, $lng) {
            try {
                $r = Http::timeout(15)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $lat, 'longitude' => $lng, 'timezone' => 'Asia/Manila', 'forecast_days' => 10,
                    'hourly' => 'precipitation,precipitation_probability,cloud_cover,wind_speed_10m,wind_gusts_10m,temperature_2m',
                    'daily' => 'weather_code,precipitation_sum,precipitation_probability_max,wind_gusts_10m_max,temperature_2m_max,temperature_2m_min',
                ])->json();
            } catch (\Throwable $e) {
                return ['hours' => [], 'days' => []];
            }
            $h = (array) ($r['hourly'] ?? []);
            $hours = [];
            foreach (array_slice((array) ($h['time'] ?? []), 0, 120) as $i => $t) {
                $hours[] = ['t' => $t, 'rain' => $h['precipitation'][$i] ?? null, 'pop' => $h['precipitation_probability'][$i] ?? null, 'cloud' => $h['cloud_cover'][$i] ?? null,
                    'wind' => $h['wind_speed_10m'][$i] ?? null, 'gust' => $h['wind_gusts_10m'][$i] ?? null, 'temp' => $h['temperature_2m'][$i] ?? null];
            }
            $d = (array) ($r['daily'] ?? []);
            $days = [];
            foreach ((array) ($d['time'] ?? []) as $i => $t) {
                $days[] = ['date' => $t, 'rain' => $d['precipitation_sum'][$i] ?? null, 'pop' => $d['precipitation_probability_max'][$i] ?? null,
                    'gust' => $d['wind_gusts_10m_max'][$i] ?? null, 'tmax' => $d['temperature_2m_max'][$i] ?? null, 'tmin' => $d['temperature_2m_min'][$i] ?? null, 'code' => $d['weather_code'][$i] ?? null];
            }

            return ['hours' => $hours, 'days' => $days];
        });
    }

    /* ------------------------------------------------------------ Anee */

    public function generate(Request $request)
    {
        if (! Tier::farmCan('aiAnalyses')) {
            Tier::farmDenyFor('aiAnalyses', 'Anee\'s weather reading comes with {plan}, and with every plan above it.');
        }
        $payer = $this->payer();
        $settings = AiSetting::current();
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->json(false, $payer->canUseAi() ? 'Anee is not switched on yet. Please check back soon.' : Tier::aneeNeeds($payer, 'The weather reading'), [], 403);
        }
        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        if ($lat < 3 || $lat > 23 || $lng < 114 || $lng > 129) {
            return $this->json(false, 'Pick a place in the Philippines first.', [], 422);
        }
        $lot = null;
        $lotFacts = null;
        if ($request->filled('lotId')) {
            $lot = AsScheduleLot::where('deleteStatus', 1)->find((int) $request->input('lotId'));
            $season = $lot ? AsCroppingSchedule::active()->forClient(WorkerContext::effectiveOwnerId())->find($lot->croppingScheduleId) : null;
            if (! $lot || ! $season) {
                return $this->json(false, 'That lot is not on your farm.', [], 404);
            }
            $lotFacts = $this->lotFacts($lot, $season) + ['name' => $lot->lotName, 'season' => $season->title];
        }
        $price = (float) AiPrices::of(self::KIND);
        if ($this->credits->balance($payer->id) < $price && ! $this->credits->unlimited($payer->id)) {
            return $this->json(false, 'You need ' . ceil($price) . ' credits for this reading and have ' . number_format((int) floor($this->credits->balance($payer->id))) . '.', ['outOfCredits' => true], 402);
        }
        $standing = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('status', 'pending')
            ->where('deleteStatus', 1)->where('created_at', '>', now()->subMinutes(10))->orderByDesc('id')->first();
        if ($standing) {
            return $this->json(true, 'Already working on it.', ['pending' => true, 'id' => $standing->id]);
        }
        $p = ['lat' => round($lat, 5), 'lng' => round($lng, 5), 'place' => mb_substr(trim((string) $request->input('place', '')), 0, 160), 'lotId' => $lot?->id, 'lot' => $lotFacts];
        $title = 'Weather · ' . ($p['place'] ?: 'my farm') . ($lotFacts ? ' · ' . $lotFacts['name'] : '') . ' · ' . now('Asia/Manila')->format('M j, g:i A');
        $id = DB::table('as_plant_analyses')->insertGetId([
            'userId' => Auth::id(), 'kind' => self::KIND, 'title' => mb_substr($title, 0, 190), 'params' => json_encode($p),
            'report' => json_encode(new \stdClass), 'credits' => 0, 'status' => 'pending', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => ['pending' => true, 'id' => $id]])->send();
            fastcgi_finish_request();
            $this->runJob($id, (int) $payer->id, $settings, $p);
            exit;
        }
        @set_time_limit(600);
        $this->runJob($id, (int) $payer->id, $settings, $p);

        return $this->jobState($id);
    }

    private function runJob(int $id, int $payerId, AiSetting $settings, array $p): void
    {
        $beat = function (string $phase, int $try = 1) use ($id): void {
            DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['report' => json_encode(['phase' => $phase, 'try' => $try]), 'updated_at' => now()]);
        };
        try {
            $beat('context');
            $storms = $this->stormsNear($p['lat'], $p['lng']);
            $fc = $this->hourly($p['lat'], $p['lng']);
            $ctx = ['storms' => $storms, 'forecast' => $fc, 'climate' => FieldContext::climate($p['lat'], $p['lng']), 'enso' => EnsoOutlook::forPrompt()];
            $settings = $settings->forField('PH');
            $result = $this->ai->askForJson($settings, $this->prompt($p, $ctx), 6000, fn (string $t) => $this->parse($t), ['onPhase' => $beat]);
            $anee = $result['data'] ?? null;
            if ($anee === null) {
                Log::warning('skyweather: unparsable answer', ['head' => mb_substr((string) ($result['text'] ?? ''), 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The reading could not be read. Nothing was charged. Please try again.');
            }
            $report = ['format' => 1, 'anee' => $anee, 'storms' => $storms, 'days' => $fc['days'] ?? [], 'enso' => $ctx['enso'], 'at' => now('Asia/Manila')->format('M j, Y g:i A')];
            $row = DB::table('as_plant_analyses')->where('id', $id)->first();
            $charged = (float) AiPrices::of(self::KIND);
            $note = AiUsage::record(self::KIND, (int) $row->userId, $payerId, $id, $settings, $result, (int) $charged);
            $this->credits->chargeAllowingNegative($payerId, $charged, mb_substr('Satellite Weather reading: ' . ($p['place'] ?: 'farm') . $note, 0, 250));
            DB::table('as_plant_analyses')->where('id', $id)->update(['report' => json_encode($report), 'credits' => round($charged, 2), 'status' => 'ready', 'error' => null, 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('as_plant_analyses')->where('id', $id)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
        }
    }

    private function prompt(array $p, array $ctx): string
    {
        $storms = collect($ctx['storms']['storms'] ?? [])->take(4)->map(fn ($s) => [
            'name' => $s['name'] ?? null, 'current' => $s['current'] ?? null, 'alert' => $s['alert'] ?? null, 'maxWindKmh' => $s['maxWindKmh'] ?? null,
            'eyeNow' => $s['eye'] ?? null, 'distanceKmNow' => $s['distanceKm'] ?? null, 'closestApproach' => $s['closest'] ?? null,
            'forecastPoints' => collect($s['points'] ?? [])->where('forecast', true)->map(fn ($q) => ['ph' => $q['ph'], 'lat' => $q['lat'], 'lng' => $q['lng'], 'cat' => $q['cat']])->values(),
        ])->values();
        $days = collect($ctx['forecast']['days'] ?? [])->map(fn ($d) => $d['date'] . ': rain ' . ($d['rain'] ?? '?') . ' mm (' . ($d['pop'] ?? '?') . '%), gusts ' . ($d['gust'] ?? '?') . ' km/h, ' . ($d['tmin'] ?? '?') . '-' . ($d['tmax'] ?? '?') . 'C')->implode("\n");
        $lot = $p['lot'] ? json_encode($p['lot'], JSON_UNESCAPED_UNICODE) : 'No lot chosen: speak to a general farm here.';

        return "You are Anee, a smart farm technician for Filipino farmers. Read the sky over this farm and say what it means for the crop. Return ONE JSON object, nothing else.\n\n"
            . 'Farm location: ' . ($p['place'] ?: 'not named') . ' (' . $p['lat'] . ', ' . $p['lng'] . "), Philippines\n"
            . 'Today (Philippine time): ' . now('Asia/Manila')->format('l, F j, Y g:i A') . "\n"
            . "THE LOT TO CHECK AGAINST: " . $lot . "\n\n"
            . "ACTIVE TROPICAL CYCLONES (GDACS; distances are from the farm, by the Haversine formula; times are Philippine time):\n" . json_encode($storms, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n"
            . "FORECAST FOR THE FARM, DAY BY DAY (Open-Meteo):\n" . $days . "\n\n"
            . "THE SAME WEEKS IN THE LAST FIVE YEARS:\n" . json_encode($ctx['climate']) . "\n\n"
            . 'ENSO: ' . ($ctx['enso'] ?: 'not available') . "\n\n"
            . "RULES: Within 300 km a storm is a direct threat; say when its closest approach is, how strong, and the wind and rain to expect. If no storm is near, say so plainly and focus on rain, wind and heat. Tie every action to the lot's crop and stage (for example: harvest early if the grain is mature enough, drain or open the canals, delay fertilizer before heavy rain, prop or hill up tall corn, secure seedlings, protect the harvest in storage). Use PAGASA wind signal language only as a guide, never claim PAGASA issued a signal. Never invent a number not given above. Say what you cannot know.\n\n"
            . 'Return exactly: {"headline":"one line","risk":"low|moderate|high|severe","riskWhy":"",'
            . '"storm":{"status":"none near|watch|warning","reading":"","closestApproach":"when and how close","expect":"wind and rain to expect at the farm"},'
            . '"next72h":"plain words","next10Days":"plain words",'
            . '"forLot":{"reading":"what this weather means for this crop at this stage","risks":["",""],"opportunities":["e.g. a dry window to spray or harvest"]},'
            . '"actions":[{"when":"now|before the storm|during|after|this week","what":"","why":""}],'
            . '"climate":"ENSO and what these weeks usually bring","confidence":"low|medium|high","watch":"what to keep checking"}';
    }

    private function parse(string $text): ?array
    {
        $t = preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? $text;
        $a = strpos($t, '{');
        $b = strrpos($t, '}');
        if ($a === false || $b === false) {
            return null;
        }
        $d = json_decode(substr($t, $a, $b - $a + 1), true);

        return is_array($d) && isset($d['headline']) ? $d : null;
    }

    public function jobState(int $id)
    {
        $r = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->json(false, 'That reading is gone.', [], 404);
        }
        if ($r->status === 'pending') {
            $beatAt = \Illuminate\Support\Carbon::parse($r->updated_at ?: $r->created_at);
            if ($beatAt->lt(now()->subSeconds(AiClient::TIMEOUT_DOCUMENT + 60)) || \Illuminate\Support\Carbon::parse($r->created_at)->lt(now()->subMinutes(15))) {
                $why = 'The reading was stopped halfway. Nothing was charged. Please run it again.';
                DB::table('as_plant_analyses')->where('id', $id)->where('status', 'pending')->update(['status' => 'failed', 'deleteStatus' => 0, 'error' => $why, 'updated_at' => now()]);

                return $this->json(false, $why, ['status' => 'failed'], 502);
            }
            $beat = json_decode((string) $r->report, true) ?: [];

            return $this->json(true, 'Working…', ['pending' => true, 'id' => (int) $r->id, 'status' => 'pending', 'phase' => (string) ($beat['phase'] ?? 'start'),
                'try' => (int) ($beat['try'] ?? 1), 'beatAgo' => (int) max(0, now()->diffInSeconds($beatAt, true))]);
        }
        if ($r->status === 'failed') {
            DB::table('as_plant_analyses')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

            return $this->json(false, $r->error ?: 'The reading failed and nothing was charged. Please try again.', ['status' => 'failed'], 502);
        }

        return $this->json(true, 'Ready.', ['status' => 'ready', 'savedId' => (int) $r->id, 'title' => $r->title, 'report' => json_decode($r->report, true),
            'params' => json_decode($r->params, true), 'charged' => (float) $r->credits, 'balance' => round($this->credits->balance($this->payer()->id), 2)]);
    }

    public function list(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));
        $rows = DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('deleteStatus', 1)->where('status', 'ready')
            ->orderByDesc('id')->skip(($page - 1) * 20)->take(21)->get(['id', 'title', 'report', 'created_at']);

        return $this->json(true, 'ok', ['hasMore' => $rows->count() > 20, 'rows' => $rows->take(20)->map(function ($r) {
            $rep = json_decode((string) $r->report, true) ?: [];

            return ['id' => $r->id, 'title' => $r->title, 'risk' => $rep['anee']['risk'] ?? null, 'headline' => $rep['anee']['headline'] ?? '',
                'at' => \Illuminate\Support\Carbon::parse($r->created_at)->timezone('Asia/Manila')->format('M j, g:i A')];
        })->values()]);
    }

    public function one(int $id)
    {
        return $this->jobState($id);
    }

    public function destroy(int $id)
    {
        DB::table('as_plant_analyses')->where('userId', Auth::id())->where('kind', self::KIND)->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->json(true, 'Reading deleted.');
    }

    private function payer(): User
    {
        $payerId = WorkerContext::effectiveOwnerId();

        return $payerId === (int) Auth::id() ? Auth::user() : (User::find($payerId) ?? Auth::user());
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
