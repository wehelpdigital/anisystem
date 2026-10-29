<?php
// The criticality tables with each stage's usual days, for the nutrient pages. Read only.
chdir(__DIR__ . '/../..');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Support\CropStages;
use App\Support\CropCatalog;
$crit = json_decode(file_get_contents('database/range-effect/criticality.json'), true);
// One crop to stand for each table, its count, and the name the page says.
$reps = [
    'rice' => ['rice', 'DAT', 'Palay, transplanted'],
    'rice_dsr_wet' => ['rice', 'DAS', 'Palay, direct seeded'],
    'corn_yellow' => ['corn_yellow', 'DAP', 'Yellow corn'],
    'corn_sweet' => ['corn_sweet', 'DAP', 'Sweet corn'],
    'sugarcane' => ['sugarcane', 'DAP', 'Sugarcane'],
    'banana' => ['banana', 'DAP', 'Banana'],
    'leafy' => [null, 'DAT', 'Leafy vegetables (pechay, cabbage)'],
    'fruiting' => [null, 'DAT', 'Fruit vegetables (tomato, eggplant, pepper)'],
    'cucurbit' => [null, 'DAP', 'Gourds and melons (ampalaya, squash)'],
    'legume' => [null, 'DAP', 'Legumes (mungbean, peanut, sitaw)'],
    'root' => [null, 'DAP', 'Root crops (kamote, cassava)'],
    'bulb' => [null, 'DAT', 'Onion and garlic'],
    'cereal' => [null, 'DAP', 'Other grains (sorghum)'],
];
$wantCrop = ['leafy' => 'pechay', 'fruiting' => 'tomato', 'cucurbit' => 'ampalaya', 'legume' => 'mungbean', 'root' => 'sweet_potato', 'bulb' => 'onion', 'cereal' => 'sorghum'];
$out = [];
foreach ($reps as $table => [$crop, $counter, $say]) {
    $c = $crop ?: ($wantCrop[$table] ?? null);
    if ($c && ! isset(CropStages::crops()[$c])) {
        // find any crop with this pattern
        foreach (CropCatalog::CROPS as $k => $v) {
            if (($v['pattern'] ?? '') === $table) { $c = $k; break; }
        }
    }
    $stages = CropStages::stagesFor($c, $counter, CropStages::maturity($c));
    $rows = [];
    foreach ($stages as $i => $s) {
        $to = isset($stages[$i + 1]) ? $stages[$i + 1][0] - 1 : CropStages::maturity($c);
        $rows[] = ['label' => $s[1], 'from' => $s[0], 'to' => $to];
    }
    $out[$table] = ['crop' => $c, 'counter' => $counter, 'say' => $say, 'maturity' => CropStages::maturity($c), 'stages' => $rows, 'crit' => $crit['crops'][$table] ?? null, 'late' => $crit['late'][$table] ?? null];
}
file_put_contents($argv[1], json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
foreach ($out as $t => $o) {
    echo $t, ' ', $o['crop'], ' ', $o['counter'], ' mat ', $o['maturity'], ' stages ', count($o['stages']), ' crit ', $o['crit'] ? count($o['crit']) : 0, ' : ', implode(' | ', array_map(fn ($s) => $s['label'] . ' ' . $s['from'] . '-' . $s['to'], $o['stages'])), "\n";
    if ($o['crit']) { $miss = array_diff(array_column($o['stages'], 'label'), array_keys($o['crit'])); if ($miss) echo '   NO CRIT FOR: ', implode(', ', $miss), "\n"; }
}
