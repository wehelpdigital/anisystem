<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\AsOrder;
use App\Support\AiUsage;
use App\Support\GcashReceipt;
use App\Support\ManualPay;
use Carbon\Carbon;

/**
 * Anee reads a GCash receipt; the code decides (2026-09-30).
 *
 * She is shown the uploaded picture or PDF with GcashReceipt::guide() and
 * asked only to READ it: what app, what kind of screen, the amount, the
 * recipient's name and number, the Ref No., the date and time, and any sign
 * of editing. She is never told what the order expects, so she cannot "see"
 * the number she was hoping for.
 *
 * Then every check is made here, in code, against the order:
 *   a GCash app receipt, sent/successful; no editing seen; she is sure of it;
 *   the amount sent is the order's total to the centavo; the recipient is
 *   our GCash number (or, when the number is hidden, our masked name); a
 *   13-digit Ref No. never used on another order and matching any the buyer
 *   typed; the same file never sent before; and the payment made after the
 *   order was opened (and not in the future).
 * Only when all pass is the verdict "pass". A clear contradiction is "fail";
 * anything she could not read, or a receipt from another app, is "unsure".
 * Either way the order then waits for a person, with the reasons shown.
 *
 * The run is logged on as_ai_usage (kind 'receipt') at no charge to anyone:
 * the GCash processing fee on the order pays for it.
 */
class ReceiptCheck
{
    /** How sure she must say she is, 0-100, before a pass. */
    public const SURE = 80;

    public function __construct(private AiClient $ai) {}

    public function run(AsOrder $order): array
    {
        $file = OrderService::file($order);
        if (! $file) {
            return $this->save($order, $this->report('skipped', 'There was no picture or PDF to read.', [], null));
        }

        $settings = AiSetting::current();
        if (! $settings->isUsable()) {
            return $this->save($order, $this->report('error', 'The AI is not switched on, so this payment waits for a person.', [], null));
        }
        $settings = $settings->forDocument();
        $settings->temperature = 0;

        $result = $this->ai->ask($settings, [], $this->prompt(), ['mime' => $file->mime, 'data' => base64_encode($file->bytes)], 1200, [
            'system' => 'You examine payment receipts for a small Philippine business. You read one uploaded image or PDF and report exactly what it shows, in JSON. You never guess a value that is not clearly visible; when unsure, you say so.',
            'timeout' => 90,
        ]);

        try {
            AiUsage::record('receipt', (int) $order->userId, 0, $order->id, $settings, $result, 0);
        } catch (\Throwable $e) {
            report($e);
        }

        if (empty($result['ok'])) {
            return $this->save($order, $this->report('error', 'Anee could not read the receipt just now (' . ($result['error'] ?? 'no answer') . '). A person will check it.', [], null));
        }
        $read = self::parse((string) $result['text']);
        if (! $read) {
            return $this->save($order, $this->report('error', 'Anee\'s reading came back unreadable. A person will check it.', [], null));
        }

        return $this->save($order, $this->judge($order, $read));
    }

