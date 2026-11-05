<?php

use App\Support\SitePages;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The keyword fixes from the 2026-11-05 SEO audit.
 *
 * sync(): the shipped pages with new titles (palay in English, corn husk, alitangya), and the off target passages gone.
 *
 * The dated news roundups: 20 of them chased the searches App\Support\PriceHomes gives to one permanent page each, so they
 * are named for their news instead. The addresses stay, nothing links to a dead page. The stuffed phrase "rice palay price
 * philippines" also leaves their words. A post edited in the mother app is left as it is.
 */
return new class extends Migration
{
    /** slug => [title, metaTitle, the day for its focus]. */
    private const POSTS = [
        'rice-palay-price-philippines-2026-11-05' => ['El Nino Drought and P884M Cane Aid: Farm News for November 5, 2026', 'El Nino Drought and P884M Cane Aid: Farm News, November 5', 'November 5'],
        'rice-palay-price-philippines-2026-11-04' => ['Imports Rise and Storm In Fa Approaches: Farm News for November 4, 2026', 'Imports and Storm In Fa Watch: Farm News, November 4', 'November 4'],
        'fertilizer-price-philippines-2026-11-03' => ['El Nino Calamity Hits Farm Production and Fertilizer Costs: Farm News for November 3, 2026', 'El Nino Calamity and Fertilizer Costs: Farm News, November 3', 'November 3'],
        'rice-palay-price-philippines-2026-11-02' => ['One Ton Bags, Typhoon Watch and Hog Rules: Farm News for November 2, 2026', 'One Ton Bags and Typhoon Watch: Farm News, November 2', 'November 2'],
        'rice-palay-price-philippines-2026-11-01' => ['Weak Peso Threatens Input Costs: Farm News for November 1, 2026', 'Weak Peso and Input Costs: Farm News, November 1', 'November 1'],
        'rice-palay-price-philippines-2026-10-31' => ['Calamity Across 109 Areas and Rising Fuel Costs: Farm News for October 31, 2026', 'Calamity in 109 Areas and Fuel Costs: Farm News, October 31', 'October 31'],
        'fertilizer-price-philippines-2026-10-30' => ['Fertilizer Import Pressures and El Nino in Davao: Farm News for October 30, 2026', 'Fertilizer Imports and El Nino: Farm News, October 30', 'October 30'],
        'palay-price-today-2026-10-27' => ['Diesel Increase, Weak Peso and El Nino Drought: Farm News for October 27, 2026', 'Diesel, Weak Peso and El Nino: Farm News, October 27', 'October 27'],
        'rice-palay-price-philippines-2026-10-26' => ['Severe El Nino Farm Losses and Southern Rains: Farm News for October 26, 2026', 'El Nino Losses and Southern Rains: Farm News, October 26', 'October 26'],
        'rice-palay-price-philippines-2026-10-25' => ['El Nino Drought and Pork Import Tariffs: Farm News for October 25, 2026', 'El Nino and Pork Import Tariffs: Farm News, October 25', 'October 25'],
        'rice-palay-price-philippines-2026-10-24' => ['Super El Nino Deficit, Diesel Hike and Typhoon Rosal Exit: Farm News for October 24, 2026', 'Diesel Hike and Typhoon Rosal: Farm News, October 24', 'October 24'],
        'palay-price-today-2026-10-23' => ['Buffer Stocks Drop and Cyclones Threaten: Farm News for October 23, 2026', 'Buffer Stocks and Cyclones: Farm News, October 23', 'October 23'],
        'rice-palay-price-philippines-2026-10-22' => ['Super Typhoon Rosal and El Nino Farm Losses: Farm News for October 22, 2026', 'Super Typhoon Rosal and El Nino: Farm News, October 22', 'October 22'],
        'rice-palay-price-philippines-2026-10-21' => ['El Nino Damage and Livestock Import Shift: Farm News for October 21, 2026', 'El Nino Damage and Livestock Imports: Farm News, October 21', 'October 21'],
        'palay-price-today-2026-10-19' => ['Typhoon Champi and Super El Nino Threats: Farm News for October 19, 2026', 'Typhoon Champi and Super El Nino: Farm News, October 19', 'October 19'],
        'rice-palay-price-philippines-2026-10-18' => ['Typhoon Champi and Fuel Pressures: Farm News for October 18, 2026', 'Typhoon Champi and Fuel Pressures: Farm News, October 18', 'October 18'],
        'palay-rice-2026-10-14' => ['El Nino Threat, Fuel Aid and Solar Pumps: Farm News for October 14, 2026', 'El Nino, Fuel Aid and Solar Pumps: Farm News, October 14', 'October 14'],
        'palay-rice-2026-10-13' => ['El Nino Threat, Fuel Rollback and Cattle Imports: Farm News for October 13, 2026', 'Fuel Rollback and Cattle Imports: Farm News, October 13', 'October 13'],
        'el-nino-visayas-fuel-palay-price-today-2026-10-11' => ['El Niño Hits Visayas Farms, Plus Fuel Updates: Farm News for October 11, 2026', 'El Niño Hits Visayas Farms: Farm News, October 11', 'October 11'],
        'palay-rice-stocks-fall-el-nino-2026-10-07' => ['Palay Stocks Fall Before El Niño, Plus Fuel Prices and Ube Markets: Farm News for October 7, 2026', 'Palay Stocks Fall Before El Niño: Farm News, October 7', 'October 7'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('as_site_pages')) {
            return;
        }
        SitePages::sync();

        // "Rice Palay Price Philippines and Support Policies" to "Palay Price and Support Policies"; "the rice palay price
        // philippines outlook" to "the palay price outlook". Done on the JSON text, whose structure it cannot touch.
        $plain = fn (?string $s) => $s === null ? null : preg_replace_callback('/rice palay price,? philippines/i',
            fn ($m) => ctype_upper($m[0][0]) ? 'Palay Price' : 'palay price', $s);

        foreach (self::POSTS as $slug => [$title, $meta, $day]) {
            $row = DB::table('as_site_pages')->where('section', 'blog')->where('slug', $slug)->where('kind', 'roundup')->whereNull('editedAt')
                ->first(['id', 'excerpt', 'metaDescription', 'blocks']);
            if (! $row) {
                continue;
            }
            DB::table('as_site_pages')->where('id', $row->id)->update([
                'title' => $title,
                'metaTitle' => $meta,
                'focusKeyword' => 'farm news ' . $day,
                'excerpt' => $plain($row->excerpt),
                'metaDescription' => $plain($row->metaDescription),
                'blocks' => $plain($row->blocks),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The words stay.
    }
};
