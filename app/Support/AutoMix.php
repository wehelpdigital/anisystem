<?php

namespace App\Support;

use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsScheduleLot;

/**
 * The sprays of an Anee Auto season, checked for the tank (2026-10-28, the owner: "in the anee auto, you can also do
 * mixing sprays that are compatible to save labor as long as it is spot on the schedule and compatible and safe to
 * spray").
 *
 * Anee's calendar may already put two products in one spray (AutoOptions' COMBINED SPRAYS rule); here the app checks
 * every such tank with the Compatibility Checker's own engine (CompatCheck, its rules and the product book) and says
 * what it found on the task: mixes fine, a jar test first, or never in one tank (then the trips it splits into). And
 * when two sprays of one lot fall on the same day, it checks them together and, when they may share a tank, says so
 * on both, one trip instead of two. Every product is read by its name in the book (an active ingredient finds a
 * registered product of it); a product the book cannot read leaves the mix unchecked and says a jar test first.
 * Runs after every placing of the days (the draft, the approval, each morning).
 */
final class AutoMix
{
    private const SPRAYS = ['herbicide', 'insecticide', 'fungicide', 'foliar'];

    /** @return int how many tasks it said something on */
    public static function audit(AsAutoSeason $auto): int
    {
        if (! AutoOptions::of((array) $auto->answers)['mixing'] || ! ProductLibrary::ready()) {
            return 0;
        }
        $tasks = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')->whereIn('kind', self::SPRAYS)->get();
        if ($tasks->isEmpty()) {
            return 0;
        }
        $crops = AsScheduleLot::whereIn('id', $tasks->pluck('lotId')->filter()->unique())->pluck('crop', 'id');
        $said = 0;
        foreach ($tasks as $t) {
            $d = (array) $t->detail;
            unset($d['mix'], $d['mixWith']);
            $products = array_values(array_filter((array) ($d['products'] ?? []), fn ($p) => trim((string) ($p['name'] ?? '')) !== ''));
            if (count($products) >= 2) {
                $r = self::check($products, (string) ($crops[$t->lotId] ?? ''));
                if ($r) {
                    $d['mix'] = $r;
                    $said++;
                }
            }
            $t->detail = $d;
        }
        // Two sprays of one lot on the same day: one trip when they may share a tank.
        foreach ($tasks->groupBy(fn ($t) => $t->lotId . '|' . $t->plannedDate?->toDateString()) as $group) {
            if ($group->count() < 2 || ! $group->first()->plannedDate) {
                continue;
            }
            $all = $group->flatMap(fn ($t) => (array) (((array) $t->detail)['products'] ?? []))->values()->all();
            $r = $all ? self::check($all, (string) ($crops[$group->first()->lotId] ?? '')) : null;
            if ($r && in_array($r['verdict'], ['ok', 'care'], true)) {
                foreach ($group as $t) {
                    $d = (array) $t->detail;
                    $d['mixWith'] = ['tasks' => $group->where('id', '!=', $t->id)->map(fn ($o) => ['id' => (int) $o->id, 'title' => $o->title])->values()->all(),
                        'verdict' => $r['verdict'], 'say' => $r['verdict'] === 'ok' ? 'These sprays may share one tank: one trip instead of ' . $group->count() . '.' : 'These sprays may share one tank after a jar test: one trip instead of ' . $group->count() . '.',
                        'order' => $r['order']];
                    $t->detail = $d;
                    $said++;
                }
            }
        }
        foreach ($tasks as $t) {
            if ($t->isDirty('detail')) {
                $t->save();
            }
        }

        return $said;
    }

    /**
     * One tank of products checked: verdict (ok, care, no, unknown), the headline in words, the order to add them and,
     * when they must not share a tank, the trips they split into (by product name).
     */
    public static function check(array $products, string $crop): ?array
    {
        $items = [];
        $names = [];
        foreach (array_values($products) as $i => $p) {
            $name = trim((string) ($p['name'] ?? ''));
            $found = ProductLibrary::find($name) ?? (ProductLibrary::search($name, null, $crop ?: null, 1)[0] ?? null);
            $items[] = ['product' => $found ?: ['b' => $name], 'applied' => 'spray', 'amount' => null, 'unit' => null, 'per' => null, 'name' => $name, 'ref' => 'p' . $i];
            $names[$i] = $name;
        }
        try {
            $r = (new CompatCheck(['mode' => 'spray', 'crop' => $crop ?: null]))->run($items);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        // The order to fill the tank, as the checker says it: each step with the products that go in at it.
        $order = array_values(array_filter(array_map(fn ($o) => is_array($o) ? trim((string) ($o['say'] ?? '')) : (string) $o, (array) ($r['order'] ?? []))));
        $trips = array_map(fn ($trip) => array_map(fn ($i) => $names[$i] ?? '', (array) $trip), (array) ($r['trips'] ?? []));

        return ['verdict' => $r['verdict'], 'headline' => trim((string) ($r['headline']['t'] ?? '') . '. ' . (string) ($r['headline']['s'] ?? ''), '. '), 'order' => $order, 'trips' => count($trips) > 1 ? $trips : [],
            'say' => match ($r['verdict']) {
                'ok' => 'Checked: these mix fine in one tank.',
                'care' => 'Checked: a jar test first, then they may share a tank.',
                'no' => 'Checked: do not put these in one tank.' . (count($trips) > 1 ? ' Spray them on separate trips: ' . implode(', then ', array_map(fn ($t) => implode(' with ', $t), $trips)) . '.' : ''),
                default => 'Not every product could be checked: do a jar test first.',
            }];
    }
}
