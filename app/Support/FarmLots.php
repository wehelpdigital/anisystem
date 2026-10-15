<?php

namespace App\Support;

use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * THE FARM'S LOTS (2026-10-16, the owner): "all of these analysis require the
 * same type of question about the soil and irrigation which always pertains
 * to a specific lot". A lot is answered once, with where it is (province,
 * town or city, barangay), its size and its soil, water and last season,
 * and every analysis then only asks which lot.
 *
 * A lot is a row of as_field_profiles (App\Support\FieldProfile, the saved
 * field profiles of 2026-10-15) with its place and size beside the answers.
 * Profiles saved before the lots had no place: they are lots too, and ask for
 * their place the first time an analysis uses them.
 *
 * Every lot is to be checked every six months (STALE_DAYS from the last
 * save or "Still correct"): Global Lots and every lot picker mark an old
 * lot, and lots:remind rings the bell once a cycle (staleNotifiedAt).
 */
final class FarmLots
{
    /** Six months: after this a lot's answers are to be checked again. */
    public const STALE_DAYS = 182;

    /**
     * The units a lot's size is given in: key => [name, short, hectares in
     * one, the line under it]. The same three the season lots and the Land
     * Prep Planner use. The size is kept in hectares, the unit beside it.
     */
    public const AREA_UNITS = [
        'ha' => ['Hectares', 'ha', 1.0, '10,000 square meters each'],
        'sqm' => ['Square meters', 'sq m', 0.0001, 'For a small plot or a garden'],
        'acre' => ['Acres', 'acres', 0.40468564, 'About 4,047 square meters each'],
    ];

