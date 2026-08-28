<?php

namespace Database\Seeders;

use App\Models\AsCropScene;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Ninety pictures of a crop growing: fifteen plant shapes, six points each.
 *
 * The band names are the ones the crop's own people use — a grain tillers, a
 * root bulks, a tree flushes — because "stage 3 of 6" is a number and
 * "Tillering" is a thing you can go and look at. The line under each is what
 * the plant needs at that point, said in one sentence.
 */
class CropSceneSeeder extends Seeder
{
    /** The six hues, in season order. */
    private const HUE = ['soil', 'sprout', 'leaf', 'bloom', 'fill', 'gold'];

    public function run(): void
    {
        // family => six [label, blurb] in band order.
        $book = [
            'grain' => [
                ['Sown', 'The seed is in and nothing shows yet. Keep the water shallow and the birds off; this is the week the field looks empty and is not.'],
                ['Seedling', 'First blades up. Thin water, no herbicide yet, and pull the weeds by hand while they are still smaller than the crop.'],
                ['Tillering', 'The plant is making its own copies. This is where the yield is decided — feed it nitrogen now, not later.'],
                ['Booting and heading', 'The head is forming inside the stem. Water demand peaks here; a dry week now costs grains that never fill.'],
                ['Grain filling', 'Milk to dough. Keep the water on until the grain hardens, and watch for the birds that have been waiting all season.'],
                ['Ripe for harvest', 'Eighty per cent golden is the moment. Drain the field a week ahead so the ground carries the crew and the sacks.'],
            ],
            'corn' => [
                ['Sown', 'Seed in dry, firm soil. Rain within three days is what you want; a week of wet on ungerminated seed is a replant.'],
                ['Seedling', 'Three to five leaves. Side-dress early and get the weeds out — corn loses more to weeds in the first month than in all the rest.'],
                ['Vegetative', 'Growing fast and hungry. This is the nitrogen window; a pale stand now stays pale.'],
                ['Tasselling and silking', 'The two weeks that make the crop. Heat and drought here take kernels off the cob and they do not come back.'],
                ['Grain filling', 'The kernel is dented and filling. Keep the water steady and check the ears for borers before they get in.'],
                ['Ready to harvest', 'Husks dry and the kernel is hard. Get it in before a storm flattens the stand.'],
            ],
            'cane' => [
                ['Planted', 'Setts in the furrow. Keep them moist until they push; a dry sett is a gap in the row for the next two years.'],
                ['Sprouting', 'Shoots breaking. Weed hard now — cane is slow to close its canopy and the weeds know it.'],
                ['Tillering', 'The stool is filling out. Earth up and feed; every tiller that stands now is a cane you cut later.'],
                ['Grand growth', 'The fastest months. Water and nitrogen, and keep the field clean underneath.'],
                ['Maturing', 'Sugar is going into the cane. Ease off the nitrogen and the water; a lush cane in the last months is a watery cane.'],
                ['Ready to cut', 'Bottom leaves dry and the cane rings hollow. Cut and mill quickly — sugar starts leaving the moment it is down.'],
            ],
            'legume' => [
                ['Sown', 'Seed in warm soil, shallow. If you inoculated, keep the seed out of the sun until it is covered.'],
                ['Seedling', 'First true leaves. Legumes make their own nitrogen — do not feed them like a cereal or you get leaves and no pods.'],
                ['Vegetative', 'Branching out. Keep it weeded and watch the underside of the leaf for the first of the sucking pests.'],
                ['Flowering', 'Flowers open. Do not spray anything harsh now; the bees working this field are what turns flowers into pods.'],
                ['Pod filling', 'Pods setting and swelling. Water matters more here than at any other point of the season.'],
                ['Ready to pick', 'Pods full and starting to dry. Pick in the cool of the morning and dry them properly or they mould in the sack.'],
            ],
            'root' => [
                ['Planted', 'Cuttings or setts in loose soil. Ridge properly now — the root grows into the space you give it and no further.'],
                ['Sprouting', 'Shoots showing. Fill the gaps in the first two weeks; a gap in a root crop is a gap all season.'],
                ['Vine growth', 'Leaves and vines running. Weed and hill up; the crop is under the soil and the soil is where the work is.'],
                ['Root initiation', 'The plant decides how many roots to make. Steady water, and no heavy nitrogen — it feeds the leaf at the root\'s expense.'],
                ['Bulking', 'The root is swelling. This is where the weight comes from, so keep the moisture even and the field clean.'],
                ['Ready to lift', 'Leaves yellowing and the crown cracking the soil. Lift on a dry day and cure them in the shade before they go in a sack.'],
            ],
            'leafy' => [
                ['Sown', 'Seed in a fine bed, barely covered. Keep it damp and shaded; leafy seed dries out in an afternoon.'],
                ['Seedling', 'Two true leaves. Thin now, hard-heartedly — crowded seedlings share every pest they have between them.'],
                ['Leaf growth', 'Filling out. Steady nitrogen and steady water; a check now shows as a small, bitter leaf later.'],
                ['Heading', 'Hearts forming. Keep the water even — swinging between wet and dry is what splits a cabbage.'],
                ['Nearly ready', 'Full and firm. Check the underside of the outer leaves; this is the week the worms find it.'],
                ['Ready to cut', 'Cut in the cool of the morning and get it out of the sun. Leafy crops lose their price in an hour on a hot tricycle.'],
            ],
            'vine' => [
                ['Sown', 'Seed in hills, and the trellis goes up now, not later. A vine that has run along the ground does not go back up.'],
                ['Seedling', 'First runners. Protect the young leaf from the beetles; they can strip a hill in two days at this size.'],
                ['Vine growth', 'Running and climbing. Train the vines and keep the base clear so the air moves and the mildew does not settle.'],
                ['Flowering', 'Male flowers first, then female. If the bees are not coming you will have to hand-pollinate at dawn.'],
                ['Fruit set', 'Fruit swelling. Steady water — a dry spell now gives you a bitter, misshapen fruit and no way to fix it.'],
                ['Ready to pick', 'Pick young and pick often. A vine left to ripen one fruit stops making the next ten.'],
            ],
            'fruitveg' => [
                ['Sown', 'Seedbed sown and covered. Keep it warm and moist and out of direct rain until they push.'],
                ['Transplanted', 'Set out in the evening, watered in. Shade them for three days; a transplant burnt on day one never catches up.'],
                ['Vegetative', 'Bushing out. Stake before they need it, and take the lowest leaves off so nothing touches the soil.'],
                ['Flowering', 'Flowers setting. Calcium and steady water now, or you meet blossom-end rot when the fruit is half-grown.'],
                ['Fruit filling', 'Fruit swelling and colouring. Do not let the field dry out and then flood it — that is how fruit splits.'],
                ['Ready to pick', 'Pick as they colour, every second day. Fruit left on the bush tells the plant to stop making more.'],
            ],
            'bulb' => [
                ['Planted', 'Sets or seed in fine, well-drained soil. Bulbs rot in a wet bed faster than anything else in the field.'],
                ['Sprouting', 'Green tips through. Weed by hand — the roots are shallow and a hoe cuts them without you noticing.'],
                ['Leaf growth', 'Every leaf now is a ring in the bulb later. Feed it while the leaves are still coming.'],
                ['Bulb initiation', 'The plant switches from leaf to bulb. Stop the nitrogen here or you get a fat neck and a small bulb.'],
                ['Bulbing', 'The bulb is filling. Ease the water off as the leaves start to soften.'],
                ['Ready to lift', 'Tops down and necks soft. Lift and cure in the shade for two weeks, or they will not keep to market.'],
            ],
            'banana' => [
                ['Planted', 'Sucker in a well-dug hole with the soil firmed back. Water it in and mulch heavily.'],
                ['Establishing', 'New leaves opening. Keep the base clean and the mulch thick; the roots are shallow and near the surface.'],
                ['Vegetative', 'Building the mat. De-sucker to the ones you want — a crowded mat gives small bunches from all of them.'],
                ['Shooting', 'The bunch has emerged. Prop it now; a loaded plant goes over in the first strong wind and takes the season with it.'],
                ['Bunch filling', 'Fingers filling out. Bag the bunch, keep the water steady, and remove the bell once the last hand has set.'],
                ['Ready to harvest', 'Fingers rounded and the angles gone. Cut with the bunch supported — bruised fruit is unsellable fruit.'],
            ],
            'palm' => [
                ['Planted', 'Seedling out with the collar at soil level. Ring-weed and keep the circle clear for the first two years.'],
                ['Establishing', 'New fronds opening. Water through the dry months; a palm checked young is a palm behind for a decade.'],
                ['Vegetative', 'Crown building. Feed potassium — the fruit takes more of it than anything else the palm needs.'],
                ['Flowering', 'Inflorescences opening. Keep the crown clean and watch for beetles in the spear.'],
                ['Fruit filling', 'Nuts filling on the bunch. Steady moisture, and clear the circle so the fallen ones are easy to find.'],
                ['Ready to harvest', 'Bunches mature. Harvest on a schedule, not on a hunch, and never stand under a bunch being cut.'],
            ],
            'tree' => [
                ['Planted', 'Young tree in a wide hole, staked and watered. Wide beats deep — the roots run out, not down.'],
                ['Establishing', 'Settling in. Ring-weed, mulch and water through the dry season; the first two years decide the next twenty.'],
                ['Vegetative flush', 'Pushing new growth. Prune for shape and let the light into the middle of the canopy.'],
                ['Flowering', 'In flower. Do not spray anything that kills a bee, and keep the water even — stress now drops the whole set.'],
                ['Fruit development', 'Fruit sizing. Thin if the tree has set too heavily; a hundred good fruit beat three hundred small ones.'],
                ['Ready to harvest', 'Fruit mature. Pick with a stem on and handle it once — every extra handling is a bruise you sell at a discount.'],
            ],
            'shrub' => [
                ['Planted', 'Seedling out under some shade. Young bushes burn in full sun before they have the leaf to cope with it.'],
                ['Establishing', 'Taking hold. Mulch heavily and keep the weeds off the collar.'],
                ['Vegetative', 'Bush building. Prune to open the middle — an airless bush is where disease starts.'],
                ['Flowering', 'In flower. Keep the water steady; a shrub in flower under stress drops the flowers and you wait a year.'],
                ['Cherry filling', 'Fruit filling and colouring. Feed it now, because this is when the bush is spending everything it has.'],
                ['Ready to pick', 'Pick only the ripe ones and go back for the rest. Stripping the branch mixes green with ripe and drops the grade.'],
            ],
            'spiky' => [
                ['Planted', 'Suckers or crowns in, firm and upright. A leaning plant grows a leaning fruit.'],
                ['Establishing', 'Rooting in. Keep the weeds down; the plant has no canopy yet to do it for you.'],
                ['Vegetative', 'Leaves lengthening. Feed through the leaf as well as the soil — the roots on this one are small for the plant.'],
                ['Forcing and flowering', 'Flowering induced or natural. From here the clock is fixed, so plan the harvest crew now.'],
                ['Fruit development', 'Fruit filling and colouring. Shade the crown in fierce sun or the shoulder scalds.'],
                ['Ready to harvest', 'Colour breaking at the base and the smell there. Cut with a short stem and keep it out of the sun.'],
            ],
            'mixed' => [
                ['Planted', 'In the ground. The first fortnight is the one that decides whether this is a crop or a replant.'],
                ['Seedling', 'Up and small. Weeds are the whole battle at this size — the crop cannot compete yet and will not tell you until it is losing.'],
                ['Growing', 'Making its frame. Feed and water steadily; a check now shows up in the yield and never in the leaf.'],
                ['Flowering', 'In flower. The most sensitive fortnight of any season — do not let it go short of water, and go easy with anything sprayed.'],
                ['Filling', 'Filling out. Even moisture and a clean field. Most of what you harvest is put on here.'],
                ['Ready to harvest', 'Ready. Pick in the cool of the day, handle it once, and get it out of the sun.'],
            ],
        ];

        $n = 0;
        $seen = [];
        foreach ($book as $family => $bands) {
            foreach ($bands as $band => [$label, $blurb]) {
                $seen[] = $family . ':' . $band;
                AsCropScene::updateOrCreate(
                    ['family' => $family, 'band' => $band],
                    ['label' => $label, 'hue' => self::HUE[$band], 'blurb' => $blurb, 'deleteStatus' => 1],
                );
                $n++;
            }
        }

        // Retire, do not delete: the app's convention everywhere else.
        foreach (AsCropScene::query()->get() as $row) {
            if (! in_array($row->family . ':' . $row->band, $seen, true) && $row->deleteStatus === 1) {
                $row->update(['deleteStatus' => 0]);
            }
        }

        Cache::forget('as_crop_scenes.map');
        $this->command?->info("Crop scenes: {$n} pictures across " . count($book) . ' plant families.');
    }
}
