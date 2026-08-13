<?php

namespace App\Support;

/**
 * What to actually do, stage by stage, crop by crop.
 *
 * CropStages says where the plant is; this says what a grower does about it.
 * Each stage gets two lists on purpose:
 *
 *   do    — the work of the stage, in the order it is usually done.
 *   watch — what goes wrong here, so it is recognised early rather than
 *           diagnosed afterwards.
 *
 * The content is ordinary Philippine extension guidance (PhilRice, PhilMech,
 * DA-BPI and the usual cooperative practice), written the way a farmhand
 * would say it rather than the way a manual would. It is a guide: a grower's
 * own records beat any of it, and the sheet says so.
 *
 * Keyed by crop, then by the stage's index in CropStages::CROPS[crop]['stages'].
 */
class CropStageTips
{
    public const TIPS = [
        'rice' => [
            0 => [
                'do' => [
                    'Keep water shallow — 2 to 3 cm. Deep water at this point drowns new roots.',
                    'Walk the field on day 3 and again on day 7 and replace any seedling that did not take.',
                    'Keep the levees tight; a leaking levee costs you the fertiliser you are about to apply.',
                ],
                'watch' => [
                    'Yellowing at the tips is normal transplanting shock and passes in about a week.',
                    'Golden apple snails go for the softest seedlings first — hand-pick at dawn, or bait along the channels.',
                ],
            ],
            1 => [
                'do' => [
                    'First nitrogen goes on now, on a field with only a film of water so it does not run off.',
                    'Weed before day 15. Weeds taken out later have already cost you the tillers they crowded out.',
                    'Hold water at 2 to 3 cm to suppress the next flush of weeds.',
                ],
                'watch' => [
                    'Whorl maggot damage looks like ragged, chewed leaf edges; the crop usually grows past it.',
                    'Patches that stay stunted after fertiliser are often zinc deficiency, not nitrogen.',
                ],
            ],
            2 => [
                'do' => [
                    'Second nitrogen split. This is the fertiliser that decides how many stems you carry.',
                    'Raise water to 3 to 5 cm now that the plant is established.',
                    'Count tillers on a few hills — under 15 by day 35 means the field is behind.',
                ],
                'watch' => [
                    'Rice black bug and green leafhopper build up here; check at night with a torch.',
                    'Too much nitrogen now makes soft, leafy plants that lodge later. Follow the plan, not the colour.',
                ],
            ],
            3 => [
                'do' => [
                    'The panicle-initiation fertiliser — usually the biggest single decision of the season.',
                    'Never let the field dry between now and flowering.',
                    'Split the top-dress if the leaves are already dark; the plant cannot use it all at once.',
                ],
                'watch' => [
                    'Water stress here shows up as fewer grains per panicle, and nothing later fixes it.',
                    'Sheath blight starts at the waterline in a dense crop — look low, not high.',
                ],
            ],
            4 => [
                'do' => [
                    'Keep water steady at 3 to 5 cm through booting.',
                    'Scout twice a week; this is the last comfortable window for a spray decision.',
                    'Clear the bunds of tall weeds where stem borers shelter.',
                ],
                'watch' => [
                    'Deadhearts and whiteheads mean stem borer, and whiteheads mean you are already late.',
                    'Neck blast shows on the panicle base — humid, overcast weeks are the risky ones.',
                ],
            ],
            5 => [
                'do' => [
                    'Do nothing that stresses the crop. No drainage, no unnecessary traffic, no spraying in the heat.',
                    'Hold 3 to 5 cm of water through the whole flowering period.',
                    'If you must spray, do it late afternoon when flowering has closed for the day.',
                ],
                'watch' => [
                    'A hot, dry spell during flowering causes empty grains — you will only see it at harvest.',
                    'Rice bug arrives at first milk; it is the classic cause of pecky, discoloured grain.',
                ],
            ],
            6 => [
                'do' => [
                    'Keep the field moist through milk and dough, then drain gradually about 10 days before harvest.',
                    'Book the harvester and the drying space now, not in the last week.',
                    'Set up rat guards and bird scaring along the edges.',
                ],
                'watch' => [
                    'Draining too early shrivels the last grains; too late and the machine bogs down.',
                    'Lodging after a storm means harvesting at once, even a few days early.',
                ],
            ],
            7 => [
                'do' => [
                    'Harvest when 80 to 85% of the grains have gone golden — usually 30 to 35 days after flowering.',
                    'Thresh the same day, and get the grain under a dryer or on a mat within a day.',
                    'Dry down to 14% for selling, 12% if it is going into storage.',
                ],
                'watch' => [
                    'Grain left in the heap overnight heats and yellows, and buyers pay less for it.',
                    'Over-drying cracks the grain and the mill recovery drops.',
                ],
            ],
        ],

        'corn' => [
            0 => [
                'do' => [
                    'Keep the topsoil damp but never soaked until the shoot is through.',
                    'Replant gaps within the first week so the field matures evenly.',
                ],
                'watch' => [
                    'Cutworms sever seedlings at the base overnight; look for wilted plants lying flat.',
                    'Crusted soil after heavy rain can stop emergence — break it gently.',
                ],
            ],
            1 => [
                'do' => [
                    'First side-dress of nitrogen, banded beside the row rather than broadcast.',
                    'Weed by day 20. Corn loses more to early weeds than to almost anything else.',
                    'Thin doubles if you planted heavy; two plants in one hill make two small ears.',
                ],
                'watch' => [
                    'Fall armyworm feeds inside the whorl — check the funnel of the newest leaf.',
                    'Purple leaves usually mean cold, wet soil locking up phosphorus, not a shortage of it.',
                ],
            ],
            2 => [
                'do' => [
                    'Second side-dress before the plant is knee to waist high.',
                    'Hill up soil around the base for anchorage against wind.',
                    'Make sure water is available from here on; the plant is setting its final size.',
                ],
                'watch' => [
                    'A dry fortnight now shortens the cob before you ever see a tassel.',
                    'Leaf blight starts on the lower leaves in humid weather.',
                ],
            ],
            3 => [
                'do' => [
                    'Keep the soil moist — this is the thirstiest week of the crop.',
                    'Finish all field traffic before the tassel opens.',
                ],
                'watch' => [
                    'Water stress at tasselling delays silking, and pollen with no silk is a bare cob.',
                ],
            ],
            4 => [
                'do' => [
                    'Irrigate every few days if there is no rain. Nothing else matters as much as water now.',
                    'Leave the crop alone otherwise; pollination is happening.',
                ],
                'watch' => [
                    'Silks cut by earworm or drought mean missing kernels at the tip.',
                    'Very hot, dry afternoons kill pollen — early morning irrigation helps.',
                ],
            ],
            5 => [
                'do' => [
                    'Steady water through filling; the ear is putting on its weight.',
                    'Check a few ears by peeling back the husk to judge progress.',
                ],
                'watch' => [
                    'Earworm at the tip is normal and tolerable; rot spreading down the ear is not.',
                    'Stalk rot shows as plants that snap or lean late in the season.',
                ],
            ],
            6 => [
                'do' => [
                    'For grain, harvest at the black layer — around 20 to 25% moisture — then dry to 14%.',
                    'For green corn, pick at 70 to 75 days while the kernel still spurts milk.',
                    'Dry on a clean surface; grit in the grain costs you at the buying station.',
                ],
                'watch' => [
                    'Aflatoxin follows late harvest and slow drying more than anything else.',
                ],
            ],
        ],

        'banana' => [
            0 => [
                'do' => ['Water weekly through the first two months.', 'Mulch the base to hold moisture and keep weeds down.'],
                'watch' => ['Corm weevil damage shows as slow, stunted suckers.', 'Standing water rots a new corm quickly.'],
            ],
            1 => [
                'do' => [
                    'Feed every six to eight weeks; bananas are heavy feeders, potassium above all.',
                    'Desucker to one follower so the mat does not compete with itself.',
                    'Remove dry and diseased leaves and take them out of the block.',
                ],
                'watch' => ['Sigatoka spots start as fine streaks on the underside of a leaf.', 'Yellowing that climbs from the oldest leaves upward can be Panama disease — mark that mat.'],
            ],
            2 => [
                'do' => ['Keep potassium up; this is what the bunch will spend.', 'Prop tall plants before they are carrying weight.'],
                'watch' => ['Wind damage now costs the leaves that would have filled the bunch.'],
            ],
            3 => [
                'do' => ['Bag the bunch once the fingers have turned up.', 'Remove the bell after the last hand has set.', 'Deleaf anything shading the bunch.'],
                'watch' => ['Thrips scar the peel inside a badly fitted bag.'],
            ],
            4 => [
                'do' => ['Water steadily; the bunch fills on what the plant can draw.', 'Check props weekly as the weight builds.'],
                'watch' => ['A leaning plant will go over in the first strong wind, bunch and all.'],
            ],
            5 => [
                'do' => ['Harvest at three-quarters full for a market that is a day or more away.', 'Cut with the stalk and handle the fingers as little as possible.'],
                'watch' => ['Latex staining on the peel is what buyers downgrade first.'],
            ],
        ],

        'mango' => [
            0 => [
                'do' => ['Induce only on mature, rested flushes.', 'Water the tree well before induction, not after.'],
                'watch' => ['Inducing a tree that is still flushing gives leaves instead of flowers.'],
            ],
            1 => [
                'do' => ['Protect the panicles on schedule against hopper and anthracnose.', 'Avoid overhead watering while the flowers are open.'],
                'watch' => ['Rain during flowering brings anthracnose, and anthracnose in flower means no crop.'],
            ],
            2 => [
                'do' => ['Keep soil moisture steady — swings cause fruit drop.', 'Continue the spray programme through fruit set.'],
                'watch' => ['Most of what set will drop; that is normal, and panic-spraying does not stop it.'],
            ],
            3 => [
                'do' => ['Bag the fruit once it is chicken-egg size.', 'Feed potassium for size and sweetness.'],
                'watch' => ['Fruit fly stings before bagging are the usual cause of rejects at packing.'],
            ],
            4 => [
                'do' => ['Ease off water in the last two weeks for sugar.', 'Test a few fruit by specific gravity rather than by eye.'],
                'watch' => ['Heavy rain close to maturity splits fruit and dilutes flavour.'],
            ],
            5 => [
                'do' => ['Harvest with a short pedicel and let the latex drain away from the skin.', 'Keep the fruit out of the sun between tree and shed.'],
                'watch' => ['Latex burn shows up as black streaks two days later, in the buyer\'s hands.'],
            ],
        ],

        'sugarcane' => [
            0 => [
                'do' => ['Keep the furrow moist until the buds are through.', 'Fill gaps by day 30 so the stand is even.'],
                'watch' => ['Setts planted too deep rot before they sprout.'],
            ],
            1 => [
                'do' => ['First fertiliser at tillering — the number of millable canes is decided here.', 'Weed thoroughly; cane is slow to close the row.'],
                'watch' => ['Early shoot borer kills the primary shoot and shows as deadhearts.'],
            ],
            2 => [
                'do' => ['Water and nitrogen through the grand growth phase.', 'Earth up to support the stools and bury the weeds.', 'Detrash the lower leaves for airflow.'],
                'watch' => ['Lodged cane is harder to cut and loses sugar.'],
            ],
            3 => [
                'do' => ['Stop nitrogen; it keeps the cane growing when it should be storing sugar.', 'Taper irrigation as the crop matures.'],
                'watch' => ['Late nitrogen or late rain drops the Brix at the mill.'],
            ],
            4 => [
                'do' => ['Harvest on the mill schedule and deliver the same day where you can.', 'Cut at ground level — the bottom internodes carry the most sugar.'],
                'watch' => ['Cut cane left standing for days loses weight and sugar every hour.'],
            ],
        ],

        'coconut' => [
            0 => [
                'do' => ['Water through the dry months of the first year.', 'Keep a weed-free ring a metre and a half wide.'],
                'watch' => ['Rhinoceros beetle bores into the crown of young palms.'],
            ],
            1 => [
                'do' => ['Feed twice a year, salt included.', 'Keep the ring clean and mulched.'],
                'watch' => ['Neglected young palms simply take longer to bear — years, not months.'],
            ],
            2 => [
                'do' => ['Potassium and salt as the palm starts to flower.', 'Clear old fronds and nuts that harbour beetles.'],
                'watch' => ['Button shedding is normal in the first flowerings.'],
            ],
            3 => [
                'do' => ['Harvest every 45 to 60 days rather than letting nuts drop.', 'Keep the tree base clear so pickers can work safely.'],
                'watch' => ['Nuts left to fall are nuts that split, sprout, or feed the rats.'],
            ],
        ],

        'vegetables' => [
            0 => [
                'do' => ['Water lightly and often; the roots are shallow.', 'Shade at midday in harsh weather until they harden.'],
                'watch' => ['Damping-off takes whole trays in wet, still conditions.'],
            ],
            1 => [
                'do' => ['Nitrogen for frame and leaf.', 'Stake or trellis before the plants need it, not after.', 'Mulch to keep soil off the leaves.'],
                'watch' => ['Aphids and whitefly build up fast in the dry season.'],
            ],
            2 => [
                'do' => ['Keep moisture even — swings cause flower drop and split fruit.', 'Ease off nitrogen; too much now gives leaves instead of fruit.'],
                'watch' => ['Blossom-end rot is a calcium and watering problem, not a disease.'],
            ],
            3 => [
                'do' => ['Potassium through fruiting.', 'Pick regularly; an over-ripe fruit left on the plant slows the next one.'],
                'watch' => ['Fruit and pod borers arrive with the first fruit.'],
            ],
            4 => [
                'do' => ['Harvest in the cool of the morning and get the produce into shade at once.', 'Grade as you pick — it is faster than sorting twice.'],
                'watch' => ['Field heat left in the crate is what costs you a day of shelf life.'],
            ],
        ],
    ];

