<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Import the full Philippine gazetteer (provinces, cities/municipalities and
 * barangays) into as_locations for @location tagging. Data comes from the
 * public PSGC dataset; a local copy under storage/app/ph/ is used if present.
 */
class ImportPhLocations extends Command
{
    protected $signature = 'locations:import {--fresh : Empty the table first}';

    protected $description = 'Import PH provinces, cities and barangays for @location tagging';

    private const BASE = 'https://raw.githubusercontent.com/isaacdarcilla/philippine-addresses/master/';

    public function handle(): int
    {
        $provinces = $this->load('province.json');
        $cities = $this->load('city.json');
        $barangays = $this->load('barangay.json');

        if (! $provinces || ! $cities || ! $barangays) {
            $this->error('Could not load the location data (no internet and no local copy under storage/app/ph/).');

            return self::FAILURE;
        }

        $provinceByCode = [];
        foreach ($provinces as $p) {
            $provinceByCode[$p['province_code']] = $this->cleanName($p['province_name']);
        }
        $cityByCode = [];
        foreach ($cities as $c) {
            $cityByCode[$c['city_code']] = [
                'name' => $this->cleanName($c['city_name']),
                'province' => $provinceByCode[$c['province_code']] ?? null,
            ];
        }

        if ($this->option('fresh')) {
            DB::table('as_locations')->truncate();
            $this->info('Cleared as_locations.');
        }

        $seen = [];
        $rows = [];
        $now = now();
        $inserted = 0;

        $flush = function () use (&$rows, &$inserted) {
            if ($rows) {
                DB::table('as_locations')->insert($rows);
                $inserted += count($rows);
                $rows = [];
            }
        };

        // Provinces
        foreach ($provinceByCode as $name) {
            $rows[] = $this->row('province', $name, $name, $name, null, $seen, $now, 0);
        }
        // Cities / municipalities
        foreach ($cityByCode as $c) {
            if (! $c['name']) {
                continue;
            }
            $label = trim($c['name'] . ($c['province'] ? ', ' . $c['province'] : ''));
            $rows[] = $this->row('city', $c['name'], $label, $c['province'], $c['name'], $seen, $now, 1);
        }
        $flush();

        // Barangays (the big set) — chunk the inserts.
        $bar = $this->output->createProgressBar(count($barangays));
        foreach ($barangays as $b) {
            $city = $cityByCode[$b['city_code']] ?? null;
            $name = $this->cleanName($b['brgy_name']);
            if (! $name) {
                $bar->advance();
                continue;
            }
            $parts = array_filter([$name, $city['name'] ?? null, $city['province'] ?? null]);
            $label = Str::limit(implode(', ', $parts), 190, '');
            $rows[] = $this->row('barangay', $name, $label, $city['province'] ?? null, $city['name'] ?? null, $seen, $now, 2);
            if (count($rows) >= 1000) {
                $flush();
            }
            $bar->advance();
        }
        $flush();
        $bar->finish();
        $this->newLine(2);
        $this->info("Imported {$inserted} locations.");

        return self::SUCCESS;
    }

    /** Build one insert row with a unique slug. */
    private function row(string $type, string $name, string $label, ?string $province, ?string $city, array &$seen, $now, int $sort): array
    {
        $base = Str::limit(Str::slug($label ?: $name), 74, '');
        $slug = $base;
        $i = 2;
        while ($slug === '' || isset($seen[$slug])) {
            $slug = $slug === '' ? 'loc-' . $i : $base . '-' . $i;
            $i++;
        }
        $seen[$slug] = true;

        return [
            'type' => $type,
            'name' => Str::limit($name, 160, ''),
            'label' => Str::limit($label, 200, ''),
            'province' => $province,
            'city' => $city,
            'slug' => $slug,
            'sort' => $sort,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Trim PSGC quirks: drop "(Pob.)"/"(Capital)" parentheticals and rewrite
     * "City Of Urdaneta" / "Science City Of Muñoz" → "Urdaneta City" so the
     * place is searchable by its real name.
     */
    private function cleanName(string $raw): string
    {
        $name = preg_replace('/\s*\((?:pob\.?|capital|[^)]*)\)\s*/i', ' ', $raw);
        $name = trim(preg_replace('/\s+/', ' ', (string) $name));

        if (preg_match('/\bcity\s+of\s+(.+)$/iu', $name, $m)) {
            $name = trim($m[1]) . ' City';
        }

        return $name;
    }

    /** Load a dataset from storage/app/ph/{file} if present, else fetch it. */
    private function load(string $file): ?array
    {
        $local = storage_path('app/ph/' . $file);
        if (is_file($local)) {
            $data = json_decode((string) file_get_contents($local), true);
            if (is_array($data)) {
                return $data;
            }
        }
        try {
            $res = Http::timeout(60)->retry(1, 500)->get(self::BASE . $file);
            if ($res->ok() && is_array($res->json())) {
                return $res->json();
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return null;
    }
}
