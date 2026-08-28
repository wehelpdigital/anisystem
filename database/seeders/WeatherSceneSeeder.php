<?php

namespace Database\Seeders;

use App\Models\AsWeatherScene;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * What every kind of sky means for the work.
 *
 * The advice is the reason this table exists. A forecast that says "Rain,
 * 80%" tells a farmer what they can see out of the window; one that says
 * "hold the spraying, it washes off before it works" tells them what to do
 * about it, which is the only reason anybody opens a forecast.
 *
 * Each line is one decision, not a lecture — what to stop, what to bring in,
 * what is a good use of this particular sky. Keyed on the key, so re-running
 * refreshes the drawing and the colour (ours) and leaves the words alone once
 * somebody has reworded them for their own province.
 */
class WeatherSceneSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::SKIES as $i => [$key, $label, $tagalog, $greeting, $scene, $hue, $advice]) {
            $row = AsWeatherScene::firstOrNew(['key' => $key]);
            $row->label = $label;
            $row->scene = $scene;
            $row->hue = $hue;
            $row->sortOrder = $i;
            $row->deleteStatus = 1;
            // The words are only written where nobody has written their own.
            if (blank($row->tagalog)) {
                $row->tagalog = $tagalog;
            }
            if (blank($row->greeting)) {
                $row->greeting = $greeting;
            }
            if (blank($row->advice)) {
                $row->advice = $advice;
            }
            $row->save();
        }

        Cache::forget('as_weather_scenes.map');
    }

    /** @var array<int, array{0:string,1:string,2:string,3:string,4:string,5:string,6:string}> */
    private const SKIES = [
        ['clear', 'Clear', 'Maaraw', 'Maaraw na', 'clear', 'sun',
            'A drying day. Get the grain out early and turn it every hour. Do the heavy work before ten and keep water with you.'],

        ['clear_night', 'Clear night', 'Malinaw na gabi', 'Malinaw na', 'clear_night', 'night',
            'Cool and dry overnight. Good for leaving grain covered outside, and a still night is a good one to irrigate.'],

        ['partly', 'Partly cloudy', 'Maulap-ulap', 'Maaliwalas na', 'partly', 'sky',
            'A comfortable working day. Good for transplanting and for spraying, as long as the wind stays behind you.'],

        ['partly_night', 'Partly cloudy night', 'Maulap na gabi', 'Maaliwalas na', 'partly_night', 'night',
            'Mild overnight. Cover the drying grain anyway — cloud can turn to drizzle without warning.'],

        ['cloudy', 'Overcast', 'Makulimlim', 'Makulimlim na', 'cloudy', 'grey',
            'Poor drying, gentle on transplants. Fine for weeding and field work; do not lay grain out expecting it to dry.'],

        ['fog', 'Fog', 'Maulap at hamog', 'Mahamog na', 'fog', 'grey',
            'Wet leaves until mid-morning. Hold off spraying — it will not stick — and watch for blast and mildew in the days after.'],

        ['drizzle', 'Drizzle', 'Ambon', 'Maambon na', 'drizzle', 'rain',
            'Not enough to fill the field, enough to ruin a spray. Postpone anything that has to stay on the leaf.'],

        ['rain', 'Rain', 'Maulan', 'Maulang', 'rain', 'rain',
            'Cover the fertiliser and bring the drying grain in. No spraying today: it washes off before it works, and you paid for it.'],

        ['heavy_rain', 'Heavy rain', 'Malakas na ulan', 'Malakas ang ulan ngayong', 'heavy_rain', 'storm',
            'Walk the bunds when it eases and check for breaches. Stay out of standing floodwater, and wear the kapote if you must go out.'],

        ['showers', 'Rain showers', 'Pabugso-bugsong ulan', 'Maulang', 'showers', 'rain',
            'Work in the gaps and keep a cover to hand. Not a drying day, and not a spraying one — a shower an hour later undoes it.'],

        ['storm', 'Thunderstorm', 'May kulog at kidlat', 'Mabagyong', 'storm', 'storm',
            'Come in when you hear thunder. The tallest thing in an open field should not be you. Secure loose sheets and covers before it arrives.'],

        ['snow', 'Snow', 'Niyebe', 'Maniyebeng', 'snow', 'sky',
            'Cold enough to stop growth. Protect seedlings and young transplants, and keep animals under shelter.'],

        ['hot', 'Very hot', 'Napakainit', 'Mainit na', 'hot', 'heat',
            'Heat, not sun, is the danger. Heavy work before ten, shade every hour, and drink before you are thirsty. Water the field early or late, never at noon.'],

        ['windy', 'Windy', 'Mahangin', 'Mahanging', 'windy', 'wind',
            'No spraying: the drift goes where you did not aim it, onto the neighbour or onto you. Check that tall crops and covers are tied down.'],
    ];
}
