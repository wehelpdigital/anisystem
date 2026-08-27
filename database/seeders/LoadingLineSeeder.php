<?php

namespace Database\Seeders;

use App\Models\AsLoadingLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * What the app says while it is still thinking.
 *
 * Every one is something that actually happens on a farm while you wait for
 * something else, which is the joke: the app is doing what the farmer is
 * doing. They come in pairs. The first line says what is going on; the second
 * is the aside somebody would really add — "Waking the carabao… / He heard
 * you. He is thinking about it." One sentence is a caption. Two are a voice.
 *
 * Keyed on the line itself, so re-running adds what is new and leaves
 * anybody's edits from the admin alone. A subline is only ever filled in
 * where there is not one already, for the same reason.
 */
class LoadingLineSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::LINES as [$line, $sub, $scene]) {
            $row = AsLoadingLine::firstOrCreate(
                ['line' => $line, 'surface' => 'board'],
                ['scene' => $scene, 'subline' => $sub, 'deleteStatus' => 1]
            );

            // An older row from before there were sublines gets its second
            // half; one somebody has written themselves is left alone.
            if (blank($row->subline)) {
                $row->subline = $sub;
                $row->save();
            }
        }

        Cache::forget('as_loading_lines.board');
    }

    /** @var array<int, array{0:string,1:string,2:string}> */
    public const LINES = [
        // ---- The hens ---------------------------------------------------
        ['Preparing the chicken eggs…', 'They are not in a hurry.', 'egg'],
        ['Counting the eggs before they hatch…', 'A risk, and we are taking it.', 'egg'],
        ['Asking the hens to hurry up…', 'The hens have their own schedule.', 'egg'],
        ['Turning the eggs one more time…', 'Twice a day, every day. No exceptions.', 'egg'],
        ['Warming the eggs a little longer…', 'Twenty-one days is twenty-one days.', 'egg'],
        ['Sending the rooster back to bed…', 'He is not listening.', 'egg'],
        ['Feeding the chickens first…', 'They complain otherwise.', 'egg'],
        ['Finding where the hen has been laying…', 'Under the house. Again.', 'egg'],

        // ---- The seedbed ------------------------------------------------
        ['Waking up the seedlings…', 'Gently. They have had a long night.', 'seedling'],
        ['Telling the seedlings it is morning…', 'Some of them believe it.', 'seedling'],
        ['Straightening the rows…', 'Crooked rows haunt a person.', 'seedling'],
        ['Sorting the good seeds from the empty ones…', 'The empty ones float. That is the trick.', 'seedling'],
        ['Whispering to the seedbed…', 'It helps. Nobody knows why.', 'seedling'],
        ['Counting the tillers…', 'Lost count at forty. Starting again.', 'seedling'],
        ['Pulling one weed on the way past…', 'It is never just one.', 'seedling'],
        ['Looking for where the shovel went…', 'It was here yesterday.', 'seedling'],
        ['Watering the seedbed…', 'A fine spray, not a flood.', 'seedling'],
        ['Thinning the seedlings…', 'The hard part is pulling the good ones.', 'seedling'],
        ['Checking under a leaf for eggs…', 'Not the chicken kind.', 'seedling'],
        ['Hardening off the seedlings…', 'A few hours of sun a day.', 'seedling'],
        ['Reading the soil…', 'It says it is hungry.', 'seedling'],

        // ---- The machine ------------------------------------------------
        ['Starting the tractor…', 'The third try is usually the one.', 'tractor'],
        ['Warming up the hand tractor…', 'It likes to be asked nicely.', 'tractor'],
        ['Looking for the tractor key…', 'Check the other pocket.', 'tractor'],
        ['Waiting for the last pass to finish…', 'Two more rounds.', 'tractor'],
        ['Filling the tank before the sun gets high…', 'Diesel now, coffee after.', 'tractor'],
        ['Sharpening the bolo…', 'A dull blade is more work than a sharp one.', 'tractor'],
        ['Tightening one bolt that keeps working loose…', 'The same bolt. Every season.', 'tractor'],
        ['Greasing the bearings…', 'Now, not after it starts squealing.', 'tractor'],
        ['Backing the trailer through the gate…', 'One try. Always one try.', 'tractor'],
        ['Waiting for the mechanic…', 'He said this morning.', 'tractor'],
        ['Checking the tyre pressure…', 'The back left, as usual.', 'tractor'],
        ['Cleaning the air filter…', 'Dust in, power out.', 'tractor'],

        // ---- The sky ----------------------------------------------------
        ['Waiting for the rain to pass…', 'It will pass. Probably.', 'rain'],
        ['Checking if the clouds mean it…', 'They usually do not.', 'rain'],
        ['Counting the drops on the roof…', 'Getting faster.', 'rain'],
        ['Asking the sky politely…', 'Worth a try.', 'rain'],
        ['Watching one dark cloud very closely…', 'It is coming this way.', 'rain'],
        ['Running the sacks under cover…', 'Faster than it looks.', 'rain'],
        ['Digging the drain a little deeper…', 'Before it is needed, not after.', 'rain'],
        ['Reading the wind…', 'Coming off the sea. That means rain.', 'rain'],
        ['Bringing the washing in…', 'Priorities.', 'rain'],
        ['Watching the river…', 'Still low. Still watching.', 'rain'],

        ['Letting the dew dry first…', 'Spraying wet leaves is money on the ground.', 'sun'],
        ['Waiting for the sun to clear the trees…', 'Ten more minutes.', 'sun'],
        ['Working out how hot this will get…', 'Hot.', 'sun'],
        ['Finding shade for the merienda…', 'Under the mango, as always.', 'sun'],
        ['Squinting at the far end of the field…', 'Something is greener over there.', 'sun'],
        ['Waiting out the noon heat…', 'Nothing good happens at one o’clock.', 'sun'],
        ['Checking the shade cloth…', 'One corner has come loose.', 'sun'],
        ['Drinking water before the work, not after…', 'A lesson learned the hard way.', 'sun'],

        // ---- The carabao ------------------------------------------------
        ['Waking the carabao…', 'He heard you. He is thinking about it.', 'carabao'],
        ['The carabao says five more minutes…', 'That was ten minutes ago.', 'carabao'],
        ['Walking the carabao to the field…', 'At his pace, not yours.', 'carabao'],
        ['Giving the carabao a drink first…', 'Non-negotiable.', 'carabao'],
        ['Explaining the plan to the carabao…', 'He has heard better plans.', 'carabao'],
        ['Scratching the carabao behind the ear…', 'Purely professional.', 'carabao'],
        ['Getting the carabao out of the mud…', 'He is not helping.', 'carabao'],
        ['Fixing the yoke…', 'It rubs on one side.', 'carabao'],

        // ---- The crop ---------------------------------------------------
        ['Counting the grains…', 'There are a great many grains.', 'rice'],
        ['Checking if the palay is ready…', 'Bite one. You will know.', 'rice'],
        ['Weighing the last sack…', 'Forty-nine and a half. Close enough.', 'rice'],
        ['Drying the palay on the road…', 'Watching for tricycles.', 'rice'],
        ['Bringing in the last cavan…', 'The heaviest one, obviously.', 'rice'],
        ['Rubbing a head of rice between two fingers…', 'Hard, not chalky. Good.', 'rice'],
        ['Chasing a maya out of the field…', 'It will be back.', 'rice'],
        ['Walking the bund…', 'Looking for the hole a rat made.', 'rice'],
        ['Levelling the paddy…', 'Water finds every mistake.', 'rice'],
        ['Checking for empty panicles…', 'A few. Not too many.', 'rice'],
        ['Sacking up…', 'Fifty kilos at a time.', 'rice'],
        ['Waiting for the thresher…', 'He is at the neighbour’s.', 'rice'],

        // ---- The water --------------------------------------------------
        ['Filling the sprayer…', 'Measure twice. It is not water.', 'watering'],
        ['Opening the canal gate…', 'Slowly, or the bund goes.', 'watering'],
        ['Waiting for the water to reach the far end…', 'It always takes longer than you think.', 'watering'],
        ['Checking the water level…', 'Ankle deep is about right.', 'watering'],
        ['Chasing the water down the furrow…', 'It has found a hole again.', 'watering'],
        ['Untangling the hose…', 'How does it even do this.', 'watering'],
        ['Priming the pump…', 'Give it a minute.', 'watering'],
        ['Mixing the fertiliser…', 'Read the label. Then read it again.', 'watering'],
        ['Waiting for the tank to fill…', 'Slower than it ought to be.', 'watering'],
        ['Repairing the bund…', 'Mud, hands, patience.', 'watering'],

        // ---- The rest of the farm ---------------------------------------
        ['Waiting for the bees to finish…', 'They are working. So are we.', 'bee'],
        ['Letting the bees get on with it…', 'No spraying while they are out.', 'bee'],
        ['Counting the flowers…', 'More than yesterday.', 'bee'],
        ['Following one bee back to the hive…', 'Lost it at the fence.', 'bee'],
        ['Watching a butterfly and calling it work…', 'It counts as scouting.', 'bee'],
        ['Looking for the good insects…', 'Not everything with six legs is a problem.', 'bee'],

        ['Reading the moon…', 'Waning. Good for root crops.', 'moon'],
        ['Checking the planting calendar…', 'Lola’s calendar, not the phone’s.', 'moon'],
        ['Waiting for the roosters…', 'Four in the morning, like clockwork.', 'moon'],
        ['Closing the gate for the night…', 'Twice, because of the goat.', 'moon'],
        ['Asking the neighbour how he did it…', 'He will not say.', 'moon'],
        ['Locking the shed…', 'The bolo lives inside now.', 'moon'],
        ['Writing the day down…', 'Before any of it is forgotten.', 'moon'],
        ['Adding up what today cost…', 'Less than yesterday. Somehow.', 'moon'],
        ['Turning off the pump for the night…', 'Listen for the click.', 'moon'],
        ['Feeding the dog…', 'He has been waiting since four.', 'moon'],
        ['Counting the sacks one last time…', 'The same number as before. Good.', 'moon'],
        ['Sitting down for one minute…', 'One minute.', 'moon'],
        ['Waiting for the kettle…', 'Kape muna.', 'moon'],
    ];
}