    /**
     * Direct-seeded rice differs from transplanted rice in its first two
     * stages and nowhere else: there is no transplant to recover from, and
     * the weeks the transplanted crop spends in a seedbed are spent in the
     * field instead — which is why weeds decide a DSR crop more than almost
     * anything else. From tillering onwards the work is the same, so the rest
     * of the table is shared.
     */
    private const DIRECT_OVERRIDES = [
        'rice' => [
            0 => [
                'do' => [
                    'Keep the field saturated but not flooded until the shoots are through — seed under standing water rots.',
                    'Sow onto a level bed. Every dip becomes a puddle that drowns its seed, every hump a dry patch that does not germinate.',
                    'Plan the first weed pass now: a direct-seeded field and its weeds start on the same day.',
                ],
                'watch' => [
                    'Birds and rats take broadcast seed before it is even up. Watch the first three days.',
                    'A crust after heavy rain traps the shoots underneath it — break it gently.',
                ],
            ],
            1 => [
                'do' => [
                    'Bring water up to 2–3 cm once the seedlings stand, and hold it there to hold the weeds down.',
                    'First nitrogen at 10–15 days, on a field with only a film of water.',
                    'Thin or fill in the worst patches while the plants are still small enough to move.',
                ],
                'watch' => [
                    'Weeds are the single biggest cause of a poor direct-seeded crop, and they are cheapest to beat in this fortnight.',
                    'Golden apple snails clear whole patches of young seedlings overnight.',
                ],
            ],
        ],
    ];

