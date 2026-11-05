<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;

/**
 * The Money switch on a worker's login (2026-10-22, the owner: "All money on
 * the board").
 *
 * Money is the season's pesos wherever the board shows them: what a task
 * costs, what each worker is paid, the day's cash, extra expenses, income,
 * and the prices of materials and services. A worker whose grant says None
 * is never SENT those figures (not hidden by a stylesheet over numbers still
 * in the page): the board is handed the season with them blanked, and the
 * board's own arithmetic then finds nothing to show. View shows them; Edit
 * may also write extra expenses and income (the board's pen still decides
 * whether the season can be changed at all).
 *
 * A worker who cannot write money and edits a task never touches its money
 * either: the controller keeps the prices, pay and errand amounts the task
 * already has (ActivityController::saveActivity), so a blanked form saved
 * back cannot wipe the owner's figures.
 *
 * An owner on their own farm sees and writes everything; so does anybody
 * outside a worker context.
 */
class MoneyView
{
    /** Is money kept from whoever is asking? */
    public static function hidden(): bool
    {
        return WorkerContext::inWorkerContext() && ! WorkerContext::canUseModule('money');
    }

    /** May whoever is asking change money (prices, pay, expenses, income)? */
    public static function writable(): bool
    {
        return ! WorkerContext::inWorkerContext() || WorkerContext::canWriteModule('money');
    }

    /**
     * A season loaded for the board, its money blanked in memory (never saved):
     * the roster's rates, each task's service price, item prices, agreed pay
     * and errand amounts, the catalogue's prices, the day book.
     */
    public static function blankSchedule(AsCroppingSchedule $schedule): void
    {
        foreach ($schedule->workers as $w) {
            $w->costPerHalfDay = 0;
        }
        foreach ($schedule->activities as $a) {
            self::blankActivity($a);
        }
        if ($schedule->relationLoaded('materials')) {
            foreach ($schedule->materials as $m) {
                $m->priceAmount = null;
            }
        }
        if ($schedule->relationLoaded('services')) {
            foreach ($schedule->services as $s) {
                $s->serviceCost = null;
            }
        }
        $schedule->setRelation('dayExpenses', collect());
        $schedule->setRelation('dayIncomes', collect());
    }

    /**
     * A stock note without its price. The shed writes the price into the words
     * ("Bought for “Planting” at ₱5,950.00 each", "Bought at ₱1,350.00 each"),
     * and the board draws those notes under each day.
     */
    public static function blankNote(?string $note): ?string
    {
        if ($note === null || $note === '') {
            return $note;
        }
        $note = preg_replace('/\s*·?\s*Bought at [^\d\s]{0,3}[\d,.]+ each/u', '', $note);
        $note = preg_replace('/\s+at [^\d\s]{0,3}[\d,.]+ each/u', '', $note);
        // A figure with the money sign typed by hand goes the same way.
        $note = preg_replace('/\s*' . preg_quote(Region::symbol(), '/') . '\s?[\d,]+(?:\.\d+)?/u', '', $note);

        // By letters, not bytes: trim() with a dot in its list can cut a letter in half.
        return (string) preg_replace('/^[\s·]+|[\s·]+$/u', '', $note);
    }

    /** One task's money blanked in memory. */
    public static function blankActivity($a): void
    {
        $a->servicePrice = null;
        if ($a->relationLoaded('items')) {
            foreach ($a->items as $it) {
                $it->unitPrice = null;
            }
        }
        if ($a->relationLoaded('workers')) {
            foreach ($a->workers as $w) {
                $w->costPerHalfDay = 0;
                if ($w->pivot) {
                    $w->pivot->salaryAmount = null;
                }
            }
        }
        if (is_array($a->reminders)) {
            $a->reminders = array_map(fn ($r) => is_array($r) ? array_merge($r, ['kind' => 'none', 'amount' => 0]) : $r, $a->reminders);
        }
    }

    /**
     * A task as JSON for the board, its money blanked: what show, save,
     * restore, duplicate and the draft door hand back.
     */
    public static function blankActivityArray(array $d): array
    {
        // Lists arrive as Collections as often as arrays (serializeItems, the
        // relations); read as the JSON they are about to become, so none slips by.
        $d = json_decode(json_encode($d), true) ?: [];
        if (array_key_exists('servicePrice', $d)) {
            $d['servicePrice'] = null;
        }
        foreach (['labourTotal', 'materialsTotal', 'goodsTotal', 'errandSpend'] as $k) {
            if (array_key_exists($k, $d)) {
                $d[$k] = 0;
            }
        }
        foreach ((array) ($d['items'] ?? []) as $i => $it) {
            if (is_array($it)) {
                $it['unitPrice'] = null;
                foreach (['material' => 'priceAmount', 'service' => 'serviceCost'] as $rel => $col) {
                    if (is_array($it[$rel] ?? null)) {
                        $it[$rel][$col] = null;
                    }
                }
                $d['items'][$i] = $it;
            }
        }
        foreach ((array) ($d['workers'] ?? []) as $i => $w) {
            if (is_array($w)) {
                $w['costPerHalfDay'] = 0;
                if (is_array($w['pivot'] ?? null)) {
                    $w['pivot']['salaryAmount'] = null;
                }
                $d['workers'][$i] = $w;
            }
        }
        foreach ((array) ($d['workerPay'] ?? []) as $id => $p) {
            if (is_array($p)) {
                $d['workerPay'][$id] = array_merge($p, ['amount' => null, 'total' => 0]);
            }
        }
        if (is_array($d['reminders'] ?? null)) {
            $d['reminders'] = array_map(fn ($r) => is_array($r) ? array_merge($r, ['kind' => 'none', 'amount' => 0]) : $r, $d['reminders']);
        }

        return $d;
    }
}
