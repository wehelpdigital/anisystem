<?php

namespace App\Support;

use App\Models\AsSiteSetting;

/**
 * What a genuine receipt of a payment to our account looks like -- the
 * reference Anee reads every uploaded proof against -- and the matching the
 * code does on what she read.
 *
 * Our account is a GCash wallet, but the QR buyers are shown is a QR Ph code
 * (InstaPay), and since 2026-10-17 buyers are never told it is GCash: they
 * pay from GCash, Maya or any bank app, so a receipt from any of them is a
 * fair proof (section 3 below, and ReceiptCheck::judge()).
 *
 * Written 2026-09-30 from a real GCash "Sent via GCash" receipt, GCash's own
 * help pages and its warnings about AI-made fake receipts. The owner can
 * extend it from the mother app (AniSystem > Orders > settings), stored under
 * `pay.receiptGuide`; what is stored there is added to GUIDE, never swapped
 * for it. Every approved order's reading (as_orders.aiReport) is the growing
 * record of real receipts this guide is checked against.
 *
 * Anee only READS. Whether an order is approved is decided in
 * App\Services\ReceiptCheck by comparing what she read with the order.
 */
class GcashReceipt
{
    public const GUIDE_KEY = 'pay.receiptGuide';

    public const GUIDE = <<<'TXT'
GENUINE RECEIPTS OF A PAYMENT TO OUR ACCOUNT (Philippines, 2024-2026)

The receiving account is a GCash wallet. Buyers are shown its QR code, a
QR Ph code that any bank or wallet app can scan (InstaPay), so the receipt
may come from the GCash app, from Maya, or from any bank's app.

1. GCash "Express Send" / "Send via QR" receipt (the GCash app's own confirmation or
   its saved image, often named GCash-<number>-<date>.PNG):
   - A royal-blue page with a white receipt card; a round blue badge with a
     white check mark sits on the card's top edge.
   - The RECIPIENT's name in bold blue capitals, MASKED: the first one or two
     letters, dots or bullets, the last letter, e.g. "DO•••N MA•••N T.".
   - Under it, the recipient's mobile number in a pale pill, "+63 9XX XXX XXXX"
     (sometimes partly masked), then the grey words "Sent via GCash".
   - "Amount" with the figure on the right (e.g. "30,000.00", no peso sign),
     then a line, then "Total Amount Sent" with "₱" and the figure in large
     bold (the same figure when there is no fee).
   - In the lower, paler section: "Ref No." followed by a 13-digit number
     printed in groups 4-3-6, e.g. "1023 796 539709", and under it the date
     and time, e.g. "Dec 18, 2024 11:22 PM".
   - Newer receipts show a green card near the bottom: "<n>g (gCO2e) By going
     digital, you reduce your carbon footprint from transportation, paper,
     and plastic."; the card's foot is cut in a zigzag, like torn paper.
   - A plain screenshot may also show the phone's own status bar (time,
     signal, battery) above it; a saved receipt image does not.
2. Other GCash screens: the Transaction History detail ("Express Send",
   amount, date, Ref No.) and the "You have sent PHP ... to ... Ref. No. ..."
   text message. These are GCash too, but carry less to check.
