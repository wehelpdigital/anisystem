<?php

namespace Database\Seeders;

use App\Models\AsLoadingLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * What the app says while it is still thinking.
 *
 * All of them are things that actually happen on a farm while you wait for
 * something else, which is the joke: the app is doing what the farmer is
 * doing. Keyed on the line itself, so re-running this adds what is new and
 * leaves anybody's edits from the admin alone.
 */
class LoadingLineSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::LINES as [$line, $scene]) {
            AsLoadingLine::firstOrCreate(
                ['line' => $line, 'surface' => 'board'],
                ['scene' => $scene, 'deleteStatus' => 1]
            );
        }

        Cache::forget('as_loading_lines.board');
    }

    /** @var array<int, array{0: string, 1: string}> */
    public const LINES = [
        // The hens
        ['Preparing the chicken eggs…', 'egg'],
        ['Counting the eggs before they hatch…', 'egg'],
        ['Asking the hens to hurry up…', 'egg'],
        ['Turning the eggs one more time…', 'egg'],
        ['Warming the eggs a little longer…', 'egg'],
        ['Sending the rooster back to bed…', 'egg'],

        // The seedbed
        ['Waking up the seedlings…', 'seedling'],
        ['Telling the seedlings it is morning…', 'seedling'],
        ['Straightening the rows…', 'seedling'],
        ['Sorting the good seeds from the empty ones…', 'seedling'],
        ['Whispering to the seedbed…', 'seedling'],
        ['Counting the tillers…', 'seedling'],
        ['Pulling one weed on the way past…', 'seedling'],
        ['Looking for where the shovel went…', 'seedling'],

        // The machine
        ['Starting the tractor…', 'tractor'],
        ['Warming up the hand tractor…', 'tractor'],
        ['Looking for the tractor key…', 'tractor'],
        ['Waiting for the last pass to finish…', 'tractor'],
        ['Filling the tank before the sun gets high…', 'tractor'],
        ['Sharpening the bolo…', 'tractor'],
        ['Tightening one bolt that keeps working loose…', 'tractor'],

        // The sky
        ['Waiting for the rain to pass…', 'rain'],
        ['Checking if the clouds mean it…', 'rain'],
        ['Counting the drops on the roof…', 'rain'],
        ['Asking the sky politely…', 'rain'],
        ['Watching one dark cloud very closely…', 'rain'],
        ['Running the sacks under cover…', 'rain'],

        ['Letting the dew dry first…', 'sun'],
        ['Waiting for the sun to clear the trees…', 'sun'],
        ['Working out how hot this will get…', 'sun'],
        ['Finding shade for the merienda…', 'sun'],
        ['Squinting at the far end of the field…', 'sun'],

        // The carabao
        ['Waking the carabao…', 'carabao'],
        ['The carabao says five more minutes…', 'carabao'],
        ['Walking the carabao to the field…', 'carabao'],
        ['Giving the carabao a drink first…', 'carabao'],
        ['Explaining the plan to the carabao…', 'carabao'],

        // The crop
        ['Counting the grains…', 'rice'],
        ['Checking if the palay is ready…', 'rice'],
        ['Weighing the last sack…', 'rice'],
        ['Drying the palay on the road…', 'rice'],
        ['Bringing in the last cavan…', 'rice'],
        ['Rubbing a head of rice between two fingers…', 'rice'],
        ['Chasing a maya out of the field…', 'rice'],

        // The water
        ['Filling the sprayer…', 'watering'],
        ['Opening the canal gate…', 'watering'],
        ['Waiting for the water to reach the far end…', 'watering'],
        ['Checking the water level…', 'watering'],
        ['Chasing the water down the furrow…', 'watering'],
        ['Untangling the hose…', 'watering'],

        // The rest of the farm
        ['Waiting for the bees to finish…', 'bee'],
        ['Letting the bees get on with it…', 'bee'],
        ['Counting the flowers…', 'bee'],
        ['Following one bee back to the hive…', 'bee'],

        ['Reading the moon…', 'moon'],
        ['Checking the planting calendar…', 'moon'],
        ['Waiting for the roosters…', 'moon'],
        ['Closing the gate for the night…', 'moon'],
        ['Asking the neighbour how he did it…', 'moon'],
    ];
}