    /** Compare what she read with the order. Pure: no AI, no writes. */
    public function judge(AsOrder $order, array $read): array
    {
        $s = ManualPay::settings();
        $checks = [];
        $add = function (string $key, string $label, ?bool $ok, string $detail, bool $hard = true) use (&$checks) {
            $checks[] = ['key' => $key, 'label' => $label, 'ok' => $ok, 'detail' => $detail, 'hard' => $hard];
        };
        $txt = fn ($v) => trim((string) ($v ?? ''));

        // 1. A GCash receipt of a sent, successful payment.
        $isReceipt = (bool) ($read['isReceipt'] ?? false);
        $app = strtolower($txt($read['app'] ?? ''));
        $add('receipt', 'A GCash payment receipt', $isReceipt && $app === 'gcash' ? true : ($isReceipt ? null : false),
            ! $isReceipt ? 'This does not look like a payment receipt.'
                : ($app === 'gcash' ? 'A GCash ' . str_replace('_', ' ', $txt($read['kind'] ?? 'receipt')) . '.' : 'A receipt from ' . ($app ?: 'another app') . ', not the GCash app: checked by hand.'));
        $status = strtolower($txt($read['status'] ?? ''));
        $add('status', 'Sent successfully', in_array($status, ['success', 'successful', 'sent', 'completed'], true) ? true : ($status === '' || $status === 'unknown' ? null : false),
            $status === '' || $status === 'unknown' ? 'No status could be read.' : 'It says: ' . $status . '.');

        // 2. No sign of editing, and she is sure.
        //    A model's own idea of "today" can be a year or two behind, and it
        //    once called a receipt dated 2026 "in the future" -- in 2026. When
        //    a payment happened is this code's question (check 7, Philippine
        //    time), never hers, so a sign that is only about the date being
        //    in the future or the past is set aside.
        $signs = array_values(array_filter(array_map('strval', (array) ($read['editingSigns'] ?? [])),
            fn ($s) => trim($s) !== '' && ! self::onlyAboutWhen($s)));
        $add('edits', 'No sign of editing', $signs ? false : true, $signs ? implode('; ', $signs) : 'Nothing looked altered.');
        $score = max(0, min(100, (int) ($read['authenticity'] ?? 0)));
        $add('sure', 'Anee is sure it is genuine', $score >= self::SURE ? true : null, 'She rates it ' . $score . ' out of 100 (needs ' . self::SURE . ').', false);

        // 3. The amount: the order's total, to the centavo.
        $amount = self::money($read['amountToRecipient'] ?? $read['amount'] ?? null);
        $want = (float) $order->total;
        $add('amount', 'The amount is ₱' . number_format($want, 2), $amount === null ? null : abs($amount - $want) < 0.01,
            $amount === null ? 'No amount could be read.' : 'The receipt shows ₱' . number_format($amount, 2) . '.');

        // 4. The recipient: our number, or (when hidden) our masked name.
        $numOk = GcashReceipt::numberMatches($read['recipientNumber'] ?? null, (string) $s['gcashNumber']);
        $nameOk = GcashReceipt::nameMatches($read['recipientName'] ?? null, (string) $s['gcashName']);
        $recipOk = $numOk === false ? false : ($numOk === true ? ($nameOk === false ? false : true) : ($nameOk === true ? true : ($nameOk === false ? false : null)));
        $add('recipient', 'Sent to our GCash (' . ManualPay::spacedNumber() . ')', $recipOk,
            'Read: ' . ($txt($read['recipientName'] ?? '') ?: 'no name') . ', ' . ($txt($read['recipientNumber'] ?? '') ?: 'no number') . '.');

        // 5. The Ref No.: 13 digits, new, and the one the buyer typed.
        $ref = GcashReceipt::refDigits($read['referenceNumber'] ?? null);
        $typed = GcashReceipt::refDigits($order->refNumber);
        $refUsed = $ref !== '' && AsOrder::where('id', '!=', $order->id)
            ->whereNotIn('status', [AsOrder::CANCELLED])
            ->where(fn ($q) => $q->where('refNumber', $ref)->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(aiReport, '$.read.ref')) = ?", [$ref]))->exists();
        $refOk = $ref === '' ? null : (strlen($ref) === 13 && ! $refUsed && ($typed === '' || $typed === $ref));
        $add('ref', 'A new 13-digit Ref No.', $refOk,
            $ref === '' ? 'No Ref No. could be read.'
                : ($refUsed ? 'Ref No. ' . $ref . ' was already used on another order.'
                    : (strlen($ref) !== 13 ? 'Ref No. ' . $ref . ' is not 13 digits.'
                        : ($typed !== '' && $typed !== $ref ? 'The typed Ref No. (' . $typed . ') is not the one on the receipt (' . $ref . ').' : 'Ref No. ' . $ref . '.'))));

        // 6. The same file never sent before.
        $dupe = app(OrderService::class)->shaSeenElsewhere($order->proofSha, $order->id);
        $add('file', 'A receipt not sent before', ! $dupe, $dupe ? 'This exact file was already sent with another order.' : 'First time this file is seen.');

        // 7. Paid after the order was opened, and not in the future.
        $when = self::when($read['dateTime'] ?? null);
        $opened = Carbon::parse($order->created_at)->timezone('Asia/Manila');
        $timeOk = $when === null ? null : ($when->gte($opened->copy()->subMinutes(15)) && $when->lte(Carbon::now('Asia/Manila')->addMinutes(15)));
        $add('time', 'Paid after the order was opened', $timeOk,
            $when === null ? 'No date and time could be read.'
                : 'Paid ' . $when->format('M j, Y g:i A') . '; the order was opened ' . $opened->format('M j, Y g:i A') . '.');

        // The verdict: any hard "no" fails; anything unread is unsure.
        $hardNo = collect($checks)->contains(fn ($c) => $c['ok'] === false && $c['hard']);
        $unsure = collect($checks)->contains(fn ($c) => $c['ok'] !== true);
        $verdict = $hardNo ? 'fail' : ($unsure ? 'unsure' : 'pass');
        $summary = match ($verdict) {
            'pass' => 'Every check passed.',
            'fail' => 'Something on the receipt does not match this order.',
            default => 'Anee could not confirm everything, so a person will look.',
        };

        return $this->report($verdict, $summary, $checks, [
            'app' => $app, 'kind' => $txt($read['kind'] ?? ''), 'status' => $status,
            'amount' => $amount, 'recipientName' => $txt($read['recipientName'] ?? ''), 'recipientNumber' => $txt($read['recipientNumber'] ?? ''),
            'senderName' => $txt($read['senderName'] ?? ''), 'ref' => $ref, 'dateTime' => $when?->toDateTimeString(),
            'authenticity' => $score, 'notes' => mb_substr($txt($read['notes'] ?? ''), 0, 500),
        ]);
    }

