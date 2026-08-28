<?php

namespace Database\Seeders;

use App\Models\AsLoadingLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * What the app says while it is still thinking.
 *
 * These used to be jokes about the farm. They are reminders now, because a
 * wait is a few seconds of somebody's attention and there is better to do
 * with it: wear the coat, drink before you are thirsty, spray with the wind
 * behind you, keep the chemicals out of drink bottles.
 *
 * They come in pairs, and the pair is the whole point. The first line is the
 * reminder; the second is WHY, in the voice of somebody who has seen it go
 * wrong. "Boots, not slippers" is nagging. "Boots, not slippers — snakes,
 * nails and glass do not announce themselves" is advice.
 *
 * Nothing here is a diagnosis or a dose. It is the sort of thing a good
 * neighbour says over the fence, and every one of it is safe to be wrong
 * about in the sense that following it costs nothing.
 *
 * Keyed on the line itself, so re-running adds what is new and leaves
 * anybody's edits from the admin alone — but the scene and the reason are
 * refreshed, because those are ours to keep in step with the drawings.
 * Anything on this surface that is NOT in this list is retired rather than
 * deleted: the jokes are gone from the screen and still in the table.
 */
class LoadingLineSeeder extends Seeder
{
    public function run(): void
    {
        $keep = [];

        foreach (self::LINES as [$line, $sub, $scene]) {
            $keep[] = $line;
            $row = AsLoadingLine::firstOrNew(['line' => $line, 'surface' => 'board']);
            $row->scene = $scene;
            $row->deleteStatus = 1;
            // The reason is only written where nobody has written their own.
            if (blank($row->subline)) {
                $row->subline = $sub;
            }
            $row->save();
        }

        // The old jokes step aside. deleteStatus 0 is the app's own word for
        // "not shown" — pool() already filters on it — so nothing is lost and
        // an admin can bring any of them back.
        AsLoadingLine::where('surface', 'board')
            ->whereNotIn('line', $keep)
            ->update(['deleteStatus' => 0]);

        Cache::forget('as_loading_lines.board');
    }

