<?php

namespace App\Support;

/**
 * The chemistry of a fertilizer the book does not list, read from its name
 * and its analysis (a farmer's own product, or one Anee found): which forms
 * of nitrogen it likely carries, calcium, sulfate, phosphate, metals,
 * boron, silicate and the rest. The same reading the product book's build
 * makes for the FPA list, kept simple: a name that says "calcium nitrate" or
 * a grade with calcium and nitrogen is a calcium nitrate. Where the name and
 * analysis say nothing, nothing is guessed.
 */
final class FertTraits
{
    /** @param array<string, float> $n percent by weight (N, P2O5, K2O, Ca, Mg, S, Zn, B, Fe, Mn, Cu, Mo, SiO2...) */
    public static function infer(string $name, array $n, string $form = ''): array
    {
        $s = mb_strtolower($name);
        $g = fn (string $k) => (float) ($n[$k] ?? 0);
        $t = [];
        if (preg_match('/\burea\b/', $s) || ($g('N') >= 44 && ! $g('P2O5') && ! $g('K2O'))) {
            $t[] = 'urea';
        }
        if (preg_match('/ammon|ammosul|21-0-0|\bams\b|\bdap\b|\bmap\b|16-20-0|ammophos/', $s) || ($g('N') > 0 && $g('S') > 15 && ! $g('Ca'))) {
            $t[] = 'ammonium';
        }
        if (preg_match('/nitrate|calnit|cal-?n\b|\bcan\b/', $s) || ($g('N') > 0 && $g('Ca') > 10 && ! $g('S'))) {
            $t[] = 'nitrate';
        }
        if ($g('Ca') > 0) {
            if ($form !== 'liquid' && preg_match('/lime|apog|dolomit|calcite|carbonate/', $s)) {
                $t[] = 'lime';
            } elseif (preg_match('/gypsum|calcium sulph?f?ate/', $s)) {
                $t[] = 'gypsum';
            } elseif (! preg_match('/super ?phos|solophos|\bssp\b|\btsp\b/', $s)) {
                $t[] = 'calcium';
            }
        } elseif (preg_match('/\blime\b|apog|dolomit/', $s)) {
            $t[] = 'lime';
        }
        if ($g('P2O5') > 0 || preg_match('/phosph/', $s)) {
            $t[] = preg_match('/super ?phos|solophos|\bssp\b|\btsp\b|0-18-0|0-20-0|0-46-0|rock phosph/', $s) ? 'superphosphate' : 'phosphate';
        }
        if (preg_match('/thiosulph?f?ate/', $s)) {
            $t[] = 'thiosulfate';
        } elseif (($g('S') > 0 || preg_match('/sulph?f?ate/', $s)) && ! in_array('gypsum', $t, true)) {
            $t[] = 'sulfate';
        }
        if (preg_match('/muriate|\bmop\b|chloride|0-0-60/', $s) || $g('Cl') > 5) {
            $t[] = 'chloride';
        }
        if ($g('Zn') + $g('Fe') + $g('Mn') + $g('Cu') > 0 || preg_match('/zinc|iron|ferrous|mangan|copper/', $s)) {
            $t[] = preg_match('/chelat|edta|eddha|dtpa|amino|glucon|citrate|complex/', $s) ? 'chelate' : 'metal';
        }
        if ($g('B') > 0 || preg_match('/borax|boric|boron|solubor/', $s)) {
            $t[] = 'boron';
        }
        if ($g('SiO2') > 0 || preg_match('/silicate/', $s)) {
            $t[] = preg_match('/silicate/', $s) || $g('K2O') > 0 ? 'silicate' : 'silica';
        }
        if ($g('Mg') > 0 || preg_match('/magnes|epsom|kieserite/', $s)) {
            $t[] = 'magnesium';
        }
        if (preg_match('/humic|humate|fulvic/', $s)) {
            $t[] = 'humic';
        }
        if (preg_match('/seaweed|kelp|ascophyllum/', $s)) {
            $t[] = 'seaweed';
        }
        if (preg_match('/amino/', $s)) {
            $t[] = 'amino';
        }
        if (preg_match('/organic|compost|vermi|manure|guano|chicken|bokashi/', $s)) {
            $t[] = 'organic';
        }
        if (preg_match('/trichoderma|mycorrh/', $s)) {
            $t[] = 'living_fungus';
        }
        if (preg_match('/bacill|azospir|azotobac|rhizob|pseudomon|\bbio ?fert/', $s)) {
            $t[] = 'living_bact';
        }

        return array_values(array_unique($t));
    }
}
