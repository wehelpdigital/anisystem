<?php

/**
 * What changes with the country a farmer is in (2026-09-16).
 *
 * The Philippines is the home market: Taglish where a Filipino would say
 * it, pesos, town-and-province, PhilRice and PAGASA. Everywhere else the
 * app speaks plain English, prices in US dollars, asks for city-and-state
 * (or state/region), and points Anee at that country's agencies. The '*'
 * block is the international default; a country block overrides what
 * differs for it. App\Support\Region is the single reader.
 *
 * The `words` dictionary is what the views ask for through Region::t():
 * the international line is the English base and the PH line is where a
 * Filipino word is the natural one. A key missing from a country falls to
 * '*'. Add a key here before using it in a view.
 */
return [

    /* --------------------------------------------------------------
       INTERNATIONAL — every country without a block of its own.
       -------------------------------------------------------------- */
    '*' => [
        'language' => 'en',            // plain English, no Filipino words
        'currency' => ['code' => 'USD', 'symbol' => '$', 'locale' => 'en-US', 'name' => 'US dollars'],
        'timezone' => 'UTC',
        'phone' => [
            'placeholder' => '+1 555 123 4567',
            'hint' => 'Include your country code, e.g. +1, +44, +61.',
            // digits only after the spaces/dashes are stripped; a leading + is fine
            'regex' => '/^\+?[0-9]{7,15}$/',
            'error' => 'Enter a valid mobile number — digits only, 7 to 15 of them, with your country code.',
            'strip' => '/[\s\-().]+/',
        ],
        'address' => [
            'city' => ['label' => 'City', 'placeholder' => 'e.g. Springfield'],
            'region' => ['label' => 'State / Region', 'placeholder' => 'e.g. Illinois'],
            'divisions' => 'free',      // both typed by hand
        ],
        'lot' => [
            'barangay' => ['label' => 'Area / locality', 'placeholder' => 'e.g. North Road', 'prefix' => ''],
            'zone' => ['label' => 'Zone / block', 'placeholder' => 'e.g. Block 4', 'prefix' => 'Zone '],
            'town' => ['label' => 'City / town', 'placeholder' => 'e.g. Springfield'],
            'province' => ['label' => 'State / region', 'placeholder' => 'e.g. Illinois'],
        ],
        'exampleLocation' => 'e.g. Fresno, California',
        'yieldUnits' => ['tons' => 'tons', 'kg' => 'kg'],       // per hectare
        // The planting seasons a farmer picks from (When to Plant). The
        // model works out the actual months for the farmer's location.
        'seasons' => [
            'spring' => 'Spring planting',
            'summer' => 'Summer planting',
            'autumn' => 'Autumn / fall planting',
            'winter' => 'Winter / cool-season planting',
        ],
        'pay' => ['method' => 'PayPal', 'note' => 'Pay with PayPal or card — your plan is activated after our team verifies the payment.'],
        'agencies' => [
            'research' => 'the national agricultural research institute, the university extension service and the CGIAR centres (IRRI, CIMMYT, CIP)',
            'met' => 'the national meteorological service and the NOAA CPC ENSO outlook',
            'sources' => 'FAO, the CGIAR centres (IRRI, CIMMYT, CIP), the national agricultural research institute, university extension pages and reputable seed-company pages',
            'seeds' => 'the national seed registry and the seed companies selling in the country',
            'extension' => 'your local agricultural extension officer',
            'nutrient' => 'the national fertilizer recommendation for the crop and soil (soil test first where one exists)',
        ],
        'words' => [
            'greeting.morning' => 'Good morning',
            'greeting.afternoon' => 'Good afternoon',
            'greeting.evening' => 'Good evening',
            'greeting.day' => 'Good day',
            'friend' => 'friend',
            'thanks' => 'Thank you',
            'cropSearch' => 'Search — rice, corn, onion…',
            'cropNone' => 'Nothing matches that. Try another name for it.',
            'firstNameExample' => 'Alex',
            'lastNameExample' => 'Smith',
            'rice' => 'rice',
            'corn' => 'corn',
            'cropsLine' => 'For rice, corn, and more',
            'sack' => '50-kg bag',
            'sacks' => '50-kg bags',
            'harvestUnit' => 'ton',
            'harvestUnits' => 'tons',
            'farmersOf' => 'farmers',
            'farmersOfTitle' => 'Farmers',
            'countryFarmers' => 'farmers everywhere',
            'extensionOffice' => 'your local extension office',
            'techVisit' => 'Wait for an extension visit or ask around the neighbourhood.',
        ],
    ],

    /* --------------------------------------------------------------
       THE PHILIPPINES — the home market, as the app has always spoken.
       -------------------------------------------------------------- */
    'PH' => [
        'language' => 'tl-en',         // English with the natural Tagalog word
        'currency' => ['code' => 'PHP', 'symbol' => '₱', 'locale' => 'en-PH', 'name' => 'Philippine pesos'],
        'timezone' => 'Asia/Manila',
        'phone' => [
            'placeholder' => '09XXXXXXXXX',
            'hint' => 'PH mobile format: 09XXXXXXXXX (11 digits).',
            'regex' => '/^09\d{9}$/',
            'error' => 'Enter a valid PH mobile number in the format 09XXXXXXXXX (11 digits).',
            'strip' => '/[\s\-]+/',
        ],
        'address' => [
            'city' => ['label' => 'Town / City', 'placeholder' => 'e.g. Urdaneta'],
            'region' => ['label' => 'Province', 'placeholder' => 'e.g. Pangasinan'],
            'divisions' => 'ph',        // province → town from public/data/ph-locations.json
        ],
        'lot' => [
            'barangay' => ['label' => 'Barangay', 'placeholder' => 'e.g. San Jose', 'prefix' => 'Brgy. '],
            'zone' => ['label' => 'Zone / Purok', 'placeholder' => 'e.g. 3', 'prefix' => 'Zone '],
            'town' => ['label' => 'Town / City', 'placeholder' => 'Select province first'],
            'province' => ['label' => 'Province', 'placeholder' => '— Select —'],
        ],
        'exampleLocation' => 'e.g. Urdaneta, Pangasinan',
        'yieldUnits' => ['cavans' => 'cavans', 'tons' => 'tons'],
        'seasons' => [
            'dry' => 'Dry season',
            'wet' => 'Wet season',
            'third' => 'Third crop (after the dry season)',
        ],
        'pay' => ['method' => 'GCash', 'note' => 'Pay with GCash — your plan is activated after our team verifies the payment.'],
        'agencies' => [
            'research' => 'PhilRice, DA, BPI, NSIC, IRRI, UPLB, ATI, PCAARRD and FPA',
            'met' => 'PAGASA and the NOAA CPC ENSO outlook',
            'sources' => 'PhilRice, DA, BPI, NSIC, IRRI, UPLB, ATI, PCAARRD, PAGASA, FPA and reputable seed/agrochemical company pages',
            'seeds' => 'the NSIC registry and the seed companies selling in the Philippines',
            'extension' => 'your municipal agriculturist (DA) or the nearest PhilRice station',
            'nutrient' => 'the DA / PhilRice recommendation for the crop and region (PalayCheck, LCC and MOET for rice)',
        ],
        'words' => [
            'greeting.morning' => 'Magandang umaga',
            'greeting.afternoon' => 'Magandang hapon',
            'greeting.evening' => 'Magandang gabi',
            'greeting.day' => 'Magandang araw',
            'friend' => 'kaibigan',
            'thanks' => 'Salamat',
            'cropSearch' => 'Search — palay, mais, sibuyas…',
            'cropNone' => 'Nothing matches that. Try the local name.',
            'firstNameExample' => 'Juan',
            'lastNameExample' => 'dela Cruz',
            'rice' => 'palay',
            'corn' => 'mais',
            'cropsLine' => 'For Palay, Mais, and more',
            'sack' => 'sack',
            'sacks' => 'sacks',
            'harvestUnit' => 'cavan',
            'harvestUnits' => 'cavans',
            'farmersOf' => 'Filipino farmers',
            'farmersOfTitle' => 'Filipino Farmers',
            'countryFarmers' => 'Filipino farmers',
            'extensionOffice' => 'the nearest DA or PhilRice office',
            'techVisit' => 'Wait for a technician visit or ask around the barangay.',
        ],
    ],

    /* --------------------------------------------------------------
       THE UNITED STATES — the first international market to test with.
       -------------------------------------------------------------- */
    'US' => [
        'timezone' => 'America/Chicago',
        'phone' => [
            'placeholder' => '(555) 123-4567',
            'hint' => 'US mobile number, 10 digits — the +1 is optional.',
            'regex' => '/^(\+?1)?[0-9]{10}$/',
            'error' => 'Enter a valid US mobile number — 10 digits, with or without +1.',
            'strip' => '/[\s\-().]+/',
        ],
        'address' => [
            'city' => ['label' => 'City', 'placeholder' => 'e.g. Fresno'],
            'region' => ['label' => 'State', 'placeholder' => 'e.g. California'],
            'divisions' => 'list',      // state from the list below, city typed
        ],
        'lot' => [
            'barangay' => ['label' => 'Road / area', 'placeholder' => 'e.g. County Road 12', 'prefix' => ''],
            'zone' => ['label' => 'Field / block', 'placeholder' => 'e.g. North 40', 'prefix' => ''],
            'town' => ['label' => 'City / town', 'placeholder' => 'e.g. Fresno'],
            'province' => ['label' => 'State', 'placeholder' => '— Select —'],
        ],
        'exampleLocation' => 'e.g. Fresno, California',
        'agencies' => [
            'research' => 'USDA (ARS, NRCS, NASS), the state department of agriculture and the land-grant university Cooperative Extension for the state (e.g. UC ANR, Iowa State, Purdue, UNL, Texas A&M AgriLife)',
            'met' => 'NOAA / the National Weather Service, the NOAA CPC seasonal and ENSO outlooks and the US Drought Monitor',
            'sources' => 'USDA, NRCS, the state Cooperative Extension crop guides, university variety trials, the US Drought Monitor, NOAA CPC and reputable seed-company pages',
            'seeds' => 'the state seed certification agency, university variety trials and the seed companies selling in the state',
            'extension' => 'your county Cooperative Extension office',
            'nutrient' => 'the state Extension fertilizer recommendation for the crop (soil test first — every state lab publishes the rates)',
        ],
        'words' => [
            'extensionOffice' => 'your county Extension office',
            'techVisit' => 'Wait for an Extension visit or ask the neighbours.',
        ],
        'divisions' => [
            'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware',
            'District of Columbia', 'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas',
            'Kentucky', 'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi',
            'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico', 'New York',
            'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island',
            'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont', 'Virginia', 'Washington',
            'West Virginia', 'Wisconsin', 'Wyoming',
        ],
    ],

    /* --------------------------------------------------------------
       PRICES IN US DOLLARS — what the international face charges.
       The mother app (AniSystem AI > International prices) overrides
       these on the as_site_settings shelf, key `prices.usd`, without a
       deploy; these are only the defaults it starts from.
       -------------------------------------------------------------- */
    'usd' => [
        'tiers' => [
            'libreAnee' => ['month' => 1.49, 'year' => 14.99],
            'solo' => ['month' => 5, 'year' => 45],
            'owner' => ['month' => 12, 'year' => 120],
        ],
        'plans' => [
            'monthly' => 9.99,
            'season' => 24.99,
            'annual' => 69.99,
        ],
        'packs' => [
            'starter' => 1.99,
            'farmer' => 5.99,
            'season' => 14.99,
        ],
    ],
];