    /** @var array<int, array{0:string,1:string,2:string}> */
    private const LINES = [
        // ---- Rain, and what it does to people who ignore it -------------
        ['Wear your kapote before the sky opens.', 'Wet clothes and wind is how a fever starts.', 'thermometer'],
        ['Dry off before you sit down.', 'A cold back after a hot day is asking for it.', 'back'],
        ['Stay out of standing floodwater.', 'Leptospirosis gets in through a cut you never noticed.', 'flood'],
        ['Wash and dry your feet after wading.', 'Then look between the toes.', 'soap'],
        ['Come in when you hear thunder.', 'The tallest thing in an open field should not be you.', 'rain'],
        ['Cover the fertiliser before the rain.', 'A wet sack is money turning to stone.', 'sack'],
        ['Walk the bunds after heavy rain.', 'One breach empties a week of water.', 'canal'],
        ['Clear the canal before the storm, not during.', 'Nobody clears a canal well in the dark.', 'torch'],
        ['Put the sprayer away when it rains.', 'It washes off before it works, and you paid for it.', 'soap'],
        ['Bring the drying grain in early.', 'One shower undoes a whole day of sun.', 'dry'],
        ['Keep a dry set of clothes in the shed.', 'The walk home is longer when you are wet.', 'rain'],
        // ---- Sun and heat -----------------------------------------------
        ['Do the heavy work before ten.', 'The field will still be there at four.', 'sun'],
        ['Wear the hat even when it is cloudy.', 'The burn comes through the grey.', 'hat'],
        ['Take shade every hour.', 'Ten minutes now buys the whole afternoon.', 'shade'],
        ['Long sleeves beat sunburn.', 'Lighter cloth, more cover.', 'sun'],
        ['Headache and chills in the heat mean stop.', 'That is heat stroke starting, not tiredness.', 'sun'],
        ['Nobody works the noon field alone.', 'Heat is easier to see on somebody else than on yourself.', 'sun'],
        ['Rest in shade, not in the truck.', 'A parked cab is hotter than the field.', 'shade'],
        // ---- Water ------------------------------------------------------
        ['Drink before you are thirsty.', 'Thirst turns up late and leaves early.', 'ear'],
        ['Carry more water than you think you need.', 'You will not walk back for it.', 'back'],
        ['Plain water first, softdrinks after.', 'Sugar does not replace what you sweated out.', 'bottle'],
        ['A pinch of salt in the water on a long day.', 'You lose more than water out there.', 'bottle'],
        ['Dark urine means you are already behind.', 'Drink, and sit down for ten minutes.', 'torch'],
        ['Fill the jug the night before.', 'Morning you will not stop to do it.', 'clock'],
        // ---- Health -----------------------------------------------------
        ['Take your vitamins.', 'Same time every day, or it will not happen.', 'pills'],
        ['Eat before you go out.', 'An empty stomach in the sun is how people faint.', 'sun'],
        ['Never spray on an empty stomach.', 'It goes into you faster.', 'spray'],
        ['Get the tetanus shot.', 'Rusty wire and bare feet are a bad pair.', 'sharpen'],
        ['A check-up once a year.', 'Cheaper than the year you cannot work.', 'vitamin'],
        ['Bring the whole family for deworming.', 'The clinic does it free, and it is quick.', 'pills'],
        // ---- Spraying ---------------------------------------------------
        ['Mask and gloves before you mix.', 'The mix is the strongest it will ever be.', 'mask'],
        ['Spray with the wind behind you.', 'Never walk into your own mist.', 'windy'],
        ['Do not spray at noon.', 'Heat lifts it off the leaf and into the air you are breathing.', 'back'],
        ['Wash your hands before you eat or smoke.', 'That is how most of it gets swallowed.', 'soap'],
        ['Change out of sprayed clothes at the door.', 'Do not carry it into the house.', 'spray'],
        ['Rinse the sprayer away from the well.', 'Downhill of the water, always.', 'spray'],
        ['Read the label, even the one you know.', 'The dose may have changed since you last bought it.', 'label'],
        ['Never put chemicals in a drink bottle.', 'That is how children are poisoned.', 'bottle'],
        ['Lock the chemicals up.', 'A shed with a latch is not a lock.', 'spray'],
        ['Respect the re-entry time.', 'The label says how long. It means it.', 'label'],
        ['Mind the days before harvest.', 'The waiting time on the label is the one buyers test for.', 'label'],
        ['Mix outside, not in the kitchen.', 'And not with the spoon that goes back in the drawer.', 'back'],
        // ---- Feet and hands ---------------------------------------------
        ['Boots, not slippers, in the field.', 'Snakes, nails and glass do not announce themselves.', 'boots'],
        ['Shake your boots out before you put them on.', 'Something may have moved in overnight.', 'boots'],
        ['Dry your boots upside down.', 'Damp boots are where the itch starts.', 'boots'],
        ['Gloves for the thorny work.', 'A scratch you ignore is the one that swells.', 'gloves'],
        // ---- Tools and machines -----------------------------------------
        ['A sharp bolo is the safe one.', 'A dull blade slips.', 'bolo'],
        ['Cut away from your body. Always.', 'Even when the other way is faster.', 'tools'],
        ['Put the tool down before you answer the phone.', 'One hand cannot do both.', 'phone'],
        ['Check the handle before harvest.', 'A loose head finds a leg.', 'basket'],
        ['Carry the blade pointing down.', 'And never hand it over blade first.', 'tools'],
        ['Turn it off before you clear the jam.', 'Every hand ever lost was cleared with it still running.', 'tractor'],
        ['Guard back on before you start it.', 'Putting it back later means never.', 'back'],
        ['Walk behind the machine before you reverse.', 'Look. Do not assume.', 'tractor'],
        ['No passengers on the tractor.', 'There is one seat, for a reason.', 'tractor'],
        ['Let the engine cool before refuelling.', 'Petrol on hot metal does not forgive.', 'tractor'],
        ['Check the brakes before the slope.', 'Not on it.', 'tractor'],

        // ---- When it goes wrong -----------------------------------------
        ['Wash a cut straight away.', 'Field soil and open skin do not mix.', 'soap'],
        ['Keep a first aid kit in the shed.', 'And know which shelf it is on.', 'firstaid'],
        ['A cut still open tomorrow needs a clinic.', 'Do not wait for it to smell.', 'calendar'],
        ['Put the health centre in your phone tonight.', 'Nobody looks up a number well in an emergency.', 'phone'],
        ['Know who has a vehicle after dark.', 'Ask before you need to.', 'torch'],
        // ---- The crop itself --------------------------------------------
        ['Walk the field before you spray anything.', 'Half the time there is nothing to spray for.', 'spray'],
        ['Look under the leaf, not on top.', 'That is where they live.', 'seedling'],
        ['Pull the weeds while they are small.', 'A week from now it is a job.', 'weeds'],
        ['Rotate what you plant.', 'The same crop twice feeds the same pest twice.', 'pest'],
        ['Keep the seedbed damp, not flooded.', 'Drowned seed does not come back.', 'flood'],
        ['Buy seed you can trace.', 'Cheap seed is expensive in September.', 'seedling'],

        // ---- Water in the field -----------------------------------------
        ['Water early, not at noon.', 'Most of a noon watering never reaches the root.', 'ear'],
        ['Water the soil, not the leaf.', 'Wet leaves overnight is how disease starts.', 'soil'],
        ['Let it dry between waterings at tillering.', 'Roots follow the water down.', 'bottle'],
        ['Turn the pump off before you walk away.', 'Listen for the click.', 'pump'],
        ['Check the hose before the dry weeks.', 'A split you fix now is not a split you fix in a hurry.', 'hose'],
        // ---- Animals ----------------------------------------------------
        ['Water and shade for the animals first.', 'They cannot go and get it themselves.', 'shade'],
        ['Rest the carabao before it is tired.', 'After is too late.', 'nap'],
        ['Check the hooves after wet weeks.', 'Standing in mud softens them.', 'rain'],
        ['No animal works at noon either.', 'Same rule as you.', 'carabao'],
        // ---- The good insects -------------------------------------------
        ['Do not spray while the bees are out.', 'Early morning or late afternoon instead.', 'ear'],
        ['Leave the flowering weeds at the edge.', 'That is where the helpful insects live.', 'weeds'],
        ['Not everything with six legs is a pest.', 'Some of them are eating the pests for you.', 'pest'],
        // ---- After harvest ----------------------------------------------
        ['Dry to fourteen percent, not to "looks dry".', 'Above that it moulds inside the sack.', 'sack'],
        ['Turn the grain every hour on the pavement.', 'The bottom layer never dries on its own.', 'clock'],
        ['Sacks off the floor and off the wall.', 'Air underneath, or the bottom row rots.', 'sack'],
        ['Check the store for droppings.', 'One rat family eats a sack a season.', 'mouse'],
        ['Never dry grain on the road.', 'One truck costs more than the drying time saved.', 'dry'],
        ['Weigh before you sell, not after.', 'Your own scale, your own number.', 'scale'],
        // ---- Records and money ------------------------------------------
        ['Write it down before you forget.', 'Tonight you remember. Next month you will not.', 'notebook'],
        ['Keep the receipts.', 'The ones you throw away are the ones you need.', 'notebook'],
        ['Note the date you sprayed.', 'The re-entry and the harvest wait both count from it.', 'basket'],
        ['Write down what did not work, too.', 'That is the half everybody forgets.', 'notebook'],
        ['Ask two buyers before you sell.', 'Two calls can be a week of wages.', 'notebook'],
        ['Photograph the damage the same day.', 'Insurance and help both ask for a date.', 'notebook'],

        // ---- The end of the day -----------------------------------------
        ['Stop when the light goes.', 'Tired hands are how tools get away from you.', 'soap'],
        ['Sleep is part of the work.', 'Six hours is not a plan, it is a debt.', 'nap'],
        ['One day off a week.', 'The field will not notice. Your back will.', 'back'],
        ['Eat with your family before it goes cold.', 'The work will still be there.', 'moon'],
        ['Close the gate and lock the shed.', 'Then stop thinking about it.', 'canal'],
        ['Charge the phone tonight.', 'You will want it tomorrow.', 'phone'],
        ['Tell somebody where you will be working.', 'Especially if you are going alone.', 'phone'],
        // ---- Fifty more, on the same footing ----------------------------
        ['Do not shelter under a lone tree.', 'It is the first thing lightning finds.', 'lightning'],
        ['Check the roof before the wet months.', 'One loose sheet takes the rest with it.', 'roof'],
        ['Move the sacks off the floor before a storm.', 'Water comes up as often as it comes down.', 'sack'],
        ['Do not cross a flooded road on foot.', 'Six inches moving is enough to take you off your feet.', 'flood'],
        ['Keep the drying tarp where you can reach it.', 'The rain does not wait while you look for it.', 'tarp'],
        ['Watch for snails after the water rises.', 'They travel with it, and they eat young seedlings.', 'snail'],
        ['Start at first light in the hot months.', 'You will finish before the day turns dangerous.', 'clock'],
        ['A wet cloth on the neck helps more than a fan.', 'That is where the blood runs closest.', 'rain'],
        ['Salt and water after two hours in the sun.', 'Cramps are the warning before the collapse.', 'clock'],
        ['Sunglasses if you have them.', 'A day of glare off the water is a headache by evening.', 'bottle'],
        ['Never leave a child or an animal in a parked vehicle.', 'It is hotter in there than in the field.', 'carabao'],
        ['Boil drinking water after a flood.', 'The well is the last thing to clear.', 'flood'],
        ['Do not share one cup around the crew.', 'One sick person becomes five.', 'water'],
        ['Keep the water in the shade.', 'Warm water is water nobody drinks.', 'shade'],
        ['Rinse the jug every day.', 'A film inside it is where the trouble grows.', 'water'],
        ['Sleep under a net in mosquito season.', 'Dengue starts in the morning, not at night.', 'mosquito'],
        ['Empty anything holding still water.', 'Tyres, tins and the drum lid. That is where they breed.', 'mosquito'],
        ['Have your blood pressure checked.', 'Farm work hides it until it does not.', 'stetho'],
        ['Do not work through a fever.', 'A day off now is not a week off later.', 'thermometer'],
        ['Have your eyes tested.', 'Half the near-misses with a blade are people who could not see the edge.', 'eye'],
        ['Wash before you eat, every time.', 'Field hands carry more than soil.', 'soap'],
        ['One person mixes, and that person wears the gloves.', 'Passing the job around passes the exposure around.', 'gloves'],
        ['Do not blow into a blocked nozzle.', 'Use a brush. Never your mouth.', 'spray'],
        ['Mix only what you will use today.', 'Leftover mix has nowhere safe to go.', 'spray'],
        ['Keep bystanders out of the field while you spray.', 'Especially children, and especially downwind.', 'spray'],
        ['Rinse the empty container three times.', 'Then puncture it so nobody reuses it.', 'spray'],
        ['Never store chemicals near feed or seed.', 'One spill contaminates both.', 'store'],
        ['Check the sprayer for leaks before you fill it.', 'A leaking tank empties onto your back.', 'back'],
        ['Change the mask filter when breathing gets harder.', 'A spent filter is a mask you are only pretending to wear.', 'mask'],
        ['Wear closed shoes when carrying sacks.', 'A dropped cavan finds bare toes.', 'sack'],
        ['Wash the mud off boots before the road.', 'Caked soles slide on wet concrete.', 'soap'],
        ['Two people for a heavy sack.', 'Your back has to last the season.', 'sack'],
        ['Lift with the legs, not the waist.', 'And turn with your feet, not your spine.', 'back'],
        ['Keep the blade covered when it is not in your hand.', 'A bolo left leaning is a bolo that falls.', 'bolo'],
        ['Never leave a tool in the path.', 'The next person will be carrying something and looking up.', 'tools'],
        ['Oil the blade after the wet season.', 'Rust eats the edge you spent an hour on.', 'sharpen'],
        ['Do not use a tool as a lever.', 'It bends, then it snaps, then it flies.', 'tools'],
        ['Wear ear protection near the thresher.', 'Hearing does not come back once it has gone.', 'ear'],
        ['Loose clothes and machines do not mix.', 'Roll the sleeves and tuck the shirt.', 'spray'],
        ['Chock the wheels before you crawl under.', 'The handbrake is not a promise.', 'tractor'],
        ['Refuel with the engine off and nobody smoking.', 'Both, every time.', 'tractor'],
        ['Scout the same five spots every week.', 'A pattern only shows if you look in the same places.', 'seedling'],
        ['Take out the volunteer plants between seasons.', 'They carry last season\'s disease into this one.', 'weeds'],
        ['Do not plant into cold wet soil.', 'Wait three dry days; the seed will catch up.', 'soil'],
        ['Space it properly even when seed is cheap.', 'Crowded plants share pests as well as light.', 'pest'],
        ['Drain the field before you spray a herbicide.', 'Standing water carries it off the weed you aimed at.', 'canal'],
        ['Check the intake screen after every storm.', 'The pump burns out on the debris you did not clear.', 'pump'],
        ['Deworm the animals on a schedule, not on a hunch.', 'Write the date where you will see it.', 'pills'],
        ['Keep the drinking trough out of the sun.', 'Warm water grows what the animals drink.', 'bucket'],
        ['Count the spiders before you count the pests.', 'A field with spiders is a field already fighting back.', 'spider'],
        ['Clean the store before the new crop goes in.', 'Last season\'s dust carries this season\'s weevils.', 'weevil'],
        ['Do not stack sacks higher than your shoulder.', 'The one that falls is the one you are standing under.', 'sack'],
        ['Label the sack with the date and the variety.', 'In March you will not remember either.', 'sack'],
        ['Write the yield down the day you weigh it.', 'A number from memory is a number you cannot use.', 'label'],
        ['Keep one notebook, not five.', 'Records in five places are records in none.', 'label'],
        ['Look at tomorrow\'s forecast tonight.', 'Half the wasted mornings were decided the night before.', 'calendar'],
        ['Put the tools back in the same place.', 'Ten seconds now, ten minutes tomorrow.', 'back'],
        ['Do not ride home in the dark without a light.', 'The road is full of people who cannot see you.', 'torch'],
    ];
}
