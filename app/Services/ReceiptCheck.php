<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\AsOrder;
use App\Support\AiUsage;
use App\Support\GcashReceipt;
use App\Support\ManualPay;
use Carbon\Carbon;

/**
 * Anee reads a payment receipt; the code decides (2026-09-30).
 *
 * The money reaches our GCash account, but the buyer is shown a QR Ph code
 * and never the word GCash (the owner, 2026-10-17), so the receipt may come
 * from GCash, Maya or any bank's app. Each is checked the same way: the
 * amount, the reference, the receiving account and the date. Only what is
 * GCash's own (a 13-digit Ref No., the name masked GCash's way) is asked of
 * a GCash receipt alone; another app's reference has its own length, and a
 * name it prints its own way is left to a person rather than failed.
 *
 * She is shown the uploaded picture or PDF with GcashReceipt::guide() and
 * asked only to READ it: what app, what kind of screen, the amount, the
 * recipient's name and number, the Ref No., the date and time, and any sign
 * of editing. She is never told what the order expects, so she cannot "see"
 * the number she was hoping for.
 *
 * Then every check is made here, in code, against the order:
 *   a receipt of GCash, Maya or a bank app, sent/successful; no editing
 *   seen; she is sure of it; the amount the recipient got is the order's
 *   total to the centavo; the recipient is our account (its mobile number,
 *   the built-in QR's own account number, or, when no number shows, our
 *   masked name); a reference never used on another order and matching any
 *   the buyer typed (13 digits on a GCash receipt); the same file never sent
 *   before; and the payment made after the order was opened (and not in
 *   the future).
 * Only when all pass is the verdict "pass". A clear contradiction is "fail";
 * anything she could not read, or a receipt of an app she does not know, is
 * "unsure". Either way the order then waits for a person, with the reasons
 * shown.
 *
 * The run is logged on as_ai_usage (kind 'receipt') at no charge to anyone:
 * the processing fee on a QR order pays for it.
 */
class ReceiptCheck
{
    /** How sure she must say she is, 0-100, before a pass. */
    public const SURE = 80;

    /** The apps whose receipts can pass, as staff read them. */
    public const APPS = ['gcash' => 'GCash', 'maya' => 'Maya', 'bank' => 'bank app', 'wallet' => 'wallet app'];

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

        // 1. A receipt of a sent, successful payment, from GCash, Maya or a
        //    bank's app (the buyer is shown a QR Ph code, never told which).
        $isReceipt = (bool) ($read['isReceipt'] ?? false);
        $app = strtolower($txt($read['app'] ?? ''));
        $isGcash = $app === 'gcash';
        $known = isset(self::APPS[$app]);
        $add('receipt', 'A payment receipt (GCash, Maya or a bank app)', $isReceipt && $known ? true : ($isReceipt ? null : false),
            ! $isReceipt ? 'This does not look like a payment receipt.'
                : ($known ? 'A ' . self::APPS[$app] . ' ' . str_replace('_', ' ', $txt($read['kind'] ?? 'receipt')) . '.' : 'A receipt from ' . ($app ?: 'an app') . ' Anee does not know: checked by hand.'));
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

        // 4. The recipient: our number (the mobile number, or the built-in
        //    QR's own account), or (when hidden) our masked name. GCash masks
        //    names its own way; another app may print fewer or more words, so
        //    there a name that cannot be compared is a person's call, not a no.
        $acct = ManualPay::receiving();
        $numOk = GcashReceipt::anyNumberMatches($read['recipientNumber'] ?? null, $acct['numbers']);
        $nameRead = $read['recipientName'] ?? null;
        $nameOk = GcashReceipt::nameMatches($nameRead, $acct['name'], $isGcash);
        $qrNamed = $acct['qrName'] !== null && self::plain($nameRead) !== '' && self::plain($nameRead) === self::plain($acct['qrName']);
        $toGcash = (bool) preg_match('/g\s*cash|g\s*xchange/i', $txt($read['recipientBank'] ?? ''));
        if ($numOk === false) {
            $recipOk = false;
        } elseif ($numOk === true) {
            $recipOk = $nameOk === false && ! $qrNamed ? ($isGcash ? false : null) : true;
        } else {
            // No number shows: the masked name alone, and from another app
            // only with the destination printed as GCash.
            $recipOk = $nameOk === true ? ($isGcash || $toGcash ? true : null) : ($nameOk === false && ! $qrNamed ? false : null);
        }
        $add('recipient', 'Sent to our account (GCash ' . ManualPay::spacedNumber() . ')', $recipOk,
            'Read: ' . ($txt($nameRead) ?: 'no name') . ', ' . ($txt($read['recipientNumber'] ?? '') ?: 'no number')
            . ($txt($read['recipientBank'] ?? '') !== '' ? ', at ' . $txt($read['recipientBank']) : '') . '.');

