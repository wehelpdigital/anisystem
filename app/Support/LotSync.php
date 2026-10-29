<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A season's lots and the farm's lots, kept in step (2026-10-30, the owner: "not all my lots is in the lots in 2026
 * cropping schedule whereas it should be synced including the answers. please sync it").
 *
 * A season lot is one of the farm's lots (Global Lots, a row of as_field_profiles) in that season. Each season lot is
 * linked to its farm lot (as_schedule_lots.farmLotId; before that column is in, by its name), and a season lot with
 * no farm lot yet gets one made from its name, place and size. The field answers live in the farm lot, once: every
 * reading of a season lot (Range Effect, the NPK season check) takes them from there. A season lot that has answers
 * of its own (an older profile kept by the season lot) hands them to a farm lot that has none.
 *
 * Farm lots not in a season are offered to it ("Add from your lots"), never put in by themselves: a season lot gets a
 * share of every task that names no lot, so adding one changes the season's sums.
 */
final class LotSync
{
    /** Whether the link column is in (its migration runs on deploy). */
    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            try {
                $known = Cache::get('lot-sync:column');
                if ($known === null) {
                    $known = Schema::hasColumn('as_schedule_lots', 'farmLotId') ? 1 : 0;
                    Cache::put('lot-sync:column', $known, $known ? 86400 : 60);
                }
                $ready = (int) $known === 1;
            } catch (\Throwable) {
                $ready = false;
            }
        }

        return $ready;
    }

    /** The anee user whose farm lots a season's lots belong to. */
    public static function ownerOf(AsCroppingSchedule $s): int
    {
        return (int) ($s->anisystemUserId ?: 0);
    }

    /**
     * A name that says nothing of the ground ("Lot 1", "Field 2", the names Anee Auto gives): such a lot is never
     * made into a farm lot by itself, nor matched to one by name, since every season has a "Lot 1". Its card offers
     * to add it instead.
     */
    public static function generic(?string $name): bool
    {
        return (bool) preg_match('/^(lot|lote|field|plot|parcel|area)\s*(no\.?\s*)?[#\d]+[a-z]?$/iu', trim((string) $name)) || trim((string) $name) === '';
    }

    /** A name as two names are compared: "Masin 1" and " masin  1 " are one lot. */
    private static function key(?string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower((string) $name)));
    }

    /** An owner's farm lots, newest first (rows as FieldProfile::row gives them). */
    private static function farmLots(int $ownerId): array
    {
        if ($ownerId <= 0 || ! FarmLots::ready()) {
            return [];
        }

        return DB::table('as_field_profiles')->where('userId', $ownerId)->where('deleteStatus', 1)
            ->orderByDesc('updated_at')->orderByDesc('id')->get()->map(fn ($r) => FieldProfile::row($r))->all();
    }

    /** The farm lot a season lot is: its link, else the owner's farm lot keyed to it, else one of the same name. */
    public static function farmLotOf(AsScheduleLot $lot, array $farm): ?array
    {
        $id = self::ready() ? (int) ($lot->farmLotId ?? 0) : 0;
        foreach ($farm as $f) {
            if ($id && (int) $f['id'] === $id) {
                return $f;
            }
        }
        foreach ($farm as $f) {
            if ((int) ($f['lotId'] ?? 0) === (int) $lot->id) {
                return $f;
            }
        }
        $k = self::key($lot->lotName);
        if ($k === '') {
            return null;
        }
        $same = array_values(array_filter($farm, fn ($f) => self::key($f['name'] ?? '') === $k));
        // A lot no season lot holds first (Global Lots' own), then any.
        usort($same, fn ($a, $b) => (int) ! empty($a['lotId']) <=> (int) ! empty($b['lotId']));
        if ($same) {
            return $same[0];
        }
        // "Lot 1" in the season and "Lot 1, north paddies" in Global Lots: the farm lot's name goes on with a comma,
        // a dash or a bracket. Only when one lot fits.
        $longer = array_values(array_filter($farm, fn ($f) => preg_match('/^' . preg_quote($k, '/') . '\s*[,(\-–—:]/u', self::key($f['name'] ?? '')) === 1));

        return count($longer) === 1 ? $longer[0] : null;
    }

    /** A season lot's size in the farm lot's terms: hectares and the unit it was given in. */
    private static function size(AsScheduleLot $lot): array
    {
        $ha = NpkPlan::hectares($lot->lotSize, $lot->lotSizeUnit);
        $u = mb_strtolower((string) $lot->lotSizeUnit);
        $unit = str_contains($u, 'sq') || str_contains($u, 'meter') || $u === 'm2' ? 'sqm' : (str_contains($u, 'acre') ? 'acre' : 'ha');

        return [$ha && $ha > 0 ? $ha : null, $unit];
    }

    /**
     * Every lot of a season with its farm lot: linked, made where missing, and given the place and size the farm
     * lot lacked. Returns [seasonLotId => farm lot row]. Best effort: a database without the columns does nothing.
     */
    public static function season(AsCroppingSchedule $s): array
    {
        $owner = self::ownerOf($s);
        if ($owner <= 0 || ! FarmLots::ready()) {
            return [];
        }
        $out = [];
        try {
            $farm = self::farmLots($owner);
            foreach ($s->lots()->where('deleteStatus', 1)->orderBy('id')->get() as $lot) {
                $f = self::farmLotOf($lot, $farm);
                if (! $f && self::generic($lot->lotName)) {
                    continue;
                }
                $f = self::fill($owner, $lot, $f);
                if (! $f) {
                    continue;
                }
                if (! array_filter($farm, fn ($x) => (int) $x['id'] === (int) $f['id'])) {
                    array_unshift($farm, $f);
                }
                if (self::ready() && (int) ($lot->farmLotId ?? 0) !== (int) $f['id']) {
                    DB::table('as_schedule_lots')->where('id', $lot->id)->update(['farmLotId' => (int) $f['id']]);
                }
                $out[(int) $lot->id] = $f;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $out;
    }

    /**
     * A season lot's farm lot made when missing, or given what it lacks: the place and size from the season lot, and
     * the season lot's own answers when the farm lot has none.
     */
    private static function fill(int $owner, AsScheduleLot $lot, ?array $f): ?array
    {
        [$ha, $unit] = self::size($lot);
        $place = ['province' => $lot->locProvince ?: null, 'town' => $lot->locTown ?: null, 'barangay' => $lot->locBarangay ?: null];
        if (! $f) {
            $data = ['name' => (string) ($lot->lotName ?: 'Lot ' . $lot->id)] + array_filter($place) + ($ha ? ['areaHa' => $ha, 'areaUnit' => $unit] : []);

            return FarmLots::save($owner, $data + self::ownAnswers($lot));
        }
        $data = [];
        foreach ($place as $k => $v) {
            if ($v && empty($f[$k])) {
                $data[$k] = $v;
            }
        }
        if ($ha && empty($f['areaHa'])) {
            $data['areaHa'] = $ha;
            $data['areaUnit'] = $unit;
        }
        if (empty($f['answers'])) {
            $data += self::ownAnswers($lot, (int) $f['id']);
        }
        // And the other way: a season lot with no place takes its farm lot's.
        if (! array_filter($place) && array_filter([$f['province'] ?? null, $f['town'] ?? null, $f['barangay'] ?? null])) {
            $lot->forceFill(['locProvince' => $f['province'] ?? null, 'locTown' => $f['town'] ?? null, 'locBarangay' => $f['barangay'] ?? null])->save();
        }

        return $data ? (FarmLots::save($owner, $data, (int) $f['id']) ?? $f) : $f;
    }

    /** Answers a season lot kept on a profile row of its own (an older build), for a farm lot that has none. */
    private static function ownAnswers(AsScheduleLot $lot, int $except = 0): array
    {
        $r = DB::table('as_field_profiles')->where('lotId', $lot->id)->where('deleteStatus', 1)->where('id', '!=', $except)
            ->orderByDesc('updated_at')->first(['answers']);
        $a = $r ? (array) (json_decode((string) $r->answers, true) ?: []) : [];

        return $a ? ['answers' => $a] : [];
    }

    /**
     * The field answers a season lot reads: its farm lot's (linked, keyed to it, or of the same name), else a
     * profile the season lot kept itself. [] when nothing is known.
     */
    public static function answersFor(AsCroppingSchedule $s, AsScheduleLot $lot): array
    {
        try {
            $f = self::farmLotOf($lot, self::farmLots(self::ownerOf($s)));
            if ($f && ! empty($f['answers'])) {
                return FieldProfile::normalize((array) $f['answers']);
            }
            $own = self::ownAnswers($lot);

            return $own ? FieldProfile::normalize($own['answers']) : [];
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** The farm lots not in a season yet (by link or name), for "Add from your lots". */
    public static function notIn(AsCroppingSchedule $s): array
    {
        $farm = self::farmLots(self::ownerOf($s));
        $used = [];
        foreach ($s->lots()->where('deleteStatus', 1)->get() as $lot) {
            if ($f = self::farmLotOf($lot, $farm)) {
                $used[(int) $f['id']] = true;
            }
        }

        return array_values(array_filter($farm, fn ($f) => ! isset($used[(int) $f['id']])));
    }

    /**
     * Farm lots put into a season as new season lots: name, size and place from the farm lot, linked to it. The
     * crop and its day count are the season's own (its crop type, the counter it already uses). Returns the new lots.
     */
    public static function addToSeason(AsCroppingSchedule $s, array $farmIds, ?string $crop = null, ?string $dayType = null): array
    {
        $farm = self::farmLots(self::ownerOf($s));
        $by = [];
        foreach ($farm as $f) {
            $by[(int) $f['id']] = $f;
        }
        $made = [];
        foreach (array_unique(array_map('intval', $farmIds)) as $id) {
            $f = $by[$id] ?? null;
            if (! $f) {
                continue;
            }
            $unit = (string) ($f['areaUnit'] ?? 'ha');
            $ha = (float) ($f['areaHa'] ?? 0);
            $size = $ha > 0 ? ($unit === 'sqm' ? round($ha * 10000, 2) : ($unit === 'acre' ? round($ha / 0.40468564, 4) : round($ha, 4))) : 0;
            $row = [
                'croppingScheduleId' => $s->id, 'lotName' => (string) ($f['name'] ?? 'Lot'), 'lotSize' => $size,
                'lotSizeUnit' => $unit === 'sqm' ? 'sqm' : ($unit === 'acre' ? 'acre' : 'hectare'),
                'locBarangay' => $f['barangay'] ?? null, 'locTown' => $f['town'] ?? null, 'locProvince' => $f['province'] ?? null,
                'crop' => $crop, 'dayType' => $dayType ?: 'DAT', 'deleteStatus' => 1,
            ];
            if (self::ready()) {
                $row['farmLotId'] = (int) $f['id'];
            }
            $made[] = AsScheduleLot::create($row);
        }

        return $made;
    }

    /**
     * After a season lot is added or changed: it keeps (or gets) its farm lot, which takes the place and size it
     * lacked. $force: made even under a name like "Lot 1" (the farmer asked for it from the card).
     */
    public static function afterSave(AsCroppingSchedule $s, AsScheduleLot $lot, bool $force = false): ?array
    {
        $owner = self::ownerOf($s);
        if ($owner <= 0 || ! FarmLots::ready()) {
            return null;
        }
        try {
            $f = self::farmLotOf($lot, self::farmLots($owner));
            if (! $f && ! $force && self::generic($lot->lotName)) {
                return null;
            }
            $f = self::fill($owner, $lot, $f);
            if ($f && self::ready() && (int) ($lot->farmLotId ?? 0) !== (int) $f['id']) {
                DB::table('as_schedule_lots')->where('id', $lot->id)->update(['farmLotId' => (int) $f['id']]);
            }

            return $f;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** What a season lot card says of its farm lot: id, name, how many questions it has answered. */
    public static function card(?array $f): ?array
    {
        if (! $f) {
            return null;
        }
        $got = count(array_filter((array) ($f['answers'] ?? []), fn ($v) => $v !== null && $v !== '' && $v !== []));

        return ['id' => (int) $f['id'], 'name' => (string) ($f['name'] ?? ''), 'got' => $got, 'stale' => ! empty($f['stale'])];
    }
}
