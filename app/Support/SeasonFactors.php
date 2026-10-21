<?php

namespace App\Support;

/**
 * The season's sun, water and temperature factors, counted as NPK Plus's
 * seasonPlanks() counts them (resources/views/npk-plus/index.blade.php), so a
 * field reads the same in every tool (2026-10-19, the owner): the Seed Count
 * and Distance Calculator uses them for its growth penalty. NpkNeed::seasonWater
 * is the twin of the rain and water share; this adds the sun and the heat.
 *
 * $hist = ClimateStore::season() for the window, $window =
 * NpkNeed::seasonWindow(), $enso = ClimateStore::enso(), $water = irrigated,
 * pump (a pump or a well, 2026-10-20), partial or rainfed. Each factor is the share of a full potential the season
 * lets the crop carry: sun = clamp(rad / the crop's rad, 0.2, 1.1); water =
 * clamp(1 - ky x (1 - the share of the crop's drink the water meets), 0.1, 1);
 * temperature = clamp((1 - 0.07 x degrees off the crop's best range) x (1 -
 * 0.8 x the share of days too hot), 0.25, 1). Change it with seasonPlanks()
 * (the parity check is the scratchpad's seedcalc/season_parity.mjs).
 */
final class SeasonFactors
{
    /** The factors, or null without a history. */
    public static function of(string $climateKey, ?array $hist, array $window, ?array $enso, string $water, ?array $pump = null): ?array
    {
        if (empty($hist['avg'])) {
            return null;
        }
        $K = array_merge(NpkModel::CLIMATE['default'], NpkModel::CLIMATE[$climateKey] ?? []);
        $t = NpkNeed::ensoTilt($enso, $window);
        $a = $hist['avg'];
        $days = max(1, (int) ($window['days'] ?? 1));
        $clamp = fn ($v, $lo, $hi) => (float) max($lo, min($hi, $v));
        $rain = (float) $a['rain'] * $t['rain'];
        $rad = (float) $a['rad'] * $t['sun'];
        $tmean = (float) $a['tmean'] + $t['temp'];
        $et0 = (float) $a['et0'] * ($t['rain'] < 1 ? 1 + (1 - $t['rain']) * 0.15 : 1);
        $need = $et0 * (float) $K['kc'] + (float) ($K['extraMm'] ?? 0);
        $eff = $rain * 0.75;
        // A pump or a well makes up most of what the rain leaves short, less in the months it runs low (NpkNeed::pumpCover,
        // 2026-10-21); a part time source half (NpkModel::FIELD, NpkNeed::seasonWater).
        $pc = $water === 'pump' ? NpkNeed::pumpCover($pump, $window, $enso) : null;
        $cover = $pc ? (float) $pc['cover'] : ($water === 'partial' ? (float) NpkModel::FIELD['partialCover'] : 0.0);
        $supply = $water === 'irrigated' ? $need : $eff + $cover * max(0, $need - $eff);
        $wr = min(1, $supply / max(1, $need));
        $fw = $clamp(1 - (float) $K['ky'] * (1 - $wr), 0.1, 1);
        $fs = $clamp($rad / (float) $K['rad'], 0.2, 1.1);
        $dev = max(0, (float) $K['opt'][0] - $tmean, $tmean - (float) $K['opt'][1]);
        $phase = ($enso['phase'] ?? '') ?: null;
        $hotRaw = (float) ($K['hot'] >= 35 ? ($a['hot35'] ?? 0) : ($K['hot'] >= 32 ? ($a['hot32'] ?? 0) : ($a['hot30'] ?? 0)));
        $hotN = $hotRaw * ($phase === 'el_nino' ? 1.3 : ($phase === 'la_nina' ? 0.85 : 1));
        $ft = $clamp((1 - 0.07 * $dev) * (1 - 0.8 * min(1, $hotN / $days)), 0.25, 1);

        return ['pump' => $pc, 'sun' => $fs, 'water' => $fw, 'temp' => $ft, 'rain' => $rain, 'rad' => $rad, 'tmean' => $tmean, 'need' => $need, 'supply' => $supply,
            'wr' => $wr, 'hotN' => $hotN, 'dev' => $dev, 'tilt' => $t, 'K' => $K];
    }
}