3. Paid from ANOTHER app into this account (by scanning the QR, or by an
   InstaPay transfer to the mobile number): Maya, or a bank's app (BPI,
   BDO, UnionBank, Landbank, Metrobank, Security Bank, RCBC, PNB, ChinaBank,
   a rural bank and others), or another wallet. That app's own confirmation
   screen or receipt, in its own colours and fonts. It shows the amount, the
   recipient (a name, often masked, and a mobile or account number, often
   masked; a scanned QR may be shown under the QR's own name "anee.io" and
   account number 217020000000656), often the destination as GCash or
   G-Xchange, often the word InstaPay or QR Ph, a reference number (letters
   and digits, of the app's own length; some show a trace number as well),
   and the date and time. Any transfer fee the bank or wallet charged the
   SENDER is shown apart from the amount sent; report the amount the
   recipient receives as amountToRecipient.

WHAT FAKES GET WRONG (GCash warns that AI-made fake receipts circulate):
fonts that differ between lines or from the app's own font; digits that are
bolder, larger or misaligned beside their neighbours; a peso sign that sits
off the baseline; on a GCash receipt, a Ref No. that is not 13 digits or not
grouped 4-3-6; a date written in a format the app does not use; blurred or
smudged patches around the amount, name or number; a card that is cropped
tightly around the figures; a status other than sent or successful; screens
from a web page or a "receipt maker". A receipt is never proof on its own:
only the receiver's own transaction history is. So read carefully, say what
is visible, and never fill in a value that cannot be read.
TXT;

    public static function guide(): string
    {
        $extra = trim((string) AsSiteSetting::get(self::GUIDE_KEY, ''));

        return self::GUIDE . ($extra !== '' ? "\n\nMORE FROM THE OWNER:\n" . $extra : '');
    }

    /** Digits only; a mask becomes '*'. '+63 956 904 ••••' -> '0956904****'. */
    public static function numberShape(?string $n): string
    {
        $n = trim((string) $n);
        if ($n === '') {
            return '';
        }
        $n = preg_replace('/[•●·∙*xX#]/u', '*', $n);
        $n = preg_replace('/[^0-9*+]/', '', $n);
        if (str_starts_with($n, '+63')) {
            $n = '0' . substr($n, 3);
        } elseif (str_starts_with($n, '63') && strlen($n) === 12) {
            $n = '0' . substr($n, 2);
        }

        return str_replace('+', '', $n);
    }

    /**
     * Does a read number agree with the receiving number? Every digit that
     * both show must be the same, and at least four must be visible.
     * null when nothing was read.
     *
     * Apps mask differently: GCash keeps the length ("+63 956 904 ••••"), a
     * bank may print "••••••4144" or "0956*****44" with any number of dots.
     * So a masked reading of another length is matched by the digits it
     * shows at each end: our number must begin with what it shows before
     * the mask and end with what it shows after it.
     */
    public static function numberMatches(?string $read, string $ours): ?bool
    {
        $a = self::numberShape($read);
        $b = self::numberShape($ours);
        if ($a === '') {
            return null;
        }
        if (strlen($a) !== strlen($b)) {
            if (! str_contains($a, '*')) {
                return false;
            }
            $head = substr($a, 0, strpos($a, '*'));
            $tail = substr($a, strrpos($a, '*') + 1);
            $shown = strlen($head) + strlen($tail);
            if ($shown > strlen($b) || ! str_starts_with($b, $head) || ($tail !== '' && ! str_ends_with($b, $tail))) {
                return false;
            }

            return $shown >= 4;
        }
        $seen = 0;
        for ($i = 0, $n = strlen($a); $i < $n; $i++) {
            if ($a[$i] === '*' || $b[$i] === '*') {
                continue;
            }
            if ($a[$i] !== $b[$i]) {
                return false;
            }
            $seen++;
        }

        return $seen >= 4;
    }

    /** The first of our numbers a read number agrees with: true, false, or null when nothing was read. */
    public static function anyNumberMatches(?string $read, array $ours): ?bool
    {
        $seen = null;
        foreach ($ours as $n) {
            $ok = self::numberMatches($read, (string) $n);
            if ($ok === true) {
                return true;
            }
            $seen = $ok ?? $seen;
        }

        return $seen;
    }

    /**
     * Do two masked names agree? GCash masks the middle of every word, and
     * masks it differently on the QR and on the receipt, so only the letters
     * both show count: each word's first letter, and its last when shown.
     *
     * A bank or another wallet may print the name with fewer or more words
     * (no middle name, a full name): with $sameCount false, a different
     * number of words is "cannot tell" (null) rather than a mismatch.
     */
    public static function nameMatches(?string $read, string $ours, bool $sameCount = true): ?bool
    {
        $words = function (string $s): array {
            $s = mb_strtoupper(preg_replace('/[•●·∙*]+/u', '*', $s));
            $s = preg_replace('/[^A-Z*\s]/u', '', $s);

            return array_values(array_filter(preg_split('/\s+/', trim($s))));
        };
        $a = $words((string) $read);
        $b = $words($ours);
        if (! $a) {
            return null;
        }
        if (count($a) !== count($b)) {
            return $sameCount ? false : null;
        }
        foreach ($a as $i => $w) {
            $v = $b[$i];
            if ($w[0] !== '*' && $v[0] !== '*' && $w[0] !== $v[0]) {
                return false;
            }
            $wl = substr($w, -1);
            $vl = substr($v, -1);
            if (strlen($w) > 1 && strlen($v) > 1 && $wl !== '*' && $vl !== '*' && $wl !== $vl) {
                return false;
            }
        }

        return true;
    }

    /** A GCash Ref No.: 13 digits, however it was spaced. */
    public static function refDigits(?string $ref): string
    {
        return preg_replace('/\D/', '', (string) $ref);
    }

    /**
     * Any app's reference, as one comparable key: a leading label ("Ref No.",
     * "Reference ID:", "Trace no.") dropped, then letters and digits only,
     * in capitals. A GCash Ref No. comes out as its 13 digits.
     */
    public static function refKey(?string $ref): string
    {
        $r = trim((string) $ref);
        $r = (string) preg_replace('/^(ref(erence)?|trace|transaction|txn)\.?\s*(no\.?|number|id|#)?\s*[:.#]?\s*/i', '', $r);

        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $r));
    }
}