    /**
     * Whether an "editing sign" is only her doubt about WHEN (a date or year
     * she thinks is in the future or too old) rather than something she can
     * point at on the picture.
     */
    public static function onlyAboutWhen(string $sign): bool
    {
        $s = mb_strtolower($sign);
        $aboutWhen = (bool) preg_match('/\b(future|not yet (happened|occurred)|has(n\'t| not) happened|upcoming|too old|outdated|in the past)\b/u', $s);
        $aboutLooks = (bool) preg_match('/\b(font|bold|align|misalign|blur|smudg|pixel|cropp|colou?r|spacing|kerning|edited|photoshop|overlay|mismatch)/u', $s);

        return $aboutWhen && ! $aboutLooks;
    }

    private function prompt(): string
    {
        // Today, in the Philippines, said in so many words: the model's own
        // sense of the date is its training's, not the calendar's.
        $now = \Illuminate\Support\Carbon::now('Asia/Manila');
        $today = "\n\nTODAY\nIt is " . $now->format('l, F j, Y, g:i A') . ' in the Philippines (Asia/Manila, UTC+8). '
            . 'Your own sense of the current date may be out of date: trust this one. Read the receipt\'s date and time exactly as printed. '
            . 'Whether that date is right for this payment is checked by our system, so never list a date or a year as a sign of editing '
            . 'because it seems to be in the future or the past, and do not lower your authenticity score for it.';

        return GcashReceipt::guide() . $today . <<<'TXT'


THE TASK
Read the attached image or PDF. Report ONLY what is visible. Do not correct
or complete anything. Reply with one JSON object and nothing else:

{
  "isReceipt": true or false,          // a payment confirmation or receipt at all?
  "app": "gcash" | "maya" | "bank" | "other" | "unknown",   // whose screen or document it is
  "kind": "express_send" | "qr_payment" | "instapay_transfer" | "transaction_detail" | "sms" | "other",
  "status": "success" | "pending" | "failed" | "unknown",
  "amountToRecipient": number or null,  // what the recipient receives, excluding any fee charged to the sender
  "amount": number or null,             // the "Total Amount Sent" or total shown
  "currency": "PHP" | "other",
  "recipientName": "as printed, keeping its masking" or null,
  "recipientNumber": "as printed, keeping its masking" or null,
  "senderName": "as printed" or null,
  "referenceNumber": "exactly as printed" or null,
  "dateTime": "YYYY-MM-DD HH:MM (24-hour, as printed, Philippine time)" or null,
  "editingSigns": ["each concrete sign of alteration you can point at"],
  "authenticity": 0-100,                // how sure you are this is a genuine, unaltered receipt of that app
  "notes": "one short sentence"
}
TXT;
    }

    /** The first JSON object in the answer, fences and chatter stripped. */
    public static function parse(string $text): ?array
    {
        $text = preg_replace('/^```(?:json)?|```$/m', '', trim($text));
        $a = strpos($text, '{');
        $b = strrpos($text, '}');
        if ($a === false || $b === false || $b <= $a) {
            return null;
        }
        // The model may keep the // comments of the template it was shown.
        $json = preg_replace('#(?<![:"\w])//[^\n]*#', '', substr($text, $a, $b - $a + 1));
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    private static function money(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $n = (float) preg_replace('/[^0-9.]/', '', (string) $v);

        return $n > 0 ? round($n, 2) : null;
    }

    private static function when(mixed $v): ?Carbon
    {
        $v = trim((string) ($v ?? ''));
        if ($v === '') {
            return null;
        }
        try {
            return Carbon::parse($v, 'Asia/Manila');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function report(string $verdict, string $summary, array $checks, ?array $read): array
    {
        return array_filter([
            'verdict' => $verdict,
            'summary' => $summary,
            'checks' => $checks,
            'read' => $read,
            'at' => now()->toDateTimeString(),
        ], fn ($v) => $v !== null);
    }

    private function save(AsOrder $order, array $report): array
    {
        $order->update([
            'aiStatus' => $report['verdict'],
            'aiScore' => $report['read']['authenticity'] ?? null,
            'aiReport' => $report,
            'aiCheckedAt' => now(),
        ]);

        return $report;
    }
}