    /** Whether the size's unit column is in (a second, later migration). */
    public static function unitReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            try {
                $known = Cache::get('farm-lots:unit');
                if ($known === null) {
                    $known = FieldProfile::storeReady() && Schema::hasColumn('as_field_profiles', 'areaUnit') ? 1 : 0;
                    Cache::put('farm-lots:unit', $known, $known ? 86400 : 60);
                }
                $ready = (int) $known === 1;
            } catch (\Throwable) {
                $ready = false;
            }
        }

        return $ready;
    }

    /** Whether the lot columns are in (the migration may not have run yet on this database). */
    public static function ready(): bool
    {
        static $ready = null;
        if ($ready === null) {
            try {
                $known = Cache::get('farm-lots:columns');
                if ($known === null) {
                    $known = FieldProfile::storeReady() && Schema::hasColumn('as_field_profiles', 'barangay') ? 1 : 0;
                    Cache::put('farm-lots:columns', $known, $known ? 86400 : 60);
                }
                $ready = (int) $known === 1;
            } catch (\Throwable) {
                $ready = false;
            }
        }

        return $ready;
    }

    /** "Brgy. Alibagu, Ilagan City, Isabela", or '' when no place is given. */
    public static function place(?string $barangay, ?string $town, ?string $province): string
    {
        $bits = array_values(array_filter([
            $barangay ? (preg_match('/^(brgy|barangay|poblacion)\b/i', $barangay) ? $barangay : 'Brgy. ' . $barangay) : null,
            $town ?: null, $province ?: null,
        ]));

        return implode(', ', $bits);
    }

    /** The place an analysis reads the weather by: the town and the province. */
    public static function where(?string $town, ?string $province): string
    {
        return implode(', ', array_values(array_filter([$town ?: null, $province ?: null])));
    }

    /** FieldProfile::row() with the lot's place, size and age. */
    public static function decorate(array $row, object $r): array
    {
        $at = Carbon::parse($r->updated_at ?: $r->created_at);
        $age = (int) $at->diffInDays(now(), true);
        $prov = $r->province ?? null;
        $town = $r->town ?? null;
        $brgy = $r->barangay ?? null;

        return $row + [
            'province' => $prov, 'town' => $town, 'barangay' => $brgy,
            'place' => self::place($brgy, $town, $prov),
            'where' => self::where($town, $prov),
            'areaHa' => isset($r->areaHa) && $r->areaHa !== null ? (float) $r->areaHa : null,
            'areaUnit' => isset($r->areaUnit, self::AREA_UNITS[$r->areaUnit]) ? $r->areaUnit : 'ha',
            'updatedAt' => $at->toIso8601String(),
            'ageDays' => $age,
            'stale' => $age >= self::STALE_DAYS,
            'staleOn' => $at->copy()->addDays(self::STALE_DAYS)->format('M j, Y'),
        ];
    }

    /** One lot of an owner's, as the pages see it, or null. */
    public static function one(int $ownerId, int $id): ?array
    {
        $r = DB::table('as_field_profiles')->where('userId', $ownerId)->where('id', $id)->where('deleteStatus', 1)->first();

        return $r ? FieldProfile::row($r) : null;
    }

    /**
     * Save a lot: a new one (its name made unique), or an update of `$id`.
     * Unlike FieldProfile::store() it never matches a lot by its name, and
     * a lot may be saved before its questions are answered.
     */
    public static function save(int $ownerId, array $data, ?int $id = null): ?array
    {
        if (! self::ready()) {
            return null;
        }
        $q = DB::table('as_field_profiles')->where('userId', $ownerId)->where('deleteStatus', 1);
        $have = $id ? (clone $q)->where('id', $id)->first() : null;
        if ($id && ! $have) {
            return null;
        }
        $crop = array_key_exists('crop', $data)
            ? (($data['crop'] ?? '') !== '' ? FieldCatalogue::cropKey((string) $data['crop']) : null)
            : ($have->crop ?? null);
        $name = mb_substr(trim((string) ($data['name'] ?? ($have->name ?? ''))), 0, 120) ?: 'My lot';
        // A second lot of the same name gets a number: two "North field" rows would be one lot to the farmer.
        $taken = (clone $q)->when($have, fn ($x) => $x->where('id', '!=', $have->id))->pluck('name')->map(fn ($n) => mb_strtolower((string) $n))->all();
        if (in_array(mb_strtolower($name), $taken, true)) {
            $base = $name;
            for ($i = 2; in_array(mb_strtolower($name), $taken, true); $i++) {
                $name = mb_substr($base, 0, 112) . ' ' . $i;
            }
        }
        $row = ['name' => $name, 'crop' => $crop, 'updated_at' => now()];
        if (array_key_exists('answers', $data)) {
            $row['answers'] = json_encode(FieldProfile::normalize((array) ($data['answers'] ?? []), $crop), JSON_UNESCAPED_UNICODE);
        }
        foreach (['province' => 120, 'town' => 120, 'barangay' => 160] as $k => $max) {
            if (array_key_exists($k, $data)) {
                $v = trim((string) ($data[$k] ?? ''));
                $row[$k] = $v !== '' ? mb_substr($v, 0, $max) : null;
            }
        }
        if (array_key_exists('areaHa', $data)) {
            $a = (float) ($data['areaHa'] ?? 0);
            $row['areaHa'] = $a > 0 ? round(min($a, 100000), 6) : null;
        }
        if (array_key_exists('areaUnit', $data) && self::unitReady()) {
            $row['areaUnit'] = isset(self::AREA_UNITS[$data['areaUnit'] ?? '']) ? $data['areaUnit'] : 'ha';
        }
        foreach (['lotId', 'scheduleId'] as $k) {
            if (array_key_exists($k, $data)) {
                $row[$k] = is_numeric($data[$k] ?? null) && (int) $data[$k] > 0 ? (int) $data[$k] : null;
            }
        }
        if ($have) {
            DB::table('as_field_profiles')->where('id', $have->id)->update($row);
            $rid = (int) $have->id;
        } else {
            $rid = (int) DB::table('as_field_profiles')->insertGetId($row + ['userId' => $ownerId, 'answers' => $row['answers'] ?? '{}', 'created_at' => now(), 'deleteStatus' => 1]);
        }

        return self::one($ownerId, $rid);
    }

    /** "Still correct": the answers were checked, so the six months start again. */
    public static function confirm(int $ownerId, int $id): ?array
    {
        $done = DB::table('as_field_profiles')->where('userId', $ownerId)->where('id', $id)->where('deleteStatus', 1)
            ->update(['updated_at' => now()]);

        return $done ? self::one($ownerId, $id) : null;
    }

    public static function remove(int $ownerId, int $id): bool
    {
        return (bool) DB::table('as_field_profiles')->where('userId', $ownerId)->where('id', $id)->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);
    }

    /** How many of an owner's lots are due for a check (the Global Lots tile wears it). */
    public static function staleCount(int $ownerId): int
    {
        if (! FieldProfile::storeReady()) {
            return 0;
        }
        try {
            return (int) DB::table('as_field_profiles')->where('userId', $ownerId)->where('deleteStatus', 1)
                ->where('updated_at', '<=', now()->subDays(self::STALE_DAYS))->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * The place pickers, from the Philippine gazetteer (as_locations): the
     * provinces, the towns and cities of one, or the barangays of one town.
     *
     * @return list<string>
     */
    public static function places(?string $province = null, ?string $town = null): array
    {
        $province = trim((string) $province);
        $town = trim((string) $town);
        $key = 'farm-lots:places:' . md5($province . '|' . $town);

        return Cache::remember($key, 86400, function () use ($province, $town) {
            $q = DB::table('as_locations');
            if ($province === '') {
                $q->where('type', 'province');
            } elseif ($town === '') {
                $q->where('type', 'city')->where('province', $province);
            } else {
                $q->where('type', 'barangay')->where('province', $province)->where('city', $town);
            }

            return $q->orderBy('name')->pluck('name')->map(fn ($n) => (string) $n)->unique()->values()->all();
        });
    }

    /**
     * Ring the bell for every lot whose answers turned six months old since
     * it was last rung (or since it was last saved), once a cycle. Returns how
     * many were rung.
     */
    public static function remind(): int
    {
        if (! self::ready()) {
            return 0;
        }
        $cut = now()->subDays(self::STALE_DAYS);
        $rows = DB::table('as_field_profiles')->where('deleteStatus', 1)->where('updated_at', '<=', $cut)
            ->where(fn ($q) => $q->whereNull('staleNotifiedAt')->orWhereColumn('staleNotifiedAt', '<', 'updated_at')->orWhere('staleNotifiedAt', '<=', $cut))
            ->orderBy('id')->limit(500)->get(['id', 'userId', 'name']);
        $bell = app(NotificationService::class);
        $n = 0;
        foreach ($rows as $r) {
            try {
                $bell->notify((int) $r->userId, 'lot_check', 'Time to check ' . $r->name,
                    'Its soil, water and last season answers are six months old. Check they are still right, so every analysis reads this lot as it is now.',
                    route('farmlots.index', ['lot' => $r->id], false));
                DB::table('as_field_profiles')->where('id', $r->id)->update(['staleNotifiedAt' => now()]);
                $n++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $n;
    }
}
