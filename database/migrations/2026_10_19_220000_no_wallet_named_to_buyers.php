<?php

use App\Models\AsNlEmail;
use App\Support\Newsletter\SpamCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * No wallet is named to a buyer (the owner, 2026-10-17: "remove anything
 * specifying gcash in all payment gateways or pricing texts").
 *
 * The QR buyers scan is a QR Ph code (InstaPay) on our GCash account, so any
 * bank or wallet app pays it; the screens now say so and never say GCash.
 * Stored words follow here:
 *
 *  - The three footer pages (privacy, terms, cookies) as kept in
 *    as_legal_pages: only the sentences that named GCash are swapped, and
 *    only where those exact words are still there, so anything the owner
 *    wrote in the mother app stays as written. The default files in
 *    resources/views/legal/defaults say the same new words.
 *  - Newsletter emails not yet sent that said our plans are paid by GCash
 *    (S071, S171, S245, S284 and the "Demo: Libre + Anee weekend" draft).
 *    Same rule: only an unsent email, only where the old words still stand.
 *    Each changed email is scored again (SpamCheck). Emails that name GCash
 *    as the farmer's own wallet, not as how anee.io is paid (scam warnings
 *    S038 and S112, selling produce S152, the e-wallet guide S172), keep it.
 */
return new class extends Migration
{
    private const LEGAL = [
        'privacy' => [
            ['(a GCash reference number, a PayPal transaction ID, or a screenshot of the payment). We never receive your card number, GCash PIN or PayPal password.',
                '(a reference number from your bank or wallet app, a PayPal transaction ID, or a screenshot of the payment). We never receive your card number, your bank or wallet PIN, or your PayPal password.'],
            ['GCash and PayPal process your payment under their own privacy policies.',
                'Your bank or wallet app, or PayPal, processes your payment under its own privacy policy.'],
        ],
        'terms' => [
            ['by GCash in the Philippines or by PayPal elsewhere.',
                'from a bank or wallet app in the Philippines or by PayPal elsewhere.'],
        ],
        'cookies' => [
            ['GCash and PayPal pages are on their own websites and use their own cookies.',
                'Bank, wallet and PayPal pages are on their own websites and use their own cookies.'],
        ],
    ];

    /** Newsletter email id => [[old, new], ...]; 'preheader' pairs go to that column. */
    private const EMAILS = [
        71 => [
            ['hihingan ka agad ng credit card o GCash.', 'hihingan ka agad ng credit card o ng wallet mo.'],
            ['walang automatic deduction sa GCash mo.', 'walang automatic deduction sa wallet mo.'],
        ],
        171 => [
            ['biglang may bawas sa GCash ko,', 'biglang may bawas sa wallet ko,'],
        ],
        245 => [
            ['Hindi mo kailangan ng bangko o credit card. GCash lang ang kailangan, at mano-manong sinusuri ng aming team para sigurado.',
                'Hindi mo kailangan ng bank account o credit card. I-scan mo lang ang QR namin gamit ang wallet app o bank app sa cellphone mo, at mano-manong sinusuri ng aming team para sigurado.'],
            ['sa loob mismo ng app ang upgrade gamit ang GCash.', 'sa loob mismo ng app ang upgrade, gamit ang kahit anong wallet o bank app.'],
        ],
        284 => [
            ['<strong>ang bayad ay sa pamamagitan ng GCash</strong>. Alam naming karamihan sa mga magsasaka ay walang credit card, pero marami ang may GCash o may tindahan sa kanto na nagpapa-cash-in. Dahil ikaw mismo ang magpapadala ng bayad sa pamamagitan ng GCash, ikaw ang may',
                '<strong>ang bayad ay sa pamamagitan ng QR na isa-scan mo sa wallet o bank app mo</strong>. Alam naming karamihan sa mga magsasaka ay walang credit card, pero marami ang may wallet app sa cellphone o may tindahan sa kanto na nagpapa-cash-in. Dahil ikaw mismo ang magpapadala ng bayad, ikaw ang may'],
            ['preheader', 'Sa loob ng app, bayad via GCash, at na-verify ng tao.', 'Sa loob ng app, bayad gamit ang wallet o bank app mo, at na-verify ng tao.'],
        ],
        358 => [
            ['Walang credit card, GCash lang.', 'Walang credit card. Wallet o bank app lang sa cellphone mo.'],
        ],
    ];

    public function up(): void
    {
        if (Schema::hasTable('as_legal_pages')) {
            foreach (self::LEGAL as $slug => $pairs) {
                $row = DB::table('as_legal_pages')->where('slug', $slug)->first();
                if (! $row) {
                    continue;
                }
                $body = (string) $row->body;
                foreach ($pairs as [$old, $new]) {
                    $body = str_replace($old, $new, $body);
                }
                if ($body !== (string) $row->body) {
                    DB::table('as_legal_pages')->where('id', $row->id)->update(['body' => $body, 'updated_at' => now()]);
                }
            }
        }

        if (! Schema::hasTable('as_nl_emails')) {
            return;
        }
        foreach (self::EMAILS as $id => $pairs) {
            $e = AsNlEmail::find($id);
            if (! $e || ! in_array($e->status, ['draft', 'queued', 'partial'], true)) {
                continue;
            }
            $hit = 0;
            $words = array_values(array_filter($pairs, fn ($p) => count($p) === 2));
            $walk = function ($v) use (&$walk, $words, &$hit) {
                if (is_array($v)) {
                    return array_map($walk, $v);
                }
                if (! is_string($v)) {
                    return $v;
                }
                foreach ($words as [$old, $new]) {
                    if (str_contains($v, $old)) {
                        $v = str_replace($old, $new, $v);
                        $hit++;
                    }
                }

                return $v;
            };
            $e->blocks = $walk((array) $e->blocks);
            foreach ($pairs as $p) {
                if (count($p) === 3 && str_contains((string) $e->{$p[0]}, $p[1])) {
                    $e->{$p[0]} = str_replace($p[1], $p[2], (string) $e->{$p[0]});
                    $hit++;
                }
            }
            if ($hit === 0) {
                continue;
            }
            $e->save();
            try {
                SpamCheck::forSaved($e->fresh());
            } catch (\Throwable $x) {
                report($x);
            }
        }
    }

    public function down(): void
    {
        // The new words stay.
    }
};
