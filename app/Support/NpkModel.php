<?php

namespace App\Support;

/**
 * NPK Plus's need model (2026-10-08): how much of each nutrient a crop needs
 * from fertilizer for the farmer's yield goal, on their soil. The arithmetic
 * runs in the page (it answers as the farmer types); this class holds the
 * numbers it runs on, in one place, with where they come from.
 *
 * N, P2O5, K2O. The official guide rate for the crop (NpkCrops) is written
 * for a good harvest of about GUIDE_YIELD, or 1.25 times the national
 * average where no source names one. The share of the crop's uptake the
 * guide expects fertilizer to cover is kept, so the need grows with the
 * yield goal: need = guide rate x goal / guide yield. A crop with no guide
 * counts from its uptake per ton: goal x uptake x (1 - soil share) /
 * recovery. Each is then sized by the soil (ADJUST) and, for trees, by the
 * number of plants. A legume makes its own nitrogen.
 *
 * The soil's own share (SOIL_SHARE) is what omission plots show: a field
 * given no nitrogen still yields about two thirds of its goal, one given
 * no phosphorus or potassium most of it. It makes each plank of the barrel:
 * soil share + (1 - soil share) x the part of the need the plan covers.
 *
 * Secondary, micro and beneficial elements (ELEMENTS) are needed from a bag
 * only where a shortfall is likely: a score from the crop's sensitivity, the
 * soil type, the pH and the soil's conditions decides none, watch (half the
 * usual corrective dose) or likely (the dose); a soil test overrides it.
 */
final class NpkModel
{
    public const TEXTURES = [
        'sandy' => ['Sandy (light)', 'Gritty, drains fast and dries quickly'],
        'loam' => ['Loam (medium)', 'Crumbly, holds water and still drains'],
        'clay' => ['Clay (heavy)', 'Sticky when wet, cracks when dry'],
        'unsure' => ['Not sure', 'NPK Plus counts it as a loam'],
    ];

    /** Beyond App\Support\SoilConditions, for this calculator only. */
    public const MORE_CONDITIONS = [
        'low_om' => ['Low organic matter', 'Pale, hard soil that crusts; little manure or straw goes back'],
        'peat' => ['Peat or muck', 'Very dark, spongy, light when dry'],
        'waterlogged' => ['Waterlogged', 'Water sits for days after rain (not counting a flooded rice paddy)'],
    ];

    /**
     * Omission plots: the share of the yield goal a field reaches with none of
     * that nutrient. Rice and corn from the trials (irrigated rice in
     * Indonesia and across Asia, PhilRice's soil supply figures; corn from
     * China and Southeast Asia); other crops a little lower in nitrogen,
     * since vegetables answer it more.
     */
    public const SOIL_SHARE = ['N' => 0.6, 'P2O5' => 0.85, 'K2O' => 0.85];

    public const SOIL_SHARE_CROP = [
        'rice' => ['N' => 0.7, 'P2O5' => 0.92, 'K2O' => 0.92], 'rice_dsr_wet' => ['N' => 0.7, 'P2O5' => 0.92, 'K2O' => 0.92],
        'rice_dsr_dry' => ['N' => 0.7, 'P2O5' => 0.85, 'K2O' => 0.82], 'corn_yellow' => ['N' => 0.7, 'P2O5' => 0.88, 'K2O' => 0.88],
        'corn_sweet' => ['N' => 0.7, 'P2O5' => 0.88, 'K2O' => 0.88],
    ];

    /** Recovery in the first season where the crop's source gives none. */
    public const RECOVERY = ['N' => 0.4, 'P2O5' => 0.2, 'K2O' => 0.5];

    /** The yield a crop's guide rate is written for, where its source says. */
    public const GUIDE_YIELD = ['corn_yellow' => 6.8];

    /**
     * How the soil sizes the N, P2O5 and K2O need (multipliers), and the
     * soil's own share (share: multipliers on SOIL_SHARE). Kept within 0.5 to
     * 1.8 when several apply.
     */
    public const ADJUST = [
        'sandy' => ['N' => 1.15, 'K2O' => 1.2, 'share' => ['N' => 0.9, 'K2O' => 0.9], 'why' => 'Sandy soil lets nitrogen and potassium wash down'],
        'clay' => ['K2O' => 0.9, 'why' => 'Clay holds potassium'],
        'strongAcid' => ['P2O5' => 1.3, 'why' => 'Very acid soil (pH under 5) locks up phosphorus'],
        'acid' => ['P2O5' => 1.2, 'why' => 'Acid soil locks up phosphorus'],
        'alkaline' => ['P2O5' => 1.15, 'N' => 1.05, 'why' => 'Alkaline soil locks up phosphorus and loses some urea nitrogen to the air'],
        'strongAlk' => ['P2O5' => 1.25, 'N' => 1.1, 'why' => 'Strongly alkaline soil (pH over 8) locks up phosphorus and loses urea nitrogen to the air'],
        'sodic' => ['N' => 1.1, 'K2O' => 1.1, 'why' => 'Sodic soil loses nitrogen to the air, and sodium crowds out potassium'],
        'saline' => ['K2O' => 1.15, 'N' => 1.05, 'why' => 'On salty soil potassium helps the crop keep the salt out'],
        'acid_sulfate' => ['P2O5' => 1.3, 'why' => 'Acid sulfate soil locks up phosphorus'],
        'low_om' => ['N' => 1.15, 'share' => ['N' => 0.9], 'why' => 'Low organic matter gives less nitrogen of its own'],
        'peat' => ['N' => 0.85, 'share' => ['N' => 1.1], 'why' => 'Peat gives more nitrogen of its own'],
        'waterlogged' => ['N' => 1.1, 'why' => 'Waterlogged upland soil loses nitrogen'],
        'testLow' => ['N' => 1.2, 'P2O5' => 1.3, 'K2O' => 1.3, 'share' => ['N' => 0.85, 'P2O5' => 0.85, 'K2O' => 0.85], 'why' => 'Your soil test reads low'],
        'testHigh' => ['N' => 0.8, 'P2O5' => 0.6, 'K2O' => 0.6, 'share' => ['N' => 1.1, 'P2O5' => 1.12, 'K2O' => 1.12], 'why' => 'Your soil test reads high'],
    ];

