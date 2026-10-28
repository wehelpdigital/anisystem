<?php

namespace App\Http\Controllers;

use App\Models\AsProtocol;
use App\Models\AsProtocolVersion;
use App\Support\CropStages;
use App\Support\HowToPlant;
use App\Support\Tier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * How to Plant in Quick Tools (2026-10-19, the owner): pick a crop and see
 * how traditional Filipino farmers plant it, step by step on a day count,
 * with the video where one exists and what to change for a higher yield;
 * then "Use this in my Protocol Builder" makes a new protocol draft of it.
 * Every plan (tier key howToPlant). No Anee, no credits. The guides are
 * App\Support\HowToPlant's.
 */
class HowToPlantController extends Controller
{
    public function page(Request $request)
    {
        $this->gate();
        $crops = HowToPlant::crops();
        $want = (string) $request->query('crop', '');
        $crop = HowToPlant::has($want) ? $want : (isset($crops['rice']) ? 'rice' : array_key_first($crops));
        $guide = $crop ? HowToPlant::guide($crop) : null;

        return view('how-to-plant.index', [
            'crops' => $crops,
            'crop' => $crop,
            'pick' => $guide['crop'] ?? $crop,
            'guide' => $guide,
            'way' => $guide ? HowToPlant::wayKey($guide, $crop, (string) $request->query('way', '')) : null,
        ]);
    }

    /** One crop's guide, drawn: the page swaps it in when another crop is picked. */
    public function guide(Request $request, string $crop)
    {
        $this->gate();
        $guide = HowToPlant::guide($crop);
        if (! $guide) {
            return $this->json(false, 'There is no guide for that crop yet.', [], 404);
        }
        $way = HowToPlant::wayKey($guide, $crop, (string) $request->query('way', ''));

        return $this->json(true, 'ok', [
            'crop' => $guide['crop'],
            'way' => $way,
            'name' => $guide['local'] ?: $guide['name'],
            'html' => view('how-to-plant.partials.guide', ['guide' => $guide, 'way' => $way])->render(),
        ]);
    }

    /**
     * "Use this in my Protocol Builder": a NEW protocol draft for the crop and
     * the way (never written into one the member has), each step a task on
     * its day, named "<Crop> (traditional way)", numbered when that is taken.
     */
    public function port(Request $request)
    {
        $this->gate();
        $this->builderGate();
        $v = Validator::make($request->all(), ['crop' => 'required|string|max:40', 'way' => 'nullable|string|max:40']);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        $guide = HowToPlant::guide((string) $request->input('crop'));
        if (! $guide) {
            return $this->json(false, 'There is no guide for that crop yet.', [], 404);
        }
        $way = HowToPlant::way($guide, HowToPlant::wayKey($guide, (string) $request->input('crop'), (string) $request->input('way', '')));
        if (! $way || empty($way['steps'])) {
            return $this->json(false, 'That way of planting has no steps yet.', [], 422);
        }
        $pbCrop = CropStages::normalize(HowToPlant::protocolCrop($guide, $way)) ?? CropStages::normalize((string) $guide['crop']);
        $dayType = ProtocolBuilderController::fitDayType($pbCrop, HowToPlant::wantDayType((string) ($pbCrop ?? $guide['crop']), $way));
        $counters = ProtocolBuilderController::DAY_TYPES[$dayType]['counters'] ?? ['DAS'];
        $tasks = HowToPlant::toProtocol($guide, $way, $dayType, $counters);

        $name = trim((string) $guide['name']);
        $base = count((array) $guide['ways']) > 1
            ? $name . ', ' . mb_strtolower(HowToPlant::wayShort($way)) . ' (traditional way)'
            : $name . ' (traditional way)';
        $taken = AsProtocol::active()->where('userId', (int) Auth::id())->where('title', 'like', $base . '%')->pluck('title')->flip();
        $title = $base;
        for ($n = 2; isset($taken[$title]) && $n < 200; $n++) {
            $title = $base . ' ' . $n;
        }
        $local = trim((string) ($guide['local'] ?? ''));
        $p = AsProtocol::create([
            'userId' => (int) Auth::id(),
            'title' => mb_substr($title, 0, 190),
            'description' => mb_substr('From How to Plant: how traditional farmers usually plant ' . ($local !== '' && $local !== $name ? mb_strtolower(mb_substr($local, 0, 1)) . mb_substr($local, 1) . ' (' . mb_strtolower($name) . ')' : mb_strtolower($name))
                . ', ' . mb_strtolower(HowToPlant::wayShort($way)) . '. It is a starting point: update it for a higher yield with certified seed, a soil test, the right spacing and fertilizer.', 0, 2000),
            'tags' => [],
            'crop' => $pbCrop,
            'variety' => null,
            'dayType' => $dayType,
            'tasks' => [],
            'history' => ['undo' => [], 'redo' => []],
            'rev' => 1,
            'deleteStatus' => 1,
        ]);
        $ver = AsProtocolVersion::create([
            'protocolId' => $p->id, 'userId' => (int) Auth::id(), 'name' => 'Traditional way', 'tasks' => $tasks, 'materials' => [],
            'rules' => null, 'files' => [], 'history' => ['undo' => [], 'redo' => []], 'rev' => 1, 'sortOrder' => 0, 'deleteStatus' => 1,
        ]);
        $p->forceFill(['versionId' => $ver->id])->save();
        $watering = count(array_filter($tasks, fn ($t) => ! isset($t['kind']) && preg_match('/_w\d+$/', (string) $t['id'])));
        $steps = count(array_filter($tasks, fn ($t) => ! isset($t['kind']))) - $watering;

        return $this->json(true, 'A new protocol was made with ' . $steps . ' steps' . ($watering ? ' and ' . $watering . ' watering ' . ($watering === 1 ? 'task' : 'tasks') : '') . '.', [
            'protocolId' => (int) $p->id, 'title' => $p->title, 'steps' => $steps, 'watering' => $watering, 'url' => route('pb.open', ['id' => $p->id]),
        ]);
    }

    /** The tool itself: Libre + Anee and up since 2026-10-29 (config tiers howToPlant). The public guides stay free. */
    private function gate(): void
    {
        if (! Tier::farmCan('howToPlant')) {
            Tier::farmDenyFor('howToPlant', 'How to Plant comes with {plan}. See how traditional farmers plant every crop, step by step.');
        }
    }

    /**
     * The Protocol Builder's own door: it is open on every plan today, so this
     * only speaks if a plan ever leaves it out (a protocolBuilder key set to
     * false), with the upgrade sheet the app already uses.
     */
    private function builderGate(): void
    {
        $plan = (array) config('tiers.' . Tier::ofFarm(), []);
        if (array_key_exists('protocolBuilder', $plan) && ! Tier::farmCan('protocolBuilder')) {
            Tier::farmDenyFor('protocolBuilder', 'The Protocol Builder comes with {plan}. Write your crop protocol once and bring it into every new season.');
        }
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