        // 5. The reference: new, the one the buyer typed, and of the app's
        //    shape: a GCash Ref No. is 13 digits; another app's reference is
        //    letters and digits of its own length (6 to 32 here).
        $ref = GcashReceipt::refKey($read['referenceNumber'] ?? null);
        $typed = GcashReceipt::refKey($order->refNumber);
        $refUsed = $ref !== '' && AsOrder::where('id', '!=', $order->id)
            ->whereNotIn('status', [AsOrder::CANCELLED])
            ->where(fn ($q) => $q->whereRaw("UPPER(REPLACE(REPLACE(REPLACE(refNumber, ' ', ''), '-', ''), '.', '')) = ?", [$ref])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(aiReport, '$.read.ref')) = ?", [$ref]))->exists();
        $shapeOk = $isGcash ? (strlen($ref) === 13 && ctype_digit($ref)) : (strlen($ref) >= 6 && strlen($ref) <= 32);
        $typedOff = $typed !== '' && $typed !== $ref;
        // A buyer paying from a bank may type its trace number instead: only
        // a GCash receipt has one number to type, so only there is it a no.
        $refOk = $ref === '' ? null : ($refUsed || ! $shapeOk ? false : ($typedOff ? ($isGcash ? false : null) : true));
        $add('ref', $isGcash ? 'A new Ref No. (13 digits)' : 'A new reference number', $refOk,
            $ref === '' ? 'No reference number could be read.'
                : ($refUsed ? 'Reference ' . $ref . ' was already used on another order.'
                    : (! $shapeOk ? 'Reference ' . $ref . ($isGcash ? ' is not 13 digits.' : ' is not the shape of a reference (6 to 32 letters and digits).')
                        : ($typedOff ? 'The typed reference (' . $typed . ') is not the one on the receipt (' . $ref . ').' : 'Reference ' . $ref . '.'))));

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
            'recipientBank' => $txt($read['recipientBank'] ?? ''),
            'senderName' => $txt($read['senderName'] ?? ''), 'ref' => $ref, 'dateTime' => $when?->toDateTimeString(),
            'authenticity' => $score, 'notes' => mb_substr($txt($read['notes'] ?? ''), 0, 500),
        ]);
    }

    /** Letters and digits only, lower case: "ANEE.IO" and "anee io" read alike. */
    private static function plain(mixed $v): string
    {
        return strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', (string) ($v ?? '')));
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
  "app": "gcash" | "maya" | "bank" | "wallet" | "other" | "unknown",   // whose screen or document it is: the GCash app, Maya, a bank's app or site, another wallet app
  "kind": "express_send" | "qr_payment" | "instapay_transfer" | "bank_transfer" | "transaction_detail" | "sms" | "other",
  "status": "success" | "pending" | "failed" | "unknown",
  "amountToRecipient": number or null,  // what the recipient receives, excluding any fee charged to the sender
  "amount": number or null,             // the "Total Amount Sent" or total shown
  "currency": "PHP" | "other",
  "recipientName": "as printed, keeping its masking" or null,
  "recipientNumber": "the recipient's mobile or account number as printed, keeping its masking" or null,
  "recipientBank": "the destination bank or wallet as printed (e.g. GCash, G-Xchange)" or null,
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
