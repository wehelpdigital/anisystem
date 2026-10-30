<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Manager\RangeEffectController;
use App\Models\AsProtocol;
use App\Models\AsProtocolVersion;
use App\Support\CropStages;
use App\Support\FarmLots;
use App\Support\FieldPack;
use App\Support\FieldProfile;
use App\Support\NpkPlan;
use App\Support\RangeEffect;
use App\Support\RangeEffectProtocol;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * The Coverage Planner in the Protocol Builder (2026-10-30, the owner: "this module should also be in the protocol builder
 * so that as you plan in the protocol builder, you can also open it and check if you are doing right (you can select the
 * lot where it will be applied to as a basis)"). The same sheet (sm.partials.range-effect, protocol mode) and engine,
 * on the protocol built in memory (RangeEffectProtocol): the lots offered are the farm's lots in Global Lots, whose soil
 * and place the reading takes, or a what-if lot; the days are a day zero (and a transplant) the farmer picks. The
 * product search and Anee's look up are the planner's own (sm.effect.products, sm.effect.lookup). Nothing is saved.
 */
class ProtocolEffectController extends Controller
{
    private function gate(): void
    {
        if (FieldPack::lockedHere()) {
            abort(response()->json(['success' => false, 'packLock' => true,
                'message' => 'The Coverage Planner comes with Libre + Anee and every plan above it. Upgrade to see how long every product you apply keeps working.'], 403));
        }
    }

    private function mine(int $id): AsProtocol
    {
        $p = AsProtocol::active()->where('userId', (int) Auth::id())->where('id', $id)->first();
        if (! $p) {
            abort(response()->json(['success' => false, 'message' => 'That protocol is gone.'], 404));
        }

        return $p;
    }

    /** The farm's lots as the planner's lots: each one's soil answers and place, on the protocol's crop and count. */
    private function lots(AsProtocol $p): array
    {
        $crop = CropStages::normalize($p->crop);
        $threats = RangeEffect::threatsFor($crop);
        $counter = strtoupper((string) $p->dayType) === 'DAP' ? 'DAP' : (strtoupper((string) $p->dayType) === 'DAT' ? 'DAT' : 'DAS');
        $base = ['crop' => CropStages::label($crop) ?: ((string) ($p->crop ?: 'Crop not set')), 'cropKey' => $crop, 'icon' => '🗺️', 'counter' => $counter, 'threats' => $threats, 'hasDay' => true];
        $out = [];
        foreach (FieldProfile::storeReady() ? FieldProfile::saved(WorkerContext::effectiveOwnerId(), 60) : [] as $f) {
            $out[] = $base + ['id' => (int) $f['id'], 'name' => (string) $f['name'], 'ha' => isset($f['areaHa']) && $f['areaHa'] ? (float) $f['areaHa'] : null,
                'place' => (string) ($f['place'] ?? ''), 'answers' => (object) ($f['answers'] ?? [])];
        }
        usort($out, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
        // No lot at all: the protocol on its own, its soil not counted until a lot or a what-if lot gives one.
        array_unshift($out, array_merge($base, ['id' => 0, 'icon' => (string) CropStages::icon($crop), 'name' => 'No lot', 'ha' => null, 'place' => '', 'answers' => new \stdClass]));

        return $out;
    }

    public function options(Request $request, int $id)
    {
        $this->gate();
        $p = $this->mine($id);
        $nutrients = [];
        foreach (RangeEffect::NUTRIENTS as $k => [$el, $name]) {
            $nutrients[] = ['key' => 'n:' . $k, 'short' => $k, 'name' => $name, 'micro' => in_array($k, RangeEffect::MICROS, true)];
        }
        $threats = [];
        foreach (RangeEffect::THREATS as $k => [$name, $icon]) {
            $threats[] = ['key' => 't:' . $k, 'name' => $name, 'icon' => $icon];
        }

        return response()->json(['success' => true, 'message' => 'ok', 'data' => [
            'lots' => $this->lots($p), 'nutrients' => $nutrients, 'threats' => $threats, 'water' => \App\Support\RangeEffectWater::options(), 'saved' => [],
            'lookup' => app(RangeEffectController::class)->lookupInfo(),
            'protocol' => ['id' => (int) $p->id, 'title' => (string) $p->title, 'dayType' => strtoupper((string) $p->dayType), 'readable' => RangeEffectProtocol::readable($p),
                'crop' => CropStages::label($p->crop) ?: (string) $p->crop],
        ]]);
    }

    public function run(Request $request, int $id)
    {
        $this->gate();
        $p = $this->mine($id);
        if (! RangeEffectProtocol::readable($p)) {
            return response()->json(['success' => false, 'message' => 'The Coverage Planner reads protocols of seasonal crops counted in DAS, DAT or DAP. A tree protocol counts its days another way.'], 422);
        }
        // The builder's own tasks and materials as they stand on the page (nothing here is written), else the version kept.
        $tasks = $request->input('tasks');
        $materials = $request->input('materials');
        if (! is_array($tasks)) {
            $ver = AsProtocolVersion::where('protocolId', $p->id)->where('id', (int) $p->versionId)->first()
                ?? AsProtocolVersion::where('protocolId', $p->id)->orderBy('sortOrder')->first();
            $tasks = (array) ($ver->tasks ?? []);
            $materials = (array) ($ver->materials ?? []);
        }
        $tasks = array_slice(array_values(array_filter($tasks, 'is_array')), 0, 600);
        $materials = array_slice(array_values(array_filter((array) $materials, 'is_array')), 0, 400);
        $day = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
        $start = $day($request->input('start')) ?? Carbon::now('Asia/Manila')->toDateString();
        $transplant = $day($request->input('transplant')) ?? Carbon::parse($start)->addDays(21)->toDateString();
        $ha = max(0.01, min(10000, (float) ($request->input('ha') ?: 1)));
        // The basis: a lot of Global Lots (its answers and place), or a what-if lot; neither, the soil is not counted.
        $custom = is_array($request->input('custom')) && $request->input('custom') ? RangeEffect::customOf($request->input('custom')) : null;
        $basisId = (int) $request->input('basis', 0);
        $farm = $basisId && FarmLots::ready() ? FarmLots::one(WorkerContext::effectiveOwnerId(), $basisId) : null;
        if (! $custom && $farm) {
            $custom = RangeEffect::customOf(['name' => (string) $farm['name'], 'place' => (string) ($farm['place'] ?? ''), 'answers' => (array) ($farm['answers'] ?? []), 'farm' => true]);
        }
        $name = $custom ? $custom['name'] : 'This protocol';
        $season = RangeEffectProtocol::season($p, $tasks, $materials, $start, $transplant, $ha, $name);
        $tries = app(RangeEffectController::class)->triesOf($request);
        try {
            $res = RangeEffect::run($season, 0, (string) $request->input('check', 'n:N'), $tries,
                array_values(array_filter(array_map(fn ($k) => mb_substr((string) $k, 0, 80), (array) $request->input('also', [])), fn ($k) => $k !== '')),
                array_intersect_key(array_map(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN), (array) $request->input('logic', [])), array_flip(\App\Support\RangeEffectLogic::OPTS)),
                $custom ?? ['name' => 'This protocol', 'place' => '', 'answers' => []]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $res['protocol'] = ['start' => $start, 'transplant' => strtoupper((string) $p->dayType) === 'DAT' ? $transplant : null, 'ha' => $ha, 'basis' => $farm ? (int) $farm['id'] : 0];

        return response()->json(['success' => true, 'message' => 'ok', 'data' => $res]);
    }
}