    /**
     * The usual seeding rate or stand per hectare: w = kg of seed or planting
     * material, n = plants (or hills, cuttings, trees), from Philippine
     * guides (DA, PhilRice, BPI, ATI, PhilMech, VSU, CMU) researched on
     * 2026-10-08; sweet corn and the dry seeded rice width lean on a seed
     * company guide and IRRI. GENERATED from the scratchpad's
     * npk/seeding.json by gen_seeding.py.
     */
    public const SEEDING = [
        'rice' => ['w' => [18, 40], 'n' => null, 'countWord' => null, 'note' => 'PhilRice advises 20 to 40 kg of certified inbred seed per hectare for transplanting (40 kg is the standard), and hybrid seed needs about 18 to 20 kg since its 400 square meter seedbed is sown at 50 g per square meter.',
            'sources' => [['label' => 'PhilRice, 40kg of high quality rice seeds enough for 1 ha field', 'url' => 'https://www.philrice.gov.ph/40kg-of-high-quality-rice-seeds-enough-for-1-ha-field/'], ['label' => 'PhilRice, Unlearning rice seed myths', 'url' => 'https://www.philrice.gov.ph/unlearning-rice-seed-myths/'], ['label' => 'ATI CAR, Planting guide for hybrid rice', 'url' => 'https://ati2.da.gov.ph/ati-car/content/sites/default/files/2022-12/planting_guide_for_hybrid_rice.pdf']]],
        'rice_dsr_wet' => ['w' => [40, 80], 'n' => null, 'countWord' => null, 'note' => 'PhilRice recommends 60 to 80 kg per hectare for wet direct seeding by broadcast and about 40 to 60 kg when seeds go in rows with a drum seeder.',
            'sources' => [['label' => 'PhilRice, Farmers urged to practice recommended seeding rate', 'url' => 'https://www.philrice.gov.ph/farmers-urged-to-practice-recommended-seeding-rate/'], ['label' => 'PhilRice, seeding rate articles', 'url' => 'https://www.philrice.gov.ph/tag/seeding-rate/'], ['label' => 'DA (from PNA), Rice farmers urged to follow seeding rate', 'url' => 'https://www.da.gov.ph/from-pna-rice-farmers-urged-to-follow-seeding-rate-to-improve-yield/']]],
        'rice_dsr_dry' => ['w' => [40, 80], 'n' => null, 'countWord' => null, 'note' => 'PhilRice gives 60 to 80 kg per hectare for direct seeded rice without splitting wet and dry, while the IRRI Rice Knowledge Bank puts dry seeding at 40 to 45 kg by seed drill and 50 to 60 kg broadcast.',
            'sources' => [['label' => 'PhilRice, Farmers urged to practice recommended seeding rate', 'url' => 'https://www.philrice.gov.ph/farmers-urged-to-practice-recommended-seeding-rate/'], ['label' => 'Rice Knowledge Bank Assam (IRRI and AAU), Paddy crop establishment', 'url' => 'https://rkbassam.aau.ac.in/rkbassam.in/httpdocs/uploads/factsheets/pdf/6657eeeb4f0b1.pdf']]],
        'corn_yellow' => ['w' => [18, 24], 'n' => [60000, 91000], 'countWord' => 'plants', 'note' => 'DA gives one 18 kg bag of hybrid seed per hectare, and the DA Region 2 technoguide asks for 22 to 24 kg at 70 cm rows and 18 to 20 cm hills for at least 75,000 plants, with double row planting reaching about 91,000.',
            'sources' => [['label' => 'DA RFO 2, Corn technoguide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/corn_techno_guide_final.pdf'], ['label' => 'DA RFO 2, Ang dobleng hanay sa pagtatanim ng mais', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2025/07/ANG-DOBLENG-HANAY-SA-PAGTATANIM-NG-MAIS.pdf'], ['label' => 'Context.ph, DA provides free seeds (18 kg hybrid bag per hectare)', 'url' => 'https://context.ph/2023/01/19/da-provide-free-seeds-and-fertilizers-to-qualified-corn-growers/']]],
        'corn_sweet' => ['w' => [8, 15], 'n' => [50000, 66667], 'countWord' => 'plants', 'note' => 'No Philippine guide states a sweet corn rate, so this follows a seed company guideline of 55,000 to 60,000 plants per hectare from roughly 8 to 15 kg depending on seed size, which matches 75 cm rows at 20 to 25 cm.',
            'sources' => [['label' => 'Starke Ayres, Sweetcorn production guideline (South Africa, not Philippine)', 'url' => 'https://www.starkeayres.com/uploads/files/Sweetcorn-Production-Guideline-2019.pdf'], ['label' => 'UPLB thesis, sweet corn at 45,000 to 69,000 plants per hectare (Capiz)', 'url' => 'https://www.ukdr.uplb.edu.ph/etd-undergrad/265']]],
        'sorghum' => ['w' => [8, 10], 'n' => null, 'countWord' => null, 'note' => 'The ICRISAT, MMSU and PCARRD book on sweet sorghum in the Philippines lists 8 kg of seed per hectare, and Indian sweet sorghum practice goes to about 10 kg.',
            'sources' => [['label' => 'ICRISAT, MMSU, PCARRD, Sweet Sorghum in the Philippines: Status and Future (2011)', 'url' => 'http://oar.icrisat.org/230/1/148_2011Sweet_sorghum_Philippines.pdf'], ['label' => 'TNAU Agritech, sweet sorghum (India, secondary)', 'url' => 'https://agritech.tnau.ac.in/agriculture/CropProduction/Sugarcrops/sugarcrops_sweetsorghum.html']]],
        'mungbean' => ['w' => [18, 20], 'n' => null, 'countWord' => null, 'note' => 'DA guides drill 18 to 20 kg of mungbean seed per hectare in rows 50 to 60 cm apart.',
            'sources' => [['label' => 'DA HVCDP, Mungbean production guide', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Mungbean-Production-Guide.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'peanut' => ['w' => [90, 100], 'n' => null, 'countWord' => null, 'note' => 'BPI says a hectare needs 90 to 100 kg of shelled peanut seed, which is about 150 kg of unshelled pods, in furrows 50 to 60 cm apart.',
            'sources' => [['label' => 'BPI, Organic peanut seed production', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Organic-Peanut-Seed-Production.pdf']]],
        'soybean' => ['w' => [40, 60], 'n' => null, 'countWord' => null, 'note' => 'BPI puts the soybean seed requirement at 40 to 60 kg per hectare drilled at 18 to 20 seeds per meter in furrows 60 cm apart, though a DA Mindanao leaflet uses only 20 kg for hill planting.',
            'sources' => [['label' => 'BPI, Organic soybean seed production', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Organic-Soybean-Seed-Production.pdf'], ['label' => 'DA HVCDP, Giya sa pagpananum ug soybeans', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Giya-sa-Pagpananum-ug-Soybeans.pdf']]],
        'stringbean' => ['w' => [10, 20], 'n' => [40000, 53333], 'countWord' => 'plants', 'note' => 'DA guides use 10 to 20 kg of seed per hectare for pole sitaw at 100 by 50 cm (about 40,000 plants) and 25 to 30 kg for bush sitaw at 75 by 25 cm (about 53,333 plants).',
            'sources' => [['label' => 'ATI 4B, Sitaw IEC (population and seed table)', 'url' => 'https://ati2.da.gov.ph/ati-4b/content/sites/default/files/2024-06/Sitaw%20IEC%20a.pdf'], ['label' => 'DA RFO 3, Sitaw', 'url' => 'https://rfo3.da.gov.ph/wp-content/uploads/elibrary/km/Sitaw.pdf'], ['label' => 'ATI 7, String beans', 'url' => 'https://ati2.da.gov.ph/ati-7/content/sites/default/files/users/user16/String%20Beans_FINAL2020.pdf']]],
        'sweetpotato' => ['w' => null, 'n' => [33000, 44000], 'countWord' => 'cuttings', 'note' => 'The DA Region 2 guide plants one cutting per hill at 25 to 30 cm in rows 75 to 100 cm apart, giving 33,000 to 44,000 cuttings per hectare, and VSU found 75 by 25 cm (53,333) good for NSIC Sp30.',
            'sources' => [['label' => 'DA RFO 2, Sweet potato production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/sweet_potato.pdf'], ['label' => 'VSU Annals of Tropical Research, sweetpotato spacing study', 'url' => 'https://atr.vsu.edu.ph/article/download/9/3/5']]],
        'cassava' => ['w' => null, 'n' => [10000, 15000], 'countWord' => 'cuttings', 'note' => 'DA Region 3 plants cassava at 1 by 1 m (10,000 cuttings per hectare), and Philippine agronomy trials found yields rise at closer stands of 15,000 or more.',
            'sources' => [['label' => 'DA RFO 3, Cassava', 'url' => 'https://rfo3.da.gov.ph/wp-content/uploads/elibrary/km/Cassava.pdf'], ['label' => 'CIAT, Recent progress in cassava agronomy research in the Philippines', 'url' => 'https://alliancebioversityciat.org/publications-data/recent-progress-cassava-agronomy-research-philippines']]],
        'taro' => ['w' => null, 'n' => [26667, 40000], 'countWord' => 'setts', 'note' => 'The BPI gabi guide plants setts of 100 to 120 g at 75 by 50 cm or 50 by 50 cm, which works out to 26,667 to 40,000 setts per hectare.',
            'sources' => [['label' => 'BPI, Gabi production guide', 'url' => 'https://library.buplant.da.gov.ph/./images/1640920555Gabi Production Guide.pdf']]],
        'potato' => ['w' => [1200, 2000], 'n' => null, 'countWord' => null, 'note' => 'The DA Region 2 white potato guide needs 1,200 to 2,000 kg of 30 to 40 g seed tubers per hectare at 75 by 30 cm, and BPI uses about 2,500 kg when growing seed potatoes.',
            'sources' => [['label' => 'DA RFO 2, White potato production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/White-Potato-Production-Guide.pdf'], ['label' => 'BPI, Organic potato seed production', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Organic-Potato-Seed-Production.pdf']]],
        'carrot' => ['w' => [3, 10], 'n' => null, 'countWord' => null, 'note' => 'DA guides sow 3 to 4 kg of carrot seed per hectare when drilled thin at 25 by 10 cm and up to 5 to 10 kg in denser bed sowing.',
            'sources' => [['label' => 'DA RFO 2, Carrot production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Carrot-Prod-Guide.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'ginger' => ['w' => [800, 3666], 'n' => null, 'countWord' => null, 'note' => 'DA Cordillera needs 800 to 1,500 kg of seed pieces per hectare while DA Bikol uses 2,444 to 3,666 kg (about 2,000 kg under coconut), so the amount depends mostly on seed piece size.',
            'sources' => [['label' => 'DA CAR technoguide, Production and management of ginger', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/DA-CAR-TECHNOGUIDE-IN-PRODUCTION-_-MANAGEMENT-OF-GINGER.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'pechay' => ['w' => [0.8, 3], 'n' => null, 'countWord' => null, 'note' => 'Pechay needs about 3 kg of seed per hectare when direct sown and about 1 kg when raised in a seedbed and transplanted at 20 by 10 cm.',
            'sources' => [['label' => 'DA Caraga, Tips sa pechay', 'url' => 'https://caraga.da.gov.ph/wp-content/uploads/Publication/tips_pechay.pdf'], ['label' => 'ATI 7, Pechay', 'url' => 'https://ati2.da.gov.ph/ati-7/content/sites/default/files/users/user16/PECHAY%20final_2.pdf']]],
        'cabbage' => ['w' => [0.2, 0.5], 'n' => [40000, 50000], 'countWord' => 'plants', 'note' => 'DA guides transplant cabbage at 50 by 40 cm (50,000 plants per hectare) or 60 by 40 cm (about 41,667) from 200 to 500 g of seed.',
            'sources' => [['label' => 'ATI 7, Cabbage production guide', 'url' => 'https://ati2.da.gov.ph/ati-7/content/sites/default/files/users/user16/Cabbage%20Production%20Guide.pdf'], ['label' => 'DA RFO 2, Cabbage production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Cabbage.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'lettuce' => ['w' => [0.15, 0.2], 'n' => [28600, 106700], 'countWord' => 'plants', 'note' => 'Guides use 150 to 200 g of seed per hectare, and the stated spacings (heading types 30 to 40 cm in 2 rows per bed, leaf types 20 to 25 cm square on 1 m beds) work out to about 28,600 to 106,700 plants per hectare once paths are counted.',
            'sources' => [['label' => 'DA RFO 2, Lettuce production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Lettuce.pdf'], ['label' => 'BPI, Lettuce production guide', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Lettuce-Production-Guide.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'broccoli' => ['w' => null, 'n' => [38000, 44000], 'countWord' => 'plants', 'note' => 'DA Cordillera sets broccoli at 35 by 35 cm in double rows per plot, which is about 38,000 to 44,000 plants per hectare with 30 to 50 cm paths, and BPI lists about 280 g of seed per hectare.',
            'sources' => [['label' => 'DA CAR technoguide, organic highland vegetables', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/DA-CAR-TECHNOGUIDE-IN-PRODUCTION-_-MANAGEMENT-OF-ORGANIC-HIGHLAND-VEGETABLES.pdf'], ['label' => 'BPI, Broccoli production guide', 'url' => 'https://library.buplant.da.gov.ph/./images/1641969015BROCCOLI .pdf']]],
        'tomato' => ['w' => null, 'n' => [15000, 20000], 'countWord' => 'plants', 'note' => 'DA guides transplant tomato at 50 cm hills in rows about 1 m apart (20,000 plants per hectare), and ATI puts the usual stand at 15,000 to 17,000 from 150 to 250 g of seed.',
            'sources' => [['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf'], ['label' => 'DA RFO 2, Tomato production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Tomato.pdf'], ['label' => 'ATI 7, Techno guide on tomato', 'url' => 'https://ati2.da.gov.ph/ati-7/content/sites/default/files/users/user16/Techno%20guide%20on%20Tomato_Final.pdf']]],
        'eggplant' => ['w' => null, 'n' => [13333, 26667], 'countWord' => 'plants', 'note' => 'DA and ATI guides plant eggplant 50 cm apart in rows 75 cm to 1 m apart (20,000 to 26,667 plants per hectare), widening to 75 cm in the wet season (about 13,333), from 100 to 250 g of seed.',
            'sources' => [['label' => 'DA RFO 2, Eggplant production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Eggplant.pdf'], ['label' => 'ATI 7, Eggplant', 'url' => 'https://ati2.da.gov.ph/ati-7/content/sites/default/files/users/user16/eggplant_final.pdf'], ['label' => 'ATI 4B, Gabay sa produksyon ng talong', 'url' => 'https://ati2.da.gov.ph/ati-4b/content/sites/default/files/2022-12/gabay_sa_produksyon_ng_talong1.pdf']]],
        'ampalaya' => ['w' => [2, 3], 'n' => [6000, 16667], 'countWord' => 'hills', 'note' => 'Guides use 2 to 3 kg of ampalaya seed per hectare, giving about 6,000 to 11,000 hills when direct seeded and up to 16,667 at 2 m rows with 30 cm hills.',
            'sources' => [['label' => 'BPI, Ampalaya production guide', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Ampalaya-Production-Guide.pdf'], ['label' => 'DA RFO 3, Ampalaya', 'url' => 'https://rfo3.da.gov.ph/wp-content/uploads/elibrary/km/Ampalaya.pdf'], ['label' => 'DA RFO 2, Ampalaya production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Ampalaya.pdf']]],
        'squash' => ['w' => [2, 2.5], 'n' => [2500, 5000], 'countWord' => 'hills', 'note' => 'DA guides plant squash 1 to 2 m apart in rows 2 to 3 m apart, about 2,500 to 5,000 hills per hectare, from 2 to 2.5 kg of seed.',
            'sources' => [['label' => 'DA RFO 2, Squash production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Squash.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'cucumber' => ['w' => [1.5, 2], 'n' => [33333, 44444], 'countWord' => 'hills', 'note' => 'DA guides sow 2 to 3 seeds per hill 30 cm apart in furrows 75 cm to 1 m apart (33,333 to 44,444 hills per hectare) using about 1.5 to 2 kg of seed.',
            'sources' => [['label' => 'DA RFO 2, Cucumber production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Cucumber-Production-Guide.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'okra' => ['w' => [3, 10], 'n' => [33333, 50000], 'countWord' => 'hills', 'note' => 'DA guides sow okra at 20 to 30 cm hills in rows 75 cm to 1.5 m apart (about 33,333 to 50,000 hills per hectare, thinned to 2 plants) using 3 to 10 kg of seed.',
            'sources' => [['label' => 'DA RFO 2, Okra production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Okra.pdf'], ['label' => 'DA RFO 3, Okra', 'url' => 'https://rfo3.da.gov.ph/wp-content/uploads/elibrary/km/Okra.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'chili' => ['w' => null, 'n' => [17778, 41667], 'countWord' => 'plants', 'note' => 'DA guides set hot pepper at 50 to 75 cm square (17,778 to 40,000 plants per hectare) or 30 cm hills in 80 cm furrows (41,667), from about 100 to 200 g of seed.',
            'sources' => [['label' => 'DA HVCDP, Pag aalaga ng gulay (Tagalog crop guides)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Production-Guide.pdf'], ['label' => 'DA RFO 2, Pepper production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Pepper.pdf']]],
        'bellpepper' => ['w' => null, 'n' => [26667, 44444], 'countWord' => 'plants', 'note' => 'DA guides set bell pepper 30 to 50 cm apart in rows 60 to 75 cm apart (about 26,667 to 44,444 plants per hectare), with the closest stated spacing reaching 66,667, from 100 to 300 g of seed.',
            'sources' => [['label' => 'DA RFO 2, Bell pepper production guide', 'url' => 'https://cagayanvalley.da.gov.ph/wp-content/uploads/2018/02/Bellpepper.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'watermelon' => ['w' => null, 'n' => [1600, 4444], 'countWord' => 'hills', 'note' => 'ATI places watermelon hills 1.5 by 1.5 m to 2.5 by 2.5 m apart depending on variety, which is 1,600 to 4,444 hills per hectare thinned to one plant each.',
            'sources' => [['label' => 'ATI 7, Watermelon production guide', 'url' => 'https://ati2.da.gov.ph/ati-7/content/sites/default/files/users/user16/Watermelon%20Production%20Guide.pdf']]],
        'onion' => ['w' => [3, 4.5], 'n' => [400000, 800000], 'countWord' => 'seedlings', 'note' => 'Transplanted bulb onion uses 3 to 4.5 kg of seed per hectare in the seedbed, and BPI beds of 1 m with 6 to 8 rows at 7.5 to 10 cm work out to about 400,000 to 800,000 seedlings per hectare once paths are counted.',
            'sources' => [['label' => 'PhilMech, Performance evaluation of multi row onion seeder (CIGR Journal 2019)', 'url' => 'https://cigrjournal.org/index.php/Ejounral/article/view/5427'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf'], ['label' => 'BPI, Onion production guide', 'url' => 'https://library.buplant.da.gov.ph/./images/1641945744PRODUCTIONGUIDE-ONION.pdf']]],
        'garlic' => ['w' => [400, 700], 'n' => null, 'countWord' => null, 'note' => 'BPI says a hectare needs about 400 to 700 kg of garlic cloves depending on bulb size and spacing, though some cost budgets use up to 1,000 kg.',
            'sources' => [['label' => 'BPI, Garlic production guide', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Garlic-Production-Guide.pdf'], ['label' => 'DA RFO 5 HVCDP, Guiya sa Gulay 2021 (Bikol vegetable guide)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Guiya-sa-Gulay-2021.pdf']]],
        'sugarcane' => ['w' => null, 'n' => [30000, 40000], 'countWord' => 'setts', 'note' => 'Philippine practice uses about 30,000 to 40,000 two or three budded setts (3 to 4 lacsas) per hectare.',
            'sources' => [['label' => 'ICRISAT, MMSU, PCARRD, Sweet Sorghum in the Philippines (table: sugarcane setts 40,000 per ha)', 'url' => 'http://oar.icrisat.org/230/1/148_2011Sweet_sorghum_Philippines.pdf'], ['label' => 'SRA memorandum circular on planting material (lacsas of 30,000 to 40,000 seedpieces)', 'url' => 'https://www.sra.gov.ph/view_file/memorandum_circular/vkLdZK8nNrvxDeQ']]],
        'pineapple' => ['w' => null, 'n' => [40000, 60000], 'countWord' => 'suckers', 'note' => 'A hectare of pineapple takes 40,000 to 60,000 suckers, slips or crowns, with double rows giving the higher stands.',
            'sources' => [['label' => 'VSU Annals of Tropical Research 41(2), pineapple Queen sucker production', 'url' => 'https://atr.vsu.edu.ph/article/download/110/97/187'], ['label' => 'BPI, Pineapple production guide', 'url' => 'https://library.buplant.da.gov.ph/./images/1641883999Pineapple  Production Guide.pdf']]],
        'banana' => ['w' => null, 'n' => [400, 2200], 'countWord' => 'plants', 'note' => 'BPI spaces Saba or Cardaba at 4 by 4 m to 5 by 5 m (400 to 625 plants per hectare), Lakatan at 3 by 3 m to 2 by 2.5 m (1,111 to 2,000), and Cavendish plantations run about 2,200.',
            'sources' => [['label' => 'BPI, Banana production guide', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Banana-Production-Guide.pdf'], ['label' => 'BPI, Cardaba production guide', 'url' => 'https://buplant.da.gov.ph/wp-content/uploads/2025/01/Cardaba-Production-Guide.pdf'], ['label' => 'UPLB thesis, Cavendish at Lapanday (2,194 plants per ha)', 'url' => 'https://www.ukdr.uplb.edu.ph/etd-undergrad/5027']]],
        'papaya' => ['w' => null, 'n' => [1333, 2500], 'countWord' => 'plants', 'note' => 'Guides space papaya 2 to 2.5 m apart in rows 2.5 to 3 m apart (about 1,333 to 2,000 plants per hectare), Solo plantations use about 2,067, and closer 2 by 2 m planting reaches 2,500.',
            'sources' => [['label' => 'BPI, Papaya production guide', 'url' => 'https://library.buplant.da.gov.ph/./images/1641884692Papaya  Production Guide.pdf'], ['label' => 'CMU Journal of Science, Planting densities of Solo papaya', 'url' => 'https://js.cmu.edu.ph/CMUJS/article/view/18']]],
        'mango' => ['w' => null, 'n' => [69, 100], 'countWord' => 'trees', 'note' => 'DA plants mango 10 by 10 m (100 trees per hectare) to 12 by 12 m (69 trees), and some Mindanao guides go as wide as 20 m.',
            'sources' => [['label' => 'Edge Davao, DA SMIARC on mango at 10 by 10 m', 'url' => 'https://edgedavao.net/special-feature/2010/03/araw-ng-davao-special-fruits-galore-davao-now-exports-mangoes/'], ['label' => 'DA HVCDP, Giya sa pagpananum ug manga', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Giya-sa-Pagpananum-ug-Manga-with-cover.pdf']]],
        'coconut' => ['w' => null, 'n' => [100, 180], 'countWord' => 'palms', 'note' => 'PCA style triangular planting gives 143 palms per hectare for talls at 9 m, about 160 for hybrids at 8.5 m and 180 for dwarfs at 8 m, while older square planting at 10 m holds about 100.',
            'sources' => [['label' => 'Coconut plantation management guide (PCA practice)', 'url' => 'https://www.scribd.com/doc/191936885/Mark'], ['label' => 'NAST, Banzon, The coconut as a solar energy collector: planting geometries', 'url' => 'https://www.nast.dost.gov.ph/images/pdf%20files/Publications/NAST%20Transactions/NAST%201986%20Transactions%20Volume%208/AS%203.%20The%20Coconut%20as%20a%20Solar%20Energy%20Collector.%20Planting%20Geometries%20%20%20Julian%20A.%20Banzon%20-%20(AS).pdf']]],
        'calamansi' => ['w' => null, 'n' => [400, 625], 'countWord' => 'trees', 'note' => 'ATI gives the usual calamansi spacing as 5 m between plants (400 trees per hectare), and closer 4 by 4 m planting holds 625.',
            'sources' => [['label' => 'ATI 4B, Calamansi', 'url' => 'https://ati2.da.gov.ph/ati-4b/content/sites/default/files/2022-12/calamansi_final.pdf'], ['label' => 'GSU Himal us journal, calamansi study at 4 by 4 m', 'url' => 'https://journals.gsu.edu.ph/himal-us/article/download/42/40']]],
        'coffee' => ['w' => null, 'n' => [1111, 3333], 'countWord' => 'trees', 'note' => 'DA spaces Arabica 3 by 1 m to 3 by 2 m and Robusta 3 by 1.5 m to 3 by 3 m (about 1,111 to 3,333 trees per hectare), while Liberica and Excelsa go 4 by 5 m to 5 by 5.5 m (about 364 to 500).',
            'sources' => [['label' => 'DA HVCDP, Coffee production guide (Kape)', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2022/05/Coffee-Production-guide.pdf']]],
        'cacao' => ['w' => null, 'n' => [600, 1111], 'countWord' => 'trees', 'note' => 'The DA cacao roadmap budgets 1,100 trees per hectare as a monocrop (3 by 3 m) and 600 as an intercrop under coconut.',
            'sources' => [['label' => 'DA HVCDP, Philippine Cacao Industry Roadmap', 'url' => 'https://hvcdp.da.gov.ph/wp-content/uploads/2023/07/Philippine-Cacao-Industry-Roadmap.pdf']]],
    ];

    /**
     * How sure a shortfall is, from the risk score: from 'at' up it is that
     * level. dose = the share of the element's corrective dose the field
     * needs; share = the share of the yield goal the field reaches with none
     * of it (a likely zinc shortfall costs rice about a fifth of its harvest,
     * one a soil test confirms about a third).
     */
    public const MICRO_LEVELS = [
        'watch' => ['at' => 1, 'dose' => 0.5, 'share' => 0.92],
        'likely' => ['at' => 2.5, 'dose' => 1, 'share' => 0.8],
        'test' => ['dose' => 1.2, 'share' => 0.65],
    ];

    /**
     * Secondary, micro and beneficial elements. base = the corrective dose,
     * kg of the element per hectare; weights = what raises the risk of a
     * shortfall; sensitive = crops that need more or show it first.
     */
    public const ELEMENTS = [
        'Ca' => ['base' => 100, 'rate' => [50, 250], 'weights' => ['acid' => 1, 'strongAcid' => 1.5, 'sandy' => 0.5, 'sodic' => 1, 'peat' => 0.5, 'high_k' => 0.5, 'calcareous' => -1, 'liming' => -1], 'sensitive' => ['peanut', 'tomato', 'bellpepper', 'chili', 'watermelon', 'cabbage', 'carrot', 'banana', 'mango'], 'over' => 8, 'fert' => 'gypsum, or agricultural lime on acid soil', 'toxic' => 'Too much lime pushes the pH up and locks away zinc, iron, manganese, copper and boron.', 'down' => ['calcareous' => 'limy (calcareous) soil holds plenty of calcium', 'liming' => 'the lime in the plan adds calcium'],
            'sources' => [['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf'], ['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'Rajendrudu and Williams 1987, Effect of gypsum and drought on pod initiation and crop yield in early maturing groundnut (ICRISAT, Experimental Agriculture 23)', 'url' => 'https://oar.icrisat.org/3423/1/JA_384.pdf']]],
        'Mg' => ['base' => 20, 'rate' => [10, 50], 'weights' => ['acid' => 1, 'strongAcid' => 1.5, 'sandy' => 1, 'high_k' => 1, 'peat' => 0.5, 'waterlogged' => 0.25, 'flooded' => 0.25, 'calcareous' => -0.5], 'sensitive' => ['banana', 'sugarcane', 'pineapple', 'cassava', 'corn_yellow', 'corn_sweet', 'potato', 'coffee', 'cacao', 'coconut', 'calamansi', 'tomato', 'bellpepper', 'chili', 'cabbage', 'broccoli', 'cucumber', 'watermelon', 'onion', 'stringbean', 'carrot'], 'over' => 6, 'fert' => 'dolomite on acid soil, or magnesium sulfate', 'toxic' => 'Too much magnesium crowds out potassium and calcium.', 'down' => ['calcareous' => 'limy soil carries magnesium'],
            'sources' => [['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf']]],
        'S' => ['base' => 17.5, 'rate' => [10, 40], 'weights' => ['low_om' => 1, 'sandy' => 1, 'calcareous' => 0.25, 'acid_sulfate' => -1, 'saline' => -0.25], 'sensitive' => ['cabbage', 'broccoli', 'pechay', 'onion', 'garlic', 'mungbean', 'peanut', 'soybean', 'stringbean', 'rice', 'rice_dsr_wet', 'rice_dsr_dry', 'coconut'], 'critical' => 10, 'over' => 6, 'fert' => 'ammonium sulfate, gypsum or single superphosphate', 'down' => ['acid_sulfate' => 'acid sulfate soil holds too much sulfur, not too little', 'saline' => 'salty soil often carries sulfate'],
            'sources' => [['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'IRRI Rice Doctor fact sheet, Sulfur deficiency', 'url' => 'https://keyserver.lucidcentral.org/key-server/data/0e090d01-0209-460e-810c-0d060708030c/media/Html/Sulfur_deficiency.htm'], ['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf']]],
        'Zn' => ['base' => 5, 'rate' => [5, 10], 'weights' => ['alkaline' => 1, 'strongAlk' => 1.5, 'calcareous' => 1, 'sodic' => 1, 'waterlogged' => 1, 'flooded' => 1, 'high_p' => 0.5, 'low_om' => 0.5, 'sandy' => 0.5, 'peat' => 0.5, 'liming' => 0.5, 'acid' => -0.5, 'strongAcid' => -0.5], 'sensitive' => ['rice', 'rice_dsr_wet', 'rice_dsr_dry', 'corn_yellow', 'corn_sweet', 'sorghum', 'stringbean', 'mungbean', 'onion', 'cassava', 'ginger', 'watermelon', 'pineapple', 'banana', 'mango', 'calamansi', 'coffee', 'cacao', 'coconut'], 'critical' => 0.8, 'over' => 5, 'fert' => 'zinc sulfate', 'down' => ['acid' => 'acid soil frees zinc', 'strongAcid' => 'acid soil frees zinc'],
            'sources' => [['label' => 'International Zinc Association with IRRI, Zinc Fact Sheet: Rice', 'url' => 'https://crops.zinc.org/wp-content/uploads/sites/11/2016/12/pdf_NewRiceFactSheet-FINAL.pdf'], ['label' => 'IRRI Rice Doctor fact sheet, Zinc deficiency', 'url' => 'https://keyserver.lucidcentral.org/key-server/data/0e090d01-0209-460e-810c-0d060708030c/media/Html/Zinc_deficiency.htm'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf']]],
        'B' => ['base' => 0.875, 'rate' => [0.5, 2], 'weights' => ['sandy' => 1, 'alkaline' => 0.5, 'strongAlk' => 1, 'liming' => 0.5, 'calcareous' => 0.5, 'peat' => 0.5, 'low_om' => 0.5, 'saline' => -1, 'sodic' => -1, 'clay' => -0.5], 'sensitive' => ['broccoli', 'cabbage', 'pechay', 'carrot', 'tomato', 'papaya', 'pineapple', 'sweetpotato', 'coconut', 'coffee', 'cacao', 'mango', 'banana', 'watermelon', 'calamansi'], 'sensitiveWeight' => 1.5, 'critical' => 0.5, 'over' => 2, 'fert' => 'borax or boric acid', 'toxic' => 'Boron burns crops just above the dose they need, and beans, cucumber and soybean are very sensitive: keep to the need.', 'down' => ['sodic' => 'sodic and salty soils usually hold plenty of boron, often too much', 'saline' => 'sodic and salty soils usually hold plenty of boron, often too much', 'clay' => 'clay holds more boron than sandy soil'],
            'sources' => [['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf']]],
        'Fe' => ['base' => 1.125, 'rate' => [0.5, 3], 'weights' => ['alkaline' => 1, 'strongAlk' => 1.5, 'calcareous' => 1, 'sandy' => 0.5, 'low_om' => 0.5, 'high_p' => 0.5, 'liming' => 0.5, 'waterlogged' => -1, 'flooded' => -1, 'acid' => -1, 'strongAcid' => -1, 'acid_sulfate' => -1], 'sensitive' => ['sorghum', 'soybean', 'stringbean', 'mungbean', 'rice', 'rice_dsr_wet', 'rice_dsr_dry', 'tomato', 'broccoli', 'cucumber', 'watermelon', 'pineapple', 'calamansi', 'coffee', 'banana'], 'over' => 5, 'fert' => 'ferrous sulfate as a foliar spray, or an iron chelate', 'down' => ['flooded' => 'a flooded paddy frees plenty of iron', 'waterlogged' => 'wet soil frees plenty of iron', 'acid' => 'acid soil frees iron', 'strongAcid' => 'acid soil frees iron', 'acid_sulfate' => 'acid sulfate soil frees too much iron'],
            'sources' => [['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf']]],
        'Mn' => ['base' => 2.75, 'rate' => [1, 8], 'weights' => ['alkaline' => 1, 'strongAlk' => 1.5, 'liming' => 1, 'peat' => 1, 'sodic' => 0.5, 'calcareous' => 0.5, 'sandy' => 0.5, 'acid' => -1, 'strongAcid' => -1, 'waterlogged' => -1, 'flooded' => -1], 'sensitive' => ['soybean', 'stringbean', 'sorghum', 'cucumber', 'lettuce', 'onion', 'potato', 'calamansi', 'coffee'], 'over' => 5, 'fert' => 'manganese sulfate', 'toxic' => 'On acid soil extra manganese turns toxic.', 'down' => ['flooded' => 'a flooded paddy frees plenty of manganese', 'waterlogged' => 'wet soil frees plenty of manganese', 'acid' => 'acid soil frees manganese', 'strongAcid' => 'acid soil frees manganese'],
            'sources' => [['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf']]],
        'Cu' => ['base' => 2.625, 'rate' => [1.5, 6], 'weights' => ['peat' => 1, 'sandy' => 0.5, 'calcareous' => 0.5, 'liming' => 0.5, 'excess_zn' => 0.5], 'sensitive' => ['onion', 'lettuce', 'tomato', 'carrot', 'cucumber', 'watermelon', 'pineapple', 'calamansi', 'coconut'], 'over' => 3, 'fert' => 'copper sulfate', 'toxic' => 'Copper does not wash out of the soil, so extra builds up and harms roots.',
            'sources' => [['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf']]],
        'Mo' => ['base' => 0.0563, 'rate' => [0.025, 0.15], 'weights' => ['acid' => 1, 'strongAcid' => 1.5, 'sandy' => 0.5, 'peat' => 1, 'acid_sulfate' => 0.25, 'alkaline' => -1, 'strongAlk' => -1, 'liming' => -1], 'sensitive' => ['soybean', 'mungbean', 'peanut', 'stringbean', 'broccoli', 'cabbage', 'pechay', 'lettuce', 'onion', 'tomato'], 'sensitiveWeight' => 1.5, 'over' => 3, 'fert' => 'sodium or ammonium molybdate, as a seed treatment', 'toxic' => 'Crops take extra molybdenum, but forage high in it harms cattle.', 'down' => ['alkaline' => 'alkaline soil frees molybdenum', 'strongAlk' => 'alkaline soil frees molybdenum', 'liming' => 'lime frees molybdenum'],
            'sources' => [['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'Michigan State University Extension E486, Secondary and Micronutrients for Vegetables and Field Crops (Vitosh, Warncke and Lucas, reprinted 2006)', 'url' => 'https://archive.lib.msu.edu/DMC/extension_publications/e486/e486_06.pdf'], ['label' => 'Embrapa Soja Documentos 322, Soja: Molibdenio e Cobalto (Sfredo and Oliveira 2010)', 'url' => 'https://www.infoteca.cnptia.embrapa.br/bitstream/doc/859439/1/Doc322online1.pdf']]],
        'Si' => ['base' => 150, 'rate' => [150, 600], 'weights' => ['peat' => 1], 'sensitive' => ['rice', 'rice_dsr_wet', 'rice_dsr_dry', 'sugarcane'], 'sensitiveWeight' => 1, 'beneficial' => true, 'over' => 6, 'fert' => 'rice straw or rice hull ash put back, or calcium silicate',
            'sources' => [['label' => 'IRRI Rice Doctor fact sheet, Silicon deficiency', 'url' => 'https://keyserver.lucidcentral.org/key-server/data/0e090d01-0209-460e-810c-0d060708030c/media/Html/Silicon_deficiency.htm'], ['label' => 'ICAR Central Rice Research Institute, Identification and Management of Nutrient Disorders and Diseases in Rice (Nayak et al. 2013)', 'url' => 'https://icar-crri.in/wp-content/uploads/2023/05/e_book_nuritent.pdf'], ['label' => 'Kono, Effectiveness of silicate fertilizer to japonica varieties (JIRCAS Tropical Agriculture Research Series 3)', 'url' => 'https://www.jircas.go.jp/sites/default/files/publication/tars/tars3-_241-247.pdf'], ['label' => 'UF IFAS EDIS SC092, Calcium Silicate Recommendations for Sugarcane on Florida Organic Soils', 'url' => 'https://ask.ifas.ufl.edu/publication/SC092']]],
        'Co' => ['base' => 0.0027, 'rate' => [0.002, 0.005], 'weights' => ['acid' => 0.25, 'strongAcid' => 0.75], 'sensitive' => ['mungbean', 'peanut', 'soybean', 'stringbean'], 'sensitiveWeight' => 2, 'beneficial' => true, 'over' => 2, 'fert' => 'cobalt sulfate, as a seed treatment', 'toxic' => 'Too much cobalt blocks iron and yellows the crop: it is needed in grams.',
            'sources' => [['label' => 'Embrapa Soja Documentos 322, Soja: Molibdenio e Cobalto (Sfredo and Oliveira 2010)', 'url' => 'https://www.infoteca.cnptia.embrapa.br/bitstream/doc/859439/1/Doc322online1.pdf'], ['label' => 'Lana et al. 2009, Cobalt and molybdenum concentrated suspension for soybean seed treatment (Revista Brasileira de Ciencia do Solo)', 'url' => 'https://www.scielo.br/j/rbcs/a/V9z6YVxh745dGSVygM6XTKD/?lang=en'], ['label' => 'IFA World Fertilizer Use Manual (1992)', 'url' => 'https://www.fertilizer.org/wp-content/uploads/1992/06/IFA_World_Fertilizer_Use_Manual.pdf'], ['label' => 'FAO Fertilizer and Plant Nutrition Bulletin 16, Plant nutrition for food security (Roy, Finck, Blair and Tandon 2006)', 'url' => 'https://www.fao.org/4/a0443e/a0443e.pdf']]],
    ];

    /**
     * How each crop meets the season (2026-10-08), for the sun, water and
     * temperature planks. kc = the crop's water use against the reference
     * evapotranspiration (FAO 56, averaged over the season); ky = how hard a
     * water shortfall cuts the yield (FAO 33: maize 1.25, sorghum 0.9,
     * groundnut 0.7, soybean 0.85, potato and onion 1.1, tomato 1.05,
     * cabbage 0.95, sugarcane 1.2, banana 1.25, pepper 1.1); extraMm = water
     * a flooded paddy also loses to seepage and land soaking; rad = the daily
     * sunshine (MJ per m2) a full potential yield wants; opt = the mean
     * temperatures it grows best in; hot = the day's high above which flowers
     * and fruit start to fail. Rough, common agronomy values: a guide, said so.
     */
    public const CLIMATE = [
        'default' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'rice' => ['kc' => 1.1, 'ky' => 1.2, 'rad' => 19, 'opt' => [22, 30], 'hot' => 35, 'extraMm' => 250],
        'rice_dsr_wet' => ['kc' => 1.1, 'ky' => 1.2, 'rad' => 19, 'opt' => [22, 30], 'hot' => 35, 'extraMm' => 200],
        'rice_dsr_dry' => ['kc' => 1.05, 'ky' => 1.2, 'rad' => 19, 'opt' => [22, 30], 'hot' => 35, 'extraMm' => 100],
        'corn_yellow' => ['kc' => 0.85, 'ky' => 1.25, 'rad' => 20, 'opt' => [20, 30], 'hot' => 35],
        'corn_sweet' => ['kc' => 0.85, 'ky' => 1.25, 'rad' => 20, 'opt' => [20, 30], 'hot' => 35],
        'sorghum' => ['kc' => 0.8, 'ky' => 0.9, 'rad' => 20, 'opt' => [22, 32], 'hot' => 38],
        'mungbean' => ['kc' => 0.75, 'ky' => 1.0, 'rad' => 18, 'opt' => [25, 32], 'hot' => 38],
        'peanut' => ['kc' => 0.8, 'ky' => 0.7, 'rad' => 18, 'opt' => [22, 30], 'hot' => 35],
        'soybean' => ['kc' => 0.8, 'ky' => 0.85, 'rad' => 18, 'opt' => [20, 30], 'hot' => 35],
        'stringbean' => ['kc' => 0.8, 'ky' => 1.15, 'rad' => 16, 'opt' => [20, 30], 'hot' => 35],
        'sweetpotato' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'cassava' => ['kc' => 0.75, 'ky' => 0.8, 'rad' => 17, 'opt' => [22, 32], 'hot' => 38],
        'taro' => ['kc' => 1.0, 'ky' => 1.1, 'rad' => 15, 'opt' => [21, 27], 'hot' => 33],
        'potato' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 16, 'opt' => [15, 22], 'hot' => 28],
        'carrot' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 16, 'opt' => [15, 24], 'hot' => 30],
        'ginger' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 14, 'opt' => [20, 30], 'hot' => 35],
        'pechay' => ['kc' => 0.85, 'ky' => 1.0, 'rad' => 14, 'opt' => [18, 28], 'hot' => 32],
        'cabbage' => ['kc' => 0.85, 'ky' => 0.95, 'rad' => 15, 'opt' => [15, 24], 'hot' => 30],
        'lettuce' => ['kc' => 0.85, 'ky' => 1.0, 'rad' => 14, 'opt' => [15, 24], 'hot' => 29],
        'broccoli' => ['kc' => 0.85, 'ky' => 1.0, 'rad' => 15, 'opt' => [15, 24], 'hot' => 30],
        'tomato' => ['kc' => 0.9, 'ky' => 1.05, 'rad' => 17, 'opt' => [18, 27], 'hot' => 32],
        'eggplant' => ['kc' => 0.85, 'ky' => 1.05, 'rad' => 17, 'opt' => [22, 30], 'hot' => 35],
        'ampalaya' => ['kc' => 0.85, 'ky' => 1.05, 'rad' => 17, 'opt' => [22, 30], 'hot' => 35],
        'squash' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'cucumber' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'okra' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 17, 'opt' => [22, 32], 'hot' => 38],
        'chili' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 17, 'opt' => [20, 30], 'hot' => 35],
        'bellpepper' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 16, 'opt' => [18, 27], 'hot' => 32],
        'watermelon' => ['kc' => 0.8, 'ky' => 1.1, 'rad' => 18, 'opt' => [22, 30], 'hot' => 36],
        'onion' => ['kc' => 0.85, 'ky' => 1.1, 'rad' => 16, 'opt' => [15, 25], 'hot' => 32],
        'garlic' => ['kc' => 0.8, 'ky' => 1.0, 'rad' => 15, 'opt' => [12, 24], 'hot' => 30],
        'sugarcane' => ['kc' => 1.0, 'ky' => 1.2, 'rad' => 20, 'opt' => [22, 32], 'hot' => 38],
        'pineapple' => ['kc' => 0.4, 'ky' => 0.6, 'rad' => 17, 'opt' => [22, 32], 'hot' => 36],
        'banana' => ['kc' => 1.0, 'ky' => 1.25, 'rad' => 16, 'opt' => [22, 31], 'hot' => 35],
        'papaya' => ['kc' => 0.9, 'ky' => 1.1, 'rad' => 17, 'opt' => [22, 32], 'hot' => 35],
        'mango' => ['kc' => 0.8, 'ky' => 0.8, 'rad' => 18, 'opt' => [24, 30], 'hot' => 38],
        'coconut' => ['kc' => 0.9, 'ky' => 0.8, 'rad' => 18, 'opt' => [24, 30], 'hot' => 36],
        'calamansi' => ['kc' => 0.8, 'ky' => 0.9, 'rad' => 17, 'opt' => [22, 30], 'hot' => 36],
        'coffee' => ['kc' => 0.9, 'ky' => 0.9, 'rad' => 14, 'opt' => [18, 26], 'hot' => 32],
        'cacao' => ['kc' => 0.95, 'ky' => 0.9, 'rad' => 13, 'opt' => [21, 30], 'hot' => 33],
    ];

    /**
     * How ENSO tilts a Philippine season, by strength (weak, moderate,
     * strong, very strong): El Niño brings less rain, more sun and heat, La
     * Niña more rain and cloud. PAGASA's outlooks see the El Niño rain cut
     * most from October to May, so the tilt is full for those months and
     * half in the southwest monsoon; a season more than nine months out
     * gets half, since the forecast that far is weak. A rough tilt, said so.
     */
    public const ENSO_TILT = [
        'el_nino' => ['rain' => [-0.15, -0.25, -0.35, -0.45], 'sun' => [0.03, 0.05, 0.07, 0.08], 'temp' => [0.3, 0.5, 0.7, 0.9]],
        'la_nina' => ['rain' => [0.1, 0.2, 0.3, 0.3], 'sun' => [-0.02, -0.04, -0.05, -0.06], 'temp' => [-0.2, -0.3, -0.3, -0.3]],
    ];

    public static function forClient(): array
    {
        $conditions = [];
        foreach (SoilConditions::OPTIONS as $k => $v) {
            if ($k === 'unsure') {
                continue;
            }
            [$label, $sub] = array_pad(explode(' — ', $v, 2), 2, '');
            $conditions[$k] = [$label, ucfirst($sub)];
        }

        return [
            'textures' => self::TEXTURES, 'conditions' => $conditions + self::MORE_CONDITIONS, 'soilShare' => self::SOIL_SHARE, 'soilShareCrop' => self::SOIL_SHARE_CROP, 'recovery' => self::RECOVERY,
            'guideYield' => self::GUIDE_YIELD, 'adjust' => self::ADJUST, 'seeding' => self::SEEDING, 'elements' => self::ELEMENTS, 'levels' => self::MICRO_LEVELS, 'climate' => self::CLIMATE, 'ensoTilt' => self::ENSO_TILT,
        ];
    }
}