    /**
     * @param  string|null  $counter  the count the stage was read in. Rice in
     *                                DAS was direct seeded and wants its own
     *                                first-fortnight guidance.
     *
     * @return array{do: array<int, string>, watch: array<int, string>}
     */
    public static function for(?string $crop, ?int $stageIndex, ?string $counter = null): array
    {
        $key = CropStages::normalize($crop);
        if ($key === null || $stageIndex === null) {
            return ['do' => [], 'watch' => []];
        }

        $direct = $counter !== null && strtoupper($counter) !== 'DAT';
        $rows = ($direct ? (self::DIRECT_OVERRIDES[$key][$stageIndex] ?? null) : null)
            ?? (self::TIPS[$key][$stageIndex] ?? []);

        return [
            'do' => $rows['do'] ?? [],
            'watch' => $rows['watch'] ?? [],
        ];
    }

    /** Every tip for a crop, so a page can show the whole season at once. */
    public static function allFor(?string $crop): array
    {
        $key = CropStages::normalize($crop);

        return $key ? (self::TIPS[$key] ?? []) : [];
    }

    /** How many tips the catalogue holds — used by the tip-of-the-day pool. */
    public static function count(): int
    {
        $n = 0;
        foreach (self::TIPS as $stages) {
            foreach ($stages as $rows) {
                $n += count($rows['do'] ?? []) + count($rows['watch'] ?? []);
            }
        }

        return $n;
    }
}
