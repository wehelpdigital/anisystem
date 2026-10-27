<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;

/**
 * The market for a question asked in a chat (2026-10-27, the owner: "check if any other modules can use these
 * new data"): a farmer who asks what a crop sells for, or when to sell it, got Anee's memory before. Now a
 * question about prices, selling, buyers or imports gets the app's own price records (App\Support\MarketBook):
 * the crop's farmgate price lately at the place, the months that usually pay best and worst over the last ten
 * years, the import and policy events with what the price did after each, and for palay what buyers paid
 * farmers lately, wet and dry. Never the forecast: the chat rules forbid saying what a price will be, so only
 * what has been goes over. Any other question goes without, so it pays nothing for them.
 *
 * The crop: the ones the question names (at most two), else, inside a season the farmer opened to the chat
 * (attachPlan or usePlan, as SkyLines), its lots' crops. The place: a province the question names, else the
 * lots' own, else the farmer's newest farm lot's or profile's (MarketBook::homeProvince), else the country.
 */
final class MarketLines
{
    /** Whether a question is about prices, selling, buyers or imports. */
    public static function question(string $q): bool
    {
        return (bool) preg_match('/presyo|price|magkano|\bhalaga\b|benta|ipagbili|ipinagbili|\bsell|\bsold\b|\bmarket|merkado|palengke|\bbuyers?\b|\btraders?\b|viajero|komprador|\bimport|angkat|farm ?gate|ilan ang bili|bilihan/i', $q);
    }

    /** The market lines for a question, '' when it is about something else or nothing is kept for its crop. */
    public static function forQuestion(string $q, ?AsCroppingSchedule $s = null, int $userId = 0): string
    {
        if (! self::question($q) || ! MarketBook::ready()) {
            return '';
        }
        try {
            $crops = self::named($q);
            // The price of what is bought (fertilizer, a spray, seed, labor, fuel) is not the crop's: no crop named, nothing sent.
            if (! $crops && preg_match('/abono|fertili|urea|ammosul|potash|complete|pesticide|insecticide|herbicide|fungicide|gamot|binhi|\bseeds?\b|bumili|\blabor|trabahador|\bupa\b|renta|rent\b|diesel|krudo|gasolina/i', $q)) {
                return '';
            }
            $lots = $s ? $s->lots()->where('deleteStatus', 1)->orderBy('id')->limit(12)->get() : collect();
            if (! $crops) {
                $crops = $lots->map(fn ($l) => MarketBook::cropOf((string) $l->crop))->filter(fn ($c) => $c && MarketBook::mainItem($c))->unique()->take(2)->values()->all();
            }
            if (! $crops) {
                return '';
            }
            $named = self::province($q);
            $lines = [];
            foreach ($crops as $crop) {
                $mine = $lots->filter(fn ($l) => MarketBook::cropOf((string) $l->crop) === $crop);
                $at = $named ?? (string) ($mine->pluck('locProvince')->filter()->first() ?? $lots->pluck('locProvince')->filter()->first() ?? '');
                $where = $at !== '' ? $at : MarketBook::homeProvince($userId);
                // The harvests the season is heading for (or just had): each said with its usual month.
                $harvests = $mine->map(function ($l) {
                    $days = (int) ($l->maturityDays() ?: 0);
                    if (! $l->dayZeroDate || $days <= 0) {
                        return null;
                    }
                    $h = \Illuminate\Support\Carbon::parse((string) $l->dayZeroDate)->addDays($days);

                    return $h->year * 12 + $h->month - 1;
                })->filter(fn ($i) => $i !== null && $i >= MarketBook::nowIdx() - 1)->unique()->take(2)->values()->all();
                $line = MarketBook::forPrompt($crop, $where, $harvests, true, false);
                if ($line === '') {
                    continue;
                }
                $lines[] = rtrim($line);
                if ($crop === 'rice' && ($p = MarketBook::palayNow($where))) {
                    $say = fn (?array $r, string $form) => $r ? $form . ' P' . number_format($r['p'], 2) . '/kg (most P' . number_format($r['lo'], 2) . ' to P' . number_format($r['hi'], 2) . ', ' . $r['n'] . ' reports, ' . ($r['level'] === 'country' ? 'the whole country' : $r['where']) . ')' : null;
                    $both = array_filter([$say($p['wet'], 'wet'), $say($p['dry'], 'dry')]);
                    if ($both) {
                        $lines[] = '- Palay lately, what buyers paid farmers in the last 75 days (government and town buying programs left out): ' . implode('; ', $both) . '.';
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);

            return '';
        }

        return $lines ? "THE MARKET (the app's own price records, for this question. They tell what prices have been: say them as averages for the place, that a buyer may pay more or less, and never say what a price will be. Never say where the records come from.):\n" . implode("\n", $lines) . "\n\n" : '';
    }

    /** The crops a question names, at most two, in the order named. */
    private static function named(string $q): array
    {
        $text = ' ' . mb_strtolower($q) . ' ';
        $found = [];
        foreach (self::names() as $name => $crop) {
            if (preg_match('/(?<![a-z])' . preg_quote($name, '/') . '(?:e?s)?(?![a-z])/u', $text, $m, PREG_OFFSET_CAPTURE)) {
                $found[$crop] = min($found[$crop] ?? PHP_INT_MAX, $m[0][1]);
                // A longer name is read first and taken out, so "sibuyas tagalog" is not also "sibuyas".
                $text = substr_replace($text, str_repeat(' ', strlen($m[0][0])), $m[0][1], strlen($m[0][0]));
            }
        }
        asort($found);

        return array_slice(array_keys($found), 0, 2);
    }

    /** Every crop with prices, by its English and Filipino names, the longest names first. */
    private static function names(): array
    {
        static $names = null;
        if ($names !== null) {
            return $names;
        }
        $names = [];
        foreach (MarketBook::catalog() as $c) {
            // "Onion, bulb (Sibuyas)": the name before the comma and the one in brackets.
            if (preg_match('/^([^,(]+)(?:,[^(]*)?(?:\(([^)]+)\))?/', (string) $c['label'], $m)) {
                foreach (array_filter([trim($m[1]), trim($m[2] ?? '')]) as $n) {
                    $names[mb_strtolower($n)] ??= $c['crop'];
                }
            }
        }
        $names += ['bigas' => 'rice', 'paddy' => 'rice', 'sweet corn' => 'corn_sweet', 'labuyo' => 'chili', 'copra' => 'coconut', 'kopra' => 'coconut', 'buko' => 'coconut',
            'dalandan' => 'citrus', 'ponkan' => 'citrus', 'asukal' => 'sugarcane', 'sugar' => 'sugarcane'];
        // Words that mean something else as often: tubo is also profit, gabi also night.
        foreach (['tubo', 'gabi', 'goma', 'palma', 'bulak', 'presa'] as $x) {
            unset($names[$x]);
        }
        uksort($names, fn ($a, $b) => strlen($b) <=> strlen($a));

        return $names;
    }

    /** A province the question names, as the price shelf spells it, or null. */
    private static function province(string $q): ?string
    {
        static $all = null;
        $all ??= \Illuminate\Support\Facades\Cache::remember('market:province-names', 86400, fn () => \Illuminate\Support\Facades\DB::table('as_market_series')
            ->where('geoLevel', 'province')->distinct()->pluck('geoName')->sortByDesc(fn ($n) => strlen($n))->values()->all());
        foreach ($all as $p) {
            if (preg_match('/(?<![a-z])' . preg_quote(mb_strtolower($p), '/') . '(?![a-z])/u', mb_strtolower($q))) {
                return $p;
            }
        }

        return null;
    }
}
